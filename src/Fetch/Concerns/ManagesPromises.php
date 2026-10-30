<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Async\PromiseManager;
use Fetch\Interfaces\ClientHandler;
use React\Promise\PromiseInterface;

/**
 * Compatibility shim for consumers that composed this trait directly.
 *
 * @deprecated Use Fetch\Async\PromiseManager in new integrations. ClientHandler
 *     no longer uses this trait.
 */
trait ManagesPromises
{
    /**
     * Maximum per-item concurrency accepted by the legacy mapping API.
     */
    private const MAX_MAP_CONCURRENCY = 200;

    /**
     * Whether the request should be asynchronous or not.
     */
    protected bool $isAsync = false;

    /**
     * Lazily created delegate retained for direct trait consumers.
     */
    private ?PromiseManager $legacyPromiseManager = null;

    /**
     * Toggle asynchronous request execution for the consuming handler.
     */
    public function async(?bool $async = true): ClientHandler
    {
        $this->isAsync = $async ?? true;

        return $this;
    }

    /**
     * Report whether asynchronous request execution is enabled.
     */
    public function isAsync(): bool
    {
        return $this->isAsync;
    }

    /**
     * Wrap a callable in a Matrix-managed asynchronous operation.
     */
    public function wrapAsync(callable $callable): PromiseInterface
    {
        return $this->promiseManager()->wrapAsync($callable);
    }

    /**
     * Await a promise, optionally limiting the wait in seconds.
     */
    public function awaitPromise(
        PromiseInterface $promise,
        ?float $timeout = null,
    ): mixed {
        return $this->promiseManager()->awaitPromise($promise, $timeout);
    }

    /**
     * Waits for all promises and preserves the input keys in the results.
     *
     * @param  array<PromiseInterface<mixed>>  $promises
     */
    public function all(array $promises): PromiseInterface
    {
        return $this->promiseManager()->all($promises);
    }

    /**
     * Adopts the outcome of the first promise to settle.
     *
     * @param  array<PromiseInterface<mixed>>  $promises
     */
    public function race(array $promises): PromiseInterface
    {
        return $this->promiseManager()->race($promises);
    }

    /**
     * Returns the first fulfilled promise or rejects when all fail.
     *
     * @param  array<PromiseInterface<mixed>>  $promises
     */
    public function any(array $promises): PromiseInterface
    {
        return $this->promiseManager()->any($promises);
    }

    /**
     * Runs promise-producing callbacks in order, awaiting each result.
     *
     * @param  array<callable(): PromiseInterface<mixed>>  $callables
     */
    public function sequence(array $callables): PromiseInterface
    {
        return $this->promiseManager()->sequence($callables);
    }

    /**
     * Send this request asynchronously and attach fulfillment handlers.
     */
    public function then(
        callable $onFulfilled,
        ?callable $onRejected = null,
    ): PromiseInterface {
        $this->async();

        return $this->sendAsync()->then($onFulfilled, $onRejected);
    }

    /**
     * Send this request asynchronously and attach a rejection handler.
     */
    public function catch(callable $onRejected): PromiseInterface
    {
        $this->async();

        return $this->sendAsync()->catch($onRejected);
    }

    /**
     * Send this request asynchronously and attach a settlement handler.
     */
    public function finally(callable $onFinally): PromiseInterface
    {
        $this->async();

        return $this->sendAsync()->finally($onFinally);
    }

    /**
     * Create an already fulfilled promise through the legacy delegate.
     */
    public function resolve(mixed $value): PromiseInterface
    {
        return $this->promiseManager()->resolve($value);
    }

    /**
     * Create a rejected promise through the legacy delegate.
     */
    public function reject(mixed $reason): PromiseInterface
    {
        return $this->promiseManager()->reject($reason);
    }

    /**
     * Applies the asynchronous mapper to each item and preserves its key.
     *
     * @param  array<int|string, mixed>  $items
     */
    public function map(
        array $items,
        callable $callback,
        int $concurrency = 5,
    ): PromiseInterface {
        return $this->promiseManager()->map($items, $callback, $concurrency);
    }

    /**
     * Await a promise with a required timeout measured in seconds.
     */
    protected function awaitWithTimeout(
        PromiseInterface $promise,
        float $timeout,
    ): mixed {
        return $this->promiseManager()->awaitPromise($promise, $timeout);
    }

    /**
     * Create a promise that rejects after the given delay in seconds.
     */
    protected function createTimeoutPromise(float $timeout): PromiseInterface
    {
        return $this->promiseManager()->createTimeoutPromise($timeout);
    }

    /**
     * Runs promise-producing callbacks sequentially and collects results.
     *
     * @param  array<callable(): PromiseInterface<mixed>>  $callables
     */
    protected function executeSequence(
        array $callables,
        array $results,
    ): PromiseInterface {
        if ($callables === []) {
            return $this->promiseManager()->resolve($results);
        }

        $callable = array_shift($callables);

        return $callable()->then(function (mixed $result) use (
            $callables,
            $results,
        ): PromiseInterface {
            $results[] = $result;

            return $this->executeSequence($callables, $results);
        });
    }

    /**
     * Rejects values that are not promises before aggregation.
     *
     * @param  array<mixed>  $promises
     */
    protected function validatePromises(array $promises): void
    {
        $this->promiseManager()->validatePromises($promises);
    }

    /**
     * Maps items in bounded batches while preserving their keys.
     *
     * @param  array<int|string, mixed>  $items
     */
    protected function mapBatched(
        array $items,
        callable $callback,
        int $concurrency,
    ): PromiseInterface {
        return $this->promiseManager()->map($items, $callback, $concurrency);
    }

    /**
     * Lazily construct the promise service used by this compatibility shim.
     */
    private function promiseManager(): PromiseManager
    {
        return $this->legacyPromiseManager ??= new PromiseManager;
    }
}
