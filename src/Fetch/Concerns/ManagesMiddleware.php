<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Http\MiddlewarePipeline;
use Fetch\Interfaces\Middleware;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;

/**
 * Adds a middleware pipeline to a client handler.
 *
 * Middleware are stored with a priority and an insertion sequence so the stack
 * has a stable, predictable order: higher priority runs first (outermost), and
 * ties break by insertion order. The trait also provides fluent conditional
 * configuration (`when`/`unless`) in the spirit of Laravel's Conditionable.
 *
 * @phpstan-import-type CoreHandler from MiddlewarePipeline
 */
trait ManagesMiddleware
{
    /**
     * Stores middleware with its priority for ordered request execution.
     *
     * @var array<int, array{middleware: Middleware|callable, priority: int,
     *     seq: int}>
     */
    protected array $middlewareStack = [];

    /**
     * Monotonic counter used to keep equal-priority middleware stably ordered.
     */
    protected int $middlewareSequence = 0;

    /**
     * Append a middleware to the stack.
     *
     * @return $this
     */
    public function addMiddleware(
        Middleware|callable $middleware,
        int $priority = 0,
    ): static {
        $this->middlewareStack[] = [
            'middleware' => $middleware,
            'priority' => $priority,
            'seq' => $this->middlewareSequence++,
        ];

        return $this;
    }

    /**
     * Replace the entire middleware stack.
     *
     * @param  array<int, Middleware|callable|array{
     *     0: Middleware|callable,
     *     1?: int
     * }>  $middleware
     * @return $this
     */
    public function middleware(array $middleware): static
    {
        $this->middlewareStack = [];
        $this->middlewareSequence = 0;

        foreach ($middleware as $entry) {
            if ($entry instanceof Middleware || is_callable($entry)) {
                $this->addMiddleware($entry);

                continue;
            }

            if (! is_array($entry) || ! array_key_exists(0, $entry)) {
                throw new InvalidArgumentException(
                    'Middleware entries must be middleware, callables, or '
                        .'[middleware, priority] pairs.',
                );
            }

            $entryMiddleware = $entry[0];
            $priority = $entry[1] ?? 0;

            if (
                (! $entryMiddleware instanceof Middleware &&
                    ! is_callable($entryMiddleware)) ||
                ! is_int($priority)
            ) {
                throw new InvalidArgumentException(
                    'Middleware pairs require a middleware or callable and '
                        .'an integer priority.',
                );
            }

            $this->addMiddleware($entryMiddleware, $priority);
        }

        return $this;
    }

    /**
     * Remove middleware from the stack.
     *
     * With no argument, clears the stack. With a class-string, removes every
     * middleware that is an instance of that class.
     *
     * @param  class-string|null  $class
     * @return $this
     */
    public function withoutMiddleware(?string $class = null): static
    {
        if ($class === null) {
            $this->middlewareStack = [];
            $this->middlewareSequence = 0;

            return $this;
        }

        $this->middlewareStack = array_values(
            array_filter(
                $this->middlewareStack,
                fn (array $entry): bool => ! (
                    $entry['middleware'] instanceof $class
                ),
            ),
        );

        return $this;
    }

    /**
     * Whether any middleware is registered.
     */
    public function hasMiddleware(): bool
    {
        return $this->middlewareStack !== [];
    }

    /**
     * Get the resolved middleware stack in execution order (outermost first).
     *
     * @return array<int, Middleware|callable>
     */
    public function getMiddleware(): array
    {
        return $this->resolveMiddleware();
    }

    /**
     * Apply the given callback to the handler when the condition is truthy.
     *
     * @param  mixed  $condition  A boolean, or a callable resolved with the
     *                            handler.
     * @param  callable(static, mixed): mixed  $callback
     * @param  callable(static, mixed): mixed|null  $default
     * @return $this
     */
    public function when(
        mixed $condition,
        callable $callback,
        ?callable $default = null,
    ): static {
        $value = is_callable($condition) ? $condition($this) : $condition;

        if ($value) {
            $callback($this, $value);
        } elseif ($default !== null) {
            $default($this, $value);
        }

        return $this;
    }

    /**
     * Apply the given callback to the handler when the condition is falsy.
     *
     * @param  mixed  $condition  A boolean, or a callable resolved with the
     *                            handler.
     * @param  callable(static, mixed): mixed  $callback
     * @param  callable(static, mixed): mixed|null  $default
     * @return $this
     */
    public function unless(
        mixed $condition,
        callable $callback,
        ?callable $default = null,
    ): static {
        $value = is_callable($condition) ? $condition($this) : $condition;

        if (! $value) {
            $callback($this, $value);
        } elseif ($default !== null) {
            $default($this, $value);
        }

        return $this;
    }

    /**
     * Resolve the stack into an ordered list of middleware.
     *
     * @return array<int, Middleware|callable>
     */
    protected function resolveMiddleware(): array
    {
        if ($this->middlewareStack === []) {
            return [];
        }

        $entries = $this->middlewareStack;

        usort(
            $entries,
            fn (array $a, array $b): int => $b['priority'] <=> $a['priority'] ?:
            $a['seq'] <=> $b['seq'],
        );

        return array_map(
            static fn (array $entry) => $entry['middleware'],
            $entries,
        );
    }

    /**
     * Run a request through the middleware pipeline around the given core
     * handler.
     *
     * @param  CoreHandler  $core
     */
    protected function runThroughMiddleware(
        RequestInterface $request,
        callable $core,
        ?callable $onRequest = null,
    ): ResponseInterface|PromiseInterface {
        $resolved = $this->resolveMiddleware();

        if ($resolved === []) {
            return $core($request);
        }

        return (new MiddlewarePipeline($resolved))->handle(
            $request,
            $core,
            $onRequest,
        );
    }
}
