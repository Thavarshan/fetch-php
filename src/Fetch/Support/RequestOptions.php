<?php

declare(strict_types=1);

namespace Fetch\Support;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use InvalidArgumentException;

/**
 * Centralized request options handling with clear precedence rules.
 *
 * This class provides:
 * - Body/content-type normalization to avoid conflicts
 * - Option merging with clear precedence (defaults → client → request)
 * - Validation of option values
 * - Transformation between different option formats
 *
 * **Precedence (lowest to highest):**
 * 1. Factory defaults (from GlobalServices)
 * 2. Client-level defaults (set via setDefaultOptions)
 * 3. Handler-level options (set via withOptions)
 * 4. Request-level options (passed to sendRequest)
 *
 * **Body Option Precedence:**
 * json > form_params > multipart > body
 */
class RequestOptions
{
    /**
     * Body-related option keys.
     *
     * @var array<int, string>
     */
    private const BODY_OPTIONS = [
        'body',
        'json',
        'form',
        'form_params',
        'multipart',
    ];

    /**
     * Standard Guzzle options that are passed through.
     *
     * @var array<int, string>
     */
    private const GUZZLE_OPTIONS = [
        'headers',
        'json',
        'form_params',
        'multipart',
        'body',
        'query',
        'auth',
        'verify',
        'proxy',
        'cookies',
        'allow_redirects',
        'cert',
        'ssl_key',
        'stream',
        'connect_timeout',
        'read_timeout',
        'debug',
        'sink',
        'version',
        'decode_content',
        'curl',
        'timeout',
        'progress',
    ];

    /**
     * Options that control client behavior but aren't sent to Guzzle.
     *
     * @var array<int, string>
     */
    private const FETCH_OPTIONS = [
        'method',
        'uri',
        'base_uri',
        'async',
        'retries',
        'max_retries',
        'retry_delay',
        'retry_status_codes',
        'retry_exceptions',
        'cache',
        'profiler',
        'content_type',
        'token',
        'validation',
    ];

    /**
     * Merge multiple option arrays with clear precedence.
     *
     * Later arrays take precedence over earlier ones.
     *
     * @param  array<string, mixed>  ...$optionSets
     * @return array<string, mixed>
     */
    public static function merge(array ...$optionSets): array
    {
        $result = [];
        $contentTypeIsExplicit = false;

        foreach ($optionSets as $options) {
            // Normalize option keys to canonical names
            $options = self::normalizeOptionKeys($options);
            $hasBody = self::hasBodyOption($options);
            $hasContentType = self::hasHeader(
                $options['headers'] ?? [],
                'Content-Type',
            ) || array_key_exists('content_type', $options);
            $contentType = self::getExplicitContentType($options);

            if ($hasBody) {
                if ($contentType !== null) {
                    $contentTypeIsExplicit = true;
                } elseif ($hasContentType) {
                    $result = self::withoutHeader($result, 'Content-Type');
                    $contentTypeIsExplicit = false;
                } elseif ($contentTypeIsExplicit) {
                    $options = self::withHeaderValue(
                        $options,
                        'Content-Type',
                        self::getHeaderValue(
                            $result['headers'] ?? [],
                            'Content-Type',
                        ),
                    );
                } else {
                    $result = self::withoutHeader($result, 'Content-Type');
                }
            } elseif ($hasContentType) {
                $contentTypeIsExplicit = $contentType !== null;
            }

            // Normalize body options
            $options = self::normalizeBodyOptions($options);

            if ($hasBody && isset($options['multipart'])) {
                $result = self::withoutHeader($result, 'Content-Type');
                $contentTypeIsExplicit = false;
            }

            if (self::hasBodyOption($options)) {
                $result = self::stripBodyOptionsExcept($result, []);
            }

            // Deep merge headers
            if (isset($options['headers']) && isset($result['headers'])) {
                $options['headers'] = self::mergeHeaders(
                    $result['headers'],
                    $options['headers'],
                );
            }

            // Deep merge query parameters
            if (
                isset($options['query']) &&
                isset($result['query']) &&
                is_array($options['query']) &&
                is_array($result['query'])
            ) {
                $options['query'] = array_merge(
                    $result['query'],
                    $options['query'],
                );
            }

            // Deep merge cache options
            if (
                isset($options['cache']) &&
                is_array($options['cache']) &&
                isset($result['cache']) &&
                is_array($result['cache'])
            ) {
                $options['cache'] = array_merge(
                    $result['cache'],
                    $options['cache'],
                );
            }

            $result = array_merge($result, $options);
        }

        return self::normalizeBodyOptions($result);
    }

