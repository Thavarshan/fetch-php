<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Async\PromiseManager;
use Fetch\Cache\CacheInterface;
use Fetch\Cache\CacheManager;
use Fetch\Concerns\ConfiguresRequests;
use Fetch\Concerns\HandlesMocking;
use Fetch\Concerns\HandlesUris;
use Fetch\Concerns\ManagesConnectionPool;
use Fetch\Concerns\ManagesDebugAndProfiling;
use Fetch\Concerns\ManagesEvents;
use Fetch\Concerns\ManagesMiddleware;
use Fetch\Concerns\ManagesRetries;
use Fetch\Concerns\PerformsHttpRequests;
use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Events\RedirectEvent;
use Fetch\Interfaces\AsyncTransport;
use Fetch\Interfaces\ClientHandler as ClientHandlerInterface;
use Fetch\Interfaces\Response as ResponseContract;
use Fetch\Pool\ConnectionPoolManager;
use Fetch\Support\Defaults;
use Fetch\Support\GlobalServices;
use Fetch\Support\RequestContext;
use Fetch\Support\RequestOptions as FetchRequestOptions;
use Fetch\Support\RetryDefaults;
use Fetch\Validation\ValidationManager;
use Fetch\Validation\ValidationPolicy;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Handler\Proxy;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\Promise\PromiseInterface;

class ClientHandler implements ClientHandlerInterface
{
    use ConfiguresRequests;
    use HandlesMocking;
    use HandlesUris;
    use ManagesConnectionPool;
    use ManagesDebugAndProfiling;
    use ManagesEvents;
    use ManagesMiddleware;
    use ManagesRetries;
    use PerformsHttpRequests;

    /**
     * Legacy compatibility storage. Runtime defaults are owned by GlobalServices.
     *
     * @var array<string, mixed>
     *
     * @deprecated Use GlobalServices::getDefaultOptions() instead
     */
    protected static array $defaultOptions = [
        'method' => Defaults::HTTP_METHOD->value,
        'headers' => [],
    ];

    /**
     * Default HTTP method.
     *
     * @deprecated Use Defaults::HTTP_METHOD instead
     */
    public const DEFAULT_HTTP_METHOD = Defaults::HTTP_METHOD->value;

    /**
     * Default timeout for requests in seconds.
     *
     * @deprecated Use Defaults::TIMEOUT instead
     */
    public const DEFAULT_TIMEOUT = Defaults::TIMEOUT;

    /**
     * Default number of retries.
     *
     * @deprecated Use RetryDefaults::MAX_RETRIES instead
     */
    public const DEFAULT_RETRIES = RetryDefaults::MAX_RETRIES;

    /**
     * Default delay between retries in milliseconds.
     *
     * @deprecated Use RetryDefaults::RETRY_DELAY instead
     */
    public const DEFAULT_RETRY_DELAY = RetryDefaults::RETRY_DELAY;

    /**
     * Whether the request should be asynchronous.
     */
    protected bool $isAsync = false;

    /**
     * Logger instance.
     */
    protected LoggerInterface $logger;

    /**
     * Log level used for request/response traces.
     */
    protected string $logLevel = 'debug';

    /**
     * Cache manager for this handler.
     */
    protected ?CacheManager $cacheManager = null;

    private ValidationManager $validationManager;

    private ValidationPolicy $validationPolicy;

    private PromiseManager $promiseManager;

    private ConnectionPoolManager $connectionPoolManager;

    private RequestBuilder $requestBuilder;

    private ?AsyncTransport $asyncTransport;

    private ?CurlMultiHandler $curlMultiHandler = null;

