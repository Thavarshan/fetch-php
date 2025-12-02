<?php

declare(strict_types=1);

namespace Fetch\Support;

use Fetch\Enum\Method;
use GuzzleHttp\Exception\ConnectException;

/**
 * Immutable value object representing per-request configuration and context.
 *
 * RequestContext centralizes all request-specific settings in one place,
 * providing clear precedence rules and type-safe access to configuration.
 *
 * **Precedence (lowest to highest):**
 * 1. Factory defaults (from GlobalServices::getFactoryDefaults())
 * 2. Client-level defaults (set via setDefaultOptions())
 * 3. Handler-level options (set via withOptions())
 * 4. Request-level options (passed to sendRequest())
 *
 * **Immutability:**
 * All `with*` methods return a new instance, preserving the original.
 * This enables safe cloning and prevents accidental state sharing.
 *
 * @psalm-immutable
 */
final class RequestContext
{
    /**
     * Default retryable HTTP status codes.
     *
     * @var array<int>
     */
    public const DEFAULT_RETRYABLE_STATUS_CODES = [
        408, 429, 500, 502, 503,
        504, 507, 509, 520, 521,
        522, 523, 525, 527, 530,
    ];

    /**
     * Default retryable exception class names.
     *
     * @var array<class-string<\Throwable>>
     */
    public const DEFAULT_RETRYABLE_EXCEPTIONS = [
        ConnectException::class,
    ];

    /**
     * HTTP method for the request.
     */
    private readonly string $method;

    /**
     * Request URI (may be relative if base_uri is set).
     */
    private readonly string $uri;

    /**
     * Whether this request should be executed asynchronously.
     */
    private readonly bool $async;

    /**
     * Request timeout in seconds.
     */
    private readonly int $timeout;

    /**
     * Maximum number of retry attempts.
     */
    private readonly int $maxRetries;

    /**
     * Delay between retries in milliseconds.
     */
    private readonly int $retryDelay;

    /**
     * HTTP status codes that should trigger a retry.
     *
     * @var array<int>
     */
    private readonly array $retryableStatusCodes;

    /**
     * Exception class names that should trigger a retry.
     *
     * @var array<class-string<\Throwable>>
     */
    private readonly array $retryableExceptions;

    /**
     * Whether caching is enabled for this request.
     */
    private readonly bool $cacheEnabled;

    /**
     * Whether debugging is enabled for this request.
     */
    private readonly bool $debugEnabled;

    /**
     * Request headers.
     *
     * @var array<string, string|string[]>
     */
    private readonly array $headers;

    /**
     * Additional options not covered by explicit properties.
     *
     * @var array<string, mixed>
     */
    private readonly array $options;

    /**
     * Cache-specific options.
     *
     * @var array<string, mixed>
     */
    private readonly array $cacheOptions;

    /**
     * Debug-specific options.
     *
     * @var array<string, mixed>
     */
    private readonly array $debugOptions;

    /**
     * Private constructor - use static factory methods.
     *
     * @param  array<string, string|string[]>  $headers
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $cacheOptions
     * @param  array<string, mixed>  $debugOptions
     * @param  array<int>  $retryableStatusCodes
     * @param  array<class-string<\Throwable>>  $retryableExceptions
     */
    private function __construct(
        string $method = 'GET',
        string $uri = '',
        bool $async = false,
        int $timeout = 30,
        int $maxRetries = 1,
        int $retryDelay = 100,
        bool $cacheEnabled = false,
        bool $debugEnabled = false,
        array $headers = [],
        array $options = [],
        array $cacheOptions = [],
        array $debugOptions = [],
        array $retryableStatusCodes = [],
        array $retryableExceptions = [],
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->async = $async;
        $this->timeout = $timeout;
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
        $this->cacheEnabled = $cacheEnabled;
        $this->debugEnabled = $debugEnabled;
        $this->headers = $headers;
        $this->options = $options;
        $this->cacheOptions = $cacheOptions;
        $this->debugOptions = $debugOptions;
        // Use provided values or defaults - empty array means "use defaults"
        $this->retryableStatusCodes = $retryableStatusCodes ?: self::DEFAULT_RETRYABLE_STATUS_CODES;
        $this->retryableExceptions = $retryableExceptions ?: self::DEFAULT_RETRYABLE_EXCEPTIONS;
    }

