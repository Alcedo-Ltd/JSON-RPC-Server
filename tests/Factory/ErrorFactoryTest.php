<?php

namespace Tests\Factory;

use Alcedo\JsonRpc\Server\DTO\Error;
use Alcedo\JsonRpc\Server\DTO\ErrorCodes;
use Alcedo\JsonRpc\Server\Factory\ErrorFactory;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ErrorFactory class.
 */
class ErrorFactoryTest extends TestCase
{
    public function testParseErrorWithDefaultMessage(): void
    {
        $error = ErrorFactory::parseError();

        $this->assertSame(ErrorCodes::PARSE_ERROR->value, $error->code());
        $this->assertSame(ErrorCodes::PARSE_ERROR->message(), $error->message());
        $this->assertNull($error->data());
    }

    public function testParseErrorWithCustomMessage(): void
    {
        $customMessage = 'Custom parse error message';
        $data = ['key' => 'value'];

        $error = ErrorFactory::parseError($customMessage, $data);

        $this->assertSame(ErrorCodes::PARSE_ERROR->value, $error->code());
        $this->assertSame($customMessage, $error->message());
        $this->assertSame($data, $error->data());
    }

    public function testInvalidRequestWithDefaultMessage(): void
    {
        $error = ErrorFactory::invalidRequest();

        $this->assertSame(ErrorCodes::INVALID_REQUEST->value, $error->code());
        $this->assertSame(ErrorCodes::INVALID_REQUEST->message(), $error->message());
        $this->assertNull($error->data());
    }

    public function testInvalidRequestWithCustomMessage(): void
    {
        $customMessage = 'Custom invalid request message';
        $data = 'string data';

        $error = ErrorFactory::invalidRequest($customMessage, $data);

        $this->assertSame(ErrorCodes::INVALID_REQUEST->value, $error->code());
        $this->assertSame($customMessage, $error->message());
        $this->assertSame($data, $error->data());
    }

    public function testMethodNotFoundWithDefaultMessage(): void
    {
        $error = ErrorFactory::methodNotFound();

        $this->assertSame(ErrorCodes::METHOD_NOT_FOUND->value, $error->code());
        $this->assertSame(ErrorCodes::METHOD_NOT_FOUND->message(), $error->message());
        $this->assertNull($error->data());
    }

    public function testMethodNotFoundWithCustomMessage(): void
    {
        $customMessage = 'Method xyz not found';
        $data = 123;

        $error = ErrorFactory::methodNotFound($customMessage, $data);

        $this->assertSame(ErrorCodes::METHOD_NOT_FOUND->value, $error->code());
        $this->assertSame($customMessage, $error->message());
        $this->assertSame($data, $error->data());
    }

    public function testInvalidParamsWithDefaultMessage(): void
    {
        $error = ErrorFactory::invalidParams();

        $this->assertSame(ErrorCodes::INVALID_PARAMS->value, $error->code());
        $this->assertSame(ErrorCodes::INVALID_PARAMS->message(), $error->message());
        $this->assertNull($error->data());
    }

    public function testInvalidParamsWithCustomMessage(): void
    {
        $customMessage = 'Parameters validation failed';
        $data = ['expected' => 'int', 'actual' => 'string'];

        $error = ErrorFactory::invalidParams($customMessage, $data);

        $this->assertSame(ErrorCodes::INVALID_PARAMS->value, $error->code());
        $this->assertSame($customMessage, $error->message());
        $this->assertSame($data, $error->data());
    }

    public function testInternalErrorWithDefaultMessage(): void
    {
        $error = ErrorFactory::internalError();

        $this->assertSame(ErrorCodes::INTERNAL_ERROR->value, $error->code());
        $this->assertSame(ErrorCodes::INTERNAL_ERROR->message(), $error->message());
        $this->assertNull($error->data());
    }

    public function testInternalErrorWithCustomMessage(): void
    {
        $customMessage = 'Database connection failed';
        $data = true;

        $error = ErrorFactory::internalError($customMessage, $data);

        $this->assertSame(ErrorCodes::INTERNAL_ERROR->value, $error->code());
        $this->assertSame($customMessage, $error->message());
        $this->assertSame($data, $error->data());
    }

    public function testServerErrorWithDefaultCode(): void
    {
        $error = ErrorFactory::serverError();

        $this->assertSame(ErrorCodes::SERVER_ERROR_START->value, $error->code());
        $this->assertSame(ErrorCodes::SERVER_ERROR_START->message(), $error->message());
        $this->assertNull($error->data());
    }

    public function testServerErrorWithValidCode(): void
    {
        $validCode = -32050;
        $customMessage = 'Server temporarily unavailable';
        $data = ['retry_after' => 300];

        $error = ErrorFactory::serverError($validCode, $customMessage, $data);

        $this->assertSame($validCode, $error->code());
        $this->assertSame($customMessage, $error->message());
        $this->assertSame($data, $error->data());
    }

    public function testServerErrorWithInvalidCodeTooHigh(): void
    {
        $invalidCode = -31999; // Too high
        $error = ErrorFactory::serverError($invalidCode);

        // Should default to SERVER_ERROR_START
        $this->assertSame(ErrorCodes::SERVER_ERROR_START->value, $error->code());
    }

    public function testServerErrorWithInvalidCodeTooLow(): void
    {
        $invalidCode = -32100; // Too low
        $error = ErrorFactory::serverError($invalidCode);

        // Should default to SERVER_ERROR_START
        $this->assertSame(ErrorCodes::SERVER_ERROR_START->value, $error->code());
    }

    public function testErrorJsonSerialization(): void
    {
        $message = 'Test message';
        $data = ['test' => 'data'];
        $error = ErrorFactory::parseError($message, $data);

        $json = $error->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertSame(ErrorCodes::PARSE_ERROR->value, $json['code']);
        $this->assertSame($message, $json['message']);
        $this->assertSame($data, $json['data']);
    }

    public function testErrorJsonSerializationWithoutData(): void
    {
        $message = 'Test message';
        $error = ErrorFactory::invalidRequest($message);

        $json = $error->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertSame(ErrorCodes::INVALID_REQUEST->value, $json['code']);
        $this->assertSame($message, $json['message']);
        $this->assertArrayNotHasKey('data', $json);
    }

    public function testErrorWithEmptyMessageUsesDefault(): void
    {
        $error = new Error(ErrorCodes::PARSE_ERROR->value, '');

        $this->assertSame(ErrorCodes::PARSE_ERROR->message(), $error->message());
    }

    public function testServerErrorBoundaryValues(): void
    {
        // Test exact boundary values
        $minValidCode = ErrorCodes::SERVER_ERROR_END->value; // -32000
        $maxValidCode = ErrorCodes::SERVER_ERROR_START->value; // -32099

        $minError = ErrorFactory::serverError($minValidCode);
        $maxError = ErrorFactory::serverError($maxValidCode);

        $this->assertSame($minValidCode, $minError->code());
        $this->assertSame($maxValidCode, $maxError->code());
    }
}
