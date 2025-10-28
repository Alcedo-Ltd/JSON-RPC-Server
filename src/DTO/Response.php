<?php

namespace Alcedo\JsonRpc\Server\DTO;

use Alcedo\JsonRpc\Server\Exception\InvalidResponseException;

/**
 * Represents a JSON-RPC response, encapsulating the result, error, and ID.
 * Ensures the response adheres to JSON-RPC specifications by validating
 * that either a result or an error is set, but not both.
 *
 * @author Kiril Savchev <k.savchev@gmail.com>
 */
readonly class Response implements \JsonSerializable
{
    use JsonRpcTrait;

    /**
     * Constructor method.
     *
     * @param mixed $result The result of the operation, it can be of any type.
     * @param Error|null $error An instance of Error or null if no error occurred.
     * @param int|string|null $id The identifier, which can be an integer, string, or null.
     *
     * @return void
     *
     * @throws InvalidResponseException If both result and error are set in the response.
     */
    public function __construct(
        private mixed $result = null,
        private ?Error $error = null,
        private int|string|null $id = null
    ) {
        $this->validateResponse();
    }

    /**
     * Retrieves the result of the operation.
     *
     * @return mixed The result value, which can be of any type.
     */
    public function result(): mixed
    {
        return $this->result;
    }

    /**
     * Retrieves the error instance.
     *
     * @return Error|null The error object if it exists, or null if no error is present.
     */
    public function error(): ?Error
    {
        return $this->error;
    }

    /**
     * Retrieves the identifier.
     *
     * @return int|string|null The identifier, which can be an integer, string, or null.
     */
    public function id(): int|string|null
    {
        return $this->id;
    }

    /**
     * Determines if there is an error present.
     *
     * @return bool True if an error exists, false otherwise.
     */
    public function isError(): bool
    {
        return $this->error !== null;
    }

    /**
     * Determines if the operation was successful.
     *
     * @return bool True if the operation was successful, false otherwise.
     */
    public function isSuccess(): bool
    {
        return !$this->isError();
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = [
            'jsonrpc' => $this->jsonRpc(),
        ];
        if ($this->isSuccess()) {
            $data['result'] = $this->result;
        } else {
            $data['error'] = $this->error;
        }
        if ($this->id !== null) {
            $data['id'] = $this->id;
        }

        return $data;
    }

    /**
     * Validates the response to ensure it does not contain both a result and an error.
     *
     * @return void
     * @throws InvalidResponseException( If both result and error are set in the response.
     */
    private function validateResponse(): void
    {
        if ($this->error !== null && $this->result !== null) {
            throw new InvalidResponseException('Response cannot contain both result and error.');
        }
    }
}