    /**
     * Create a new context with factory defaults.
     */
    public static function create(): self
    {
        return new self;
    }

    /**
     * Create a context from an options array.
     *
     * This method handles the various option formats used throughout the library.
     *
     * @param  array<string, mixed>  $options
     */
    public static function fromOptions(array $options): self
    {
        // Normalize body options first
        $options = RequestOptions::normalizeBodyOptions($options);

        // Extract method
        $method = $options['method'] ?? 'GET';
        if ($method instanceof Method) {
            $method = $method->value;
        }

        // Extract cache options if present
        $cacheOptions = [];
        $cacheEnabled = false;
        if (isset($options['cache'])) {
            if (is_array($options['cache'])) {
                $cacheOptions = $options['cache'];
                $cacheEnabled = $cacheOptions['enabled'] ?? true;
            } elseif (is_bool($options['cache'])) {
                $cacheEnabled = $options['cache'];
            }
            unset($options['cache']);
        }

        // Extract debug options if present
        $debugOptions = [];
        $debugEnabled = false;
        if (isset($options['debug'])) {
            if (is_array($options['debug'])) {
                $debugOptions = $options['debug'];
                $debugEnabled = true;
            } elseif (is_bool($options['debug'])) {
                $debugEnabled = $options['debug'];
            }
            unset($options['debug']);
        }

        // Extract headers
        $headers = $options['headers'] ?? [];
        unset($options['headers']);

        // Extract known properties
        // Note: Options should be normalized via RequestOptions::normalizeOptionKeys before this point
        // so 'max_retries' should already be converted to 'retries'
        $uri = (string) ($options['uri'] ?? '');
        $async = (bool) ($options['async'] ?? false);
        $timeout = (int) ($options['timeout'] ?? 30);
        $maxRetries = (int) ($options['retries'] ?? $options['max_retries'] ?? 1); // Fallback for safety
        $retryDelay = (int) ($options['retry_delay'] ?? 100);

        // Extract retryable configuration
        $retryableStatusCodes = [];
        if (isset($options['retry_status_codes']) && is_array($options['retry_status_codes'])) {
            $retryableStatusCodes = array_map('intval', $options['retry_status_codes']);
        }

        $retryableExceptions = [];
        if (isset($options['retry_exceptions']) && is_array($options['retry_exceptions'])) {
            $retryableExceptions = $options['retry_exceptions'];
        }

        // Remove extracted properties from options
        unset(
            $options['method'],
            $options['uri'],
            $options['async'],
            $options['timeout'],
            $options['retries'],
            $options['max_retries'], // Clean up legacy key if it somehow got through
            $options['retry_delay'],
            $options['retry_status_codes'],
            $options['retry_exceptions']
        );

        return new self(
            method: $method,
            uri: $uri,
            async: $async,
            timeout: $timeout,
            maxRetries: $maxRetries,
            retryDelay: $retryDelay,
            cacheEnabled: $cacheEnabled,
            debugEnabled: $debugEnabled,
            headers: $headers,
            options: $options,
            cacheOptions: $cacheOptions,
            debugOptions: $debugOptions,
            retryableStatusCodes: $retryableStatusCodes,
            retryableExceptions: $retryableExceptions
        );
    }

    /**
     * Merge this context with additional options.
     *
     * Options in $options take precedence over this context's values.
     *
     * @param  array<string, mixed>  $options
     */
    public function merge(array $options): self
    {
        // Normalize the incoming options
        $options = RequestOptions::normalizeBodyOptions($options);

        // Start with current context converted to array, then merge new options
        $currentOptions = $this->toArray();
        $mergedOptions = array_replace_recursive($currentOptions, $options);

        return self::fromOptions($mergedOptions);
    }

