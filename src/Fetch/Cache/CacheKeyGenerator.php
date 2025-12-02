<?php

declare(strict_types=1);

namespace Fetch\Cache;

/**
 * Generates cache keys for HTTP requests.
 */
class CacheKeyGenerator
{
    /**
     * Default headers that should be included in cache key variation.
     *
     * @var array<int, string>
     */
    private const DEFAULT_VARY_HEADERS = ['Accept', 'Accept-Encoding', 'Accept-Language'];

    /**
     * The prefix for all cache keys.
     */
    private string $prefix;

    /**
     * Headers to use for cache key variation.
     *
     * @var array<int, string>
     */
    private array $varyHeaders;

    /**
     * Create a new cache key generator.
     *
     * @param  array<int, string>  $varyHeaders  Headers to use for cache key variation
     */
    public function __construct(string $prefix = 'fetch:', array $varyHeaders = [])
    {
        $this->prefix = $prefix;
        $this->varyHeaders = $varyHeaders ?: self::DEFAULT_VARY_HEADERS;
    }

    /**
     * Generate a cache key for a request.
     *
     * @param  array<string, mixed>  $options  Request options
     */
    public function generate(string $method, string $uri, array $options = []): string
    {
        $components = [
            'method' => strtoupper($method),
            'uri' => $this->normalizeUri($uri),
            'headers' => $this->extractVaryHeaders($options),
        ];

        $upperMethod = $components['method'];

        // Include query parameters in the hash (normalized)
        if (isset($options['query']) && is_array($options['query'])) {
            $components['query'] = $this->normalizeArray($options['query']);
        }

        // Include body hash for unsafe methods when opt-in is set
        $cacheConfig = $options['cache'] ?? [];
        $bodyCachingEnabled = is_array($cacheConfig) && ($cacheConfig['cache_body'] ?? false);

        if (! in_array($upperMethod, ['GET', 'HEAD'], true) && $bodyCachingEnabled) {
            $bodyHash = $this->hashBody($options);
            if ($bodyHash !== null) {
                $components['body_hash'] = $bodyHash;
            }
        }

        return $this->prefix.hash('sha256', serialize($components));
    }

    /**
     * Generate a cache key with a custom key provided by the user.
     */
    public function generateCustom(string $customKey): string
    {
        return $this->prefix.$customKey;
    }

    /**
     * Get the vary headers.
     *
     * @return array<int, string>
     */
    public function getVaryHeaders(): array
    {
        return $this->varyHeaders;
    }

    /**
     * Set the vary headers.
     *
     * @param  array<int, string>  $varyHeaders
     */
    public function setVaryHeaders(array $varyHeaders): void
    {
        $this->varyHeaders = $varyHeaders;
    }

    /**
     * Normalize a URI for consistent cache key generation.
     */
    private function normalizeUri(string $uri): string
    {
        $parsed = parse_url($uri);
        if ($parsed === false) {
            return $uri;
        }

        // Build normalized URI
        $normalized = '';

        if (isset($parsed['scheme'])) {
            $normalized .= strtolower($parsed['scheme']).'://';
        }

        if (isset($parsed['host'])) {
            $normalized .= strtolower($parsed['host']);
        }

        if (isset($parsed['port'])) {
            // Only include non-default ports
            $defaultPorts = ['http' => 80, 'https' => 443];
            $scheme = $parsed['scheme'] ?? 'http';
            if (! isset($defaultPorts[$scheme]) || $parsed['port'] !== $defaultPorts[$scheme]) {
                $normalized .= ':'.$parsed['port'];
            }
        }

        $normalized .= $parsed['path'] ?? '/';

        // Sort and normalize query parameters
        if (isset($parsed['query'])) {
            $normalized .= '?'.$this->normalizeQuery($parsed['query']);
        }

        return $normalized;
    }

    /**
     * Normalize query parameters for consistent cache key generation.
     */
    private function normalizeQuery(string $query): string
    {
        // Parse query string into array of [key, value] pairs, preserving duplicates and order
        $pairs = [];
        foreach (explode('&', $query) as $part) {
            if ($part === '') {
                continue;
            }
            $kv = explode('=', $part, 2);
            $key = urldecode($kv[0]);
            $value = isset($kv[1]) ? urldecode($kv[1]) : '';
            $pairs[] = [$key, $value];
        }
        // Sort pairs by key, then by value, to normalize
        usort($pairs, function ($a, $b) {
            if ($a[0] === $b[0]) {
                return strcmp($a[1], $b[1]);
            }

            return strcmp($a[0], $b[0]);
        });
        // Rebuild query string
        $normalized = [];
        foreach ($pairs as [$key, $value]) {
            $normalized[] = rawurlencode($key).'='.rawurlencode($value);
        }

        return implode('&', $normalized);
    }

    /**
     * Normalize an array recursively for deterministic hashing.
     *
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    private function normalizeArray(array $value): array
    {
        ksort($value);

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->normalizeArray($item);
            }
        }

        return $value;
    }

    /**
     * Extract vary headers from request options.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, string>
     */
    private function extractVaryHeaders(array $options): array
    {
        $varyHeaders = [];
        $headers = $options['headers'] ?? [];

        foreach ($this->varyHeaders as $header) {
            foreach ($headers as $name => $value) {
                if (strcasecmp($name, $header) === 0) {
                    $varyHeaders[strtolower($header)] = is_array($value) ? implode(', ', $value) : (string) $value;
                    break;
                }
            }
        }

        return $varyHeaders;
    }

    /**
     * Hash the request body for cache key generation.
     *
     * @param  array<string, mixed>  $options
     */
    private function hashBody(array $options): ?string
    {
        if (isset($options['json'])) {
            return hash('sha256', json_encode($options['json']) ?: '');
        }

        if (isset($options['body'])) {
            $body = $options['body'];
            if (is_string($body)) {
                return hash('sha256', $body);
            }

            return hash('sha256', serialize($body));
        }

        if (isset($options['form_params'])) {
            return hash('sha256', http_build_query($options['form_params']));
        }

        return null;
    }
}
