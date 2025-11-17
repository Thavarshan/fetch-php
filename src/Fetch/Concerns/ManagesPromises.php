<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Interfaces\ClientHandler;
use InvalidArgumentException;
use Matrix\Exceptions\TimeoutException;
use React\Promise\PromiseInterface;
use RuntimeException;
use Throwable;

use function Matrix\Support\all;
use function Matrix\Support\any;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\race;
use function Matrix\Support\reject;
use function Matrix\Support\resolve;
use function Matrix\Support\timeout;

trait ManagesPromises
{
    /**
     * Whether the request should be asynchronous or not.
     */
    protected bool $isAsync = false;

    /**
     * Set the request to be asynchronous or not.
     *
     * @param  bool|null  $async  Whether to execute the request asynchronously
     * @return $this
     */
    public function async(?bool $async = true): ClientHandler
    {
        $this->isAsync = $async ?? true;

        return $this;
    }

    /**
     * Check if the request will be executed asynchronously.
     *
     * @return bool Whether the request is asynchronous
     */
    public function isAsync(): bool
    {
        return $this->isAsync;
    }

    /**
     * Wrap a callable to run asynchronously and return a promise.
     *
     * @param  callable  $callable  The callable to execute asynchronously
     * @return PromiseInterface The promise that will resolve with the result
     */
    public function wrapAsync(callable $callable): PromiseInterface
    {
        return async($callable);
    }

    /**
     * Wait for a promise to resolve and return its value.
     *
     * @param  PromiseInterface  $promise  The promise to wait for
     * @param  float|null  $timeout  Optional timeout in seconds
     * @return mixed The resolved value
     *
     * @throws Throwable If the promise is rejected or times out
     */
    public function awaitPromise(PromiseInterface $promise, ?float $timeout = null): mixed
    {
        try {
            if ($timeout !== null) {
                return $this->awaitWithTimeout($promise, $timeout);
            }

            return await($promise);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * Execute multiple promises concurrently and wait for all to complete.
     *
     * @param  array<PromiseInterface>  $promises  Array of promises
     * @return PromiseInterface Promise that resolves with array of results
     */
    public function all(array $promises): PromiseInterface
    {
        $this->validatePromises($promises);

        return all($promises);
    }

    /**
     * Execute multiple promises concurrently and return the first to complete.
     *
     * @param  array<PromiseInterface>  $promises  Array of promises
     * @return PromiseInterface Promise that resolves with the first result
     */
    public function race(array $promises): PromiseInterface
    {
        $this->validatePromises($promises);

        return race($promises);
    }

    /**
     * Execute multiple promises concurrently and return the first to succeed.
     *
     * @param  array<PromiseInterface>  $promises  Array of promises
     * @return PromiseInterface Promise that resolves with the first successful result
     */
    public function any(array $promises): PromiseInterface
    {
        $this->validatePromises($promises);

        return any($promises);
    }

    /**
     * Execute multiple promises in sequence.
     *
     * @param  array<callable(): PromiseInterface>  $callables  Array of callables that return promises
     * @return PromiseInterface Promise that resolves with array of results
     */
    public function sequence(array $callables): PromiseInterface
    {
        return $this->executeSequence($callables, []);
    }

    /**
     * Add a callback to be executed when the promise resolves.
     *
     * @param  callable  $onFulfilled  Callback for success
     * @param  callable|null  $onRejected  Callback for rejection
     * @return PromiseInterface The promise
     */
    public function then(callable $onFulfilled, ?callable $onRejected = null): PromiseInterface
    {
        // Make sure we're in async mode
        $this->async();

        // Create a promise from the next request
        $promise = $this->sendAsync();

        // Add callbacks
        return $promise->then($onFulfilled, $onRejected);
    }

    /**
     * Add a callback to be executed when the promise is rejected.
     *
     * @param  callable  $onRejected  Callback for rejection
     * @return PromiseInterface The promise
     */
    public function catch(callable $onRejected): PromiseInterface
    {
        // Make sure we're in async mode
        $this->async();

        // Create a promise from the next request
        $promise = $this->sendAsync();

        // Add rejection callback
        return $promise->otherwise($onRejected);
    }

    /**
     * Add a callback to be executed when the promise settles.
     *
     * @param  callable  $onFinally  Callback for completion
     * @return PromiseInterface The promise
     */
    public function finally(callable $onFinally): PromiseInterface
    {
        // Make sure we're in async mode
        $this->async();

        // Create a promise from the next request
        $promise = $this->sendAsync();

        // Add finally callback
        return $promise->always($onFinally);
    }

    /**
     * Create a resolved promise with the given value.
     *
     * @param  mixed  $value  The value to resolve with
     * @return PromiseInterface The resolved promise
     */
    public function resolve(mixed $value): PromiseInterface
    {
        return resolve($value);
    }

    /**
     * Create a rejected promise with the given reason.
     *
     * @param  mixed  $reason  The reason for rejection
     * @return PromiseInterface The rejected promise
     */
    public function reject(mixed $reason): PromiseInterface
    {
        return reject(is_string($reason) ? new RuntimeException($reason) : $reason);
    }

    /**
     * Map an array of items through an async callback.
     *
     * @param  array<mixed>  $items  Items to process
     * @param  callable  $callback  Callback that returns a promise
     * @param  int  $concurrency  Maximum number of concurrent promises
     * @return PromiseInterface Promise that resolves with array of results
     */
    public function map(array $items, callable $callback, int $concurrency = 5): PromiseInterface
    {
        if (empty($items)) {
            return resolve([]);
        }

        if ($concurrency <= 0) {
            throw new InvalidArgumentException('Concurrency must be greater than 0');
        }

        // If concurrency is unlimited or greater than the number of items,
        // we can process all at once
        if ($concurrency >= count($items)) {
            $promises = array_map($callback, $items);

            return $this->all($promises);
        }

        // Process in batches for controlled concurrency
        return $this->mapBatched($items, $callback, $concurrency);
    }

    /**
     * Wait for a promise with a timeout.
     *
     * @param  PromiseInterface  $promise  The promise to wait for
     * @param  float  $timeout  Timeout in seconds
     * @return mixed The resolved value
     *
     * @throws RuntimeException If the promise times out
     * @throws Throwable If the promise is rejected
     */
    protected function awaitWithTimeout(PromiseInterface $promise, float $timeout): mixed
    {
        try {
            // Use Matrix's built-in timeout function
            return await(timeout($promise, $timeout));
        } catch (TimeoutException $e) {
            // Convert to RuntimeException for consistency with your API
            throw new RuntimeException("Promise timed out after {$timeout} seconds", 0, $e);
        }
    }

    /**
     * Create a promise that will reject after a timeout.
     *
     * @param  float  $timeout  Timeout in seconds
     * @return PromiseInterface The timeout promise
     */
    protected function createTimeoutPromise(float $timeout): PromiseInterface
    {
        return async(function () use ($timeout) {
            $timeoutMicro = (int) ($timeout * 1000000);
            usleep($timeoutMicro);
            throw new RuntimeException("Promise timed out after {$timeout} seconds");
        });
    }

    /**
     * Execute promises in sequence recursively.
     *
     * @param  array<callable(): PromiseInterface>  $callables  Array of callables
     * @param  array<mixed>  $results  Results collected so far
     * @return PromiseInterface Promise that resolves with array of results
     */
    protected function executeSequence(array $callables, array $results): PromiseInterface
    {
        // If no more callables, resolve with the results
        if (empty($callables)) {
            return resolve($results);
        }

        // Take the first callable
        $callable = array_shift($callables);

        // Execute it and chain the next promises
        return $callable()->then(
            function ($result) use ($callables, $results) {
                $results[] = $result;

                return $this->executeSequence($callables, $results);
            }
        );
    }

    /**
     * Validate that all items in the array are promises.
     *
     * @param  array<mixed>  $promises  Array to validate
     *
     * @throws InvalidArgumentException If any item is not a PromiseInterface
     */
    protected function validatePromises(array $promises): void
    {
        foreach ($promises as $index => $promise) {
            if (! $promise instanceof PromiseInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Item at index %d is not a promise. Expected %s, got %s',
                        $index,
                        PromiseInterface::class,
                        get_debug_type($promise)
                    )
                );
            }
        }
    }