    /**
     * ClientHandler constructor.
     *
     * @param  ClientInterface|null  $httpClient  The HTTP client
     * @param  array<string, mixed>  $options  The options for the request
     * @param  int|null  $timeout  Timeout for the request in seconds
     * @param  int|null  $maxRetries  Number of retries for the request
     * @param  int|null  $retryDelay  Delay between retries in milliseconds
     * @param  bool  $isAsync  Whether the request is asynchronous
     * @param  LoggerInterface|null  $logger  Logger for request/response details
     */
    public function __construct(
        protected ?ClientInterface $httpClient = null,
        protected array $options = [],
        protected ?int $timeout = null,
        ?int $maxRetries = null,
        ?int $retryDelay = null,
        bool $isAsync = false,
        ?LoggerInterface $logger = null,
        ?CacheManager $cacheManager = null,
        ?ValidationManager $validationManager = null,
        ?ValidationPolicy $validationPolicy = null,
        ?AsyncTransport $asyncTransport = null,
        ?PromiseManager $promiseManager = null,
        ?ConnectionPoolManager $connectionPoolManager = null,
        ?RequestBuilder $requestBuilder = null,
    ) {
        $this->logger = $logger ?? new NullLogger;
        $this->isAsync = $isAsync;
        $this->maxRetries = $maxRetries ?? self::DEFAULT_RETRIES;
        $this->retryDelay = $retryDelay ?? self::DEFAULT_RETRY_DELAY;
        $this->cacheManager = $cacheManager;
        $this->validationManager = $validationManager ?? new ValidationManager;
        $this->validationPolicy = $validationPolicy ?? new ValidationPolicy;
        $this->promiseManager = $promiseManager ?? new PromiseManager;
        $this->connectionPoolManager = $connectionPoolManager ?? new ConnectionPoolManager;
        $this->requestBuilder = $requestBuilder ?? new RequestBuilder;
        $this->asyncTransport = $asyncTransport;

        // Initialize with default options using centralized merging
        $this->options = FetchRequestOptions::merge(
            self::getDefaultOptions(),
            $this->options
        );

        // Set the timeout in options
        if ($this->timeout !== null) {
            $this->options['timeout'] = $this->timeout;
        } else {
            $this->timeout = $this->options['timeout'] ?? self::DEFAULT_TIMEOUT;
            $this->options['timeout'] = $this->timeout;
        }
    }

    /**
     * Create a new client handler with factory defaults.
     *
     * @return static New client handler instance
     */
    public static function create(): static
    {
        static::initializePool();

        return new static;
    }

    /**
     * Create a client handler with preconfigured base URI.
     *
     * @param  string  $baseUri  Base URI for all requests
     * @return static New client handler instance
     *
     * @throws InvalidArgumentException If the base URI is invalid
     */
    public static function createWithBaseUri(string $baseUri): static
    {
        static::initializePool();

        $instance = new static;
        $instance->baseUri($baseUri);

        return $instance;
    }

    /**
     * Create a client handler with a custom HTTP client.
     *
     * @param  ClientInterface  $client  Custom HTTP client
     * @return static New client handler instance
     */
    public static function createWithClient(ClientInterface $client, ?AsyncTransport $asyncTransport = null): static
    {
        static::initializePool();

        return new static(httpClient: $client, asyncTransport: $asyncTransport);
    }

    /**
     * Get the default options for the request.
     *
     * Combines factory defaults with global defaults from GlobalServices.
     *
     * @return array<string, mixed> Default options
     */
    public static function getDefaultOptions(): array
    {
        // GlobalServices::getDefaultOptions() already merges factory defaults
        // with any custom defaults set via setDefaultOptions()
        return GlobalServices::getDefaultOptions();
    }

    /**
     * Set the default options for all instances.
     *
     * Updates both legacy static property and GlobalServices.
     *
     * @param  array<string, mixed>  $options  Default options
     */
    public static function setDefaultOptions(array $options): void
    {
        GlobalServices::setDefaultOptions($options);
    }

    /**
     * Reset default options to factory defaults.
     *
     * This is called by GlobalServices::reset() to ensure complete test isolation.
     *
     * @internal Used by GlobalServices for test isolation
     */
    public static function resetDefaultOptions(): void
    {
        self::$defaultOptions = [
            'method' => Method::GET->value,
            'headers' => [],
        ];
    }

    /**
     * Create a new mock response for testing.
     *
     * @param  int  $statusCode  HTTP status code
     * @param  array<string, string|string[]>  $headers  Response headers
     * @param  string|null  $body  Response body
     * @param  string  $version  HTTP protocol version
     * @param  string|null  $reason  Reason phrase
     * @return Response Mock response
     */
    public static function createMockResponse(
        int $statusCode = 200,
        array $headers = [],
        ?string $body = null,
        string $version = '1.1',
        ?string $reason = null,
    ): Response {
        return new Response($statusCode, $headers, $body ?? '', $version, $reason);
    }

