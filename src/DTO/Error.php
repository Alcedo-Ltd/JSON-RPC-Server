<?php

namespace Alcedo\JsonRpc\Server\DTO;

use Alcedo\JsonRpc\Server\Exception\InvalidErrorException;

/**
 * Represents an error with a code, message, and optional data.
 *
 * This class provides a standardized structure for errors, including a code,
 * a message, and additional optional data. It implements the JsonSerializable
 * interface to allow for JSON representation of the error instance.
 *
 * The error code corresponds to a predefined set of possible error codes
 * and their associated messages.
 */
class Error implements \JsonSerializable
{
    private ErrorCodes $errorCode;

    /**
     * Constructor method for initializing the object.
     *
     * @param int $code The error code associated with the object.
     * @param string $message The error message. If not provided, it defaults to a message from the error code.
     * @param mixed|null $data Additional data associated with the object, defaults to null.
     *
     * @return void
     *
     * @throws InvalidErrorException If the provided error code is not valid.
     */
    public function __construct(
        private readonly  int $code,
        private string $message,
        private readonly mixed $data = null,
    ) {
        $this->errorCode = ErrorCodes::fromValue($this->code);
        if (!$this->message) {
            $this->message = $this->errorCode->message();
        }
    }

    /**
     * Retrieves the code.
     *
     * @return int The code value.
     */
    public function code(): int
    {
        return $this->code;
    }

    /**
     * Retrieves the message.
     *
     * @return string The message value.
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * Retrieves the data.
     *
     * @return mixed The data value.
     */
    public function data(): mixed
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = [
            'code' => $this->code,
            'message' => $this->message,
        ];
        if ($this->data !== null) {
            $data['data'] = $this->data;
        }

        return $data;
    }
}
