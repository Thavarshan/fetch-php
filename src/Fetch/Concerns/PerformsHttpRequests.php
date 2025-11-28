<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Exceptions\RequestException as FetchRequestException;
use Fetch\Http\Response;
use Fetch\Interfaces\Response as ResponseInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Matrix\Exceptions\AsyncException;
use Psr\Http\Message\RequestInterface;
use React\Promise\PromiseInterface;

use function Matrix\Support\async;

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
        array $options = [],
    ): Response|PromiseInterface {
        $handler = static::create();
        $handler->withOptions($options);

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
        ContentType|string $contentType = ContentType::JSON,
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
        ContentType|string $contentType = ContentType::JSON,
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
        ContentType|string $contentType = ContentType::JSON,
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
        ContentType|string $contentType = ContentType::JSON,
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
        array $options = [],
    ): ResponseInterface|PromiseInterface {
        // Create a new handler with the combined options
        $handler = clone $this;
        $handler->withOptions($options);

        // Normalize method to string
        $methodStr = $method instanceof Method ? $method->value : strtoupper($method);

        // Store URI in handler options
        $handler->options['uri'] = $uri;
        $handler->options['method'] = $methodStr;

        // Build the full URI
        $fullUri = $handler->buildFullUri($uri);

        // Prepare Guzzle options
        $guzzleOptions = $handler->prepareGuzzleOptions();

        // Check for mock response first (if HandlesMocking trait is available)
        if (method_exists($handler, 'handleMockRequest')) {
            $mockResponse = $handler->handleMockRequest($methodStr, $fullUri, $guzzleOptions);
            if ($mockResponse !== null) {
                return $mockResponse;
            }
        }

        // Check for cached response (if ManagesCache trait is available)
        // Use handler options which includes cache config, not just guzzle options
        $cachedResult = null;
        if (method_exists($handler, 'getCachedResponse') && method_exists($handler, 'isCacheEnabled') && $handler->isCacheEnabled()) {
            $cachedResult = $handler->getCachedResponse($methodStr, $fullUri, $handler->options);
            if ($cachedResult['response'] !== null) {
                return $cachedResult['response'];
            }

            // Add conditional headers if we have a stale cache entry
            if ($cachedResult['cached'] !== null && method_exists($handler, 'addConditionalHeaders')) {
                $guzzleOptions = $handler->addConditionalHeaders($guzzleOptions, $cachedResult['cached']);
            }
        }

        // Start timing for logging
        $startTime = microtime(true);

        // Log the request if method exists
        if (method_exists($handler, 'logRequest')) {
            $handler->logRequest($methodStr, $fullUri, $guzzleOptions);
        }

        // Check if middleware is available and should be used
        if (method_exists($handler, 'hasMiddleware') && $handler->hasMiddleware()) {
            return $handler->executeWithMiddleware($methodStr, $fullUri, $guzzleOptions, $startTime);
        }

        // Send the request (async or sync) without middleware
        if ($handler->isAsync) {
            return $handler->executeAsyncRequest($methodStr, $fullUri, $guzzleOptions);
        } else {
            return $this->executeSyncRequestWithCache($methodStr, $fullUri, $guzzleOptions, $startTime, $cachedResult, $handler);
        }
    }

    /**
     * Execute a synchronous request with caching support.
     *
     * @param  string  $method  The HTTP method
     * @param  string  $uri  The full URI
     * @param  array<string, mixed>  $options  The Guzzle options
     * @param  float  $startTime  The request start time
     * @param  array<string, mixed>|null  $cachedResult  The cached result data
     * @param  mixed  $handler  The cloned handler instance with request-specific state
     * @return ResponseInterface The response
     */
    protected function executeSyncRequestWithCache(
        string $method,
        string $uri,
        array $options,
        float $startTime,
        ?array $cachedResult,
        mixed $handler
    ): ResponseInterface {
        try {
            $response = $handler->executeSyncRequest($method, $uri, $options, $startTime);

            // Handle 304 Not Modified response
            if ($response->getStatusCode() === 304 && $cachedResult !== null && isset($cachedResult['cached']) && method_exists($handler, 'handleNotModified')) {
                $response = $handler->handleNotModified($cachedResult['cached'], $response);
            }

            // Cache the response if caching is enabled
            // Use handler options which includes cache config
            if (method_exists($handler, 'cacheResponse') && method_exists($handler, 'isCacheEnabled') && $handler->isCacheEnabled()) {
                $handler->cacheResponse($method, $uri, $response, $handler->options);
            }

            return $response;
        } catch (\Throwable $e) {
            // Handle stale-if-error: serve stale response on error
            if ($cachedResult !== null && isset($cachedResult['cached']) && method_exists($handler, 'handleStaleIfError')) {
                $staleResponse = $handler->handleStaleIfError($cachedResult['cached']);
                if ($staleResponse !== null) {
                    return $staleResponse;
                }
            }

            throw $e;
        }
    }

    /**
     * Execute the request through the middleware pipeline.
     *
     * @param  string  $method  The HTTP method
     * @param  string  $uri  The full URI
     * @param  array<string, mixed>  $options  The Guzzle options
     * @param  float  $startTime  The request start time
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    protected function executeWithMiddleware(
        string $method,
        string $uri,
        array $options,
        float $startTime,
    ): ResponseInterface|PromiseInterface {
        // Create a PSR-7 request from the current state
        $body = null;
        if (isset($options['body'])) {
            $body = $options['body'];
        } elseif (isset($options['json'])) {
            $body = json_encode($options['json']);
        }

        $psrRequest = new GuzzleRequest(
            $method,
            $uri,
            $options['headers'] ?? [],
            $body
        );

        // Define the core handler that will be called after all middleware
        $coreHandler = function (RequestInterface $request) use ($options, $startTime): ResponseInterface|PromiseInterface {
            // Extract method and URI from the (potentially modified) request
            $method = $request->getMethod();
            $uri = (string) $request->getUri();

            // Merge any headers from the modified request back into options
            $headers = [];
            foreach ($request->getHeaders() as $name => $values) {
                $headers[$name] = implode(', ', $values);
            }
            $options['headers'] = array_merge($options['headers'] ?? [], $headers);

            // Handle body from modified request
            $body = $request->getBody();
            if ($body->getSize() > 0) {
                $body->rewind();
                $bodyContents = $body->getContents();
                $body->rewind();

                // Check if it's JSON
                $contentType = $request->getHeaderLine('Content-Type');
                if (str_contains($contentType, 'application/json')) {
                    $decoded = json_decode($bodyContents, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $options['json'] = $decoded;
                        unset($options['body']);
                    } else {
                        $options['body'] = $bodyContents;
                        unset($options['json']);
                    }
                } else {
                    $options['body'] = $bodyContents;
                    unset($options['json']);
                }
            }

            // Execute the actual request
            if ($this->isAsync) {
                return $this->executeAsyncRequest($method, $uri, $options);
            } else {
                return $this->executeSyncRequest($method, $uri, $options, $startTime);
            }
        };

        // Execute through the middleware pipeline
        return $this->getMiddlewarePipeline()->handle($psrRequest, $coreHandler);
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
        array $options = [],
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
     * Get the effective timeout for the request.
     *
     * @return int The timeout in seconds
     */
    public function getEffectiveTimeout(): int
    {
        // Next check options array
        if (isset($this->options['timeout']) && is_int($this->options['timeout'])) {
            return $this->options['timeout'];
        }

        // First check explicitly set timeout property
        if (isset($this->timeout) && is_int($this->timeout)) {
            return $this->timeout;
        }

        // Fall back to default
        return self::DEFAULT_TIMEOUT;
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
        array $options = [],
    ): ResponseInterface|PromiseInterface {
        // Skip if no body
        if ($body === null) {
            return $this->sendRequest($method, $uri, $options);
        }

        // Create a new handler instance with cloned options
        $handler = clone $this;

        // Merge options if provided
        if (! empty($options)) {
            $handler->withOptions($options);
        }

        // Configure the request body on the cloned handler
        $handler->configureRequestBody($body, $contentType);

        // Send the request using the configured handler
        return $handler->sendRequest($method, $uri);
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

        // Set timeout consistently
        if (isset($this->timeout)) {
            $guzzleOptions['timeout'] = $this->timeout;
        } elseif (isset($this->options['timeout'])) {
            $guzzleOptions['timeout'] = $this->options['timeout'];
        } else {
            $guzzleOptions['timeout'] = $this->getEffectiveTimeout();
        }

        // Ensure connect_timeout defaults sensibly if not provided
        if (! isset($guzzleOptions['connect_timeout'])) {
            $guzzleOptions['connect_timeout'] = $guzzleOptions['connect_timeout']
                ?? ($this->options['connect_timeout'] ?? $guzzleOptions['timeout']);
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
        float $startTime,
    ): ResponseInterface {
        // Start profiling if profiler is available
        $requestId = null;
        if (method_exists($this, 'startProfiling')) {
            $requestId = $this->startProfiling($method, $uri);
        }

        // Track memory for debugging
        $startMemory = memory_get_usage(true);

        return $this->retryRequest(function () use ($method, $uri, $options, $startTime, $requestId, $startMemory): ResponseInterface {
            try {
                // Record request sent event for profiling
                if ($requestId !== null && method_exists($this, 'recordProfilingEvent')) {
                    $this->recordProfilingEvent($requestId, 'request_sent');
                }

                // Send the request to Guzzle
                $psrResponse = $this->getHttpClient()->request($method, $uri, $options);

                // Record response received event for profiling
                if ($requestId !== null && method_exists($this, 'recordProfilingEvent')) {
                    $this->recordProfilingEvent($requestId, 'response_start');
                }

                // Calculate duration
                $duration = microtime(true) - $startTime;

                // Create our response object
                $response = Response::createFromBase($psrResponse);

                // End profiling
                if ($requestId !== null && method_exists($this, 'endProfiling')) {
                    $this->endProfiling($requestId, $response->getStatusCode());
                }

                // Create debug info if debug mode is enabled
                if (method_exists($this, 'isDebugEnabled') && $this->isDebugEnabled()) {
                    $memoryUsage = memory_get_usage(true) - $startMemory;
                    $timings = [
                        'total_time' => round($duration * 1000, 3),
                        'start_time' => $startTime,
                        'end_time' => microtime(true),
                    ];

                    if (method_exists($this, 'createDebugInfo')) {
                        $this->createDebugInfo($method, $uri, $options, $response, $timings, $memoryUsage);
                    }
                }

                // Trigger retry on configured retryable status codes
                if (in_array($response->getStatusCode(), $this->getRetryableStatusCodes(), true)) {
                    $psrRequest = new GuzzleRequest($method, $uri, $options['headers'] ?? []);

                    throw new FetchRequestException('Retryable status: '.$response->getStatusCode(), $psrRequest, $psrResponse);
                }

                // Log response if method exists
                if (method_exists($this, 'logResponse')) {
                    $this->logResponse($response, $duration);
                }

                return $response;
            } catch (GuzzleException $e) {
                // End profiling with error
                if ($requestId !== null && method_exists($this, 'endProfiling')) {
                    $this->endProfiling($requestId, null);
                }

                // Normalize to Fetch RequestException to participate in retry logic
                if ($e instanceof GuzzleRequestException) {
                    $req = $e->getRequest();
                    $res = $e->getResponse();

                    throw new FetchRequestException(sprintf('Request %s %s failed: %s', $method, $uri, $e->getMessage()), $req, $res, $e);
                }

                // Fallback when we don't get a Guzzle RequestException (no request available)
                $psrRequest = new GuzzleRequest($method, $uri, $options['headers'] ?? []);

                throw new FetchRequestException(sprintf('Request %s %s failed: %s', $method, $uri, $e->getMessage()), $psrRequest, null, $e);
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
        array $options,
    ): PromiseInterface {
        return async(function () use ($method, $uri, $options): ResponseInterface {
            $startTime = microtime(true);

            // Since this is in an async context, we can use try-catch for proper promise rejection
            try {
                // Execute the synchronous request inside the async function
                $response = $this->executeSyncRequest($method, $uri, $options, $startTime);

                return $response;
            } catch (\Throwable $e) {
                // Log the error without interfering with promise rejection
                if (isset($this->logger)) {
                    $this->logger->error('Async request failed', [
                        'method' => $method,
                        'uri' => $uri,
                        'error' => $e->getMessage(),
                        'exception_class' => get_class($e),
                    ]);
                }

                // Use withErrorContext to add request information to the error
                $contextMessage = "Request $method $uri failed";

                // Throw the exception - in the async context, this will properly reject the promise
                throw new AsyncException($contextMessage.': '.$e->getMessage(), $e->getCode(), $e /* Preserve the original exception as previous */);
            }
        });
    }
}
