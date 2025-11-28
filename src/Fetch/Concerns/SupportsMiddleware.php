<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Http\MiddlewarePipeline;
use Fetch\Interfaces\ClientHandler;
use Fetch\Interfaces\MiddlewareInterface;

/**
 * Trait for adding middleware support to HTTP clients.
 *
 * This trait provides methods for managing and executing middleware
 * that can transform requests and responses.
 */
trait SupportsMiddleware
{
    /**
     * The middleware pipeline instance.
     */
    protected ?MiddlewarePipeline $middlewarePipeline = null;

    /**
     * Set multiple middleware at once.
     *
     * @param  array<MiddlewareInterface|array{middleware: MiddlewareInterface, priority: int}>  $middleware
     * @return $this
     */
    public function middleware(array $middleware): ClientHandler
    {
        $this->middlewarePipeline = new MiddlewarePipeline($middleware);

        return $this;
    }

    /**
     * Add a single middleware to the pipeline.
     *
     * @param  MiddlewareInterface  $middleware  The middleware to add
     * @param  int  $priority  Higher priority middleware runs first (default: 0)
     * @return $this
     */
    public function addMiddleware(MiddlewareInterface $middleware, int $priority = 0): ClientHandler
    {
        $this->getMiddlewarePipeline()->add($middleware, $priority);

        return $this;
    }

    /**
     * Prepend middleware to run first (with highest priority).
     *
     * @param  MiddlewareInterface  $middleware  The middleware to prepend
     * @return $this
     */
    public function prependMiddleware(MiddlewareInterface $middleware): ClientHandler
    {
        $this->getMiddlewarePipeline()->prepend($middleware);

        return $this;
    }

    /**
     * Get the middleware pipeline instance.
     */
    public function getMiddlewarePipeline(): MiddlewarePipeline
    {
        if ($this->middlewarePipeline === null) {
            $this->middlewarePipeline = new MiddlewarePipeline;
        }

        return $this->middlewarePipeline;
    }

    /**
     * Check if any middleware is registered.
     */
    public function hasMiddleware(): bool
    {
        return $this->middlewarePipeline !== null && ! $this->middlewarePipeline->isEmpty();
    }

    /**
     * Clear all middleware from the pipeline.
     *
     * @return $this
     */
    public function clearMiddleware(): ClientHandler
    {
        if ($this->middlewarePipeline !== null) {
            $this->middlewarePipeline->clear();
        }

        return $this;
    }

    /**
     * Conditionally add middleware.
     *
     * @param  bool  $condition  The condition to check
     * @param  callable  $callback  Callback that receives $this and should add middleware
     * @return $this
     */
    public function when(bool $condition, callable $callback): ClientHandler
    {
        if ($condition) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Conditionally add middleware (inverse of when).
     *
     * @param  bool  $condition  The condition to check
     * @param  callable  $callback  Callback that receives $this and should add middleware
     * @return $this
     */
    public function unless(bool $condition, callable $callback): ClientHandler
    {
        return $this->when(! $condition, $callback);
    }
}
