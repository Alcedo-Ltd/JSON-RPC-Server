<?php

namespace Alcedo\JsonRpc\Server\DTO;

use Alcedo\JsonRpc\Server\Exception\InvalidBatchElementException;

/**
 * Represents a batch response that extends the functionality of the Batch class
 * and performs element validation to ensure the inclusion of only Response instances.
 */
class BatchResponse extends Batch
{
    /**
     * Filter out the notifications from the batch response.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return array_filter($this->getArrayCopy(), fn (Response $response) => !$response->isNotification());
    }

    /**
     * Validates that the provided value is an instance of Request.
     *
     * @param mixed $value The value to validate.
     *
     * @return void
     *
     * @throws InvalidBatchElementException If the value is not an instance of Response.
     */
    protected function validateElement(mixed $value): void
    {
        if (!$value instanceof Response) {
            throw new InvalidBatchElementException('Only Request objects are allowed.');
        }
    }
}
