<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Interfaces\MiddlewareInterface;
use Fetch\Interfaces\Response as ResponseInterface;
use Psr\Http\Message\RequestInterface;
use React\Promise\PromiseInterface;

/**
 * Manages the execution of middleware in a pipeline pattern.
 *
 * The pipeline executes middleware in order, where each middleware can:
 * - Modify the request before passing to the next handler
 * - Modify the response after receiving from the next handler
 * - Short-circuit the pipeline by returning early
 */
class MiddlewarePipeline
{
    /**
     * The middleware stack.
     *
     * @var array<array{middleware: MiddlewareInterface, priority: int}>
     */
    protected array $middleware = [];

    /**
     * Create a new middleware pipeline.
     *
     * @param  array<MiddlewareInterface|array{middleware: MiddlewareInterface, priority: int}>  $middleware
     */
    public function __construct(array $middleware = [])
    {
        foreach ($middleware as $item) {
            if ($item instanceof MiddlewareInterface) {
                $this->middleware[] = ['middleware' => $item, 'priority' => 0];
            } else {
                $this->middleware[] = $item;
            }
        }

        $this->sortMiddleware();
    }

    /**
     * Add middleware to the pipeline.
     *
     * @param  MiddlewareInterface  $middleware  The middleware to add
     * @param  int  $priority  Higher priority middleware runs first (default: 0)
     * @return $this
     */
    public function add(MiddlewareInterface $middleware, int $priority = 0): self
    {
        $this->middleware[] = ['middleware' => $middleware, 'priority' => $priority];
        $this->sortMiddleware();

        return $this;
    }

    /**
     * Prepend middleware to run first (with highest priority).
     *
     * @param  MiddlewareInterface  $middleware  The middleware to prepend
     * @return $this
     */
    public function prepend(MiddlewareInterface $middleware): self
    {
        $maxPriority = 0;
        foreach ($this->middleware as $item) {
            if ($item['priority'] > $maxPriority) {
                $maxPriority = $item['priority'];
            }
        }

        return $this->add($middleware, $maxPriority + 1);
    }

    /**
     * Process the request through the middleware pipeline.
     *
     * @param  RequestInterface  $request  The request to process
     * @param  callable  $coreHandler  The final handler (typically the HTTP client)
     * @return ResponseInterface|PromiseInterface<ResponseInterface> The response or promise
     */
    public function handle(RequestInterface $request, callable $coreHandler): ResponseInterface|PromiseInterface
    {
        if (empty($this->middleware)) {
            return $coreHandler($request);
        }

        $pipeline = $this->buildPipeline($coreHandler);

        return $pipeline($request);
    }

    /**
     * Get the current middleware stack.
     *
     * @return array<array{middleware: MiddlewareInterface, priority: int}>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Check if the pipeline has any middleware.
     */
    public function isEmpty(): bool
    {
        return empty($this->middleware);
    }

    /**
     * Get the number of middleware in the pipeline.
     */
    public function count(): int
    {
        return count($this->middleware);
    }

    /**
     * Clear all middleware from the pipeline.
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->middleware = [];

        return $this;
    }

    /**
     * Build the middleware pipeline as a single callable.
     *
     * @param  callable  $coreHandler  The final handler
     * @return callable The composed pipeline
     */
    protected function buildPipeline(callable $coreHandler): callable
    {
        // Build the pipeline from the inside out (reverse order)
        // So the first middleware in the array runs first
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            function (callable $carry, array $item): callable {
                /** @var MiddlewareInterface $middleware */
                $middleware = $item['middleware'];

                return function (RequestInterface $request) use ($carry, $middleware): ResponseInterface|PromiseInterface {
                    return $middleware->handle($request, $carry);
                };
            },
            $coreHandler
        );

        return $pipeline;
    }

    /**
     * Sort middleware by priority (highest first).
     */
    protected function sortMiddleware(): void
    {
        usort($this->middleware, function (array $a, array $b): int {
            return $b['priority'] <=> $a['priority'];
        });
    }
}
