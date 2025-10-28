<?php

namespace Alcedo\JsonRpc\Server;

use Alcedo\JsonRpc\Server\DTO\Response;

/**
 * Defines an interface for remote procedure calls.
 */
interface RemoteProcedureInterface
{
    /**
     * Executes the call and returns the response.
     *
     * @return Response The response object resulting from the call.
     */
    public function call(): Response;
}
