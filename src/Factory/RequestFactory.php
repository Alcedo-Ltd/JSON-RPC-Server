<?php

namespace Alcedo\JsonRpc\Server\Factory;

use Alcedo\JsonRpc\Server\DTO\BatchRequest;
use Alcedo\JsonRpc\Server\DTO\ErrorCodes;
use Alcedo\JsonRpc\Server\DTO\Request;
use Alcedo\JsonRpc\Server\Exception\ErrorException;
use Alcedo\JsonRpc\Server\Exception\InvalidBatchElementException;
use Alcedo\JsonRpc\Server\Exception\InvalidErrorException;
use Alcedo\JsonRpc\Server\Exception\InvalidMethodNameException;
use Psr\Http\Message\RequestInterface;
use ValueError;
use JsonException;

/**
 * Responsible for creating Request or BatchRequest objects using server requests or arrays.
 */
class RequestFactory
{
    /**
     * Creates a Request or BatchRequest object from the provided ServerRequestInterface instance.
     *
     * @param RequestInterface $request The server request instance from which to construct the object.
     *
     * @return Request|BatchRequest Returns a Request object if the body of the request represents a single request,
     *                              or a BatchRequest object if the body represents a batch of requests.
     *
     * @throws ErrorException If the request body is empty or the method is not provided.
     * @throws ErrorException If the request body contains invalid JSON, that cannot be parsed.
     * @throws ErrorException If the body of the request contains invalid JSON, that cannot be parsed.
     * @throws InvalidBatchElementException If the body of the request contains invalid JSON, that cannot be parsed.
     * @throws InvalidMethodNameException|InvalidErrorException If the method name is invalid.
     */
    public function fromServerRequest(RequestInterface $request): Request|BatchRequest
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException | ValueError $exception) {
            throw ErrorException::fromErrorCode(ErrorCodes::PARSE_ERROR, $exception);
        }
        if (array_key_exists(0, $body)) {
            return $this->createBatchRequest($body);
        }

        return $this->fromArray($body);
    }

    /**
     * Creates a new Request object from the given array.
     *
     * @param array $request An associative array containing the request data.
     *                        Expected keys are 'method', 'id' (optional), and 'params' (optional).
     *
     * @return Request|BatchRequest Returns an initialized Request object.
     *
     * @throws ErrorException Throws an exception if the 'method' key is missing in the input array.
     * @throws InvalidMethodNameException Throws an exception if the 'method' key contains an invalid method name.
     * @throws InvalidBatchElementException If the body of the request contains invalid JSON, that cannot be parsed.
     * @throws InvalidErrorException If the method name is invalid.
     */
    public function fromArray(array $request): Request|BatchRequest
    {
        if (array_key_exists(0, $request)) {
            return $this->createBatchRequest($request);
        }

        $method = $request['method'] ?? null;
        if (!$method) {
            throw ErrorException::fromErrorCode(ErrorCodes::INVALID_REQUEST);
        }
        $id = $request['id'] ?? null;
        $params = $request['params'] ?? [];

        return new Request($method, $params, $id);
    }

    /**
     * Creates a BatchRequest object from an array of requests.
     *
     * @param array $request An array of associative arrays where each item represents a single request.
     *                        Each request should contain the necessary keys to create a valid Request object.
     *
     * @return BatchRequest Returns an initialized BatchRequest containing the processed requests.
     *
     * @throws InvalidBatchElementException If the body of the request contains invalid JSON, that cannot be parsed.
     * @throws InvalidMethodNameException|InvalidErrorException If the method name is invalid.
     */
    private function createBatchRequest(array $request): BatchRequest
    {
        $batch = new BatchRequest();
        foreach ($request as $item) {
            try {
                $batch->append($this->fromArray($item));
            } catch (ErrorException $exception) {
                $batch->append($exception->toError());
            }
        }

        return $batch;
    }
}
