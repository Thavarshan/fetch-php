<?php

declare(strict_types=1);

namespace Fetch\Events;

use Fetch\Request;

class RequestSending
{
    /**
     * The request instance.
     */
    public Request $request;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
