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

final class PromiseManager
{
    private const MAX_MAP_CONCURRENCY = 200;

    public function wrapAsync(callable $callable): PromiseInterface
    {
        return async($callable);
    }

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
     * @param  array<PromiseInterface<mixed>>  $promises
     * @return PromiseInterface<array<mixed>>
     */
    public function all(array $promises): PromiseInterface
    {
        $this->validatePromises($promises);

        return all($promises);
    }

    /**
     * @param  array<PromiseInterface<mixed>>  $promises
     * @return PromiseInterface<mixed>
     */
    public function race(array $promises): PromiseInterface
    {
        $this->validatePromises($promises);

        return race($promises);
    }

    /**
     * @param  array<PromiseInterface<mixed>>  $promises
     * @return PromiseInterface<mixed>
     */
    public function any(array $promises): PromiseInterface
    {
        $this->validatePromises($promises);

        return any($promises);
    }

    /**
     * @param  array<callable(): PromiseInterface<mixed>>  $callables
     * @return PromiseInterface<array<mixed>>
     */
    public function sequence(array $callables): PromiseInterface
    {
        return $this->executeSequence($callables, []);
    }

    public function resolve(mixed $value): PromiseInterface
    {
        return resolve($value);
    }

    public function reject(mixed $reason): PromiseInterface
    {
        return reject(
            is_string($reason) ? new RuntimeException($reason) : $reason,
        );
    }

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

    /** @param array<mixed> $promises */
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
