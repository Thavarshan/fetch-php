<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Cache\CacheInterface;
use Fetch\Cache\CacheManager;
use Fetch\Concerns\ConfiguresRequests;
use Fetch\Concerns\HandlesMocking;
use Fetch\Concerns\HandlesUris;
use Fetch\Concerns\ManagesConnectionPool;
use Fetch\Concerns\ManagesDebugAndProfiling;
use Fetch\Concerns\ManagesPromises;
use Fetch\Concerns\ManagesRetries;
use Fetch\Concerns\PerformsHttpRequests;
use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Interfaces\ClientHandler as ClientHandlerInterface;
use Fetch\Interfaces\Response as ResponseContract;
use Fetch\Support\GlobalServices;
use Fetch\Support\RequestContext;
use Fetch\Support\RequestOptions as FetchRequestOptions;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use LogicException;
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
    use ManagesPromises;
    use ManagesRetries;
    use PerformsHttpRequests;

    /**
     * Default options for the request (legacy).
     *
     * @var array<string, mixed>
     *
     * @deprecated Use GlobalServices::getDefaultOptions() instead
     */
    protected static array $defaultOptions = [
        'method' => self::DEFAULT_HTTP_METHOD,
        'headers' => [],
    ];

    /**
     * Default HTTP method.
     */
    public const DEFAULT_HTTP_METHOD = 'GET';

    /**
     * Default timeout for requests in seconds.
     */
    public const DEFAULT_TIMEOUT = 30;

    /**
     * Default number of retries.
     */
    public const DEFAULT_RETRIES = 1;

    /**
     * Default delay between retries in milliseconds.
     */
    public const DEFAULT_RETRY_DELAY = 100;

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
    ) {
        $this->logger = $logger ?? new NullLogger;
        $this->isAsync = $isAsync;
        $this->maxRetries = $maxRetries ?? self::DEFAULT_RETRIES;
        $this->retryDelay = $retryDelay ?? self::DEFAULT_RETRY_DELAY;
        $this->cacheManager = $cacheManager;

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
     * @throws \InvalidArgumentException If the base URI is invalid
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
    public static function createWithClient(ClientInterface $client): static
    {
        static::initializePool();

        return new static(httpClient: $client);
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
        // Update legacy static property for backward compatibility
        self::$defaultOptions = array_merge(self::$defaultOptions, $options);

        // Update GlobalServices for new architecture
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
        return new Response($statusCode, $headers, $body, $version, $reason);
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
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);

        $headers = array_merge(
            ['Content-Type' => ContentType::JSON->value],
            $headers
        );

        return self::createMockResponse($statusCode, $headers, $jsonData);
    }

    /**
     * Get the HTTP client.
     *
     * @return ClientInterface The HTTP client
     */
    public function getHttpClient(): ClientInterface
    {
        if (! $this->httpClient) {
            $this->httpClient = new GuzzleClient([
                RequestOptions::CONNECT_TIMEOUT => $this->options['timeout'] ?? self::DEFAULT_TIMEOUT,
                RequestOptions::HTTP_ERRORS => false, // We'll handle HTTP errors ourselves
            ]);
        }

        return $this->httpClient;
    }

    /**
     * Set the HTTP client.
     *
     * @param  ClientInterface  $client  The HTTP client
     * @return $this
     */
    public function setHttpClient(ClientInterface $client): self
    {
        $this->httpClient = $client;

        return $this;
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

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $context = RequestContext::fromOptions($this->options);
        // Use context-based URI building for stateless operation
        $fullUri = $this->buildFullUriFromContext($context);
        $guzzleOptions = $context->toGuzzleOptions();

        // Start profiling for this request
        $requestId = $this->startProfiling($method, $fullUri);

        return $this->executeAsyncRequest(
            $method,
            $fullUri,
            $guzzleOptions,
            $startTime,
            $startMemory,
            $requestId,
            $context
        );
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
                'uri' => $this->getFullUri(),
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
}
