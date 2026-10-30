<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use React\Promise\PromiseInterface;

/**
 * Sends a request through an asynchronous transport implementation.
 */
interface AsyncTransport
{
    /**
     * Start a request and resolve with its response or reject on failure.
     *
     * @param  array<string, mixed>  $options
     * @return PromiseInterface<\Psr\Http\Message\ResponseInterface>
     */
    public function requestAsync(
        string $method,
        string $uri,
        array $options,
    ): PromiseInterface;
}