    /**
     * Process items in batches with controlled concurrency.
     *
     * @param  array<mixed>  $items  Items to process
     * @param  callable  $callback  Callback that returns a promise
     * @param  int  $concurrency  Maximum number of concurrent promises
     * @return PromiseInterface Promise that resolves with array of results
     */
    protected function mapBatched(array $items, callable $callback, int $concurrency): PromiseInterface
    {
        $results = [];
        $pendingPromises = [];
        $itemKeys = array_keys($items);
        $i = 0;
        $totalItems = count($items);

        // Initial function to start the first batch
        $startBatch = function () use (&$pendingPromises, &$i, $totalItems, $itemKeys, $items, $callback, &$results, &$startBatch, $concurrency) {
            // Fill up to concurrency
            while (count($pendingPromises) < $concurrency && $i < $totalItems) {
                $key = $itemKeys[$i];
                $item = $items[$key];
                $promise = $callback($item, $key);

                if (! ($promise instanceof PromiseInterface)) {
                    throw new RuntimeException('Callback must return a Promise');
                }

                // Add this promise to the pending queue with a handler to process the next item
                $pendingPromises[$key] = $promise->then(
                    function ($result) use ($key, &$results, &$pendingPromises, &$startBatch) {
                        $results[$key] = $result;
                        unset($pendingPromises[$key]);
                        $startBatch(); // Process the next item

                        return $result;
                    },
                    function ($reason) use ($key, &$pendingPromises) {
                        unset($pendingPromises[$key]);

                        return reject($reason); // Propagate the rejection
                    }
                );

                $i++;
            }

            // If we've processed all items and have no more pending promises, resolve
            if ($i >= $totalItems && empty($pendingPromises)) {
                return resolve($results);
            }

            // Return a promise that resolves when all pending promises are done
            if (! empty($pendingPromises)) {
                return all($pendingPromises)->then(function () use (&$results) {
                    return $results;
                });
            }

            return resolve($results);
        };

        // Start the process
        return $startBatch();
    }
}
