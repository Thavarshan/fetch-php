<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use React\Promise\PromiseInterface;

interface PromiseHandler
{
    public function async(?bool $async = true): self;

    public function isAsync(): bool;

    /**
     * @return PromiseInterface<mixed>
     */
    public function wrapAsync(callable $callable): PromiseInterface;

    /**
     * @param  PromiseInterface<mixed>  $promise
     */
    public function awaitPromise(PromiseInterface $promise, ?float $timeout = null): mixed;

    /**
     * @param  array<PromiseInterface<mixed>>  $promises
     * @return PromiseInterface<array<mixed>>
     */
    public function all(array $promises): PromiseInterface;

    /**
     * @param  array<PromiseInterface<mixed>>  $promises
     * @return PromiseInterface<mixed>
     */
    public function race(array $promises): PromiseInterface;

    /**
     * @param  array<PromiseInterface<mixed>>  $promises
     * @return PromiseInterface<mixed>
     */
    public function any(array $promises): PromiseInterface;

    /**
     * @param  array<callable(): PromiseInterface<mixed>>  $callables
     * @return PromiseInterface<array<mixed>>
     */
    public function sequence(array $callables): PromiseInterface;

    public function resolve(mixed $value): PromiseInterface;

    public function reject(mixed $reason): PromiseInterface;

    /**
     * @param  array<mixed>  $items
     */
    public function map(array $items, callable $callback, int $concurrency = 5): PromiseInterface;

    /**
     * Add a callback to be executed when the promise resolves.
     *
     * This method sets the handler to async mode and sends the request,
     * returning a promise with the attached callbacks.
     *
     * @param  callable  $onFulfilled  Callback for success
     * @param  callable|null  $onRejected  Callback for rejection
     * @return PromiseInterface The promise with attached callbacks
     */
    public function then(callable $onFulfilled, ?callable $onRejected = null): PromiseInterface;

    /**
     * Add a callback to be executed when the promise is rejected.
     *
     * This method sets the handler to async mode and sends the request,
     * returning a promise with the rejection callback.
     *
     * @param  callable  $onRejected  Callback for rejection
     * @return PromiseInterface The promise with attached rejection callback
     */
    public function catch(callable $onRejected): PromiseInterface;

    /**
     * Add a callback to be executed when the promise settles (fulfilled or rejected).
     *
     * This method sets the handler to async mode and sends the request,
     * returning a promise with the finally callback.
     *
     * @param  callable  $onFinally  Callback for completion
     * @return PromiseInterface The promise with attached finally callback
     */
    public function finally(callable $onFinally): PromiseInterface;
}