    /**
     * Create a copy with a different HTTP method.
     */
    public function withMethod(string|Method $method): self
    {
        $methodStr = $method instanceof Method ? $method->value : strtoupper($method);

        return new self(
            method: $methodStr,
            uri: $this->uri,
            async: $this->async,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            cacheEnabled: $this->cacheEnabled,
            debugEnabled: $this->debugEnabled,
            headers: $this->headers,
            options: $this->options,
            cacheOptions: $this->cacheOptions,
            debugOptions: $this->debugOptions,
            retryableStatusCodes: $this->retryableStatusCodes,
            retryableExceptions: $this->retryableExceptions
        );
    }

    /**
     * Create a copy with a different URI.
     */
    public function withUri(string $uri): self
    {
        return new self(
            method: $this->method,
            uri: $uri,
            async: $this->async,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            cacheEnabled: $this->cacheEnabled,
            debugEnabled: $this->debugEnabled,
            headers: $this->headers,
            options: $this->options,
            cacheOptions: $this->cacheOptions,
            debugOptions: $this->debugOptions,
            retryableStatusCodes: $this->retryableStatusCodes,
            retryableExceptions: $this->retryableExceptions
        );
    }

    /**
     * Create a copy with async mode set.
     */
    public function withAsync(bool $async = true): self
    {
        return new self(
            method: $this->method,
            uri: $this->uri,
            async: $async,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            cacheEnabled: $this->cacheEnabled,
            debugEnabled: $this->debugEnabled,
            headers: $this->headers,
            options: $this->options,
            cacheOptions: $this->cacheOptions,
            debugOptions: $this->debugOptions,
            retryableStatusCodes: $this->retryableStatusCodes,
            retryableExceptions: $this->retryableExceptions
        );
    }

    /**
     * Create a copy with a different timeout.
     */
    public function withTimeout(int $timeout): self
    {
        return new self(
            method: $this->method,
            uri: $this->uri,
            async: $this->async,
            timeout: $timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            cacheEnabled: $this->cacheEnabled,
            debugEnabled: $this->debugEnabled,
            headers: $this->headers,
            options: $this->options,
            cacheOptions: $this->cacheOptions,
            debugOptions: $this->debugOptions,
            retryableStatusCodes: $this->retryableStatusCodes,
            retryableExceptions: $this->retryableExceptions
        );
    }

    /**
     * Create a copy with retry configuration.
     */
    public function withRetry(int $maxRetries, int $delayMs = 100): self
    {
        return new self(
            method: $this->method,
            uri: $this->uri,
            async: $this->async,
            timeout: $this->timeout,
            maxRetries: max(0, $maxRetries),
            retryDelay: max(0, $delayMs),
            cacheEnabled: $this->cacheEnabled,
            debugEnabled: $this->debugEnabled,
            headers: $this->headers,
            options: $this->options,
            cacheOptions: $this->cacheOptions,
            debugOptions: $this->debugOptions,
            retryableStatusCodes: $this->retryableStatusCodes,
            retryableExceptions: $this->retryableExceptions
        );
    }

    /**
     * Create a copy with custom retryable status codes.
     *
     * @param  array<int>  $statusCodes  HTTP status codes that should trigger a retry
     */
    public function withRetryableStatusCodes(array $statusCodes): self
    {
        return new self(
            method: $this->method,
            uri: $this->uri,
            async: $this->async,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            cacheEnabled: $this->cacheEnabled,
            debugEnabled: $this->debugEnabled,
            headers: $this->headers,
            options: $this->options,
            cacheOptions: $this->cacheOptions,
            debugOptions: $this->debugOptions,
            retryableStatusCodes: array_map('intval', $statusCodes),
            retryableExceptions: $this->retryableExceptions
        );
    }

