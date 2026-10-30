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
 * Explicitly bridges Guzzle's promise queue and optional cURL multi driver to React promises.
 */
final class GuzzleAsyncTransport implements AsyncTransport
{
    private ?TimerInterface $pump = null;

    /** @var array<int, callable(Throwable): void> */
    private array $active = [];

    private int $nextId = 1;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly ?CurlMultiHandler $curlMultiHandler = null,
        private readonly bool $supportsQueueOnlyHandlers = false,
    ) {}

    public function requestAsync(string $method, string $uri, array $options): PromiseInterface
    {
        if ($this->curlMultiHandler === null && ! $this->supportsQueueOnlyHandlers) {
            return reject(new AsyncTransportException(
                'Guzzle async requests need a CurlMultiHandler or an explicit AsyncTransport. Queue-only Guzzle handlers are supported only when explicitly enabled.'
            ));
        }

        $guzzlePromise = $this->client->requestAsync($method, $uri, $options);
        $id = $this->nextId++;
        $settled = false;
        $release = function () use ($id): void {
            unset($this->active[$id]);
            $this->stopPumpWhenIdle();
        };

        $deferred = new Deferred(function (callable $resolve, callable $reject) use ($guzzlePromise, &$settled, $release): void {
            if (! $settled) {
                $settled = true;
                $release();
                $guzzlePromise->cancel();
            }

            $reject(new CancellationException);
        });

        $this->active[$id] = function (Throwable $exception) use ($deferred, &$settled, $release, $guzzlePromise): void {
            if ($settled) {
                return;
            }

            $settled = true;
            $release();
            $guzzlePromise->cancel();
            $deferred->reject($exception);
        };

        $guzzlePromise->then(
            function (ResponseInterface $response) use ($deferred, &$settled, $release): void {
                if (! $settled) {
                    $settled = true;
                    $release();
                    $deferred->resolve($response);
                }
            },
            function (Throwable $exception) use ($deferred, &$settled, $release): void {
                if (! $settled) {
                    $settled = true;
                    $release();
                    $deferred->reject($exception);
                }
            }
        );

        if ($this->curlMultiHandler !== null) {
            $this->startPump();
        } else {
            Loop::futureTick(function () use ($guzzlePromise, &$settled, $id): void {
                if ($settled) {
                    return;
                }

                try {
                    GuzzlePromiseUtils::queue()->run();
                } catch (Throwable $exception) {
                    ($this->active[$id] ?? static fn () => null)($exception);

                    return;
                }

                if ($guzzlePromise->getState() === GuzzlePromiseInterface::PENDING) {
                    ($this->active[$id] ?? static fn () => null)(new AsyncTransportException(
                        'The configured Guzzle client has pending async I/O but no event-loop driver. Inject an AsyncTransport or a GuzzleAsyncTransport with its CurlMultiHandler.'
                    ));
                }
            });
        }

        return $deferred->promise();
    }

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

    private function stopPumpWhenIdle(): void
    {
        if ($this->active !== [] || ! $this->pump instanceof TimerInterface) {
            return;
        }

        Loop::cancelTimer($this->pump);
        $this->pump = null;
    }
}
