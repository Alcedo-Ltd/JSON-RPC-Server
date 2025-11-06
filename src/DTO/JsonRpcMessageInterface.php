<?php

namespace Alcedo\JsonRpc\Server\DTO;

/**
 * Interface representing a JSON-RPC message structure.
 */
interface JsonRpcMessageInterface extends \JsonSerializable
{
    /**
     * Retrieves the JSON-RPC version.
     *
     * @return string The JSON-RPC version string.
     */
    public function jsonrpc(): string;

    /**
     * The JSON-RPC message identifier.
     *
     * @return int|string|null
     */
    public function id(): int|string|null;

    /**
     * Determines if the JSON-RPC message is a notification.
     *
     * @return bool True if it is a notification, false otherwise.
     */
    public function isNotification(): bool;
}
