<?php

namespace Alcedo\JsonRpc\Server\Exception;

/**
 * Represents an exception that is thrown when an invalid element is encountered
 * in a batch processing operation.
 *
 * This exception is used to indicate that one or more elements in a batch
 * do not meet the required criteria or contain invalid data.
 *
 * Extends the base Exception class.
 */
class InvalidBatchElementException extends \Exception
{
    // ...
}
