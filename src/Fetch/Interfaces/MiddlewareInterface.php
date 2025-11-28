<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Psr\Http\Message\RequestInterface;
use React\Promise\PromiseInterface;

/**
 * Interface for HTTP middleware that can transform requests and responses.
 *
 * Middleware follows the chain of responsibility pattern, where each middleware
 * can process the request, call the next handler, and process the response.
 */
interface MiddlewareInterface
{
    /**
     * Process the request and return a response.
     *
     * @param  RequestInterface  $request  The HTTP request to process
     * @param  callable  $next  The next middleware or final handler
     * @return Response|PromiseInterface<Response> The response or promise
     */
    public function handle(RequestInterface $request, callable $next): Response|PromiseInterface;
}
