<?php

namespace Alcedo\JsonRpc\Server\Factory;

use Alcedo\JsonRpc\Server\DTO\BatchRequest;
use Alcedo\JsonRpc\Server\DTO\Error;
use Alcedo\JsonRpc\Server\DTO\ErrorCodes;
use Alcedo\JsonRpc\Server\DTO\JsonRpcMessageInterface;
use Alcedo\JsonRpc\Server\DTO\Request;
use Alcedo\JsonRpc\Server\DTO\Response;
use Alcedo\JsonRpc\Server\Exception\ErrorException;
use Alcedo\JsonRpc\Server\Exception\InvalidBatchElementException;
use Alcedo\JsonRpc\Server\Exception\InvalidErrorException;
use Alcedo\JsonRpc\Server\Exception\InvalidMethodNameException;
use Alcedo\JsonRpc\Server\Exception\InvalidResponseException;
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
     * @return JsonRpcMessageInterface|BatchRequest Returns a Request object if the body of the request represents
     *                            a single request, or a BatchRequest object if the body represents a batch of requests.
     *
     * @throws ErrorException If the request body is empty or the method is not provided.
     * @throws ErrorException If the request body contains invalid JSON, that cannot be parsed.
     * @throws ErrorException If the body of the request contains invalid JSON, that cannot be parsed.
     * @throws InvalidBatchElementException If the body of the request contains invalid JSON, that cannot be parsed.
     * @throws InvalidMethodNameException|InvalidErrorException If the method name is invalid.
     */
    public function fromServerRequest(RequestInterface $request): JsonRpcMessageInterface|BatchRequest
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException | ValueError $exception) {
            return new Response(error: new Error(
                ErrorCodes::PARSE_ERROR->value,
                originalException: $exception
            ));
        }
        if (array_key_exists(0, $body)) {
            return $this->createBatchRequest($body);
        }

        return $this->fromArray($body);
    }

    /**
     * Creates a new Request object from the given array.
     *
     * @param array $request An associative array containing the request data for a batch or single request.
     *                        Expected keys for a single request are 'method', 'id' (optional), and 'params' (optional).
     *
     * @return Request|BatchRequest Returns an initialized Request object.
     *
     * @throws InvalidBatchElementException If the body of the request contains invalid JSON, that cannot be parsed.
     * @throws InvalidErrorException If the error code is invalid.
     * @throws InvalidResponseException If the response contains both a result and an error.
     * @throws InvalidMethodNameException
     * @throws ErrorException
     */
    public function fromArray(array $request): JsonRpcMessageInterface|BatchRequest
    {
        if (array_key_exists(0, $request)) {
            return $this->createBatchRequest($request);
        }
        $id = $request['id'] ?? null;
        try {
            if (!array_key_exists('jsonrpc', $request) || $request['jsonrpc'] !== Request::VERSION) {
                throw ErrorException::fromErrorCode(ErrorCodes::INVALID_REQUEST);
            }

            $method = $request['method'] ?? null;
            if (!$method) {
                throw ErrorException::fromErrorCode(ErrorCodes::INVALID_REQUEST);
            }
            $params = $request['params'] ?? [];
            $request = new Request($method, $params, $id);
        } catch (InvalidMethodNameException $exception) {
            $request = new Response(
                error: new Error(ErrorCodes::INVALID_REQUEST->value, message: $exception->getMessage()),
                id: $id
            );
        } catch (ErrorException $exception) {
            $request = new Response(error: $exception->toError()->setOriginalException($exception), id: $id);
        }

        return $request;
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
     * @throws InvalidErrorException If the error code is invalid.
     * @throws InvalidResponseException If the response contains both a result and an error.
     * @throws InvalidMethodNameException
     * @throws ErrorException
     */
    private function createBatchRequest(array $request): BatchRequest
    {
        $batch = new BatchRequest();
        foreach ($request as $item) {
            if (array_key_exists(0, $item)) {
                $result = new Response(error: ErrorFactory::invalidRequest(data: $item));
                if (array_key_exists('id', $item)) {
                    $result->setId($item['id']);
                }
            } else {
                $result = $this->fromArray($item);
            }
            $batch->append($result);
        }

        return $batch;
    }
}
