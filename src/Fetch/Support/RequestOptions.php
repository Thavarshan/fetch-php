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
    private const BODY_OPTIONS = ['body', 'json', 'form', 'form_params', 'multipart'];

    /**
     * Standard Guzzle options that are passed through.
     *
     * @var array<int, string>
     */
    private const GUZZLE_OPTIONS = [
        'headers', 'json', 'form_params', 'multipart', 'body',
        'query', 'auth', 'verify', 'proxy', 'cookies', 'allow_redirects',
        'cert', 'ssl_key', 'stream', 'connect_timeout', 'read_timeout',
        'debug', 'sink', 'version', 'decode_content', 'curl', 'timeout', 'progress',
    ];

    /**
     * Options that control client behavior but aren't sent to Guzzle.
     *
     * @var array<int, string>
     */
    private const FETCH_OPTIONS = [
        'method', 'uri', 'base_uri', 'async', 'retries', 'max_retries',
        'retry_delay', 'retry_status_codes', 'retry_exceptions',
        'cache', 'profiler', 'content_type', 'token',
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

        foreach ($optionSets as $options) {
            // Normalize option keys to canonical names
            $options = self::normalizeOptionKeys($options);

            // Normalize body options
            $options = self::normalizeBodyOptions($options);

            // Deep merge headers
            if (isset($options['headers']) && isset($result['headers'])) {
                $options['headers'] = array_merge($result['headers'], $options['headers']);
            }

            // Deep merge query parameters
            if (isset($options['query']) && isset($result['query'])) {
                $options['query'] = array_merge($result['query'], $options['query']);
            }

            // Deep merge cache options
            if (isset($options['cache']) && is_array($options['cache']) && isset($result['cache']) && is_array($result['cache'])) {
                $options['cache'] = array_merge($result['cache'], $options['cache']);
            }

            $result = array_merge($result, $options);
        }

        return $result;
    }

    /**
     * Normalize option keys to canonical names.
     *
     * This method standardizes option keys while maintaining backward compatibility:
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
    public static function normalizeBodyOptions(array $options): array
    {
        $headers = $options['headers'] ?? [];
        $contentTypeOverride = $options['content_type'] ?? null;

        $setContentType = static function (ContentType|string $type) use (&$headers, $contentTypeOverride): void {
            if ($contentTypeOverride !== null) {
                $headers['Content-Type'] = $contentTypeOverride instanceof ContentType
                    ? $contentTypeOverride->value
                    : (string) $contentTypeOverride;

                return;
            }

            if (! isset($headers['Content-Type'])) {
                $headers['Content-Type'] = $type instanceof ContentType ? $type->value : (string) $type;
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
            // multipart sets its own content type; ensure header is removed to avoid conflicts
            unset($headers['Content-Type']);
        } elseif (isset($options['body'])) {
            $options = self::stripBodyOptionsExcept($options, ['body']);
            if (is_array($options['body'])) {
                // Check if explicit content type requests JSON or no content type specified
                $isJsonContentType = $contentTypeOverride === null
                    || (is_string($contentTypeOverride) && str_contains($contentTypeOverride, 'json'))
                    || ($contentTypeOverride instanceof ContentType && $contentTypeOverride === ContentType::JSON);

                if ($isJsonContentType) {
                    // Convert array body to json option for Guzzle
                    $options['json'] = $options['body'];
                    unset($options['body']);
                    $setContentType(ContentType::JSON);
                } else {
                    // Array body with non-JSON content type - encode to string but keep user's content type
                    // This preserves backward compatibility for withBody($array, 'text/plain') etc.
                    $options['body'] = json_encode($options['body']);
                    $setContentType($contentTypeOverride);
                }
            } elseif ($contentTypeOverride !== null) {
                // String/stream body with explicit content type
                $setContentType($contentTypeOverride);
            }
        }

        if ($headers !== []) {
            $options['headers'] = $headers;
        }

        unset($options['content_type']); // normalized into headers

        return $options;
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
            if (! is_string($method) && ! $method instanceof Method) {
                throw new InvalidArgumentException('Method must be a string or Method enum');
            }
        }

        // Validate timeout
        if (isset($options['timeout'])) {
            if (! is_int($options['timeout']) || $options['timeout'] < 0) {
                throw new InvalidArgumentException('Timeout must be a non-negative integer');
            }
        }

        // Validate retries
        if (isset($options['retries'])) {
            if (! is_int($options['retries']) || $options['retries'] < 0) {
                throw new InvalidArgumentException('Retries must be a non-negative integer');
            }
        }

        // Validate retry_delay
        if (isset($options['retry_delay'])) {
            if (! is_int($options['retry_delay']) || $options['retry_delay'] < 0) {
                throw new InvalidArgumentException('Retry delay must be a non-negative integer');
            }
        }

        // Validate base_uri format
        if (isset($options['base_uri']) && ! filter_var($options['base_uri'], FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid base URI: {$options['base_uri']}");
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
    public static function withAuth(array $options, string $type, mixed $credentials): array
    {
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
    public static function get(array $options, string $key, mixed $default = null): mixed
    {
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
    public static function getMethod(array $options, string $default = 'GET'): string
    {
        $method = $options['method'] ?? $default;

        return $method instanceof Method ? $method->value : strtoupper((string) $method);
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
     * Handles both list-style multipart arrays and single-part associative arrays,
     * converting them to the standard Guzzle multipart format.
     *
     * @param  array<int, array{name: string, contents: mixed, headers?: array<string, string>}>|array<string, mixed>  $multipart
     * @return array<int, array{name: string, contents: mixed, headers?: array<string, string>}>
     */
    public static function normalizeMultipart(array $multipart): array
    {
        // Empty array or already a list - return as-is
        if ($multipart === [] || array_is_list($multipart)) {
            /** @var array<int, array{name: string, contents: mixed, headers?: array<string, string>}> $multipart */
            return $multipart;
        }

        // Single associative array - convert to list format
        $part = [
            'name' => (string) ($multipart['name'] ?? 'file'),
            'contents' => $multipart['contents'] ?? ($multipart['body'] ?? ''),
        ];

        if (isset($multipart['headers']) && is_array($multipart['headers'])) {
            $part['headers'] = array_map(static fn ($v): string => (string) $v, $multipart['headers']);
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
    protected static function stripBodyOptionsExcept(array $options, array $allowed): array
    {
        foreach (self::BODY_OPTIONS as $key) {
            if (! in_array($key, $allowed, true)) {
                unset($options[$key]);
            }
        }

        return $options;
    }
}