    /**
     * Create a copy with custom retryable exception types.
     *
     * @param  array<class-string<\Throwable>>  $exceptions  Exception class names that should trigger a retry
     */
    public function withRetryableExceptions(array $exceptions): self
    {
        return new self(
            method: $this->method,
            uri: $this->uri,
            async: $this->async,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            cacheEnabled: $this->cacheEnabled,
            debugEnabled: $this->debugEnabled,
            headers: $this->headers,
            options: $this->options,
            cacheOptions: $this->cacheOptions,
            debugOptions: $this->debugOptions,
            retryableStatusCodes: $this->retryableStatusCodes,
            retryableExceptions: $exceptions
        );
    }

    /**
     * Create a copy with caching enabled/disabled.
     *
     * @param  bool|array<string, mixed>  $cache
     */
    public function withCache(bool|array $cache = true): self
    {
        $enabled = is_bool($cache) ? $cache : true;
        $options = is_array($cache) ? $cache : [];

        return new self(
            method: $this->method,
            uri: $this->uri,
            async: $this->async,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            cacheEnabled: $enabled,
            debugEnabled: $this->debugEnabled,
            headers: $this->headers,
            options: $this->options,
            cacheOptions: $options,
            debugOptions: $this->debugOptions,
            retryableStatusCodes: $this->retryableStatusCodes,
            retryableExceptions: $this->retryableExceptions
        );
    }

    /**
     * Create a copy with debugging enabled/disabled.
     *
     * @param  bool|array<string, mixed>  $debug
     */
    public function withDebug(bool|array $debug = true): self
    {
        $enabled = is_bool($debug) ? $debug : true;
        $options = is_array($debug) ? $debug : [];

        return new self(
            method: $this->method,
            uri: $this->uri,
            async: $this->async,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            cacheEnabled: $this->cacheEnabled,
            debugEnabled: $enabled,
            headers: $this->headers,
            options: $this->options,
            cacheOptions: $this->cacheOptions,
            debugOptions: $options,
            retryableStatusCodes: $this->retryableStatusCodes,
            retryableExceptions: $this->retryableExceptions
        );
    }

    /**
     * Create a copy with a header added/replaced.
     *
     * @param  string|string[]  $value
     */
    public function withHeader(string $name, string|array $value): self
    {
        $headers = $this->headers;
        $headers[$name] = $value;

        return new self(
            method: $this->method,
            uri: $this->uri,
            async: $this->async,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            cacheEnabled: $this->cacheEnabled,
            debugEnabled: $this->debugEnabled,
            headers: $headers,
            options: $this->options,
            cacheOptions: $this->cacheOptions,
            debugOptions: $this->debugOptions,
            retryableStatusCodes: $this->retryableStatusCodes,
            retryableExceptions: $this->retryableExceptions
        );
    }

    /**
     * Create a copy with multiple headers added/replaced.
     *
     * @param  array<string, string|string[]>  $headers
     */
    public function withHeaders(array $headers): self
    {
        return new self(
            method: $this->method,
            uri: $this->uri,
            async: $this->async,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            cacheEnabled: $this->cacheEnabled,
            debugEnabled: $this->debugEnabled,
            headers: array_merge($this->headers, $headers),
            options: $this->options,
            cacheOptions: $this->cacheOptions,
            debugOptions: $this->debugOptions,
            retryableStatusCodes: $this->retryableStatusCodes,
            retryableExceptions: $this->retryableExceptions
        );
    }

    /**
     * Create a copy with an option set.
     */
    public function withOption(string $key, mixed $value): self
    {
        $options = $this->options;
        $options[$key] = $value;

        return new self(
            method: $this->method,
            uri: $this->uri,
            async: $this->async,
            timeout: $this->timeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            cacheEnabled: $this->cacheEnabled,
            debugEnabled: $this->debugEnabled,
            headers: $this->headers,
            options: $options,
            cacheOptions: $this->cacheOptions,
            debugOptions: $this->debugOptions,
            retryableStatusCodes: $this->retryableStatusCodes,
            retryableExceptions: $this->retryableExceptions
        );
    }

    // Getters

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function isAsync(): bool
    {
        return $this->async;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }

    /**
     * Get the retryable HTTP status codes.
     *
     * @return array<int>
     */
    public function getRetryableStatusCodes(): array
    {
        return $this->retryableStatusCodes;
    }

