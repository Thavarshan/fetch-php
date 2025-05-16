<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Http\Response;
use Fetch\Interfaces\Response as ResponseInterface;
use GuzzleHttp\Exception\GuzzleException;
use React\Promise\PromiseInterface;
use RuntimeException;

use function async;

trait PerformsHttpRequests
{
    /**
     * Handles an HTTP request with the given method, URI, and options.
     *
     * @param  string  $method  The HTTP method to use
     * @param  string  $uri  The URI to request
     * @param  array<string, mixed>  $options  Additional options for the request
     * @return Response|PromiseInterface Response or promise
     */
    public static function handle(
        string $method,
        string $uri,
        array $options = []
    ): Response|PromiseInterface {
        $handler = new static;
        $handler->applyOptions($options);

        return $handler->sendRequest($method, $uri);
    }

    /**
     * Send a HEAD request.
     *
     * @param  string  $uri  The URI to request
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function head(string $uri): ResponseInterface|PromiseInterface
    {
        return $this->sendRequest(Method::HEAD, $uri);
    }

    /**
     * Send a GET request.
     *
     * @param  string  $uri  The URI to request
     * @param  array<string, mixed>  $queryParams  Optional query parameters
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function get(string $uri, array $queryParams = []): ResponseInterface|PromiseInterface
    {
        $options = [];
        if (! empty($queryParams)) {
            $options['query'] = $queryParams;
        }

        return $this->sendRequest(Method::GET, $uri, $options);
    }

    /**
     * Send a POST request.
     *
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  The request body
     * @param  ContentType|string  $contentType  The content type of the request
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function post(
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = ContentType::JSON
    ): ResponseInterface|PromiseInterface {
        return $this->sendRequestWithBody(Method::POST, $uri, $body, $contentType);
    }

    /**
     * Send a PUT request.
     *
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  The request body
     * @param  ContentType|string  $contentType  The content type of the request
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function put(
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = ContentType::JSON
    ): ResponseInterface|PromiseInterface {
        return $this->sendRequestWithBody(Method::PUT, $uri, $body, $contentType);
    }

    /**
     * Send a PATCH request.
     *
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  The request body
     * @param  ContentType|string  $contentType  The content type of the request
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function patch(
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = ContentType::JSON
    ): ResponseInterface|PromiseInterface {
        return $this->sendRequestWithBody(Method::PATCH, $uri, $body, $contentType);
    }

    /**
     * Send a DELETE request.
     *
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  Optional request body
     * @param  ContentType|string  $contentType  The content type of the request
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function delete(
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = ContentType::JSON
    ): ResponseInterface|PromiseInterface {
        return $this->sendRequestWithBody(Method::DELETE, $uri, $body, $contentType);
    }

    /**
     * Send an OPTIONS request.
     *
     * @param  string  $uri  The URI to request
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function options(string $uri): ResponseInterface|PromiseInterface
    {
        return $this->sendRequest(Method::OPTIONS, $uri);
    }

    /**
     * Send an HTTP request.
     *
     * @param  Method|string  $method  The HTTP method
     * @param  string  $uri  The URI to request
     * @param  array<string, mixed>  $options  Additional options
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function sendRequest(
        Method|string $method,
        string $uri,
        array $options = []
    ): ResponseInterface|PromiseInterface {
        // Store original options for later restoration
        $originalOptions = $this->options;

        try {
            // Normalize method to string
            $methodStr = $method instanceof Method ? $method->value : strtoupper($method);

            // Merge options
            if (! empty($options)) {
                $this->withOptions($options);
            }

            // Store URI in options for building full URI
            $this->options['uri'] = $uri;
            $this->options['method'] = $methodStr;

            // Build the full URI
            $fullUri = $this->buildFullUri($uri);

            // Prepare Guzzle options
            $guzzleOptions = $this->prepareGuzzleOptions();

            // Start timing for logging
            $startTime = microtime(true);

            // Log the request if method exists
            if (method_exists($this, 'logRequest')) {
                $this->logRequest($methodStr, $fullUri, $guzzleOptions);
            }

            // Send the request (async or sync)
            if ($this->isAsync) {
                return $this->executeAsyncRequest($methodStr, $fullUri, $guzzleOptions);
            } else {
                return $this->executeSyncRequest($methodStr, $fullUri, $guzzleOptions, $startTime);
            }
        } finally {
            // Always restore original options
            $this->options = $originalOptions;
        }
    }

    /**
     * Sends an HTTP request with the specified parameters.
     *
     * @param  string|Method  $method  HTTP method (e.g., GET, POST)
     * @param  string  $uri  URI to send the request to
     * @param  mixed  $body  Request body
     * @param  string|ContentType  $contentType  Content type of the request
     * @param  array<string, mixed>  $options  Additional request options
     * @return Response|PromiseInterface Response or promise
     */
    public function request(
        string|Method $method,
        string $uri,
        mixed $body = null,
        string|ContentType $contentType = ContentType::JSON,
        array $options = []
    ): Response|PromiseInterface {
        // Normalize method to string
        $methodStr = $method instanceof Method ? $method->value : strtoupper($method);

        // Apply any additional options
        if (! empty($options)) {
            $this->withOptions($options);
        }

        // Configure request body if provided
        if ($body !== null) {
            $this->configureRequestBody($body, $contentType);
        }

        // Send the request using our unified method
        return $this->sendRequest($methodStr, $uri);
    }

