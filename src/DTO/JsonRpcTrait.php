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
    public const VERSION = '2.0';

    /** The JSON-RPC message identifier. */
    private int|string|null $id;

    /**
     * Retrieves the JSON-RPC version.
     *
     * @return string The JSON-RPC version string.
     */
    public function jsonRpc(): string
    {
        return self::VERSION;
    }

    /**
     * The JSON-RPC message identifier.
     *
     * @return int|string|null
     */
    public function id(): int|string|null
    {
        return $this->id;
    }

    /**
     * Determines if the JSON-RPC message is a notification.
     *
     * @return bool True if it is a notification, false otherwise.
     */
    public function isNotification(): bool
    {
        return $this->id === null;
    }
}
