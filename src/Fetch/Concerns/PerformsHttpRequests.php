<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Cache\CacheManager;
use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Exceptions\RequestException as FetchRequestException;
use Fetch\Http\Response;
use Fetch\Interfaces\Response as ResponseInterface;
use Fetch\Support\RequestContext;
use Fetch\Support\RequestOptions;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Matrix\Exceptions\AsyncException;
use React\Promise\PromiseInterface;
use RuntimeException;

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
     * This method is now stateless per-request: it builds an immutable RequestContext
     * from merged options and passes it through the execution stack without mutating
     * the handler's shared state. This makes the handler safe for concurrent usage.
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
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Normalize method to string
        $methodStr = $method instanceof Method ? $method->value : strtoupper($method);

        // Build immutable request context from handler defaults + per-request options
        $requestOptions = RequestOptions::merge(
            $this->options,
            ['async' => $this->isAsync],
            $options,
            ['method' => $methodStr, 'uri' => $uri],
        );

        RequestOptions::validate($requestOptions);

        $context = RequestContext::fromOptions($requestOptions);

        // Build the full URI using context, not handler state
        $fullUri = $this->buildFullUriFromContext($context);

        // Prepare Guzzle options from context
        $guzzleOptions = $context->toGuzzleOptions();

        // Start profiling once for this request path
        $requestId = $this->startProfiling($methodStr, $fullUri);

        // Check for mock response first (if HandlesMocking trait is available)
        if (method_exists($this, 'handleMockRequest')) {
            $mockResponse = $this->handleMockRequest($methodStr, $fullUri, $guzzleOptions);
            if ($mockResponse !== null) {
                $this->recordProfilingEvent($requestId, 'response_start');
                $this->endProfiling($requestId, $mockResponse->getStatusCode());

                $connectionStats = method_exists($this, 'getConnectionDebugStats')
                    ? $this->getConnectionDebugStats()
                    : [];

                $debugInfo = $this->captureDebugSnapshot($methodStr, $fullUri, $guzzleOptions, $mockResponse, $startTime, $startMemory, $connectionStats);

                // Attach debug info to response if available
                if ($debugInfo !== null && $mockResponse instanceof Response) {
                    $mockResponse->withDebugInfo($debugInfo);
                }

                return $mockResponse;
            }
        }

        // Check for cached response via CacheManager if available
        $cachedResult = null;
        $cacheManager = $this->getCacheManagerFromHandler($this);
        $requestOptionsWithAsync = array_merge($context->toArray(), ['async' => $context->isAsync()]);

        if ($cacheManager !== null) {
            $cachedResult = $cacheManager->getCachedResponse($methodStr, $fullUri, $requestOptionsWithAsync);
            if ($cachedResult['response'] !== null) {
                $this->recordProfilingEvent($requestId, 'response_start');
                $this->endProfiling($requestId, $cachedResult['response']->getStatusCode());

                $connectionStats = method_exists($this, 'getConnectionDebugStats')
                    ? $this->getConnectionDebugStats()
                    : [];

                $debugInfo = $this->captureDebugSnapshot($methodStr, $fullUri, $guzzleOptions, $cachedResult['response'], $startTime, $startMemory, $connectionStats);

                // Attach debug info to cached response if available
                if ($debugInfo !== null && $cachedResult['response'] instanceof Response) {
                    $cachedResult['response']->withDebugInfo($debugInfo);
                }

                return $cachedResult['response'];
            }

            // Add conditional headers if we have a stale cache entry
            if ($cachedResult['cached'] !== null) {
                $guzzleOptions = $cacheManager->addConditionalHeaders($guzzleOptions, $cachedResult['cached']);
            }
        }

        // Log the request if method exists
        if (method_exists($this, 'logRequest')) {
            $this->logRequest($methodStr, $fullUri, $guzzleOptions);
        }

        // Send the request (async or sync) based on context
        if ($context->isAsync()) {
            $promise = $this->executeAsyncRequest(
                $methodStr,
                $fullUri,
                $guzzleOptions,
                $startTime,
                $startMemory,
                $requestId,
                $context
            );

            return $promise
                ->otherwise(function (\Throwable $e) use ($methodStr, $fullUri) {
                    throw $this->withErrorContext($e, $methodStr, $fullUri);
                });
        }

        try {
            $response = $this->executeSyncRequestWithCache(
                $methodStr,
                $fullUri,
                $guzzleOptions,
                $requestOptionsWithAsync,
                $startTime,
                $startMemory,
                $cachedResult,
                $requestId,
                $context
            );

            return $response;
        } catch (\Throwable $e) {
            throw $this->withErrorContext($e, $methodStr, $fullUri);
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
        array $options = [],
    ): Response|PromiseInterface {
        $mergedOptions = RequestOptions::merge($this->options, $options);

        if ($body !== null) {
            $mergedOptions = $this->applyBodyOptions($mergedOptions, $body, $contentType);
        }

        return $this->sendRequest($method, $uri, $mergedOptions);
    }

    /**
     * Get the effective timeout for the request.
     *
     * Supports per-request timeout via RequestContext, with handler defaults as fallback.
     *
     * @param  RequestContext|null  $context  Optional request context for per-request override
     * @return int The timeout in seconds
     */
    public function getEffectiveTimeout(?RequestContext $context = null): int
    {
        // First check RequestContext for per-request override
        if ($context !== null) {
            return $context->getTimeout();
        }

        // Next check options array
        if (isset($this->options['timeout']) && is_int($this->options['timeout'])) {
            return $this->options['timeout'];
        }

        // Then check explicitly set timeout property
        if (isset($this->timeout) && is_int($this->timeout)) {
            return $this->timeout;
        }

        // Fall back to default
        return self::DEFAULT_TIMEOUT;
    }

    /**
     * Apply body-related options without mutating handler state.
     *
     * This is a pure function that takes options and body config, and returns
     * a new options array with body configuration applied. It does not mutate
     * any handler properties, making it safe for concurrent usage.
     *
     * @param  array<string, mixed>  $options  Base options array
     * @param  mixed  $body  The request body
     * @param  ContentType|string  $contentType  The content type
     * @return array<string, mixed> New options array with body applied
     */
    protected function applyBodyOptions(array $options, mixed $body, ContentType|string $contentType): array
    {
        if ($body === null) {
            return $options;
        }

        // Normalize content type
        $contentTypeEnum = ContentType::normalizeContentType($contentType);
        $contentTypeValue = $contentTypeEnum instanceof ContentType
            ? $contentTypeEnum->value
            : (string) $contentTypeEnum;

        // Initialize headers if not set
        if (! isset($options['headers'])) {
            $options['headers'] = [];
        }

        // Clear any existing body options to prevent conflicts
        unset($options['body'], $options['json'], $options['form_params'], $options['multipart']);

        if (is_array($body)) {
            $options = match ($contentTypeEnum) {
                ContentType::JSON => $this->applyJsonBodyPure($options, $body),
                ContentType::FORM_URLENCODED => $this->applyFormParamsPure($options, $body),
                ContentType::MULTIPART => $this->applyMultipartPure($options, $body),
                default => $this->applyGenericArrayBodyPure($options, $body, $contentTypeValue),
            };
        } else {
            // String body
            $options['body'] = $body;
            if (! isset($options['headers']['Content-Type'])) {
                $options['headers']['Content-Type'] = $contentTypeValue;
            }
        }

        return $options;
    }

    /**
     * Get the CacheManager from a handler instance if available.
     *
     * Uses the CacheableHandler interface getter to properly encapsulate access.
     * Note: Cloning a handler shares the CacheManager instance (intentional - it's stateless per-request).
     *
     * @param  object  $handler  The handler instance to check
     * @return CacheManager|null The cache manager or null if not available
     */
    protected function getCacheManagerFromHandler(object $handler): ?CacheManager
    {
        if (method_exists($handler, 'getCacheManager')) {
            return $handler->getCacheManager();
        }

        return null;
    }

    /**
     * Execute a synchronous request with caching support.
     *
     * @param  string  $method  The HTTP method
     * @param  string  $uri  The full URI
     * @param  array<string, mixed>  $options  The Guzzle options
     * @param  array<string, mixed>  $requestOptions  The request options with async flag
     * @param  float  $startTime  The request start time
     * @param  int  $startMemory  The starting memory usage
     * @param  array<string, mixed>|null  $cachedResult  The cached result data
     * @param  string|null  $requestId  The request ID for profiling
     * @param  RequestContext  $context  The request context
     * @return ResponseInterface The response
     */
    protected function executeSyncRequestWithCache(
        string $method,
        string $uri,
        array $options,
        array $requestOptions,
        float $startTime,
        int $startMemory,
        ?array $cachedResult,
        ?string $requestId = null,
        ?RequestContext $context = null,
    ): ResponseInterface {
        $cacheManager = $this->getCacheManagerFromHandler($this);

        try {
            $response = $this->executeSyncRequest($method, $uri, $options, $startTime, $startMemory, $requestId, $context);

            // Handle 304 Not Modified response
            if ($response->getStatusCode() === 304 && $cachedResult !== null && isset($cachedResult['cached']) && $cacheManager !== null) {
                $response = $cacheManager->handleNotModified($cachedResult['cached'], $response);
            }

            // Cache the response if caching is enabled
            if ($cacheManager !== null) {
                $cacheManager->cacheResponse($method, $uri, $response, $requestOptions);
            }

            $connectionStats = method_exists($this, 'getConnectionDebugStats')
                ? $this->getConnectionDebugStats()
                : [];

            $debugInfo = $this->captureDebugSnapshot($method, $uri, $options, $response, $startTime, $startMemory, $connectionStats);

            // Attach debug info to response if available
            if ($debugInfo !== null && $response instanceof Response) {
                $response->withDebugInfo($debugInfo);
            }

            // Finalize profiling for the successful response
            $this->recordProfilingEvent($requestId, 'response_start');
            $this->endProfiling($requestId, $response->getStatusCode());

            return $response;
        } catch (\Throwable $e) {
            // Handle stale-if-error: serve stale response on error
            if ($cachedResult !== null && isset($cachedResult['cached']) && $cacheManager !== null) {
                $staleResponse = $cacheManager->handleStaleIfError($cachedResult['cached'], $method, $uri);
                if ($staleResponse !== null) {
                    $this->recordProfilingEvent($requestId, 'response_start');
                    $this->endProfiling($requestId, $staleResponse->getStatusCode());

                    $connectionStats = method_exists($this, 'getConnectionDebugStats')
                        ? $this->getConnectionDebugStats()
                        : [];

                    $debugInfo = $this->captureDebugSnapshot($method, $uri, $options, $staleResponse, $startTime, $startMemory, $connectionStats);

                    // Attach debug info to stale response if available
                    if ($debugInfo !== null && $staleResponse instanceof Response) {
                        $staleResponse->withDebugInfo($debugInfo);
                    }

                    return $staleResponse;
                }
            }

            throw $e;
        }
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

        $mergedOptions = RequestOptions::merge($this->options, $options);
        $bodyOptions = $this->applyBodyOptions($mergedOptions, $body, $contentType);

        return $this->sendRequest($method, $uri, $bodyOptions);
    }

    /**
     * Prepare options for Guzzle using the request context.
     *
     * This method now prefers the explicit RequestContext parameter for stateless
     * operation. When context is not provided, it falls back to building a context
     * from handler options (for backwards compatibility).
     *
     * @param  RequestContext|null  $context  Optional explicit context (preferred)
     * @return array<string, mixed>
     */
    protected function prepareGuzzleOptions(?RequestContext $context = null): array
    {
        // Prefer explicit context over handler state
        $context = $context ?? RequestContext::fromOptions($this->options);

        return $context->toGuzzleOptions();
    }

    /**
     * Execute a synchronous HTTP request.
     *
     * @param  string  $method  The HTTP method
     * @param  string  $uri  The full URI
     * @param  array<string, mixed>  $options  The Guzzle options
     * @param  float  $startTime  The request start time
     * @param  int  $startMemory  The starting memory usage
     * @param  string|null  $requestId  The request ID for profiling
     * @param  RequestContext|null  $context  The request context
     * @return ResponseInterface The response
     */
    protected function executeSyncRequest(
        string $method,
        string $uri,
        array $options,
        float $startTime,
        int $startMemory,
        ?string $requestId = null,
        ?RequestContext $context = null,
    ): ResponseInterface {
        // Start profiling if not already started
        if ($requestId === null && method_exists($this, 'startProfiling')) {
            $requestId = $this->startProfiling($method, $uri);
        }

        return $this->retryRequest($context, function () use ($method, $uri, $options, $startTime, $requestId, $startMemory, $context): ResponseInterface {
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

                $connectionStats = method_exists($this, 'getConnectionDebugStats')
                    ? $this->getConnectionDebugStats()
                    : [];

                $debugInfo = $this->captureDebugSnapshot($method, $uri, $options, $response, $startTime, $startMemory, $connectionStats);

                // Attach debug info to response if available
                if ($debugInfo !== null) {
                    $response->withDebugInfo($debugInfo);
                }

                // Trigger retry on configured retryable status codes
                // IMPORTANT: Check context first for per-request retry status codes, fall back to handler state
                $retryableStatusCodes = $context !== null && $context->getRetryableStatusCodes() !== []
                    ? $context->getRetryableStatusCodes()
                    : $this->getRetryableStatusCodes();

                if (in_array($response->getStatusCode(), $retryableStatusCodes, true)) {
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
     * @param  float  $startTime  The request start time
     * @param  int  $startMemory  The starting memory usage
     * @param  string|null  $requestId  The request ID for profiling
     * @param  RequestContext|null  $context  The request context
     * @return PromiseInterface A promise that resolves with the response
     */
    protected function executeAsyncRequest(
        string $method,
        string $uri,
        array $options,
        float $startTime,
        int $startMemory,
        ?string $requestId = null,
        ?RequestContext $context = null,
    ): PromiseInterface {
        // Capture context in closure to ensure per-request retry config is used
        return async(function () use ($method, $uri, $options, $startTime, $startMemory, $requestId, $context): ResponseInterface {
            // Since this is in an async context, we can use try-catch for proper promise rejection
            try {
                // Execute the synchronous request inside the async function
                // IMPORTANT: Pass context to ensure per-request retry configuration is respected
                $response = $this->executeSyncRequest($method, $uri, $options, $startTime, $startMemory, $requestId, $context);

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

                $wrapped = $this->withErrorContext($e, $method, $uri);

                // Throw the exception - in the async context, this will properly reject the promise
                throw new AsyncException($wrapped->getMessage(), $wrapped->getCode(), $wrapped /* Preserve the original exception as previous */);
            }
        });
    }

    /**
     * Add request context to exceptions while preserving the original chain.
     */
    protected function withErrorContext(\Throwable $e, string $method, string $uri): \Throwable
    {
        $contextMessage = sprintf('Request %s %s failed', strtoupper($method), $uri);

        // Avoid double-wrapping if the exception already contains context
        if (str_contains($e->getMessage(), $contextMessage)) {
            return $e;
        }

        return new RuntimeException($contextMessage.': '.$e->getMessage(), (int) $e->getCode(), $e);
    }

    /**
     * Apply JSON body options without mutation.
     *
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function applyJsonBodyPure(array $options, array $body): array
    {
        $options['json'] = $body;
        if (! isset($options['headers']['Content-Type'])) {
            $options['headers']['Content-Type'] = ContentType::JSON->value;
        }

        return $options;
    }

    /**
     * Apply form params body options without mutation.
     *
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function applyFormParamsPure(array $options, array $body): array
    {
        $options['form_params'] = $body;
        if (! isset($options['headers']['Content-Type'])) {
            $options['headers']['Content-Type'] = ContentType::FORM_URLENCODED->value;
        }

        return $options;
    }

    /**
     * Apply multipart body options without mutation.
     *
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function applyMultipartPure(array $options, array $body): array
    {
        // Normalize multipart if needed
        $multipart = RequestOptions::normalizeMultipart($body);
        $options['multipart'] = $multipart;
        // Remove Content-Type header as Guzzle sets it with boundary
        unset($options['headers']['Content-Type']);

        return $options;
    }

    /**
     * Apply generic array body as JSON string without mutation.
     *
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function applyGenericArrayBodyPure(array $options, array $body, string $contentType): array
    {
        $options['body'] = json_encode($body);
        if (! isset($options['headers']['Content-Type'])) {
            $options['headers']['Content-Type'] = $contentType;
        }

        return $options;
    }
}
