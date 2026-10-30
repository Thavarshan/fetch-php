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
    private const MAX_MAP_CONCURRENCY = 200;

    /** Whether the request should be asynchronous or not. */
    protected bool $isAsync = false;

    private ?PromiseManager $legacyPromiseManager = null;

    public function async(?bool $async = true): ClientHandler
    {
        $this->isAsync = $async ?? true;

        return $this;
    }

    public function isAsync(): bool
    {
        return $this->isAsync;
    }

    public function wrapAsync(callable $callable): PromiseInterface
    {
        return $this->promiseManager()->wrapAsync($callable);
    }

    public function awaitPromise(
        PromiseInterface $promise,
        ?float $timeout = null,
    ): mixed {
        return $this->promiseManager()->awaitPromise($promise, $timeout);
    }

    /** @param array<PromiseInterface<mixed>> $promises */
    public function all(array $promises): PromiseInterface
    {
        return $this->promiseManager()->all($promises);
    }

    /** @param array<PromiseInterface<mixed>> $promises */
    public function race(array $promises): PromiseInterface
    {
        return $this->promiseManager()->race($promises);
    }

    /** @param array<PromiseInterface<mixed>> $promises */
    public function any(array $promises): PromiseInterface
    {
        return $this->promiseManager()->any($promises);
    }

    /** @param array<callable(): PromiseInterface<mixed>> $callables */
    public function sequence(array $callables): PromiseInterface
    {
        return $this->promiseManager()->sequence($callables);
    }

    public function then(
        callable $onFulfilled,
        ?callable $onRejected = null,
    ): PromiseInterface {
        $this->async();

        return $this->sendAsync()->then($onFulfilled, $onRejected);
    }

    public function catch(callable $onRejected): PromiseInterface
    {
        $this->async();

        return $this->sendAsync()->catch($onRejected);
    }

    public function finally(callable $onFinally): PromiseInterface
    {
        $this->async();

        return $this->sendAsync()->finally($onFinally);
    }

    public function resolve(mixed $value): PromiseInterface
    {
        return $this->promiseManager()->resolve($value);
    }

    public function reject(mixed $reason): PromiseInterface
    {
        return $this->promiseManager()->reject($reason);
    }

    /** @param array<int|string, mixed> $items */
    public function map(
        array $items,
        callable $callback,
        int $concurrency = 5,
    ): PromiseInterface {
        return $this->promiseManager()->map($items, $callback, $concurrency);
    }

    protected function awaitWithTimeout(
        PromiseInterface $promise,
        float $timeout,
    ): mixed {
        return $this->promiseManager()->awaitPromise($promise, $timeout);
    }

    protected function createTimeoutPromise(float $timeout): PromiseInterface
    {
        return $this->promiseManager()->createTimeoutPromise($timeout);
    }

    /** @param array<callable(): PromiseInterface<mixed>> $callables */
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

    /** @param array<mixed> $promises */
    protected function validatePromises(array $promises): void
    {
        $this->promiseManager()->validatePromises($promises);
    }

    /** @param array<int|string, mixed> $items */
    protected function mapBatched(
        array $items,
        callable $callback,
        int $concurrency,
    ): PromiseInterface {
        return $this->promiseManager()->map($items, $callback, $concurrency);
    }

    private function promiseManager(): PromiseManager
    {
        return $this->legacyPromiseManager ??= new PromiseManager;
    }
}
