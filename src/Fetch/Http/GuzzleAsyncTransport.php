<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Exceptions\AsyncTransportException;
use Fetch\Interfaces\AsyncTransport;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Promise\PromiseInterface as GuzzlePromiseInterface;
use GuzzleHttp\Promise\Utils as GuzzlePromiseUtils;
use Matrix\Exceptions\CancellationException;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Throwable;

use function React\Promise\reject;

/**
 * Explicitly bridges Guzzle's promise queue and optional cURL multi driver to
 * React promises.
 */
final class GuzzleAsyncTransport implements AsyncTransport
{
    /**
     * Periodic event-loop driver while one or more requests are active.
     */
    private ?TimerInterface $pump = null;

    /**
     * Tracks active requests so cancellation can reach their promises.
     *
     * @var array<int, callable(Throwable): void>
     */
    private array $active = [];

    /**
     * Monotonically increasing key for active request cleanup callbacks.
     */
    private int $nextId = 1;

    /**
     * Configure Guzzle queue processing and an optional cURL multi driver.
     *
     * Queue-only handlers must be explicitly enabled because pending I/O
     * otherwise has no driver that can make progress.
     */
    public function __construct(
        private readonly ClientInterface $client,
        private readonly ?CurlMultiHandler $curlMultiHandler = null,
        private readonly bool $supportsQueueOnlyHandlers = false,
    ) {}

    /**
     * Start an async request and bridge its settlement to a React promise.
     *
     * The active request is removed and the periodic pump is stopped when the
     * promise settles or is cancelled.
     *
     * @param  array<string, mixed>  $options
     * @return PromiseInterface<ResponseInterface>
     */
    public function requestAsync(
        string $method,
        string $uri,
        array $options,
    ): PromiseInterface {
        if (
            $this->curlMultiHandler === null &&
            ! $this->supportsQueueOnlyHandlers
        ) {
            return reject(
                new AsyncTransportException(
                    'Guzzle async requests need a CurlMultiHandler or an '
                        .'explicit AsyncTransport. Queue-only Guzzle handlers '
                        .'are supported only when explicitly enabled.',
                ),
            );
        }

        $guzzlePromise = $this->client->requestAsync($method, $uri, $options);
        $id = $this->nextId++;
        $settled = false;
        $release = function () use ($id): void {
            unset($this->active[$id]);
            $this->stopPumpWhenIdle();
        };

        $deferred = new Deferred(function (
            callable $resolve,
            callable $reject,
        ) use ($guzzlePromise, &$settled, $release): void {
            if (! $settled) {
                $settled = true;
                $release();
                $guzzlePromise->cancel();
            }

            $reject(new CancellationException);
        });

        $this->active[$id] = function (Throwable $exception) use (
            $deferred,
            &$settled,
            $release,
            $guzzlePromise,
        ): void {
            if ($settled) {
                return;
            }

            $settled = true;
            $release();
            $guzzlePromise->cancel();
            $deferred->reject($exception);
        };

        $guzzlePromise->then(
            function (ResponseInterface $response) use (
                $deferred,
                &$settled,
                $release,
            ): void {
                if (! $settled) {
                    $settled = true;
                    $release();
                    $deferred->resolve($response);
                }
            },
            function (Throwable $exception) use (
                $deferred,
                &$settled,
                $release,
            ): void {
                if (! $settled) {
                    $settled = true;
                    $release();
                    $deferred->reject($exception);
                }
            },
        );

        if ($this->curlMultiHandler !== null) {
            $this->startPump();
        } else {
            Loop::futureTick(function () use (
                $guzzlePromise,
                &$settled,
                $id,
            ): void {
                if ($settled) {
                    return;
                }

                try {
                    GuzzlePromiseUtils::queue()->run();
                } catch (Throwable $exception) {
                    ($this->active[$id] ?? static fn () => null)($exception);

                    return;
                }

                if (
                    $guzzlePromise->getState() ===
                    GuzzlePromiseInterface::PENDING
                ) {
                    ($this->active[$id] ?? static fn () => null)(
                        new AsyncTransportException(
                            'The configured Guzzle client has pending async'
                                .' I/O but no event-loop driver. Inject an '
                                .'AsyncTransport or a GuzzleAsyncTransport '
                                .'with its CurlMultiHandler.',
                        ),
                    );
                }
            });
        }

        return $deferred->promise();
    }

    /**
     * Start one shared loop timer to advance cURL and Guzzle's promise queue.
     */
    private function startPump(): void
    {
        if ($this->pump instanceof TimerInterface) {
            return;
        }

        $this->pump = Loop::addPeriodicTimer(0.001, function (): void {
            try {
                $this->curlMultiHandler?->tick();
                GuzzlePromiseUtils::queue()->run();
            } catch (Throwable $exception) {
                $failures = $this->active;
                foreach ($failures as $reject) {
                    $reject($exception);
                }
            }

            $this->stopPumpWhenIdle();
        });
    }

    /**
     * Stop the shared loop timer after the final active request settles.
     */
    private function stopPumpWhenIdle(): void
    {
        if ($this->active !== [] || ! ($this->pump instanceof TimerInterface)) {
            return;
        }

        Loop::cancelTimer($this->pump);
        $this->pump = null;
    }
}
