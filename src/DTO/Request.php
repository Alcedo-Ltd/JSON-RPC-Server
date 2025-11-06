<?php

namespace Alcedo\JsonRpc\Server\DTO;

use Alcedo\JsonRpc\Server\Exception\InvalidMethodNameException;

/**
 * Represents a JSON-RPC request that includes a method name, parameters, and an optional identifier.
 * Provides functionality to validate the method name and check if the request is a notification.
 *
 * @author Kiril Savchev <k.savchev@gmail.com>
 */
class Request implements JsonRpcMessageInterface
{
    use JsonRpcTrait;

    /**
     * Constructor method for initializing the class instance.
     *
     * @param string $method The method name to be used.
     * @param array $params Optional array of parameters.
     * @param int|string|null $id Optional identifier, it can be an integer, string, or null.
     *
     * @return void
     *
     * @throws InvalidMethodNameException if the method name starts with the reserved prefix 'rpc.'.
     */
    public function __construct(
        private string $method,
        private array $params = [],
        int|string|null $id = null
    ) {
        $this->id = $id;
        $this->validateMethod();
    }

    /**
     * Retrieves the method value.
     *
     * @return string The value of the method property.
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Retrieves the parameters.
     *
     * @return array The parameters.
     */
    public function params(): array
    {
        return $this->params;
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
            'method' => $this->method(),
        ];
        if ($this->id() !== null) {
            $data['id'] = $this->id();
        }
        if ($this->params()) {
            $data['params'] = $this->params();
        }

        return $data;
    }

    /**
     * Validates the method name to ensure it does not start with the reserved prefix 'rpc.'.
     *
     * @return void
     *
     * @throws InvalidMethodNameException if the method name starts with the reserved prefix 'rpc.'.
     */
    private function validateMethod(): void
    {
        if (str_starts_with($this->method, 'rpc.')) {
            throw new InvalidMethodNameException('Method names starting with "rpc." are reserved for internal use.');
        }
    }
}
