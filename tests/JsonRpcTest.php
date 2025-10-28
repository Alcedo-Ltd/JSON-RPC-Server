<?php

namespace Alcedo\Tests\JsonRpc\Server;

use Alcedo\JsonRpc\Server\DTO\BatchRequest;
use Alcedo\JsonRpc\Server\DTO\Error;
use Alcedo\JsonRpc\Server\DTO\ErrorCodes;
use Alcedo\JsonRpc\Server\DTO\Request;
use Alcedo\JsonRpc\Server\DTO\Response;
use Alcedo\JsonRpc\Server\Exception\InvalidBatchElementException;
use Alcedo\JsonRpc\Server\Exception\InvalidResponseException;
use PHPUnit\Framework\TestCase;

class JsonRpcTest extends TestCase
{
    public function testJsonSerializingSuccessfulResponse(): void
    {
        $response = new Response('success', id: 1);
        $expected = '{"jsonrpc":"2.0","result":"success","id":1}';
        $this->assertSame($expected, json_encode($response));
    }

    public function testJsonSerializingErrorResponse(): void
    {
        $response = new Response(error: new Error(ErrorCodes::INTERNAL_ERROR->value, data: 'testdata'), id: 1);
        $expected = '{"jsonrpc":"2.0","error":{"code":-32603,"message":"Internal error","data":"testdata"},"id":1}';
        $this->assertSame($expected, json_encode($response));
    }

    public function testResponseWithBothResultAndError(): void
    {
        $this->expectException(InvalidResponseException::class);
        new Response(result: 'success', error: new Error(ErrorCodes::INTERNAL_ERROR->value, data: 'testdata'), id: 1);
    }

    public function testValidateBatchRequestWithInvalidElement(): void
    {
        $batch = new BatchRequest();
        $this->expectException(InvalidBatchElementException::class);
        $batch->append(new Response(result: 'success', id: 1));
    }

    public function testExchangeBatchContent(): void
    {
        $batch = new BatchRequest();
        $newContent = [new Request('testmethod'), new Request('testmethod2')];
        $this->assertSame($newContent, $batch->exchangeArray($newContent));
    }

    public function testJsonSerializeBatch(): void
    {
        $newContent = [new Request('testmethod'), new Request('testmethod2')];
        $batch = new BatchRequest($newContent);
        $this->assertEquals(json_encode($newContent), json_encode($batch));
    }
}
