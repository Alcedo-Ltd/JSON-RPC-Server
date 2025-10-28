<?php

namespace Alcedo\JsonRpc\Server\DTO;

/**
 * Provides functionality for handling JSON-RPC protocol version.
 *
 * This trait includes a method to retrieve the version to return the JSON-RPC version.
 *
 * @author  Kiril Savchev <k.savchev@gmail.com>
 */
trait JsonRpcTrait
{
    /**
     * Retrieves the JSON-RPC version.
     *
     * @return string The JSON-RPC version string.
     */
    public function jsonRpc(): string
    {
        return '2.0';
    }
}
