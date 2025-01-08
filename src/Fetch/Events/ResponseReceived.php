<?php

declare(strict_types=1);

namespace Fetch\Events;

use Fetch\Request;
use Fetch\Response;

class ResponseReceived
{
    /**
     * The request instance.
     */
    public Request $request;

    /**
     * The response instance.
     */
    public Response $response;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
