<?php

declare(strict_types=1);

namespace Fetch\Events;

use Fetch\Exceptions\ConnectionException;
use Fetch\Request;

class ConnectionFailed
{
    /**
     * The request instance.
     */
    public Request $request;

    /**
     * The exception instance.
     */
    public ConnectionException $exception;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Request $request, ConnectionException $exception)
    {
        $this->request = $request;
        $this->exception = $exception;
    }
}
