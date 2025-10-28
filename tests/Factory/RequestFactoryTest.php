<?php

namespace Alcedo\Tests\JsonRpc\Server\Factory;

use Alcedo\JsonRpc\Server\DTO\BatchRequest;
use Alcedo\JsonRpc\Server\DTO\Error;
use Alcedo\JsonRpc\Server\DTO\Request;
use Alcedo\JsonRpc\Server\Exception\ErrorException;
use Alcedo\JsonRpc\Server\Exception\InvalidBatchElementException;
use Alcedo\JsonRpc\Server\Exception\InvalidMethodNameException;
use Alcedo\JsonRpc\Server\Factory\RequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class RequestFactoryTest extends TestCase
{
    /**
     * Tests that a single request is correctly parsed into a Request object.
     */
    public function testFromServerRequestSingleRequest(): void
    {
        $requestArray = ['method' => 'testMethod', 'id' => 1, 'params' => ['param1' => 'value1']];
        $jsonBody = json_encode($requestArray);
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->method('getBody')->willReturn($this->createStream($jsonBody));

        $factory = new RequestFactory();
        $result = $factory->fromServerRequest($mockRequest);

        $this->assertInstanceOf(Request::class, $result);
        $this->assertSame('testMethod', $result->method());
        $this->assertSame(1, $result->id());
        $this->assertSame(['param1' => 'value1'], $result->params());
        $this->assertSame('2.0', $result->jsonRpc());
        $this->assertFalse($result->isNotification());
        $this->assertArrayIsEqualToArrayIgnoringListOfKeys($requestArray, $result->jsonSerialize(), ['jsonrpc']);
    }

    /**
     * Tests that a request with null params is correctly handled.
     */
    public function testFromServerRequestWithNullParams(): void
    {
        $jsonBody = json_encode(['method' => 'testMethod', 'id' => 1]);
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->method('getBody')->willReturn($this->createStream($jsonBody));

        $factory = new RequestFactory();
        $result = $factory->fromServerRequest($mockRequest);

        $this->assertInstanceOf(Request::class, $result);
        $this->assertSame('testMethod', $result->method());
        $this->assertSame(1, $result->id());
        $this->assertSame([], $result->params());
    }

    /**
     * Tests that a batch request is correctly parsed into a BatchRequest object.
     */
    public function testFromServerRequestBatchRequest(): void
    {
        $jsonBody = json_encode([
            ['method' => 'testMethod1', 'id' => 1, 'params' => ['param1' => 'value1']],
            ['method' => 'testMethod2', 'id' => 2, 'params' => ['param2' => 'value2']],
        ]);
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->method('getBody')->willReturn($this->createStream($jsonBody));

        $factory = new RequestFactory();
        $result = $factory->fromServerRequest($mockRequest);

        $this->assertInstanceOf(BatchRequest::class, $result);
        $this->assertCount(2, $result);

        $this->assertInstanceOf(Request::class, $result[0]);
        $this->assertSame('testMethod1', $result[0]->method());
        $this->assertSame(1, $result[0]->id());
        $this->assertSame(['param1' => 'value1'], $result[0]->params());

        $this->assertInstanceOf(Request::class, $result[1]);
        $this->assertSame('testMethod2', $result[1]->method());
        $this->assertSame(2, $result[1]->id());
        $this->assertSame(['param2' => 'value2'], $result[1]->params());
    }

    /**
     * Tests that an exception is thrown if the body is invalid JSON.
     */
    public function testFromServerRequestInvalidJson(): void
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->method('getBody')->willReturn($this->createStream("{invalid: 'json'}"));

        $factory = new RequestFactory();

        $this->expectException(ErrorException::class);
        $factory->fromServerRequest($mockRequest);
    }

    /**
     * Tests that an exception is thrown if the method key is missing in a single request.
     */
    public function testFromServerRequestMissingMethod(): void
    {
        $jsonBody = json_encode(['id' => 1, 'params' => ['param1' => 'value1']]);
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->method('getBody')->willReturn($this->createStream($jsonBody));

        $factory = new RequestFactory();

        $this->expectException(ErrorException::class);
        $factory->fromServerRequest($mockRequest);
    }

    /**
     * Tests that an exception is thrown if any request in a batch is missing the method key.
     */
    public function testFromServerRequestBatchRequestWithMissingMethod(): void
    {
        $jsonBody = json_encode([
            ['id' => 2, 'params' => ['param2' => 'value2']],
        ]);
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->method('getBody')->willReturn($this->createStream($jsonBody));

        $factory = new RequestFactory();
        $batch = $factory->fromServerRequest($mockRequest);
        $this->assertInstanceOf(Error::class, $batch[0]);
    }

    public function testFromServerRequestWithInvalidMethodName(): void
    {
        $jsonBody = json_encode([
            'id' => 2,
            'method' => 'rpc.invalid.method',
            'params' => ['param2' => 'value2'],
        ]);
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->method('getBody')->willReturn($this->createStream($jsonBody));

        $factory = new RequestFactory();
        $this->expectException(InvalidMethodNameException::class);
        $factory->fromServerRequest($mockRequest);
    }

    /**
     * Creates a stream for the given content.
     *
     * @param string $content The content to be wrapped in a stream.
     * @return StreamInterface A readable stream instance.
     */
    private function createStream(string $content): StreamInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($content);

        return $stream;
    }
}