    /**
     * Create a JSON response for testing.
     *
     * @param  array<mixed>|object  $data  JSON data
     * @param  int  $statusCode  HTTP status code
     * @param  array<string, string|string[]>  $headers  Additional headers
     * @return Response Mock JSON response
     */
    public static function createJsonResponse(
        array|object $data,
        int $statusCode = 200,
        array $headers = [],
    ): Response {
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        $headers = array_merge(
            ['Content-Type' => ContentType::JSON->value],
            $headers
        );

        return self::createMockResponse($statusCode, $headers, $jsonData);
    }

    /** Set whether requests on this handler use asynchronous transport. */
    public function async(?bool $async = true): ClientHandlerInterface
    {
        $this->isAsync = $async ?? true;

        return $this;
    }

    public function isAsync(): bool
    {
        return $this->isAsync;
    }

    public function wrapAsync(callable $callable): PromiseInterface
    {
        return $this->promiseManager->wrapAsync($callable);
    }

    public function awaitPromise(PromiseInterface $promise, ?float $timeout = null): mixed
    {
        return $this->promiseManager->awaitPromise($promise, $timeout);
    }

    public function all(array $promises): PromiseInterface
    {
        return $this->promiseManager->all($promises);
    }

    public function race(array $promises): PromiseInterface
    {
        return $this->promiseManager->race($promises);
    }

    public function any(array $promises): PromiseInterface
    {
        return $this->promiseManager->any($promises);
    }

    public function sequence(array $callables): PromiseInterface
    {
        return $this->promiseManager->sequence($callables);
    }

    public function then(callable $onFulfilled, ?callable $onRejected = null): PromiseInterface
    {
        $this->async();

        return $this->sendAsync()->then($onFulfilled, $onRejected);
    }

    public function catch(callable $onRejected): PromiseInterface
    {
        $this->async();

        return $this->sendAsync()->catch($onRejected);
    }

    public function finally(callable $onFinally): PromiseInterface
    {
        $this->async();

        return $this->sendAsync()->finally($onFinally);
    }

    public function resolve(mixed $value): PromiseInterface
    {
        return $this->promiseManager->resolve($value);
    }

    public function reject(mixed $reason): PromiseInterface
    {
        return $this->promiseManager->reject($reason);
    }

    public function map(array $items, callable $callback, int $concurrency = 5): PromiseInterface
    {
        return $this->promiseManager->map($items, $callback, $concurrency);
    }

    /**
     * Get the HTTP client.
     *
     * @return ClientInterface The HTTP client
     */
    public function getHttpClient(): ClientInterface
    {
        if (! $this->httpClient) {
            $clientOptions = [
                RequestOptions::CONNECT_TIMEOUT => $this->options['timeout'] ?? self::DEFAULT_TIMEOUT,
                RequestOptions::HTTP_ERRORS => false, // We'll handle HTTP errors ourselves
            ];

            if (extension_loaded('curl')) {
                $curlMultiHandler = new CurlMultiHandler(['select_timeout' => 0.001]);
                $this->curlMultiHandler = $curlMultiHandler;
                $clientOptions['handler'] = HandlerStack::create(
                    Proxy::wrapStreaming($curlMultiHandler, new StreamHandler),
                );
            }

            $this->httpClient = new GuzzleClient($clientOptions);
            $this->asyncTransport ??= new GuzzleAsyncTransport($this->httpClient, $this->curlMultiHandler);
        }

        return $this->httpClient;
    }

    /**
     * Set the HTTP client.
     *
     * @param  ClientInterface  $client  The HTTP client
     * @return $this
     */
    public function setHttpClient(ClientInterface $client, ?AsyncTransport $asyncTransport = null): self
    {
        $this->httpClient = $client;
        $this->asyncTransport = $asyncTransport;
        $this->curlMultiHandler = null;

        return $this;
    }

    public function setAsyncTransport(AsyncTransport $asyncTransport): self
    {
        $this->asyncTransport = $asyncTransport;

        return $this;
    }

    /** Configure a JSON Schema for outgoing JSON request bodies. */
    public function withRequestSchema(mixed $schema): static
    {
        $this->validationPolicy = new ValidationPolicy(
            requestSchema: $schema,
            responseSchema: $this->validationPolicy->responseSchema,
            openApiSpec: $this->validationPolicy->openApiSpec,
            validateContracts: $this->validationPolicy->validateContracts,
        );

        return $this;
    }

