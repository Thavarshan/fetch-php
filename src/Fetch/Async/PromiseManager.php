<?php

declare(strict_types=1);

namespace Fetch\Async;

use InvalidArgumentException;
use Matrix\Exceptions\TimeoutException;
use React\Promise\PromiseInterface;
use RuntimeException;

use function Matrix\Support\all;
use function Matrix\Support\any;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\delay;
use function Matrix\Support\map as matrixMap;
use function Matrix\Support\race;
use function Matrix\Support\reject;
use function Matrix\Support\resolve;
use function Matrix\Support\timeout;

/**
 * Adapts Matrix promise operations to the execution contracts used by FetchPHP.
 *
 * The manager centralizes promise creation, awaiting, aggregation, and mapping
 * for {@see \Fetch\Http\ClientHandler}. It does not itself send HTTP
 * requests or guarantee that a wrapped callback performs nonblocking I/O.
 */
final class PromiseManager
{
    /**
     * Maximum per-item concurrency accepted by {@see self::map()}.
     */
    private const MAX_MAP_CONCURRENCY = 200;

    /**
     * Wrap a callable in a Matrix-managed asynchronous operation.
     */
    public function wrapAsync(callable $callable): PromiseInterface
    {
        return async($callable);
    }

    /**
     * Run the event loop until a promise settles and return its fulfillment.
     *
     * @param  PromiseInterface<mixed>  $promise
     *
     * @throws RuntimeException When the optional timeout expires
     */
    public function awaitPromise(
        PromiseInterface $promise,
        ?float $timeout = null,
    ): mixed {
        if ($timeout === null) {
            return await($promise);
        }

        try {
            return await(timeout($promise, $timeout));
        } catch (TimeoutException $exception) {
            throw new RuntimeException(
                "Promise timed out after {$timeout} seconds",
                0,
                $exception,
            );
        }
    }

    /**
     * Waits for all promises and preserves the input keys in the results.
     *
     * @param  array<PromiseInterface<mixed>>  $promises
     * @return PromiseInterface<array<mixed>>
     */
    public function all(array $promises): PromiseInterface
    {
        $this->validatePromises($promises);

        return all($promises);
    }

    /**
     * Adopts the outcome of the first promise to settle.
     *
     * @param  array<PromiseInterface<mixed>>  $promises
     * @return PromiseInterface<mixed>
     */
    public function race(array $promises): PromiseInterface
    {
        $this->validatePromises($promises);

        return race($promises);
    }

    /**
     * Returns the first fulfilled promise or rejects when all fail.
     *
     * @param  array<PromiseInterface<mixed>>  $promises
     * @return PromiseInterface<mixed>
     */
    public function any(array $promises): PromiseInterface
    {
        $this->validatePromises($promises);

        return any($promises);
    }

    /**
     * Runs promise-producing callbacks in order, awaiting each result.
     *
     * @param  array<callable(): PromiseInterface<mixed>>  $callables
     * @return PromiseInterface<array<mixed>>
     */
    public function sequence(array $callables): PromiseInterface
    {
        return $this->executeSequence($callables, []);
    }

    /**
     * Create an already fulfilled promise for the supplied value.
     */
    public function resolve(mixed $value): PromiseInterface
    {
        return resolve($value);
    }

    /**
     * Create a rejected promise, wrapping string reasons in RuntimeException.
     */
    public function reject(mixed $reason): PromiseInterface
    {
        return reject(
            is_string($reason) ? new RuntimeException($reason) : $reason,
        );
    }

    /**
     * Reject a promise after the given delay, measured in seconds.
     */
    public function createTimeoutPromise(float $timeout): PromiseInterface
    {
        return delay($timeout)->then(static function () use ($timeout): never {
            throw new RuntimeException(
                "Promise timed out after {$timeout} seconds",
            );
        });
    }

    /**
     * Matrix maps values and preserves its input keys. Wrapping each entry lets
     * fetch-php retain its
     * established two-argument callback signature while keeping Matrix
     * responsible for scheduling.
     *
     * @param  array<int|string, mixed>  $items
     * @param  callable(mixed, int|string): PromiseInterface<mixed>  $callback
     * @return PromiseInterface<array<int|string, mixed>>
     */
    public function map(
        array $items,
        callable $callback,
        int $concurrency = 5,
    ): PromiseInterface {
        if ($concurrency <= 0) {
            throw new InvalidArgumentException(
                'Concurrency must be greater than 0',
            );
        }

        if ($concurrency > self::MAX_MAP_CONCURRENCY) {
            throw new InvalidArgumentException(
                sprintf(
                    'Concurrency value %d exceeds the maximum allowed (%d).',
                    $concurrency,
                    self::MAX_MAP_CONCURRENCY,
                ),
            );
        }

        $entries = [];
        foreach ($items as $key => $item) {
            $entries[] = ['key' => $key, 'value' => $item];
        }

        return matrixMap(
            $entries,
            static function (array $entry) use ($callback): PromiseInterface {
                $promise = $callback($entry['value'], $entry['key']);
                if (! ($promise instanceof PromiseInterface)) {
                    throw new RuntimeException(
                        'Callback must return a Promise',
                    );
                }

                return $promise->then(
                    static fn (mixed $value): array => [
                        'key' => $entry['key'],
                        'value' => $value,
                    ],
                );
            },
            $concurrency,
        )->then(static function (array $results): array {
            $mapped = [];
            foreach ($results as $result) {
                if (
                    is_array($result) &&
                    array_key_exists('key', $result) &&
                    array_key_exists('value', $result)
                ) {
                    $mapped[$result['key']] = $result['value'];
                }
            }

            return $mapped;
        });
    }

    /**
     * Rejects values that are not promises before aggregation.
     *
     * @param  array<mixed>  $promises
     */
    public function validatePromises(array $promises): void
    {
        foreach ($promises as $index => $promise) {
            if (! ($promise instanceof PromiseInterface)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Item at index %s is not a promise. Expected %s, '
                            .'got %s',
                        (string) $index,
                        PromiseInterface::class,
                        get_debug_type($promise),
                    ),
                );
            }
        }
    }

    /**
     * Runs promise-producing callbacks sequentially and collects results.
     *
     * @param  array<callable(): PromiseInterface<mixed>>  $callables
     * @param  array<mixed>  $results
     * @return PromiseInterface<array<mixed>>
     */
    private function executeSequence(
        array $callables,
        array $results,
    ): PromiseInterface {
        if ($callables === []) {
            return resolve($results);
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
}
