<?php

namespace Alcedo\JsonRpc\Server\Exception;

use Alcedo\JsonRpc\Server\DTO\Error;
use Alcedo\JsonRpc\Server\DTO\ErrorCodes;
use Exception;
use Throwable;

/**
 * Represents an error exception which extends the base Exception class.
 * Provides methods for creating an instance from error codes
 * and converting the exception to an Error object.
 */
class ErrorException extends Exception
{
    /**
     * Creates a new ErrorException instance based on the provided error code.
     *
     * @param ErrorCodes $errorCode The error code from which the exception will be created.
     * @param Throwable|null $prev Optional previous throwable for exception chaining.
     *
     * @return ErrorException Returns a new instance of ErrorException.
     */
    public static function fromErrorCode(ErrorCodes $errorCode, ?Throwable $prev = null): ErrorException
    {
        return new self($errorCode->message(), $errorCode->value, $prev);
    }

    /**
     * Converts the current object state into an Error instance.
     *
     * @param mixed $data Optional additional data to include in the error.
     *
     * @return Error The generated Error object.
     *
     * @throws InvalidErrorException If the error code is not valid.
     */
    public function toError(mixed $data = null): Error
    {
        return new Error($this->code, $this->message, $data);
    }
}
