<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Interfaces\Middleware;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;

/**
 * Composes an ordered list of middleware into a single callable pipeline.
 *
 * The list is treated as outermost-first: the first middleware wraps the
 * second, which wraps the third, and so on, with the core request handler at
 * the centre. Both {@see Middleware} instances and plain callables with the
 * signature `fn(RequestInterface $request, callable $next)` are supported.
 *
 * The pipeline is transport-agnostic: whatever the core handler returns (a
 * {@see ResponseInterface} synchronously, or a {@see PromiseInterface} in async
 * mode) flows back out through the middleware unchanged.
 */
final class MiddlewarePipeline
{
    /**
     * @param  array<int, Middleware|callable>  $middleware  Ordered outermost-first.
     */
    public function __construct(
        private readonly array $middleware,
    ) {}

    /**
     * Run the request through the middleware stack and core handler.
     *
     * @param  callable(RequestInterface): (ResponseInterface|PromiseInterface)  $core
     */
    public function handle(RequestInterface $request, callable $core): ResponseInterface|PromiseInterface
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            fn (callable $next, Middleware|callable $middleware): callable => fn (RequestInterface $req): ResponseInterface|PromiseInterface => $this->call($middleware, $req, $next),
            $core,
        );

        return $pipeline($request);
    }

    /**
     * Invoke a single middleware entry.
     *
     * @param  callable(RequestInterface): (ResponseInterface|PromiseInterface)  $next
     */
    private function call(Middleware|callable $middleware, RequestInterface $request, callable $next): ResponseInterface|PromiseInterface
    {
        if ($middleware instanceof Middleware) {
            return $middleware->handle($request, $next);
        }

        $result = $middleware($request, $next);

        if (! $result instanceof ResponseInterface && ! $result instanceof PromiseInterface) {
            throw new InvalidArgumentException(sprintf(
                'Callable middleware must return a %s or %s, got %s.',
                ResponseInterface::class,
                PromiseInterface::class,
                get_debug_type($result),
            ));
        }

        return $result;
    }
}
