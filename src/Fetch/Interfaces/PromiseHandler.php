<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use React\Promise\PromiseInterface;

/**
 * Controls async request mode and composes ReactPHP promises.
 */
interface PromiseHandler
{
    /**
     * Enable or disable asynchronous request execution.
     */
    public function async(?bool $async = true): self;

    /**
     * Report whether asynchronous request execution is enabled.
     */
    public function isAsync(): bool;

    /**
     * Runs a callback in a promise and converts thrown errors to rejection.
     *
     * @return PromiseInterface<mixed>
     */
    public function wrapAsync(callable $callable): PromiseInterface;

    /**
     * Waits for a promise and returns its resolved value or throws its error.
     *
     * @param  PromiseInterface<mixed>  $promise
     */
    public function awaitPromise(
        PromiseInterface $promise,
        ?float $timeout = null,
    ): mixed;

    /**
     * Waits for all promises and preserves the input keys in the results.
     *
     * @param  array<PromiseInterface<mixed>>  $promises
     * @return PromiseInterface<array<mixed>>
     */
    public function all(array $promises): PromiseInterface;

    /**
     * Adopts the outcome of the first promise to settle.
     *
     * @param  array<PromiseInterface<mixed>>  $promises
     * @return PromiseInterface<mixed>
     */
    public function race(array $promises): PromiseInterface;

    /**
     * Returns the first fulfilled promise or rejects when all fail.
     *
     * @param  array<PromiseInterface<mixed>>  $promises
     * @return PromiseInterface<mixed>
     */
    public function any(array $promises): PromiseInterface;

    /**
     * Runs promise-producing callbacks in order, awaiting each result.
     *
     * @param  array<callable(): PromiseInterface<mixed>>  $callables
     * @return PromiseInterface<array<mixed>>
     */
    public function sequence(array $callables): PromiseInterface;

    /**
     * Create an already fulfilled promise for the supplied value.
     */
    public function resolve(mixed $value): PromiseInterface;

    /**
     * Create a rejected promise with the supplied failure reason.
     */
    public function reject(mixed $reason): PromiseInterface;

    /**
     * Applies the asynchronous mapper to each item and preserves its key.
     *
     * @param  array<mixed>  $items
     */
    public function map(
        array $items,
        callable $callback,
        int $concurrency = 5,
    ): PromiseInterface;

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
    public function then(
        callable $onFulfilled,
        ?callable $onRejected = null,
    ): PromiseInterface;

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
     * Add a callback to be executed when the promise settles (fulfilled or
     *     rejected).
     *
     * This method sets the handler to async mode and sends the request,
     * returning a promise with the finally callback.
     *
     * @param  callable  $onFinally  Callback for completion
     * @return PromiseInterface The promise with attached finally callback
     */
    public function finally(callable $onFinally): PromiseInterface;
}
