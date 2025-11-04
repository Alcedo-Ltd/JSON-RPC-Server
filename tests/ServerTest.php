<?php

namespace Alcedo\Tests\JsonRpc\Server;

use Alcedo\JsonRpc\Server\DTO\BatchResponse;
use Alcedo\JsonRpc\Server\DTO\Error;
use Alcedo\JsonRpc\Server\DTO\ErrorCodes;
use Alcedo\JsonRpc\Server\DTO\Response;
use Alcedo\JsonRpc\Server\Factory\RequestFactory;
use Alcedo\JsonRpc\Server\RemoteProcedureInterface;
use Alcedo\JsonRpc\Server\Server;
use Alcedo\JsonRpc\Server\Exception\InvalidBatchElementException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

class ServerTest extends TestCase
{
    public function testExecuteArrayRequestWithCallableSuccess(): void
    {
        $map = [
            'sum' => function (int $a, int $b): int { return $a + $b; },
        ];
        $server = $this->makeServer($map);

        $response = $server->executeArrayRequest([
            'jsonrpc' => '2.0',
            'method' => 'sum',
            'id' => 1,
            'params' => [2, 3],
        ]);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertSame(5, $response->result());
        $this->assertSame(1, $response->id());
    }

    public function testExecuteArrayRequestWithRemoteProcedureSuccess(): void
    {
        $fixedId = 123; // the procedure controls the id in the Response
        $remote = new class implements RemoteProcedureInterface {
            public function call(): Response
            {
                return new Response(result: 'ok');
            }
        };

        $map = [
            'remote.ok' => $remote,
        ];
        $server = $this->makeServer($map);

        // No params to avoid argument count mismatch with RemoteProcedureInterface::call()
        $response = $server->executeArrayRequest([
            'jsonrpc' => '2.0',
            'method' => 'remote.ok',
            'id' => $fixedId,
        ]);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertSame('ok', $response->result());
        $this->assertSame($fixedId, $response->id());
    }

