<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Interfaces\AsyncTransport;
use Fetch\Support\RetryStrategy;
use GuzzleHttp\Exception\GuzzleException;
use React\Promise\PromiseInterface;
use Throwable;

final class AsyncRequestExecutor
{
    public function __construct(private readonly AsyncTransport $transport) {}

    /**
     * @param  array<string, mixed>  $options
     * @return PromiseInterface<\Fetch\Interfaces\Response>
     */
    public function execute(
        string $method,
        string $uri,
        array $options,
        RetryStrategy $retryStrategy,
        ?callable $onRetry = null,
        ?callable $onRequest = null,
    ): PromiseInterface {
        return $retryStrategy->executeAsync(function () use ($method, $uri, $options, $retryStrategy, $onRequest): PromiseInterface {
            if ($onRequest !== null) {
                $onRequest();
            }

            try {
                $promise = $this->transport->requestAsync($method, $uri, $options);
            } catch (Throwable $exception) {
                if ($exception instanceof GuzzleException) {
                    throw RequestExecutor::normalizeGuzzleException($exception, $method, $uri, $options);
                }

                throw $exception;
            }

            return $promise
                ->then(fn ($response) => RequestExecutor::normalizeResponse(
                    $response,
                    $retryStrategy,
                    $method,
                    $uri,
                    $options,
                ))
                ->catch(function (Throwable $exception) use ($method, $uri, $options): never {
                    if ($exception instanceof GuzzleException) {
                        throw RequestExecutor::normalizeGuzzleException($exception, $method, $uri, $options);
                    }

                    throw $exception;
                });
        }, $onRetry);
    }
}