    /**
     * Normalize option keys to canonical names.
     *
     * This method standardizes option keys while maintaining backward
     * compatibility:
     * - 'retries' → kept as canonical form (more concise)
     * - 'max_retries' → normalized to 'retries'
     *
     * Legacy keys are supported but normalized to canonical form.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function normalizeOptionKeys(array $options): array
    {
        // Normalize retry options: prefer 'retries' over 'max_retries'
        if (isset($options['max_retries']) && ! isset($options['retries'])) {
            $options['retries'] = $options['max_retries'];
            unset($options['max_retries']);
        }

        // If both are set, 'retries' takes precedence (remove duplicate)
        if (isset($options['retries']) && isset($options['max_retries'])) {
            unset($options['max_retries']);
        }

        return $options;
    }

    /**
     * Normalize body/content-related options and headers.
     *
     * Ensures only one body option is set and content-type is consistent.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function normalizeBodyOptions(
        array $options,
        ContentType|string|null $serializationType = null,
    ): array {
        $headers = $options['headers'] ?? [];
        $contentTypeOverride = $options['content_type'] ?? null;

        $setContentType = static function (ContentType|string $type) use (
            &$headers,
            $contentTypeOverride,
        ): void {
            if ($contentTypeOverride !== null) {
                self::setHeaderValue(
                    $headers,
                    'Content-Type',
                    $contentTypeOverride instanceof ContentType
                        ? $contentTypeOverride->value
                        : (string) $contentTypeOverride,
                );

                return;
            }

            if (self::getHeaderValue($headers, 'Content-Type') === null) {
                self::setHeaderValue(
                    $headers,
                    'Content-Type',
                    $type instanceof ContentType
                        ? $type->value
                        : (string) $type,
                );
            }
        };

        // JSON body takes precedence
        if (isset($options['json'])) {
            $options = self::stripBodyOptionsExcept($options, ['json']);
            $setContentType(ContentType::JSON);
        } elseif (isset($options['form']) || isset($options['form_params'])) {
            $formData = $options['form'] ?? $options['form_params'];
            $options = self::stripBodyOptionsExcept($options, ['form_params']);
            $options['form_params'] = $formData;
            $setContentType(ContentType::FORM_URLENCODED);
        } elseif (isset($options['multipart'])) {
            $options = self::stripBodyOptionsExcept($options, ['multipart']);
            // multipart sets its own content type; ensure header is removed to
            // avoid conflicts
            self::removeHeader($headers, 'Content-Type');
        } elseif (isset($options['body'])) {
            $options = self::stripBodyOptionsExcept($options, ['body']);
            if (is_array($options['body'])) {
                $explicitContentType = $contentTypeOverride ??
                    $serializationType ??
                    self::getHeaderValue($headers, 'Content-Type');
                $explicitContentType =
                    $explicitContentType instanceof ContentType
                        ? $explicitContentType->value
                        : strtolower((string) $explicitContentType);

                // Check if explicit content type requests JSON or no content
                // type specified
                $isJsonContentType =
                    $explicitContentType === '' ||
                    str_contains($explicitContentType, 'json') ||
                    $explicitContentType === ContentType::JSON->value;

                if ($isJsonContentType) {
                    // Convert array body to json option for Guzzle
                    $options['json'] = $options['body'];
                    unset($options['body']);
                    $setContentType(ContentType::JSON);
                } elseif (
                    str_starts_with(
                        $explicitContentType,
                        ContentType::FORM_URLENCODED->value,
                    )
                ) {
                    $body = $options['body'];
                    $options = self::stripBodyOptionsExcept($options, [
                        'form_params',
                    ]);
                    $options['form_params'] = $body;
                    $setContentType(ContentType::FORM_URLENCODED);
                } elseif (
                    str_starts_with(
                        $explicitContentType,
                        ContentType::MULTIPART->value,
                    )
                ) {
                    $body = $options['body'];
                    $options = self::stripBodyOptionsExcept($options, [
                        'multipart',
                    ]);
                    $options['multipart'] = self::normalizeMultipart($body);
                    self::removeHeader($headers, 'Content-Type');
                } else {
                    // Array body with non-JSON content type - encode to string
                    // but keep user's content type
                    // This preserves backward compatibility for
                    // withBody($array, 'text/plain') etc.
                    $options['body'] = json_encode($options['body']);
                    if ($explicitContentType !== '') {
                        $setContentType($explicitContentType);
                    }
                }
            } elseif (
                $contentTypeOverride !== null || $serializationType !== null
            ) {
                // Keep an explicit header while recording the body format.
                $setContentType($contentTypeOverride ?? $serializationType);
            }
        }

        if ($headers !== [] || array_key_exists('headers', $options)) {
            $options['headers'] = $headers;
        }

        unset($options['content_type']); // normalized into headers

        return $options;
    }

    /**
     * Replace any body representation before applying a fluent body setter.
     *
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $bodyOptions
     * @return array<string, mixed>
     */
    public static function replaceBodyOptions(
        array $options,
        array $bodyOptions,
        bool $preserveContentType = true,
        ContentType|string|null $serializationType = null,
    ): array {
        $options = self::stripBodyOptionsExcept($options, []);

        if (! $preserveContentType) {
            $options = self::withoutHeader($options, 'Content-Type');
        }

        return self::normalizeBodyOptions(
            array_merge($options, $bodyOptions),
            $serializationType,
        );
    }

