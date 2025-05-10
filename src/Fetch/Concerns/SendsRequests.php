<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Http\Response;
use Fetch\Interfaces\Response as ResponseInterface;
use GuzzleHttp\Client as SyncClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use React\Promise\PromiseInterface;
use RuntimeException;

use function async;

trait SendsRequests
{
    /**
     * Apply options and execute the request.
     *
     * @param  string  $method  The HTTP method to use
     * @param  string  $uri  The URI to request
     * @param  array<string, mixed>  $options  The request options
     * @return ResponseInterface|PromiseInterface The response or promise
     *
     * @throws RuntimeException If the request fails
     */
    public static function handle(
        string $method,
        string $uri,
        array $options = []
    ): ResponseInterface|PromiseInterface {
        $handler = new static;
        $handler->applyOptions($options);

        return $handler->finalizeRequest($method, $uri);
    }

    /**
     * Make a request with any HTTP method.
     *
     * @param  string  $method  The HTTP method to use
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  Optional request body
     * @param  string  $contentType  The content type of the request
     * @param  array<string, mixed>  $options  Additional request options
     * @return ResponseInterface|PromiseInterface The response or promise
     *
     * @throws RuntimeException If the request fails
     */
    public function request(
        string $method,
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = ContentType::JSON->value,
        array $options = []
    ): ResponseInterface|PromiseInterface {
        // Apply any additional options
        if (! empty($options)) {
            $this->withOptions($options);
        }

        // Normalize method to uppercase
        $method = strtoupper($method);

        // Try to convert to enum to validate
        try {
            $methodEnum = Method::fromString($method);
            $method = $methodEnum->value;
        } catch (\ValueError $e) {
            throw new InvalidArgumentException("Invalid HTTP method: {$method}");
        }

        // Configure request body for methods that accept one
        if ($methodEnum->supportsRequestBody() && $body !== null) {
            $this->configurePostableRequest($body, $contentType);
        }

        return $this->finalizeRequest($method, $uri);
    }

    /**
     * Get the synchronous HTTP client.
     *
     * @return ClientInterface The HTTP client
     */
    public function getSyncClient(): ClientInterface
    {
        if (! $this->syncClient) {
            $this->syncClient = new SyncClient([
                RequestOptions::CONNECT_TIMEOUT => $this->options['timeout'] ?? self::DEFAULT_TIMEOUT,
                RequestOptions::HTTP_ERRORS => false, // We'll handle HTTP errors ourselves
            ]);
        }

        return $this->syncClient;
    }

    /**
     * Get the raw prepared options array ready for Guzzle.
     *
     * @return array<string, mixed> The prepared options
     */
    public function getPreparedOptions(): array
    {
        return $this->preparedOptions ?? $this->options;
    }

    /**
     * Apply the options to the handler.
     *
     * @param  array<string, mixed>  $options  The request options
     */
    protected function applyOptions(array $options): void
    {
        // Extract and set client if provided
        if (isset($options['client'])) {
            if (! ($options['client'] instanceof ClientInterface)) {
                throw new InvalidArgumentException('Client must be an instance of GuzzleHttp\ClientInterface');
            }
            $this->setSyncClient($options['client']);
            unset($options['client']); // Remove client from options to avoid conflicts
        }

        // Merge options
        $this->options = array_merge($this->options, $options);

        // Set specific properties
        $this->timeout = $options['timeout'] ?? $this->timeout;
        $this->retries = $options['retries'] ?? $this->retries;
        $this->retryDelay = $options['retry_delay'] ?? $this->retryDelay;
        $this->isAsync = ! empty($options['async']);

        // Handle base URI
        if (isset($options['base_uri'])) {
            $this->baseUri($options['base_uri']);
            // Keep base_uri in options for Guzzle's constructor
        }
    }

    /**
     * Finalize the request and send it.
     *
     * @param  string  $method  The HTTP method to use
     * @param  string  $uri  The URI to request
     * @return ResponseInterface|PromiseInterface The response or promise
     *
     * @throws RuntimeException If the request fails
     */
    protected function finalizeRequest(string $method, string $uri): ResponseInterface|PromiseInterface
    {
        $this->options['method'] = $method;
        $this->options['uri'] = $uri;

        $this->mergeOptionsAndProperties();
        $this->prepareOptionsForGuzzle();

        return $this->isAsync ? $this->sendAsync() : $this->sendSync();
    }

    /**
     * Merge class properties and options into the final options array.
     */
    protected function mergeOptionsAndProperties(): void
    {
        $this->options['timeout'] = $this->timeout ?? self::DEFAULT_TIMEOUT;
        $this->options['retries'] = $this->retries ?? self::DEFAULT_RETRIES;
        $this->options['retry_delay'] = $this->retryDelay ?? self::DEFAULT_RETRY_DELAY;
    }

    /**
     * Prepare options for Guzzle by removing custom options.
     */
    protected function prepareOptionsForGuzzle(): void
    {
        $guzzleOptions = $this->options;

        // Remove our custom options that aren't supported by Guzzle
        unset(
            $guzzleOptions['method'],
            $guzzleOptions['uri'],
            $guzzleOptions['retries'],
            $guzzleOptions['retry_delay'],
            $guzzleOptions['async']
        );

        $this->preparedOptions = $guzzleOptions;
    }

    /**
     * Send a synchronous HTTP request.
     *
     * @return ResponseInterface The HTTP response
     *
     * @throws RuntimeException If the request fails
     */
    protected function sendSync(): ResponseInterface
    {
        return $this->retryRequest(function (): ResponseInterface {
            try {
                $psrResponse = $this->getSyncClient()->request(
                    $this->options['method'],
                    $this->getFullUri(),
                    $this->preparedOptions ?? $this->options
                );

                return Response::createFromBase($psrResponse);
            } catch (GuzzleException $e) {
                throw new RuntimeException(
                    sprintf(
                        'Request %s %s failed: %s',
                        $this->options['method'],
                        $this->getFullUri(),
                        $e->getMessage()
                    ),
                    $e->getCode(),
                    $e
                );
            }
        });
    }

    /**
     * Send an asynchronous HTTP request.
     *
     * @return PromiseInterface The promise for the HTTP response
     */
    protected function sendAsync(): PromiseInterface
    {
        return async(function (): ResponseInterface {
            return $this->sendSync();
        });
    }
}