    /** Configure a JSON Schema for JSON response bodies. */
    public function withResponseSchema(mixed $schema): static
    {
        $this->validationPolicy = new ValidationPolicy(
            requestSchema: $this->validationPolicy->requestSchema,
            responseSchema: $schema,
            openApiSpec: $this->validationPolicy->openApiSpec,
            validateContracts: $this->validationPolicy->validateContracts,
        );

        return $this;
    }

    /** Configure an OpenAPI 3.0.x JSON or YAML file. */
    public function withOpenApiSpec(string $specPath): static
    {
        $this->validationPolicy = new ValidationPolicy(
            requestSchema: $this->validationPolicy->requestSchema,
            responseSchema: $this->validationPolicy->responseSchema,
            openApiSpec: $specPath,
            validateContracts: $this->validationPolicy->validateContracts,
        );

        return $this;
    }

    /** Enable or disable request and response contract validation. */
    public function validateContracts(bool $validate = true): static
    {
        $this->validationPolicy = new ValidationPolicy(
            requestSchema: $this->validationPolicy->requestSchema,
            responseSchema: $this->validationPolicy->responseSchema,
            openApiSpec: $this->validationPolicy->openApiSpec,
            validateContracts: $validate,
        );

        return $this;
    }

    public function getValidationManager(): ValidationManager
    {
        return $this->validationManager;
    }

    public function getValidationPolicy(?RequestContext $context = null): ValidationPolicy
    {
        $perRequest = $context?->getOption('validation', []);

        return is_array($perRequest) ? $this->validationPolicy->withOptions($perRequest) : $this->validationPolicy;
    }

    /**
     * Get the current request options.
     *
     * @return array<string, mixed> Current options
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get the request headers.
     *
     * @return array<string, mixed> Current headers
     */
    public function getHeaders(): array
    {
        return $this->options['headers'] ?? [];
    }

    /**
     * Enable caching with optional configuration.
     *
     * @param  array<string, mixed>  $options  Cache options
     */
    public function withCache(?CacheInterface $cache = null, array $options = []): self
    {
        $this->cacheManager = new CacheManager(
            cache: $cache,
            options: array_merge(['enabled' => true], $options),
            logger: $this->logger,
            logLevel: $this->logLevel
        );

        return $this;
    }

    /**
     * Disable caching.
     */
    public function withoutCache(): self
    {
        $this->cacheManager = null;

        return $this;
    }

    /**
     * Get the cache instance.
     */
    public function getCache(): ?CacheInterface
    {
        return $this->cacheManager?->getCache();
    }

    /**
     * Check if caching is enabled.
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheManager?->isEnabled() ?? false;
    }

    /**
     * Get the cache manager.
     */
    public function getCacheManager(): ?CacheManager
    {
        return $this->cacheManager;
    }

    /**
     * Check if the request has a specific header.
     *
     * @param  string  $header  Header name
     * @return bool Whether the header exists
     */
    public function hasHeader(string $header): bool
    {
        return isset($this->options['headers'][$header]);
    }

    /**
     * Check if the request has a specific option.
     *
     * @param  string  $option  Option name
     * @return bool Whether the option exists
     */
    public function hasOption(string $option): bool
    {
        return isset($this->options[$option]);
    }

    /**
     * Get debug information about the request.
     *
     * @return array<string, mixed> Debug information
     */
    public function debug(): array
    {
        return [
            'uri' => $this->getFullUri(),
            'method' => $this->options['method'] ?? Method::GET->value,
            'headers' => $this->getHeaders(),
            'options' => array_diff_key($this->options, ['headers' => true]),
            'is_async' => $this->isAsync,
            'timeout' => $this->timeout,
            'retries' => $this->maxRetries,
            'retry_delay' => $this->retryDelay,
        ];
    }

    /**
     * Set the logger instance.
     *
     * @param  LoggerInterface  $logger  PSR-3 logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Set the log level for request/response traces.
     */
    public function withLogLevel(string $level): self
    {
        $level = strtolower($level);
        $allowed = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

        if (! in_array($level, $allowed, true)) {
            throw new InvalidArgumentException("Invalid log level: {$level}");
        }

        $this->logLevel = $level;

        return $this;
    }

