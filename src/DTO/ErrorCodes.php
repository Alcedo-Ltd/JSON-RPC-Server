<?php

namespace Alcedo\JsonRpc\Server\DTO;

use Alcedo\JsonRpc\Server\Exception\InvalidErrorException;

/**
 * Represents a set of predefined error codes commonly used in a JSON-RPC context.
 *
 * Each error code corresponds to a specific type of error that might occur
 * during the processing of a request or response. These codes are based on
 * standard JSON-RPC error definitions, along with a range of server error codes.
 *
 * Enum cases:
 * - PARSE_ERROR: Error indicating an issue while parsing the JSON payload.
 * - INVALID_REQUEST: Error indicating that the request is invalid or malformed.
 * - METHOD_NOT_FOUND: Error indicating that the requested method does not exist.
 * - INVALID_PARAMS: Error indicating issues with the parameters of the request.
 * - INTERNAL_ERROR: Error indicating an internal server issue.
 * - SERVER_ERROR_START, SERVER_ERROR_END: Represents the range of custom server-defined errors.
 *
 * Methods:
 * - fromValue(int $value): Converts an integer value into the corresponding ErrorCodes enum case.
 *   Throws an exception if the value does not match any predefined error code.
 * - message(): Provides a descriptive text message associated with each error code.
 */
enum ErrorCodes: int
{
    case PARSE_ERROR = -32700;
    case INVALID_REQUEST = -32600;
    case METHOD_NOT_FOUND = -32601;
    case INVALID_PARAMS = -32602;
    case INTERNAL_ERROR = -32603;

    case SERVER_ERROR_START = -32099;
    case SERVER_ERROR_END = -32000;

    /**
     * Retrieves an instance of the class based on the provided value.
     *
     * @param int $value The integer value used to find a matching instance.
     *
     * @return self The corresponding instance of the class.
     *
     * @throws InvalidErrorException If no matching instance is found for the provided value.
     */
    public static function fromValue(int $value): self
    {
        if ($value >= self::SERVER_ERROR_START->value && $value <= self::SERVER_ERROR_END->value) {
            $value = self::SERVER_ERROR_START->value;
        }

        return self::tryFrom($value) ?? throw new InvalidErrorException('Unknown error code: ' . $value . '.');
    }

    /**
     * Provides the corresponding message string for the current instance.
     *
     * @return string The message associated with the current instance.
     */
    public function message(): string
    {
        return match ($this) {
            self::PARSE_ERROR => 'Parse error',
            self::INVALID_REQUEST => 'Invalid Request',
            self::METHOD_NOT_FOUND => 'Method not found',
            self::INVALID_PARAMS => 'Invalid params',
            self::INTERNAL_ERROR => 'Internal error',
            self::SERVER_ERROR_START, self::SERVER_ERROR_END => 'Server error',
        };
    }
}