    /**
     * Send an HTTP request with a body.
     *
     * @param  Method|string  $method  The HTTP method
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  The request body
     * @param  ContentType|string  $contentType  The content type
     * @param  array<string, mixed>  $options  Additional options
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    protected function sendRequestWithBody(
        Method|string $method,
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = ContentType::JSON,
        array $options = []
    ): ResponseInterface|PromiseInterface {
        // Skip if no body
        if ($body === null) {
            return $this->sendRequest($method, $uri, $options);
        }

        // Store original options for later restoration
        $originalOptions = $this->options;

        try {
            // Merge options
            if (! empty($options)) {
                $this->withOptions($options);
            }

            // Configure the request body
            $this->configureRequestBody($body, $contentType);

            // Send the request
            return $this->sendRequest($method, $uri);
        } finally {
            // Always restore original options
            $this->options = $originalOptions;
        }
    }

    /**
     * Prepare options for Guzzle.
     *
     * @return array<string, mixed> Options ready for Guzzle
     */
    protected function prepareGuzzleOptions(): array
    {
        $guzzleOptions = [];

        // Standard Guzzle options to include
        $standardOptions = [
            'headers', 'json', 'form_params', 'multipart', 'body',
            'query', 'auth', 'verify', 'proxy', 'cookies', 'allow_redirects',
            'cert', 'ssl_key', 'stream', 'connect_timeout', 'read_timeout',
            'debug', 'sink', 'version', 'decode_content',
        ];

        // Copy standard options if set
        foreach ($standardOptions as $option) {
            if (isset($this->options[$option])) {
                $guzzleOptions[$option] = $this->options[$option];
            }
        }

        // Set timeout
        if (isset($this->timeout)) {
            $guzzleOptions['timeout'] = $this->timeout;
        } elseif (isset($this->options['timeout'])) {
            $guzzleOptions['timeout'] = $this->options['timeout'];
        } else {
            $guzzleOptions['timeout'] = self::DEFAULT_TIMEOUT;
        }

        return $guzzleOptions;
    }

    /**
     * Execute a synchronous HTTP request.
     *
     * @param  string  $method  The HTTP method
     * @param  string  $uri  The full URI
     * @param  array<string, mixed>  $options  The Guzzle options
     * @param  float  $startTime  The request start time
     * @return ResponseInterface The response
     */
    protected function executeSyncRequest(
        string $method,
        string $uri,
        array $options,
        float $startTime
    ): ResponseInterface {
        return $this->retryRequest(function () use ($method, $uri, $options, $startTime): ResponseInterface {
            try {
                // Send the request to Guzzle
                $psrResponse = $this->getHttpClient()->request($method, $uri, $options);

                // Calculate duration
                $duration = microtime(true) - $startTime;

                // Create our response object
                $response = Response::createFromBase($psrResponse);

                // Log response if method exists
                if (method_exists($this, 'logResponse')) {
                    $this->logResponse($response, $duration);
                }

                return $response;
            } catch (GuzzleException $e) {
                throw new RuntimeException(
                    sprintf(
                        'Request %s %s failed: %s',
                        $method,
                        $uri,
                        $e->getMessage()
                    ),
                    $e->getCode(),
                    $e
                );
            }
        });
    }

    /**
     * Execute an asynchronous HTTP request.
     *
     * @param  string  $method  The HTTP method
     * @param  string  $uri  The full URI
     * @param  array<string, mixed>  $options  The Guzzle options
     * @return PromiseInterface A promise that resolves with the response
     */
    protected function executeAsyncRequest(
        string $method,
        string $uri,
        array $options
    ): PromiseInterface {
        return async(function () use ($method, $uri, $options): ResponseInterface {
            $startTime = microtime(true);
            try {
                return $this->executeSyncRequest($method, $uri, $options, $startTime);
            } catch (\Throwable $e) {
                // Log the error if possible
                if (method_exists($this, 'logger')) {
                    $this->logger->error('Async request failed', [
                        'method' => $method,
                        'uri' => $uri,
                        'error' => $e->getMessage(),
                    ]);
                }
                throw $e; // Re-throw to maintain promise rejection
            }
        });
    }
}
