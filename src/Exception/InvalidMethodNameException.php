<?php

namespace Alcedo\JsonRpc\Server\Exception;

/**
 * Exception thrown if a method name is invalid or does not conform
 * to the expected naming conventions or requirements.
 *
 * This exception is typically used to indicate an error related to
 * method name resolution or validation in dynamic method handling scenarios.
 */
class InvalidMethodNameException extends \Exception
{
    // ...
}
