<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

interface MiddlewareAware
{
    /**
     * Append a middleware to the stack.
     *
     * Higher-priority middleware run first (outermost). Middleware with equal
     * priority run in the order they were added.
     *
     * @param  Middleware|callable(\Psr\Http\Message\RequestInterface, callable): mixed  $middleware
     */
    public function addMiddleware(Middleware|callable $middleware, int $priority = 0): self;

    /**
     * Replace the entire middleware stack.
     *
     * Each entry is either a middleware (object or callable) or a
     * `[$middleware, $priority]` pair.
     *
     * @param  array<int, Middleware|callable|array{0: Middleware|callable, 1?: int}>  $middleware
     */
    public function middleware(array $middleware): self;

    /**
     * Remove middleware from the stack.
     *
     * With no argument, clears the stack. With a class-string, removes every
     * middleware that is an instance of that class.
     *
     * @param  class-string|null  $class
     */
    public function withoutMiddleware(?string $class = null): self;

    /**
     * Get the resolved middleware stack in execution order (outermost first).
     *
     * @return array<int, Middleware|callable>
     */
    public function getMiddleware(): array;

    /**
     * Apply the given callback to the handler when the condition is truthy.
     *
     * @param  mixed  $condition  A boolean, or a callable resolved with the handler.
     * @param  callable(self, mixed): mixed  $callback
     * @param  callable(self, mixed): mixed|null  $default
     */
    public function when(mixed $condition, callable $callback, ?callable $default = null): self;

    /**
     * Apply the given callback to the handler when the condition is falsy.
     *
     * @param  mixed  $condition  A boolean, or a callable resolved with the handler.
     * @param  callable(self, mixed): mixed  $callback
     * @param  callable(self, mixed): mixed|null  $default
     */
    public function unless(mixed $condition, callable $callback, ?callable $default = null): self;
}
