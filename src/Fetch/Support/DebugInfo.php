<?php

declare(strict_types=1);

namespace Fetch\Support;

use Psr\Http\Message\ResponseInterface;

/**
 * DebugInfo provides detailed information about HTTP request/response cycles.
 *
 * This class captures and formats debug information including request/response
 * headers and bodies, timing information, connection statistics, and memory usage.
 */
class DebugInfo
{
    /**
     * Default options for debug output.
     *
     * @var array<string, mixed>
     */
    protected static array $defaultOptions = [
        'request_headers' => true,
        'request_body' => true,
        'response_headers' => true,
        'response_body' => 1024, // First 1KB by default, false to disable, true for all
        'timing' => true,
        'memory' => true,
        'dns_resolution' => false,
    ];

    /**
     * Sensitive headers to redact from debug output.
     *
     * @var array<int, string>
     */
    protected const SENSITIVE_HEADERS = [
        'authorization',
        'x-api-key',
        'api-key',
        'x-auth-token',
        'cookie',
        'set-cookie',
        'x-csrf-token',
        'x-xsrf-token',
    ];

    /**
     * Create a new DebugInfo instance.
     *
     * @param  array<string, mixed>  $requestData  Request data (method, uri, headers, body)
     * @param  ResponseInterface|null  $response  The HTTP response
     * @param  array<string, float>  $timings  Timing information for the request
     * @param  array<string, mixed>  $connectionStats  Connection statistics
     * @param  int  $memoryUsage  Memory usage in bytes
     */
    public function __construct(
        protected array $requestData,
        protected ?ResponseInterface $response,
        protected array $timings = [],
        protected array $connectionStats = [],
        protected int $memoryUsage = 0,
    ) {}

    /**
     * Create a DebugInfo instance from a request and response.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     * @param  array<string, mixed>  $options  Request options including headers and body
     * @param  ResponseInterface|null  $response  The HTTP response
     * @param  array<string, float>  $timings  Timing information
     * @param  array<string, mixed>  $connectionStats  Connection statistics
     * @param  int  $memoryUsage  Memory usage in bytes
     */
    public static function create(
        string $method,
        string $uri,
        array $options,
        ?ResponseInterface $response = null,
        array $timings = [],
        array $connectionStats = [],
        int $memoryUsage = 0,
    ): static {
        // Sanitize options before storing in debug info
        $sanitizedOptions = self::sanitizeOptions($options);

        $requestData = [
            'method' => strtoupper($method),
            'uri' => $uri,
            'headers' => $sanitizedOptions['headers'] ?? [],
            'body' => $sanitizedOptions['body'] ?? ($sanitizedOptions['json'] ?? null),
        ];

        return new static($requestData, $response, $timings, $connectionStats, $memoryUsage);
    }

    /**
     * Get the default debug options.
     *
     * @return array<string, mixed>
     */
    public static function getDefaultOptions(): array
    {
        return self::$defaultOptions;
    }

    /**
     * Set default debug options.
     *
     * @param  array<string, mixed>  $options
     */
    public static function setDefaultOptions(array $options): void
    {
        self::$defaultOptions = array_merge(self::$defaultOptions, $options);
    }

    /**
     * Sanitize options to redact sensitive information.
     *
     * @param  array<string, mixed>  $options  The options to sanitize
     * @return array<string, mixed> Sanitized options
     */
    protected static function sanitizeOptions(array $options): array
    {
        $sanitizedOptions = $options;

        // Mask sensitive headers (case-insensitive)
        if (isset($sanitizedOptions['headers']) && is_array($sanitizedOptions['headers'])) {
            $sanitizedOptions['headers'] = self::sanitizeHeaders($sanitizedOptions['headers']);
        }

        // Mask auth credentials
        if (isset($sanitizedOptions['auth'])) {
            $sanitizedOptions['auth'] = '[REDACTED]';
        }

        return $sanitizedOptions;
    }

