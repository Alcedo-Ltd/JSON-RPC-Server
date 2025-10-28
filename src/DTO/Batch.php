<?php

namespace Alcedo\JsonRpc\Server\DTO;

use Alcedo\JsonRpc\Server\Exception\InvalidBatchElementException;

/**
 * An abstract class extending \ArrayObject, designed to handle a batch of elements with strict validation.
 * Provides methods to ensure all elements in the collection are valid instances of Request or Response.
 */
abstract class Batch extends \ArrayObject implements \JsonSerializable
{
    /**
     * @inheritDoc
     *
     * @throws InvalidBatchElementException If the value is not an instance of Request or Response.
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->validateElement($value);
        parent::offsetSet($key, $value);
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidBatchElementException If the value is not an instance of Request or Response.
     */
    public function append(mixed $value): void
    {
        $this->validateElement($value);
        parent::append($value);
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidBatchElementException If the value is not an instance of Request or Response.
     */
    public function exchangeArray(object|array $array): array
    {
        foreach ($array as $value) {
            $this->validateElement($value);
        }
        parent::exchangeArray($array);

        return $this->getArrayCopy();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }

    /**
     * Validates that the provided value is an instance of Request or Response.
     *
     * @param mixed $value The value to validate.
     *
     * @return void
     *
     * @throws InvalidBatchElementException If the value is not an instance of Request.
     */
    abstract protected function validateElement(mixed $value): void;
}
