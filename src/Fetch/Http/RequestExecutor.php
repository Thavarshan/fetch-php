<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Exceptions\RequestException as FetchRequestException;
use Fetch\Interfaces\Response as ResponseContract;
use Fetch\Support\RetryStrategy;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

/**
 * Performs synchronous Guzzle I/O and normalizes responses and failures.
 */
final class RequestExecutor
{
    /**
     * Bind the executor to the Guzzle client used for synchronous requests.
     */
    public function __construct(private readonly ClientInterface $client) {}

    /**
     * Convert a PSR response and mark retryable statuses as request failures.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws FetchRequestException When the response status is retryable
     */
    public static function normalizeResponse(
        PsrResponseInterface $response,
        RetryStrategy $retryStrategy,
        string $method,
        string $uri,
        array $options,
    ): ResponseContract {
        $normalized = Response::createFromBase($response);
        if (
            $retryStrategy->isRetryableStatusCode($normalized->getStatusCode())
        ) {
            throw new FetchRequestException(
                'Retryable status: '.$normalized->getStatusCode(),
                new GuzzleRequest($method, $uri, $options['headers'] ?? []),
                $response,
            );
        }

        return $normalized;
    }

    /**
     * Preserve request and response context in a FetchPHP request exception.
     *
     * @param  array<string, mixed>  $options
     */
    public static function normalizeGuzzleException(
        GuzzleException $exception,
        string $method,
        string $uri,
        array $options,
    ): FetchRequestException {
        if ($exception instanceof GuzzleRequestException) {
            return new FetchRequestException(
                sprintf(
                    'Request %s %s failed: %s',
                    $method,
                    $uri,
                    $exception->getMessage(),
                ),
                $exception->getRequest(),
                $exception->getResponse(),
                $exception,
            );
        }

        return new FetchRequestException(
            sprintf(
                'Request %s %s failed: %s',
                $method,
                $uri,
                $exception->getMessage(),
            ),
            new GuzzleRequest($method, $uri, $options['headers'] ?? []),
            null,
            $exception,
        );
    }

    /**
     * Execute with retries, normalizing Guzzle responses and exceptions.
     *
     * @param  array<string, mixed>  $options
     * @param  callable(int, int, \Throwable, int): void|null  $onRetry
     * @param  callable(): void|null  $onRequest
     */
    public function execute(
        string $method,
        string $uri,
        array $options,
        RetryStrategy $retryStrategy,
        ?callable $onRetry = null,
        ?callable $onRequest = null,
    ): ResponseContract {
        return $retryStrategy->execute(function () use (
            $method,
            $uri,
            $options,
            $retryStrategy,
            $onRequest,
        ): ResponseContract {
            if ($onRequest !== null) {
                $onRequest();
            }

            try {
                $response = $this->client->request($method, $uri, $options);
            } catch (GuzzleException $exception) {
                throw self::normalizeGuzzleException(
                    $exception,
                    $method,
                    $uri,
                    $options,
                );
            }

            $normalized = Response::createFromBase($response);
            if (
                $retryStrategy->isRetryableStatusCode(
                    $normalized->getStatusCode(),
                )
            ) {
                $request = new GuzzleRequest(
                    $method,
                    $uri,
                    $options['headers'] ?? [],
                );
                throw new FetchRequestException(
                    'Retryable status: '.$normalized->getStatusCode(),
                    $request,
                    $response,
                );
            }

            return $normalized;
        }, $onRetry);
    }
}