    /**
     * Return whether an option set contains a body option, including null.
     *
     * @param  array<string, mixed>  $options
     */
    public static function hasBodyOption(array $options): bool
    {
        foreach (self::BODY_OPTIONS as $key) {
            if (array_key_exists($key, $options)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get an explicitly supplied Content-Type from an option set.
     *
     * @param  array<string, mixed>  $options
     */
    public static function getExplicitContentType(
        array $options,
    ): ContentType|string|null {
        return self::getExplicitContentTypeOption($options) ??
            self::getExplicitContentTypeHeader($options);
    }

    /**
     * Get a content type supplied as an option, separate from a header.
     *
     * @param  array<string, mixed>  $options
     */
    public static function getExplicitContentTypeOption(
        array $options,
    ): ContentType|string|null {
        $value = $options['content_type'] ?? null;

        if ($value instanceof ContentType || is_string($value)) {
            return $value;
        }

        return null;
    }

    /**
     * Get a Content-Type header supplied in an option set.
     *
     * @param  array<string, mixed>  $options
     */
    public static function getExplicitContentTypeHeader(
        array $options,
    ): ContentType|string|null {
        $value = self::getHeaderValue(
            $options['headers'] ?? [],
            'Content-Type',
        );

        if ($value instanceof ContentType || is_string($value)) {
            return $value;
        }

        return null;
    }

    /**
     * Check for a header name without regard to its casing.
     *
     * @param  array<string, mixed>  $headers
     */
    public static function hasHeader(array $headers, string $name): bool
    {
        foreach ($headers as $header => $_) {
            if (strcasecmp((string) $header, $name) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return request options with a case-insensitively replaced header.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function withHeaderValue(
        array $options,
        string $name,
        mixed $value,
    ): array {
        $headers = $options['headers'] ?? [];
        self::setHeaderValue($headers, $name, $value);
        $options['headers'] = $headers;

        return $options;
    }

    /**
     * Return request options without a case-insensitive header name.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function withoutHeader(
        array $options,
        string $name,
    ): array {
        $headers = $options['headers'] ?? [];
        self::removeHeader($headers, $name);

        if ($headers !== [] || array_key_exists('headers', $options)) {
            $options['headers'] = $headers;
        }

        return $options;
    }

    /**
     * Read the normalized body and its serialization content type.
     *
     * Named body representations keep their encoding even when a custom
     * Content-Type header is present; that header remains in the options.
     *
     * @param  array<string, mixed>  $options
     * @return array{0: mixed, 1: ContentType|string|null}
     */
    public static function extractBodyAndContentType(array $options): array
    {
        $options = self::normalizeBodyOptions($options);
        $headers = $options['headers'] ?? [];
        $header = self::getHeaderValue($headers, 'Content-Type');
        $contentType = is_string($header)
            ? ContentType::normalizeContentType($header)
            : null;

        foreach (['json', 'form_params', 'multipart', 'body'] as $key) {
            if (! array_key_exists($key, $options)) {
                continue;
            }

            $default = match ($key) {
                'json' => ContentType::JSON,
                'form_params' => ContentType::FORM_URLENCODED,
                'multipart' => ContentType::MULTIPART,
                default => null,
            };

            $serializationType = $key === 'body'
                ? ($contentType ?? $default)
                : ($default ?? $contentType);

            return [$options[$key], $serializationType];
        }

        return [null, null];
    }

    /**
     * Get a header value without regard to the header-name casing.
     *
     * @param  array<string, mixed>  $headers
     */
    public static function getHeaderValue(
        array $headers,
        string $name,
    ): mixed {
        foreach ($headers as $header => $value) {
            if (strcasecmp((string) $header, $name) === 0) {
                return is_array($value) ? ($value[0] ?? null) : $value;
            }
        }

        return null;
    }

    /**
     * Validate options and throw on invalid values.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws InvalidArgumentException If options are invalid
     */
    public static function validate(array $options): void
    {
        // Validate method
        if (isset($options['method'])) {
            $method = $options['method'];
            if (! is_string($method) && ! ($method instanceof Method)) {
                throw new InvalidArgumentException(
                    'Method must be a string or Method enum',
                );
            }
        }

        // Validate timeout
        if (isset($options['timeout'])) {
            if (! is_int($options['timeout']) || $options['timeout'] < 0) {
                throw new InvalidArgumentException(
                    'Timeout must be a non-negative integer',
                );
            }
        }

        // Validate retries
        if (isset($options['retries'])) {
            if (! is_int($options['retries']) || $options['retries'] < 0) {
                throw new InvalidArgumentException(
                    'Retries must be a non-negative integer',
                );
            }
        }

        // Validate retry_delay
        if (isset($options['retry_delay'])) {
            if (
                ! is_int($options['retry_delay']) ||
                $options['retry_delay'] < 0
            ) {
                throw new InvalidArgumentException(
                    'Retry delay must be a non-negative integer',
                );
            }
        }

        // Validate base_uri format
        if (
            isset($options['base_uri']) &&
            ! filter_var($options['base_uri'], FILTER_VALIDATE_URL)
        ) {
            throw new InvalidArgumentException(
                "Invalid base URI: {$options['base_uri']}",
            );
        }

        if (
            isset($options['validation']) &&
            ! is_array($options['validation'])
        ) {
            throw new InvalidArgumentException(
                'Validation options must be an array.',
            );
        }

        if (isset($options['validation']) && is_array($options['validation'])) {
            $unsupported = array_diff(array_keys($options['validation']), [
                'request_schema',
                'response_schema',
                'openapi_spec',
                'validate_contracts',
            ]);
            if ($unsupported !== []) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Unsupported validation option: %s',
                        (string) reset($unsupported),
                    ),
                );
            }

            if (
                isset($options['validation']['openapi_spec']) &&
                ! is_string($options['validation']['openapi_spec'])
            ) {
                throw new InvalidArgumentException(
                    'The validation openapi_spec option must be a file path'
                        .' string.',
                );
            }

            if (
                isset($options['validation']['validate_contracts']) &&
                ! is_bool($options['validation']['validate_contracts'])
            ) {
                throw new InvalidArgumentException(
                    'The validation validate_contracts option must be a '
                        .'boolean.',
                );
            }
        }
    }

    /**
     * Extract options that are specific to Guzzle.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function toGuzzleOptions(array $options): array
    {
        $guzzleOptions = [];

        foreach (self::GUZZLE_OPTIONS as $key) {
            if (isset($options[$key])) {
                $guzzleOptions[$key] = $options[$key];
            }
        }

        return $guzzleOptions;
    }

    /**
     * Extract options specific to Fetch client behavior.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function toFetchOptions(array $options): array
    {
        $fetchOptions = [];

        foreach (self::FETCH_OPTIONS as $key) {
            if (isset($options[$key])) {
                $fetchOptions[$key] = $options[$key];
            }
        }

        return $fetchOptions;
    }

    /**
     * Apply authentication to options.
     *
     * @param  array<string, mixed>  $options
     * @param  string  $type  'basic', 'bearer', or 'token'
     * @param  mixed  $credentials  Auth credentials
     * @return array<string, mixed>
     */
    public static function withAuth(
        array $options,
        string $type,
        mixed $credentials,
    ): array {
        switch (strtolower($type)) {
            case 'basic':
                if (is_array($credentials) && count($credentials) === 2) {
                    $options['auth'] = $credentials;
                }
                break;

            case 'bearer':
            case 'token':
                $token = is_string($credentials) ? $credentials : '';
                if (! isset($options['headers'])) {
                    $options['headers'] = [];
                }
                $options['headers']['Authorization'] = 'Bearer '.$token;
                break;
        }

        return $options;
    }

    /**
     * Create options with a JSON body.
     *
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withJson(array $options, array $data): array
    {
        $options['json'] = $data;

        return self::normalizeBodyOptions($options);
    }

    /**
     * Create options with form data.
     *
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withForm(array $options, array $data): array
    {
        $options['form_params'] = $data;

        return self::normalizeBodyOptions($options);
    }

    /**
     * Create options with multipart data.
     *
     * @param  array<string, mixed>  $options
     * @param  array<int, array<string, mixed>>  $parts
     * @return array<string, mixed>
     */
    public static function withMultipart(array $options, array $parts): array
    {
        $options['multipart'] = $parts;

        return self::normalizeBodyOptions($options);
    }

    /**
     * Get a specific option value with a default.
     *
     * @param  array<string, mixed>  $options
     */
    public static function get(
        array $options,
        string $key,
        mixed $default = null,
    ): mixed {
        return $options[$key] ?? $default;
    }

    /**
     * Check if an option is set.
     *
     * @param  array<string, mixed>  $options
     */
    public static function has(array $options, string $key): bool
    {
        return isset($options[$key]);
    }

    /**
     * Get the HTTP method from options.
     *
     * @param  array<string, mixed>  $options
     */
    public static function getMethod(
        array $options,
        string $default = 'GET',
    ): string {
        $method = $options['method'] ?? $default;

        return $method instanceof Method
            ? $method->value
            : strtoupper((string) $method);
    }

    /**
     * Check if the options indicate an async request.
     *
     * @param  array<string, mixed>  $options
     */
    public static function isAsync(array $options): bool
    {
        return (bool) ($options['async'] ?? false);
    }

    /**
     * Normalize multipart array to the expected shape for Guzzle.
     *
     * Handles both list-style multipart arrays and single-part associative
     * arrays,
     * converting them to the standard Guzzle multipart format.
     *
     * @param  array<int, array{
     *     name: string,
     *     contents: mixed,
     *     headers?: array<string, string>
     * }>|array<string, mixed>  $multipart
     * @return array<int, array{
     *     name: string,
     *     contents: mixed,
     *     headers?: array<string, string>
     * }>
     */
    public static function normalizeMultipart(array $multipart): array
    {
        // Empty array or already a list - return as-is
        if ($multipart === [] || array_is_list($multipart)) {
            /**
             * Preserves the existing list shape for normalized multipart data.
             *
             * @var array<int, array{
             *     name: string,
             *     contents: mixed,
             *     headers?: array<string, string>
             * }> $multipart
             */
            return $multipart;
        }

        // Single associative array - convert to list format
        $part = [
            'name' => (string) ($multipart['name'] ?? 'file'),
            'contents' => $multipart['contents'] ?? ($multipart['body'] ?? ''),
        ];

        if (isset($multipart['headers']) && is_array($multipart['headers'])) {
            $part['headers'] = array_map(
                static fn ($v): string => (string) $v,
                $multipart['headers'],
            );
        }

        return [$part];
    }

    /**
     * Remove body-related options except the provided whitelist.
     *
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $allowed
     * @return array<string, mixed>
     */
    protected static function stripBodyOptionsExcept(
        array $options,
        array $allowed,
    ): array {
        foreach (self::BODY_OPTIONS as $key) {
            if (! in_array($key, $allowed, true)) {
                unset($options[$key]);
            }
        }

        return $options;
    }

    /**
     * Sets a header value while preserving the other request headers.
     *
     * @param  array<string, mixed>  $headers
     */
    private static function setHeaderValue(
        array &$headers,
        string $name,
        mixed $value,
    ): void {
        foreach ($headers as $header => $_) {
            if (strcasecmp((string) $header, $name) === 0) {
                $headers[$header] = $value;

                return;
            }
        }

        $headers[$name] = $value;
    }

    /**
     * Removes a header from the request options case-insensitively.
     *
     * @param  array<string, mixed>  $headers
     */
    private static function removeHeader(array &$headers, string $name): void
    {
        foreach (array_keys($headers) as $header) {
            if (strcasecmp((string) $header, $name) === 0) {
                unset($headers[$header]);
            }
        }
    }

    /**
     * Merge headers while allowing later values to replace names
     * case-insensitively.
     *
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private static function mergeHeaders(
        array $current,
        array $incoming,
    ): array {
        foreach ($incoming as $name => $value) {
            self::removeHeader($current, (string) $name);
            $current[$name] = $value;
        }

        return $current;
    }
}
