<?php

namespace Alcedo\JsonRpc\Server\DTO;

use Alcedo\JsonRpc\Server\Exception\InvalidErrorException;
use Throwable;

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
     * @param Throwable|null $originalException The original exception that caused the error defaults to null.
     * @param bool $useExceptionMessage Whether to use the exception message as the error message, defaults to false.
     * @param bool $useExceptionTraceAsData Whether to use the exception trace as the error data, defaults to false.
     * @param bool $useExceptionAsData Whether to use the exception as the error data, defaults to false.
     * @param bool $nestPreviousExceptions Whether to nest previous exceptions in the error data, defaults to false.
     *
     * @return void
     *
     * @throws InvalidErrorException If the provided error code is not valid.
     */
    public function __construct(
        private readonly int $code,
        private string $message = '',
        private readonly mixed $data = null,
        private ?Throwable $originalException = null,
        private bool $useExceptionMessage = false,
        private bool $useExceptionTraceAsData = false,
        private bool $useExceptionAsData = false,
        private bool $nestPreviousExceptions = false
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
        if ($this->useExceptionMessage && $this->originalException instanceof Throwable) {
            return $this->originalException->getMessage();
        }

        return $this->message;
    }

    /**
     * Retrieves the data.
     *
     * @return mixed The data value.
     */
    public function data(): mixed
    {
        if ($this->useExceptionTraceAsData && $this->originalException instanceof Throwable) {
            return $this->originalException->getTraceAsString();
        } elseif ($this->useExceptionAsData && $this->originalException instanceof Throwable) {
            return $this->transformException($this->originalException);
        }

        return $this->data;
    }

    /**
     * Retrieves the original exception.
     *
     * @return Throwable|null
     */
    public function originalException(): ?Throwable
    {
        return $this->originalException;
    }

    /**
     * Sets the original exception.
     *
     * @param Throwable|null $originalException
     *
     * @return Error
     */
    public function setOriginalException(?Throwable $originalException): Error
    {
        $this->originalException = $originalException;

        return $this;
    }

    /**
     * Use the exception message
     *
     * @return Error
     */
    public function useExceptionMessage(): Error
    {
        $this->useExceptionMessage = true;

        return $this;
    }

    /**
     * Use the error message set in the constructor
     *
     * @return Error
     */
    public function useTheErrorMessage(): Error
    {
        $this->useExceptionMessage = false;

        return $this;
    }

    /**
     * Use the exception trace as data
     *
     * @return Error
     */
    public function useExceptionTraceAsData(): Error
    {
        $this->useExceptionTraceAsData = true;

        return $this;
    }

    /**
     * Use the data set in the constructor
     *
     * @return Error
     */
    public function useTheErrorData(): Error
    {
        $this->useExceptionTraceAsData = false;
        $this->useExceptionAsData = false;

        return $this;
    }

    /**
     * Use the exception as data
     *
     * @return Error
     */
    public function useExceptionAsData(): Error
    {
        $this->useExceptionAsData = true;

        return $this;
    }

    /**
     * Nest previous exceptions
     *
     * @return Error
     */
    public function nestPreviousExceptions(): Error
    {
        $this->nestPreviousExceptions = true;

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $data = [
            'code' => $this->code(),
            'message' => $this->message(),
        ];
        if ($this->data() !== null) {
            $data['data'] = $this->data();
        }

        return $data;
    }

    /**
     * Transform exception to array
     *
     * @param Throwable $exception
     *
     * @return array
     */
    private function transformException(Throwable $exception): array
    {
        $data = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile() . '(' . $exception->getLine() . ')',
            'trace' => $exception->getTraceAsString(),
        ];
        if ($this->nestPreviousExceptions && $exception->getPrevious()) {
            $data['previous'] = $this->transformException($exception->getPrevious());
        }

        return $data;
    }
}