    /**
     * Sanitize headers to redact sensitive information.
     *
     * @param  array<string, mixed>  $headers  The headers to sanitize
     * @return array<string, mixed> Sanitized headers
     */
    protected static function sanitizeHeaders(array $headers): array
    {
        $sanitizedHeaders = $headers;

        foreach ($sanitizedHeaders as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_HEADERS, true)) {
                if (is_array($value)) {
                    $sanitizedHeaders[$key] = array_fill(0, count($value), '[REDACTED]');
                } else {
                    $sanitizedHeaders[$key] = '[REDACTED]';
                }
            }
        }

        return $sanitizedHeaders;
    }

    /**
     * Get the request data.
     *
     * @return array<string, mixed>
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }

    /**
     * Get the response.
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get the timing information.
     *
     * @return array<string, float>
     */
    public function getTimings(): array
    {
        return $this->timings;
    }

    /**
     * Get the connection statistics.
     *
     * @return array<string, mixed>
     */
    public function getConnectionStats(): array
    {
        return $this->connectionStats;
    }

    /**
     * Get the memory usage.
     */
    public function getMemoryUsage(): int
    {
        return $this->memoryUsage;
    }

    /**
     * Format the request information for output.
     *
     * @param  array<string, mixed>  $options  Output options
     * @return array<string, mixed>
     */
    public function formatRequest(array $options = []): array
    {
        $options = array_merge(self::$defaultOptions, $options);
        $formatted = [
            'method' => $this->requestData['method'] ?? 'GET',
            'uri' => $this->requestData['uri'] ?? '',
        ];

        if ($options['request_headers']) {
            $formatted['headers'] = $this->requestData['headers'] ?? [];
        }

        if ($options['request_body']) {
            $body = $this->requestData['body'] ?? null;
            if ($body !== null) {
                $formatted['body'] = $this->formatBody($body, $options['request_body']);
            }
        }

        return $formatted;
    }

    /**
     * Format the response information for output.
     *
     * @param  array<string, mixed>  $options  Output options
     * @return array<string, mixed>|null
     */
    public function formatResponse(array $options = []): ?array
    {
        if ($this->response === null) {
            return null;
        }

        $options = array_merge(self::$defaultOptions, $options);
        $formatted = [
            'status_code' => $this->response->getStatusCode(),
            'reason_phrase' => $this->response->getReasonPhrase(),
        ];

        if ($options['response_headers']) {
            $formatted['headers'] = self::sanitizeHeaders($this->response->getHeaders());
        }

        if ($options['response_body'] !== false) {
            $body = (string) $this->response->getBody();
            $this->response->getBody()->rewind();
            $formatted['body'] = $this->formatBody($body, $options['response_body']);
        }

        return $formatted;
    }

    /**
     * Get the debug information as an array.
     *
     * @param  array<string, mixed>  $options  Output options
     * @return array<string, mixed>
     */
    public function toArray(array $options = []): array
    {
        $options = array_merge(self::$defaultOptions, $options);
        $result = [
            'request' => $this->formatRequest($options),
        ];

        if ($this->response !== null) {
            $result['response'] = $this->formatResponse($options);
        }

        if ($options['timing'] && ! empty($this->timings)) {
            $result['performance'] = $this->timings;
        }

        if ($options['memory'] && $this->memoryUsage > 0) {
            $result['memory'] = [
                'bytes' => $this->memoryUsage,
                'formatted' => $this->formatBytes($this->memoryUsage),
            ];
        }

        if (! empty($this->connectionStats)) {
            $result['connection'] = $this->connectionStats;
        }

        return $result;
    }

    /**
     * Get the debug information as a JSON string.
     *
     * @param  array<string, mixed>  $options  Output options
     */
    public function dump(array $options = []): string
    {
        return json_encode($this->toArray($options), JSON_PRETTY_PRINT) ?: '{}';
    }

    /**
     * Format a body for output, optionally truncating.
     *
     * @param  mixed  $body  The body content
     * @param  bool|int  $option  True for full body, int for max bytes, false to disable
     */
    protected function formatBody(mixed $body, bool|int $option): mixed
    {
        if ($option === false) {
            return null;
        }

        // If it's an array, encode it as JSON for readability
        if (is_array($body)) {
            $body = json_encode($body, JSON_PRETTY_PRINT);
        }

        if (! is_string($body)) {
            return $body;
        }

        // Truncate if a byte limit is specified
        if (is_int($option) && strlen($body) > $option) {
            return substr($body, 0, $option).'... (truncated)';
        }

        return $body;
    }

    /**
     * Format bytes into human-readable string.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2).' '.$units[$unitIndex];
    }

    /**
     * Get debug info as string representation.
     */
    public function __toString(): string
    {
        return $this->dump();
    }
}
