<?php

namespace Alcedo\JsonRpc\Server\Factory;

use Alcedo\JsonRpc\Server\DTO\Error;
use Alcedo\JsonRpc\Server\DTO\ErrorCodes;
use Alcedo\JsonRpc\Server\Exception\InvalidErrorException;

/**
 * Factory class for generating standardized error objects based on specific error codes.
 */
class ErrorFactory
{
    /**
     * Creates an Error object for parse error.
     *
     * @param string $message Optional custom message. If empty, uses a default message.
     * @param mixed $data Optional additional data.
     *
     * @return Error The Error object for parse error.
     *
     * @throws InvalidErrorException
     */
    public static function parseError(string $message = '', mixed $data = null): Error
    {
        return new Error(
            ErrorCodes::PARSE_ERROR->value,
            $message,
            $data
        );
    }

    /**
     * Creates an Error object for invalid request.
     *
     * @param string $message Optional custom message. If empty, uses a default message.
     * @param mixed $data Optional additional data.
     *
     * @return Error The Error object for an invalid request.
     *
     * @throws InvalidErrorException
     */
    public static function invalidRequest(string $message = '', mixed $data = null): Error
    {
        return new Error(
            ErrorCodes::INVALID_REQUEST->value,
            $message,
            $data
        );
    }

    /**
     * Creates an Error object for a method not found.
     *
     * @param string $message Optional custom message. If empty, uses a default message.
     * @param mixed $data Optional additional data.
     *
     * @return Error The Error object for method not found.
     *
     * @throws InvalidErrorException
     */
    public static function methodNotFound(string $message = '', mixed $data = null): Error
    {
        return new Error(
            ErrorCodes::METHOD_NOT_FOUND->value,
            $message,
            $data
        );
    }

    /**
     * Creates an Error object for invalid params.
     *
     * @param string $message Optional custom message. If empty, uses a default message.
     * @param mixed $data Optional additional data.
     *
     * @return Error The Error object for invalid params.
     *
     * @throws InvalidErrorException
     */
    public static function invalidParams(string $message = '', mixed $data = null): Error
    {
        return new Error(
            ErrorCodes::INVALID_PARAMS->value,
            $message,
            $data
        );
    }

    /**
     * Creates an Error object for internal error.
     *
     * @param string $message Optional custom message. If empty, uses a default message.
     * @param mixed $data Optional additional data.
     *
     * @return Error The Error object for internal error.
     * @throws InvalidErrorException
     */
    public static function internalError(string $message = '', mixed $data = null): Error
    {
        return new Error(
            ErrorCodes::INTERNAL_ERROR->value,
            $message,
            $data
        );
    }

    /**
     * Creates an Error object for server error.
     *
     * @param int $code The specific server error code (must be between -32099 and -32000).
     * @param string $message Optional custom message. If empty, uses a default message.
     * @param mixed $data Optional additional data.
     *
     * @return Error The Error object for server error.
     *
     * @throws InvalidErrorException
     */
    public static function serverError(int $code = -32099, string $message = '', mixed $data = null): Error
    {
        // Ensure the code is within the server error range
        if ($code < ErrorCodes::SERVER_ERROR_START->value || $code > ErrorCodes::SERVER_ERROR_END->value) {
            $code = ErrorCodes::SERVER_ERROR_START->value;
        }

        return new Error(
            $code,
            $message,
            $data
        );
    }
}
