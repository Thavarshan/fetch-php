<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Interfaces\AsyncTransport;
use Fetch\Interfaces\Response;
use Fetch\Support\RetryStrategy;
use GuzzleHttp\Exception\GuzzleException;
use React\Promise\PromiseInterface;
use Throwable;

/**
 * Executes async requests through an AsyncTransport and RetryStrategy.
 */
final class AsyncRequestExecutor
{
    /**
     * Bind this executor to the configured asynchronous transport.
     */
    public function __construct(private readonly AsyncTransport $transport) {}

    /**
     * Send a request asynchronously and normalize its result for FetchPHP.
     *
     * @param  array<string, mixed>  $options
     * @param  callable(int, int, Throwable, int): void|null  $onRetry
     * @param  callable(): void|null  $onRequest
     * @return PromiseInterface<Response>
     */
    public function execute(
        string $method,
        string $uri,
        array $options,
        RetryStrategy $retryStrategy,
        ?callable $onRetry = null,
        ?callable $onRequest = null,
    ): PromiseInterface {
        return $retryStrategy->executeAsync(function () use (
            $method,
            $uri,
            $options,
            $retryStrategy,
            $onRequest,
        ): PromiseInterface {
            if ($onRequest !== null) {
                $onRequest();
            }

            try {
                $promise = $this->transport->requestAsync(
                    $method,
                    $uri,
                    $options,
                );
            } catch (Throwable $exception) {
                if ($exception instanceof GuzzleException) {
                    throw RequestExecutor::normalizeGuzzleException(
                        $exception,
                        $method,
                        $uri,
                        $options,
                    );
                }

                throw $exception;
            }

            return $promise
                ->then(
                    fn ($response) => RequestExecutor::normalizeResponse(
                        $response,
                        $retryStrategy,
                        $method,
                        $uri,
                        $options,
                    ),
                )
                ->catch(function (Throwable $exception) use (
                    $method,
                    $uri,
                    $options,
                ): never {
                    if ($exception instanceof GuzzleException) {
                        throw RequestExecutor::normalizeGuzzleException(
                            $exception,
                            $method,
                            $uri,
                            $options,
                        );
                    }

                    throw $exception;
                });
        }, $onRetry);
    }
}
