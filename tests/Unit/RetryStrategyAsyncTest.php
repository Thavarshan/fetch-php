<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Http\Response;
use Fetch\Support\RetryStrategy;
use InvalidArgumentException;
use Matrix\Exceptions\CancellationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

use function Matrix\Support\await;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Exercises asynchronous retry attempts, cancellation, and failure propagation.
 */
class RetryStrategyAsyncTest extends TestCase
{
    public function test_async_retries_preserve_attempt_counts(): void
    {
        $attempts = 0;
        $retries = [];
        $strategy = new RetryStrategy(
            maxRetries: 2,
            baseDelayMs: 0,
            retryableExceptions: [RuntimeException::class],
        );

        $response = await(
            $strategy->executeAsync(
                function () use (&$attempts) {
                    $attempts++;

                    if ($attempts < 3) {
                        return reject(
                            new RuntimeException('temporary failure'),
                        );
                    }

                    return resolve(new Response(200));
                },
                function (
                    int $attempt,
                    int $maxRetries,
                    Throwable $exception,
                    int $delay,
                ) use (&$retries): void {
                    $retries[] = [
                        $attempt,
                        $maxRetries,
                        $exception::class,
                        $delay,
                    ];
                },
            ),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(3, $attempts);
        $this->assertSame(
            [
                [1, 2, RuntimeException::class, 0],
                [2, 2, RuntimeException::class, 0],
            ],
            $retries,
        );
    }

    public function test_async_skips_permanent_errors_and_cancellation(): void
    {
        foreach (
            [
                new InvalidArgumentException('invalid request'),
                new CancellationException,
            ] as $failure
        ) {
            $attempts = 0;
            $strategy = new RetryStrategy(maxRetries: 3, baseDelayMs: 0);

            try {
                await(
                    $strategy->executeAsync(function () use (
                        &$attempts,
                        $failure,
                    ) {
                        $attempts++;

                        return reject($failure);
                    }),
                );
                self::fail('Expected the asynchronous request to reject.');
            } catch (Throwable $exception) {
                $this->assertSame($failure, $exception);
            }

            $this->assertSame(1, $attempts);
        }
    }

    public function test_async_exhaustion_preserves_the_original_failure(): void
    {
        $failure = new RuntimeException('transport failed');
        $attempts = 0;
        $strategy = new RetryStrategy(
            maxRetries: 1,
            baseDelayMs: 0,
            retryableExceptions: [RuntimeException::class],
        );

        try {
            await(
                $strategy->executeAsync(function () use (&$attempts, $failure) {
                    $attempts++;

                    return reject($failure);
                }),
            );
            self::fail('Expected retries to exhaust.');
        } catch (Throwable $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->assertSame(2, $attempts);
    }
}