    /**
     * Clone this client handler with the given options.
     *
     * @param  array<string, mixed>  $options  Options to apply to the clone
     * @return static New client handler instance with the applied options
     */
    public function withClonedOptions(array $options): static
    {
        $clone = clone $this;
        $clone->withOptions($options);

        return $clone;
    }

    /** @param array<mixed> $promises */
    protected function validatePromises(array $promises): void
    {
        $this->promiseManager->validatePromises($promises);
    }

    protected function awaitWithTimeout(PromiseInterface $promise, float $timeout): mixed
    {
        return $this->promiseManager->awaitPromise($promise, $timeout);
    }

    protected function createTimeoutPromise(float $timeout): PromiseInterface
    {
        return $this->promiseManager->createTimeoutPromise($timeout);
    }

    protected function getAsyncTransport(): AsyncTransport
    {
        if ($this->asyncTransport !== null) {
            return $this->asyncTransport;
        }

        $client = $this->getHttpClient();
        $this->asyncTransport = new GuzzleAsyncTransport($client, $this->curlMultiHandler);

        return $this->asyncTransport;
    }

    /**
     * Send the configured request asynchronously based on current options.
     *
     * Requires that 'method' and 'uri' are set in options.
     *
     * @return PromiseInterface<ResponseContract>
     */
    protected function sendAsync(): PromiseInterface
    {
        $method = $this->options['method'] ?? null;
        $uri = $this->options['uri'] ?? null;

        if (! is_string($method) || ! is_string($uri)) {
            throw new LogicException('sendAsync() requires method and uri to be set.');
        }

        $promise = $this->sendRequest($method, $uri, ['async' => true]);
        if (! $promise instanceof PromiseInterface) {
            throw new LogicException('The asynchronous request pipeline did not return a promise.');
        }

        return $promise;
    }

    /**
     * Log a retry attempt.
     *
     * @param  int  $attempt  Current attempt number
     * @param  int  $maxAttempts  Maximum attempts
     * @param  \Throwable  $exception  The exception that caused the retry
     */
    protected function logRetry(int $attempt, int $maxAttempts, \Throwable $exception): void
    {
        $this->logger->info(
            'Retrying request',
            [
                'attempt' => $attempt,
                'max_attempts' => $maxAttempts,
                // Read the raw URI option rather than getFullUri(): requests are
                // dispatched statelessly, so handler-state URI resolution would
                // throw when no URI is configured on the handler itself.
                'uri' => $this->options['uri'] ?? '',
                'method' => $this->options['method'] ?? Method::GET->value,
                'error' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]
        );
    }

    /**
     * Log a request.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     * @param  array<string, mixed>  $options  Request options
     */
    protected function logRequest(string $method, string $uri, array $options): void
    {
        // Remove potentially sensitive data
        $sanitizedOptions = $this->sanitizeOptions($options);

        $this->logger->log(
            $this->logLevel,
            'Sending HTTP request',
            [
                'method' => $method,
                'uri' => $uri,
                'options' => $sanitizedOptions,
            ]
        );
    }

    /**
     * Log a response.
     *
     * @param  Response  $response  HTTP response
     * @param  float  $duration  Request duration in seconds
     */
    protected function logResponse(Response $response, float $duration): void
    {
        $this->logger->log(
            $this->logLevel,
            'Received HTTP response',
            [
                'status_code' => $response->getStatusCode(),
                'reason' => $response->getReasonPhrase(),
                'duration' => round($duration, 3),
                'content_length' => $this->getResponseContentLength($response),
            ]
        );
    }

    /**
     * Get the content length of a response.
     *
     * @param  Response  $response  The response
     * @return int|string The content length
     */
    protected function getResponseContentLength(Response $response): int|string
    {
        if ($response->hasHeader('Content-Length')) {
            return $response->getHeaderLine('Content-Length');
        }

        $body = $response->getBody();
        $body->rewind();
        $content = $body->getContents();
        $body->rewind();

        return strlen($content);
    }

