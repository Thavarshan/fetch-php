<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Concerns\ConfiguresRequests;
use Fetch\Concerns\HandlesMocking;
use Fetch\Concerns\HandlesUris;
use Fetch\Concerns\ManagesConnectionPool;
use Fetch\Concerns\ManagesDebugAndProfiling;
use Fetch\Concerns\ManagesPromises;
use Fetch\Concerns\ManagesRetries;
use Fetch\Concerns\PerformsHttpRequests;
use Fetch\Concerns\SupportsMiddleware;
use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Interfaces\ClientHandler as ClientHandlerInterface;
use Fetch\Interfaces\Response as ResponseContract;
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
    use ConfiguresRequests,
        HandlesMocking,
        HandlesUris,
        ManagesConnectionPool,
        ManagesDebugAndProfiling,
        ManagesPromises,
        ManagesRetries,
        PerformsHttpRequests,
        SupportsMiddleware;

    /**
     * Default options for the request.
     *
     * @var array<string, mixed>
     */
    protected static array $defaultOptions = [
        'method' => Method::GET->value,
        'headers' => [],
    ];

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
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger;
        $this->isAsync = $isAsync;
        $this->maxRetries = $maxRetries ?? self::DEFAULT_RETRIES;
        $this->retryDelay = $retryDelay ?? self::DEFAULT_RETRY_DELAY;

        // Initialize with default options
        $this->options = array_merge(self::getDefaultOptions(), $this->options);

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
        return new static(httpClient: $client);
    }

    /**
     * Get the default options for the request.
     *
     * @return array<string, mixed> Default options
     */
    public static function getDefaultOptions(): array
    {
        return array_merge(self::$defaultOptions, [
            'timeout' => self::DEFAULT_TIMEOUT,
        ]);
    }

    /**
     * Set the default options for all instances.
     *
     * @param  array<string, mixed>  $options  Default options
     */
    public static function setDefaultOptions(array $options): void
    {
        self::$defaultOptions = array_merge(self::$defaultOptions, $options);
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
        ?string $reason = null
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
        array $headers = []
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

        $fullUri = $this->buildFullUri($uri);
        $guzzleOptions = $this->prepareGuzzleOptions();

        return $this->executeAsyncRequest($method, $fullUri, $guzzleOptions);
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

        $this->logger->debug(
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
        $this->logger->debug(
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