    public function testExecuteArrayRequestMethodNotFound(): void
    {
        $server = $this->makeServer([]); // empty container

        $response = $server->executeArrayRequest([
            'jsonrpc' => '2.0',
            'method' => 'unknown.method',
            'id' => 'x1',
        ]);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isError());
        $this->assertSame(ErrorCodes::METHOD_NOT_FOUND->value, $response->error()->code());
        $this->assertSame('x1', $response->id());
        $this->assertSame(['method' => 'unknown.method'], $response->error()->jsonSerialize()['data'] ?? null);
    }

    public function testExecuteArrayRequestProcedureNotCallable(): void
    {
        $map = [
            'bad.proc' => new \stdClass(), // not RemoteProcedureInterface and not callable
        ];
        $server = $this->makeServer($map);

        $response = $server->executeArrayRequest([
            'jsonrpc' => '2.0',
            'method' => 'bad.proc',
            'id' => 9,
        ]);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isError());
        $this->assertSame(ErrorCodes::SERVER_ERROR_START->value, $response->error()->code());
        $this->assertSame(['method' => 'bad.proc'], $response->error()->jsonSerialize()['data'] ?? null);
    }

    public function testExecuteArrayRequestCallableThrowsInternalError(): void
    {
        $map = [
            'throws' => function (): void { throw new \RuntimeException('boom'); },
        ];
        $server = $this->makeServer($map);

        $response = $server->executeArrayRequest([
            'jsonrpc' => '2.0',
            'method' => 'throws',
            'id' => 11,
        ]);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isError());
        $this->assertSame(ErrorCodes::INTERNAL_ERROR->value, $response->error()->code());
        $this->assertSame(ErrorCodes::INTERNAL_ERROR->message(), $response->error()->message());
        $this->assertSame(['method' => 'throws', 'params' => []], $response->error()->jsonSerialize()['data'] ?? null);
    }

    public function testExecuteNotificationReturnsNullAndExecutesProcedure(): void
    {
        $called = false;
        $map = [
            'notify' => function () use (&$called): void { $called = true; },
        ];
        $server = $this->makeServer($map);

        $result = $server->executeArrayRequest([
            'jsonrpc' => '2.0',
            'method' => 'notify',
            // no id => notification
        ]);

        $this->assertNull($result, 'Notification requests must return null');
        $this->assertTrue($called, 'Procedure should be executed for notifications');
    }

    public function testExecutePsrRequestBatchProcessing(): void
    {
        $fixedId = 77;
        $remote = new class($fixedId) implements RemoteProcedureInterface {
            public function __construct(private int $id) {}
            public function call(): Response { return new Response(result: 'ok.remote', id: $this->id); }
        };

        $map = [
            'sum' => function (int $a, int $b): int { return $a + $b; },
            'remote.ok' => $remote,
            'bad.proc' => new \stdClass(),
            'throws' => function (): void { throw new \RuntimeException('explode'); },
            'notify' => function (): void {},
        ];
        $server = $this->makeServer($map);

        $jsonBody = json_encode([
            ['jsonrpc' => '2.0', 'method' => 'sum', 'id' => 1, 'params' => [10, 5]],
            ['jsonrpc' => '2.0', 'method' => 'remote.ok', 'id' => $fixedId], // no params
            ['jsonrpc' => '2.0', 'method' => 'unknown.method', 'id' => 3, 'params' => []], // unknown method -> handled as Response error
            ['jsonrpc' => '2.0', 'method' => 'bad.proc', 'id' => 4], // not callable
            ['jsonrpc' => '2.0', 'method' => 'throws', 'id' => 5], // throws -> internal error
            ['jsonrpc' => '2.0', 'method' => 'notify'], // notification -> omitted from batch response
        ]);

        $psrRequest = $this->createMock(RequestInterface::class);
        $psrRequest->method('getBody')->willReturn($this->createStream($jsonBody));

        $batchResponse = $server->executePsrRequest($psrRequest);
        $this->assertInstanceOf(BatchResponse::class, $batchResponse);
        // One notification omitted -> expect 5 responses (sum, remote, error from missing method, not callable, internal error)
        $this->assertCount(5, $batchResponse);

        // 0: sum success
        $this->assertInstanceOf(Response::class, $batchResponse[0]);
        $this->assertTrue($batchResponse[0]->isSuccess());
        $this->assertSame(15, $batchResponse[0]->result());
        $this->assertSame(1, $batchResponse[0]->id());

        // 1: remote success
        $this->assertInstanceOf(Response::class, $batchResponse[1]);
        $this->assertTrue($batchResponse[1]->isSuccess());
        $this->assertSame('ok.remote', $batchResponse[1]->result());
        $this->assertSame($fixedId, $batchResponse[1]->id());

        // 2: error produced by factory (missing method in request)
        $this->assertInstanceOf(Error::class, $batchResponse[2]->error());
        $this->assertSame(ErrorCodes::METHOD_NOT_FOUND->value, $batchResponse[2]->error()->code());

        // 3: not callable
        $this->assertInstanceOf(Response::class, $batchResponse[3]);
        $this->assertTrue($batchResponse[3]->isError());
        $this->assertSame(ErrorCodes::SERVER_ERROR_START->value, $batchResponse[3]->error()->code());

        // 4: internal error from thrown exception
        $this->assertInstanceOf(Response::class, $batchResponse[4]);
        $this->assertTrue($batchResponse[4]->isError());
        $this->assertSame(ErrorCodes::INTERNAL_ERROR->value, $batchResponse[4]->error()->code());
        $this->assertNotNull($batchResponse[4]->error()->originalException());
        $this->assertEquals('explode', $batchResponse[4]->error()->originalException()->getMessage());
    }

    public function testExecutePsrRequestSingleDelegates(): void
    {
        $map = [
            'echo' => function (string $s): string { return $s; },
        ];
        $server = $this->makeServer($map);

        $jsonBody = json_encode(['jsonrpc' => '2.0', 'method' => 'echo', 'id' => 42, 'params' => ['hello']]);
        $psrRequest = $this->createMock(RequestInterface::class);
        $psrRequest->method('getBody')->willReturn($this->createStream($jsonBody));

        $response = $server->executePsrRequest($psrRequest);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertSame('hello', $response->result());
        $this->assertSame(42, $response->id());
    }

    public function testBatchRequestWithNotificationsOnly(): void
    {
        $remote = new class implements RemoteProcedureInterface {
            public function call(): Response { return new Response(result: 'ok.remote'); }
        };

        $map = [
            'sum' => function (int $a, int $b): int { return $a + $b; },
            'remote.ok' => $remote,
            'bad.proc' => new \stdClass(),
            'throws' => function (): void { throw new \RuntimeException('explode'); },
            'notify' => function (): void {},
        ];
        $server = $this->makeServer($map);
        $jsonBody = [
            ['jsonrpc' => '2.0', 'method' => 'sum', 'params' => [10, 5]],
            ['jsonrpc' => '2.0', 'method' => 'remote.ok'], // no params
            ['jsonrpc' => '2.0', 'method' => 'unknown.method', 'params' => []], // unknown method -> handled as Response error
            ['jsonrpc' => '2.0', 'method' => 'bad.proc'], // not callable
            ['jsonrpc' => '2.0', 'method' => 'throws'], // throws -> internal error
            ['jsonrpc' => '2.0', 'method' => 'notify'], // notification -> omitted from batch response
        ];
        $response = $server->executeArrayRequest($jsonBody);
        $this->assertNull($response);
    }

    public function testExecutePsrRequestBatchWithInvalidElementThrows(): void
    {
        $map = [
            'sum' => function (int $a, int $b): int { return $a + $b; },
        ];
        $server = $this->makeServer($map);

        $jsonBody = json_encode([
            ['jsonrpc' => '2.0', 'id' => 1, 'params' => [1, 2]], // missing method -> becomes Error in BatchRequest
            ['jsonrpc' => '2.0', 'method' => 'sum', 'id' => 2, 'params' => [3, 4]],
        ]);

        $psrRequest = $this->createMock(RequestInterface::class);
        $psrRequest->method('getBody')->willReturn($this->createStream($jsonBody));

        $this->expectException(InvalidBatchElementException::class);
        $server->executePsrRequest($psrRequest);
    }

    public function testArrayBatchRequest(): void
    {
        $remote = new class implements RemoteProcedureInterface {
            public function call(): Response { return new Response(result: 'ok.remote'); }
        };

        $map = [
            'sum' => function (int $a, int $b): int { return $a + $b; },
            'remote.ok' => $remote,
        ];
        $server = $this->makeServer($map);
        $jsonBody = [
            ['jsonrpc' => '2.0', 'method' => 'sum', 'id' => 1, 'params' => [10, 5]],
            ['jsonrpc' => '2.0', 'method' => 'remote.ok', 'id' => 2],
        ];
        $response = $server->executeArrayRequest($jsonBody);
        $this->assertInstanceOf(BatchResponse::class, $response);
    }

    private function makeServer(array $map): Server
    {
        $container = $this->makeContainer($map);
        return new Server(new RequestFactory(), $container);
    }

    /**
     * @param array<string, mixed> $map
     */
    private function makeContainer(array $map): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static function (string $id) use ($map): bool {
            return array_key_exists($id, $map);
        });
        $container->method('get')->willReturnCallback(static function (string $id) use ($map) {
            return $map[$id];
        });

        return $container;
    }

    private function createStream(string $content): StreamInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($content);
        return $stream;
    }
}
