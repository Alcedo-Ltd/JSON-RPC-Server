<?php

namespace Alcedo\JsonRpc\Server\Exception;

/**
 * Represents an exception that is thrown when an invalid error condition is encountered.
 *
 * This class extends the base `\Exception` class, enabling it to be used
 * as a custom exception type within applications. It can help differentiate
 * specific error scenarios related to invalid errors from generic exceptions.
 */
class InvalidErrorException extends \Exception
{
    // ...
}
