<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;

/**
 * A request/response middleware in the Fetch PHP pipeline.
 *
 * Middleware wrap the request lifecycle in an onion: each one receives the
 * outgoing PSR-7 request and a `$next` callable representing the rest of the
 * pipeline (inner middleware plus the core request dispatch). A middleware may:
 *
 * - inspect or modify the request before calling `$next($request)`;
 * - short-circuit by returning a response without calling `$next`;
 * - inspect or modify the response returned by `$next`;
 * - catch and handle errors thrown further down the pipeline.
 *
 * In synchronous mode `$next` returns a {@see ResponseInterface}. In async mode
 * it returns a {@see PromiseInterface}; middleware that need the response must
 * chain off the promise (e.g. `->then(...)`) rather than inspecting it directly.
 *
 * Plain callables with the signature `fn(RequestInterface $request, callable $next)`
 * are also accepted anywhere a Middleware is, so trivial middleware need not
 * declare a class.
 */
interface Middleware
{
    /**
     * Handle the request and produce a response (or a promise of one).
     *
     * @param  RequestInterface  $request  The outgoing request.
     * @param  callable(RequestInterface): (ResponseInterface|PromiseInterface)  $next  The rest of the pipeline.
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface|PromiseInterface;
}