    /**
     * Get the retryable exception class names.
     *
     * @return array<class-string<\Throwable>>
     */
    public function getRetryableExceptions(): array
    {
        return $this->retryableExceptions;
    }

    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    public function isDebugEnabled(): bool
    {
        return $this->debugEnabled;
    }

    /**
     * @return array<string, string|string[]>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get a specific header value.
     *
     * @return string|string[]|null
     */
    public function getHeader(string $name): string|array|null
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Check if a header exists.
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    /**
     * Get additional options.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get a specific option value.
     */
    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * Get cache-specific options.
     *
     * @return array<string, mixed>
     */
    public function getCacheOptions(): array
    {
        return $this->cacheOptions;
    }

    /**
     * Get debug-specific options.
     *
     * @return array<string, mixed>
     */
    public function getDebugOptions(): array
    {
        return $this->debugOptions;
    }

    /**
     * Check if this is a safe HTTP method (GET, HEAD, OPTIONS).
     */
    public function isSafeMethod(): bool
    {
        return in_array($this->method, ['GET', 'HEAD', 'OPTIONS'], true);
    }

    /**
     * Check if this is an idempotent HTTP method.
     */
    public function isIdempotentMethod(): bool
    {
        return in_array($this->method, ['GET', 'HEAD', 'PUT', 'DELETE', 'OPTIONS'], true);
    }

    /**
     * Check if caching should be used for this request.
     *
     * Caching is only used for synchronous requests with safe HTTP methods.
     */
    public function shouldUseCache(): bool
    {
        return $this->cacheEnabled
            && ! $this->async
            && $this->isSafeMethod();
    }

    /**
     * Convert the context to an options array.
     *
     * Useful for passing to legacy code or Guzzle.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'method' => $this->method,
            'uri' => $this->uri,
            'async' => $this->async,
            'timeout' => $this->timeout,
            'retries' => $this->maxRetries,
            'retry_delay' => $this->retryDelay,
            'headers' => $this->headers,
        ];

        // Add retry configuration if not using defaults
        if ($this->retryableStatusCodes !== self::DEFAULT_RETRYABLE_STATUS_CODES) {
            $result['retry_status_codes'] = $this->retryableStatusCodes;
        }

        if ($this->retryableExceptions !== self::DEFAULT_RETRYABLE_EXCEPTIONS) {
            $result['retry_exceptions'] = $this->retryableExceptions;
        }

        // Add cache options if enabled
        if ($this->cacheEnabled) {
            $result['cache'] = array_merge(['enabled' => true], $this->cacheOptions);
        }

        // Add debug options if enabled
        if ($this->debugEnabled) {
            $result['debug'] = empty($this->debugOptions) ? true : $this->debugOptions;
        }

        // Merge in additional options
        return array_merge($result, $this->options);
    }

    /**
     * Prepare options for Guzzle HTTP client.
     *
     * Filters and transforms options to be compatible with Guzzle.
     *
     * @return array<string, mixed>
     */
    public function toGuzzleOptions(): array
    {
        $guzzleOptions = [];

        // Standard Guzzle options to include
        $standardOptions = [
            'json', 'form_params', 'multipart', 'body',
            'query', 'auth', 'verify', 'proxy', 'cookies', 'allow_redirects',
            'cert', 'ssl_key', 'stream', 'connect_timeout', 'read_timeout',
            'sink', 'version', 'decode_content', 'curl',
        ];

        // Copy standard options if set
        foreach ($standardOptions as $option) {
            if (isset($this->options[$option])) {
                $guzzleOptions[$option] = $this->options[$option];
            }
        }

        // Add headers
        if (! empty($this->headers)) {
            $guzzleOptions['headers'] = $this->headers;
        }

        // Set timeout
        $guzzleOptions['timeout'] = $this->timeout;

        // Ensure connect_timeout defaults sensibly
        if (! isset($guzzleOptions['connect_timeout'])) {
            $guzzleOptions['connect_timeout'] = $this->timeout;
        }

        return $guzzleOptions;
    }
}