    /**
     * Sanitize options for logging.
     *
     * @param  array<string, mixed>  $options  The options to sanitize
     * @return array<string, mixed> Sanitized options
     */
    protected function sanitizeOptions(array $options): array
    {
        $sanitizedOptions = $options;

        // Mask sensitive headers (case-insensitive)
        if (isset($sanitizedOptions['headers']) && is_array($sanitizedOptions['headers'])) {
            $sensitiveHeaders = [
                'authorization', 'x-api-key', 'api-key', 'x-auth-token', 'cookie', 'set-cookie',
            ];

            foreach ($sanitizedOptions['headers'] as $key => $value) {
                if (in_array(strtolower((string) $key), $sensitiveHeaders, true)) {
                    if (is_array($value)) {
                        $sanitizedOptions['headers'][$key] = array_fill(0, count($value), '[REDACTED]');
                    } else {
                        $sanitizedOptions['headers'][$key] = '[REDACTED]';
                    }
                }
            }
        }

        // Mask auth credentials
        if (isset($sanitizedOptions['auth'])) {
            $sanitizedOptions['auth'] = '[REDACTED]';
        }

        return $sanitizedOptions;
    }

    /** @param array<int, mixed> $middleware */
    private function createRequestCoordinator(
        RequestContext $context,
        ValidationPolicy $policy,
        array $middleware,
    ): RequestCoordinator {
        return new RequestCoordinator(
            requestBuilder: $this->requestBuilder,
            validationManager: $this->getValidationManager(),
            validationPolicy: $policy,
            httpClient: $this->getHttpClient(),
            asyncTransport: $this->getAsyncTransport(),
            retryStrategy: $this->createRetryStrategy($context),
            cacheManager: $this->getCacheManager(),
            middleware: $middleware,
            hooks: new RequestCoordinatorHooks(
                mockRequest: fn (string $method, string $uri, array $options) => $this->handleMockRequest($method, $uri, $options),
                startProfiling: fn (string $method, string $uri) => $this->startProfiling($method, $uri),
                recordProfilingEvent: fn (?string $requestId, string $event) => $this->recordProfilingEvent($requestId, $event),
                endProfiling: fn (?string $requestId, ?int $statusCode) => $this->endProfiling($requestId, $statusCode),
                connectionStats: fn (): array => $this->getConnectionDebugStats(),
                captureDebugSnapshot: fn (string $method, string $uri, array $options, ?ResponseInterface $response, float $startTime, int $startMemory, array $connectionStats) => $this->captureDebugSnapshot($method, $uri, $options, $response, $startTime, $startMemory, $connectionStats),
                logRequest: fn (string $method, string $uri, array $options) => $this->logRequest($method, $uri, $options),
                logResponse: function (ResponseContract $response, float $duration): void {
                    if ($response instanceof Response) {
                        $this->logResponse($response, $duration);
                    }
                },
                eventRequest: fn (string $method, string $uri, array $headers) => $this->eventRequest($method, $uri, $headers),
                emitRequestEvent: fn (RequestInterface $request, RequestContext $requestContext, float $timestamp) => $this->emitRequestEvent($request, $requestContext, $timestamp),
                emitResponseEvent: fn (RequestInterface $request, ResponseInterface $response, RequestContext $requestContext, float $startTime) => $this->emitResponseEvent($request, $response, $requestContext, $startTime),
                emitErrorEvent: fn (RequestInterface $request, \Throwable $exception, RequestContext $requestContext, float $startTime) => $this->emitErrorEvent($request, $exception, $requestContext, $startTime),
                hasRedirectListeners: fn (): bool => $this->hasEventListeners(RedirectEvent::NAME),
                attachRedirectListener: fn (array $options, RequestInterface $request, RequestContext $requestContext): array => $this->attachRedirectListener($options, $request, $requestContext),
                notifyRetry: fn (int $attempt, int $maxAttempts, \Throwable $exception, int $delay, ?RequestContext $requestContext) => $this->notifyRetry($attempt, $maxAttempts, $exception, $delay, $requestContext),
                withErrorContext: fn (\Throwable $exception, string $method, string $uri): \Throwable => $this->withErrorContext($exception, $method, $uri),
                logAsyncFailure: function (string $method, string $uri, \Throwable $exception): void {
                    $this->logger->error('Async request failed', [
                        'method' => $method,
                        'uri' => $uri,
                        'error' => $exception->getMessage(),
                        'exception_class' => get_class($exception),
                    ]);
                },
            ),
        );
    }

    public function __clone(): void
    {
        // Pool and HTTP/2 toggles belong to the handler; GlobalServices still
        // owns the shared pool and DNS cache used by each manager instance.
        $this->connectionPoolManager = clone $this->connectionPoolManager;
    }
}
