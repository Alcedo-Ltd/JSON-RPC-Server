<?php

namespace Alcedo\JsonRpc\Server;

use Alcedo\JsonRpc\Server\DTO\BatchRequest;
use Alcedo\JsonRpc\Server\DTO\BatchResponse;
use Alcedo\JsonRpc\Server\DTO\Error;
use Alcedo\JsonRpc\Server\DTO\Request;
use Alcedo\JsonRpc\Server\DTO\Response;
use Alcedo\JsonRpc\Server\Exception\ErrorException;
use Alcedo\JsonRpc\Server\Exception\InvalidBatchElementException;
use Alcedo\JsonRpc\Server\Exception\InvalidErrorException;
use Alcedo\JsonRpc\Server\Exception\InvalidMethodNameException;
use Alcedo\JsonRpc\Server\Exception\InvalidResponseException;
use Alcedo\JsonRpc\Server\Factory\ErrorFactory;
use Alcedo\JsonRpc\Server\Factory\RequestFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Throwable;
use TypeError;

/**
 * A readonly class implementing a JSON-RPC 2.0 server. Handles the execution of JSON-RPC requests,
 * including single requests and batch requests, and produces corresponding JSON-RPC responses.
 */
readonly class Server
{
    /**
     * Constructor method.
     *
     * @param RequestFactory $requestFactory A factory to create request instances.
     * @param ContainerInterface $procedures A container that holds procedures.
     *
     * @return void
     */
    public function __construct(
        private RequestFactory $requestFactory,
        private ContainerInterface $procedures
    ) {
        // ...
    }

    /**
     * Executes a PSR-7 compatible request and returns a response.
     *
     * @param RequestInterface $request The PSR-7 request to be processed.
     *
     * @return Response|BatchResponse The processed response.
     *
     * @throws ContainerExceptionInterface If the procedure is not found in the container.
     * @throws InvalidErrorException If the procedure is not callable or an error occurs during execution.
     * @throws InvalidResponseException If both result and error are set in the response.
     * @throws InvalidBatchElementException
     * @throws InvalidMethodNameException
     * @throws ErrorException
     */
    public function executePsrRequest(RequestInterface $request): Response|BatchResponse
    {
        $rpcRequest = $this->requestFactory->fromServerRequest($request);

        return $this->execute($rpcRequest);
    }

    /**
     * Executes an array-based request and returns a response.
     *
     * @param array $request The request data in array format.
     *
     * @return Response|BatchResponse The response generated from processing the request.
     *
     * @throws ContainerExceptionInterface If the procedure is not found in the container.
     * @throws InvalidErrorException If the procedure is not callable or an error occurs during execution.
     * @throws InvalidResponseException If both result and error are set in the response.
     * @throws InvalidBatchElementException
     * @throws InvalidMethodNameException
     * @throws ErrorException
     */
    public function executeArrayRequest(array $request): Response|BatchResponse
    {
        $rpcRequest = $this->requestFactory->fromArray($request);

        return $this->execute($rpcRequest);
    }

    /**
     * Executes the given request or batch request and processes the response.
     *
     * @param Request|BatchRequest $request The request or batch request to be executed.
     *
     * @return Response|BatchResponse Returns a response, batch response.
     *
     * @throws ContainerExceptionInterface If the procedure is not found in the container.
     * @throws InvalidErrorException If the procedure is not callable or an error occurs during execution.
     * @throws InvalidResponseException If both result and error are set in the response.
     * @throws InvalidBatchElementException
     */
    public function execute(Request|BatchRequest $request): Response|BatchResponse
    {
        if ($request instanceof BatchRequest) {
            return $this->processBatchRequests($request);
        }

        return $this->processRequest($request);
    }

    /**
     * Processes a batch of requests and generates a batch response.
     *
     * @param BatchRequest $request The batch request containing multiple items to process.
     *
     * @return BatchResponse The resulting batch response after processing all items.
     *
     * @throws ContainerExceptionInterface If the procedure is not found in the container.
     * @throws InvalidErrorException If the procedure is not callable or an error occurs during execution.
     * @throws InvalidResponseException If both result and error are set in the response.
     * @throws InvalidBatchElementException
     */
    private function processBatchRequests(BatchRequest $request): BatchResponse
    {
        $batchResponse = new BatchResponse();
        foreach ($request as $item) {
            /** @var Request|Error $item */
            if ($item instanceof Request) {
                $response = $this->processRequest($item);
                if (!$item->isNotification()) {
                    $batchResponse->append($response);
                }
            } else {
                $batchResponse->append($item);
            }
        }

        return $batchResponse;
    }

    /**
     * Processes an incoming request and returns an appropriate response.
     *
     * @param Request $request The request object containing the method, parameters, and identifier.
     *
     * @return Response A response object containing the result or an error, along with the request identifier.
     *
     * @throws ContainerExceptionInterface If the procedure is not found in the container.
     * @throws InvalidErrorException If the procedure is not callable or an error occurs during execution.
     * @throws InvalidResponseException If both result and error are set in the response.
     */
    private function processRequest(Request $request): Response
    {
        $id = $request->id();
        $method = $request->method();
        $params = $request->params();
        if (!$this->procedures->has($method)) {
            return new Response(
                error: ErrorFactory::methodNotFound(data: ['method' => $method]),
                id: $id,
                request: $request,
            );
        }
        $procedure = $this->procedures->get($method);
        if ($procedure instanceof RemoteProcedureInterface) {
            $response = $procedure->call(...$params);
        } else {
            try {
                $response = $this->processCallableProcedure($procedure, $method, $params, $id);
            } catch (TypeError $exception) {
                $error = ErrorFactory::serverError(message: 'Procedure is not callable', data: ['method' => $method]);
                $error->setOriginalException($exception);
                return new Response(error: $error, id: $id);
            }
        }
        $response->for($request)->setId($id);

        return $response;
    }

    /**
     * Executes a callable procedure with the provided parameters and handles the response or errors.
     *
     * @param callable $procedure The procedure to be executed.
     * @param string $method The name of the method being invoked, used for error context.
     * @param array $params The parameters to pass to the callable procedure.
     * @param int|null $id The optional identifier for the response, used for associating errors or results.
     *
     * @return Response Returns a Response object containing the result of the procedure or an error.
     *
     * @throws InvalidErrorException If an error occurs during the execution of the procedure.
     * @throws InvalidResponseException If both result and error are set in the response.
     */
    private function processCallableProcedure(callable $procedure, string $method, array $params, ?int $id): Response
    {
        try {
            $result = call_user_func_array($procedure, $params);
            $response = new Response(result: $result);
        } catch (Throwable $exception) {
            $error = ErrorFactory::internalError(data: ['method' => $method, 'params' => $params]);
            $error->setOriginalException($exception);
            $response =  new Response(error: $error, id: $id);
        }

        return $response;
    }
}
