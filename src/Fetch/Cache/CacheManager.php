<?php

declare(strict_types=1);

namespace Fetch\Cache;

use Fetch\Http\Response;
use Fetch\Interfaces\Response as ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Manages HTTP caching for the Fetch client.
 *
 * This service encapsulates all cache-related operations including:
 * - Cache key generation
 * - Response storage and retrieval
 * - Cache header parsing and validation
 * - Conditional request handling (ETag/Last-Modified)
 * - Stale-while-revalidate and stale-if-error policies
 *
 * **Design Decisions:**
 *
 * 1. **Caching only for safe methods by default**: GET and HEAD are cacheable.
 *    POST/PUT/PATCH/DELETE are not cached unless explicitly opted in with body hashing.
 *
 * 2. **Async requests bypass cache**: Asynchronous requests do not check or store
 *    cached responses. This is intentional to avoid blocking on cache operations.
 *
 * 3. **RFC 7234 compliance**: Respects Cache-Control headers, ETag, Last-Modified,
 *    and other standard caching directives.
 *
 * @see https://tools.ietf.org/html/rfc7234 HTTP/1.1 Caching
 */
final class CacheManager
{
    /**
     * Default cache configuration.
     *
     * @var array<string, mixed>
     */
    private const DEFAULT_OPTIONS = [
        'respect_cache_headers' => true,
        'default_ttl' => 3600,
        'stale_while_revalidate' => 0,
        'stale_if_error' => 0,
        'cache_methods' => ['GET', 'HEAD'],
        'cache_status_codes' => [200, 203, 204, 206, 300, 301, 404, 410],
        'vary_headers' => ['Accept', 'Accept-Encoding', 'Accept-Language'],
        'is_shared_cache' => false,
    ];

    /**
     * The cache backend.
     */
    private CacheInterface $cache;

    /**
     * Cache configuration options.
     *
     * @var array<string, mixed>
     */
    private array $options;

    /**
     * The cache key generator.
     */
    private CacheKeyGenerator $keyGenerator;

    /**
     * Logger for cache events.
     */
    private LoggerInterface $logger;

    /**
     * Log level for cache events.
     */
    private string $logLevel;

    /**
     * Create a new cache manager.
     *
     * @param  CacheInterface|null  $cache  Cache backend (defaults to MemoryCache)
     * @param  array<string, mixed>  $options  Cache options
     * @param  LoggerInterface|null  $logger  Logger for events
     * @param  string  $logLevel  Log level for cache events
     */
    public function __construct(
        ?CacheInterface $cache = null,
        array $options = [],
        ?LoggerInterface $logger = null,
        string $logLevel = 'debug',
    ) {
        $this->cache = $cache ?? new MemoryCache;
        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);
        $this->logger = $logger ?? new NullLogger;
        $this->logLevel = $logLevel;

        $this->keyGenerator = new CacheKeyGenerator(
            'fetch:',
            $this->options['vary_headers'] ?? []
        );
    }

    /**
     * Create a cache manager with custom configuration.
     *
     * @param  array<string, mixed>  $options
     */
    public static function create(
        ?CacheInterface $cache = null,
        array $options = [],
        ?LoggerInterface $logger = null,
    ): self {
        return new self($cache, $options, $logger);
    }

    /**
     * Create a disabled cache manager that performs no caching.
     */
    public static function disabled(): self
    {
        return new self(new MemoryCache, ['enabled' => false]);
    }

    /**
     * Check if caching is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->options['enabled'] ?? true;
    }

    /**
     * Get the cache backend.
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * Get the cache options.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Determine if the request should use caching.
     *
     * @param  string  $method  HTTP method
     * @param  bool  $isAsync  Whether the request is async
     * @param  array<string, mixed>  $requestOptions  Per-request options
     */
    public function shouldUseCache(string $method, bool $isAsync, array $requestOptions = []): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        // Async requests bypass cache by design
        if ($isAsync) {
            $this->logBypass('async_requests_not_cached');

            return false;
        }

        // Check if method is cacheable
        if (! $this->isCacheableMethod($method)) {
            return false;
        }

        // Check per-request cache settings
        $cacheConfig = $requestOptions['cache'] ?? [];
        if (is_bool($cacheConfig)) {
            return $cacheConfig;
        }
        if (is_array($cacheConfig) && isset($cacheConfig['enabled'])) {
            return (bool) $cacheConfig['enabled'];
        }

        return true;
    }

    /**
     * Try to get a cached response for the request.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     * @param  array<string, mixed>  $requestOptions  Request options
     * @return array{response: Response|null, cached: CachedResponse|null, status: string}
     */
    public function getCachedResponse(string $method, string $uri, array $requestOptions = []): array
    {
        if (! $this->shouldUseCache($method, $requestOptions['async'] ?? false, $requestOptions)) {
            $this->logEvent('BYPASS', $method, $uri);

            return ['response' => null, 'cached' => null, 'status' => 'BYPASS'];
        }

        // Check for force refresh
        $cacheConfig = $requestOptions['cache'] ?? [];
        if (is_array($cacheConfig) && ($cacheConfig['force_refresh'] ?? false)) {
            $this->logEvent('REFRESH', $method, $uri);

            return ['response' => null, 'cached' => null, 'status' => 'REFRESH'];
        }

        $key = $this->generateKey($method, $uri, $requestOptions);
        $cached = $this->cache->get($key);

        if ($cached === null) {
            $this->logEvent('MISS', $method, $uri);

            return ['response' => null, 'cached' => null, 'status' => 'MISS'];
        }

        // Check if fresh
        if ($cached->isFresh()) {
            // Respect no-cache: must revalidate every time
            $cachedCacheControl = CacheControl::parse($cached->getHeaders()['Cache-Control'][0] ?? '');
            if ($cachedCacheControl->hasNoCache()) {
                $this->logEvent('REVALIDATE', $method, $uri);

                return ['response' => null, 'cached' => $cached, 'status' => 'REVALIDATE'];
            }

            $response = $this->createResponseFromCached($cached);
            $response = $response->withHeader('X-Cache-Status', 'HIT');
            $this->logEvent('HIT', $method, $uri);

            return ['response' => $response, 'cached' => $cached, 'status' => 'HIT'];
        }

        // Check for stale-while-revalidate
        $staleWhileRevalidate = $this->options['stale_while_revalidate'] ?? 0;
        if ($staleWhileRevalidate > 0 && $cached->isUsableAsStale($staleWhileRevalidate)) {
            $response = $this->createResponseFromCached($cached);
            $response = $response->withHeader('X-Cache-Status', 'STALE');
            $this->logEvent('STALE', $method, $uri);

            return ['response' => $response, 'cached' => $cached, 'status' => 'STALE'];
        }

        $this->logEvent('EXPIRED', $method, $uri);

        return ['response' => null, 'cached' => $cached, 'status' => 'EXPIRED'];
    }

    /**
     * Store a response in the cache.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     * @param  ResponseInterface  $response  The response to cache
     * @param  array<string, mixed>  $requestOptions  Request options
     */
    public function storeResponse(
        string $method,
        string $uri,
        ResponseInterface $response,
        array $requestOptions = [],
    ): void {
        if (! $this->shouldUseCache($method, $requestOptions['async'] ?? false, $requestOptions)) {
            return;
        }

        // Parse Cache-Control headers
        $cacheControl = CacheControl::fromResponse($response);

        if (! $this->shouldStoreResponse($method, $response, $cacheControl, $requestOptions)) {
            return;
        }

        // Calculate TTL
        $ttl = $this->calculateTtl($response, $cacheControl, $requestOptions);
        if ($ttl !== null && $ttl <= 0) {
            return;
        }

        $key = $this->generateKey($method, $uri, $requestOptions);
        $cachedResponse = CachedResponse::fromResponse($response, null);

        $this->cache->set($key, $cachedResponse, $ttl);
        $this->logEvent('STORE', $method, $uri);
    }

    /**
     * Add conditional headers for revalidation.
     *
     * @param  array<string, mixed>  $options  Request options
     * @param  CachedResponse|null  $cached  Cached response to revalidate
     * @return array<string, mixed> Modified options
     */
    public function addConditionalHeaders(array $options, ?CachedResponse $cached): array
    {
        if ($cached === null) {
            return $options;
        }

        if (! isset($options['headers'])) {
            $options['headers'] = [];
        }

        // Add If-None-Match for ETag
        $etag = $cached->getETag();
        if ($etag !== null) {
            $options['headers']['If-None-Match'] = $etag;
        }

        // Add If-Modified-Since for Last-Modified
        $lastModified = $cached->getLastModified();
        if ($lastModified !== null) {
            $options['headers']['If-Modified-Since'] = $lastModified;
        }

        return $options;
    }

    /**
     * Handle a 304 Not Modified response.
     *
     * @param  CachedResponse  $cached  The cached response
     * @param  ResponseInterface  $notModifiedResponse  The 304 response
     */
    public function handleNotModified(
        CachedResponse $cached,
        ResponseInterface $notModifiedResponse,
    ): Response {
        // Create a new response with the cached body but potentially updated headers
        $headers = $cached->getHeaders();

        // Update headers from the 304 response (except content-related ones)
        foreach ($notModifiedResponse->getHeaders() as $name => $values) {
            $lower = strtolower($name);
            if (in_array($lower, ['content-length', 'content-encoding', 'transfer-encoding'], true)) {
                continue;
            }
            $headers[$name] = $values;
        }

        $response = new Response(
            $cached->getStatusCode(),
            $headers,
            $cached->getBody()
        );

        return $response->withHeader('X-Cache-Status', 'REVALIDATED');
    }

    /**
     * Handle stale-if-error: serve stale response on error.
     *
     * @param  CachedResponse|null  $cached  The cached response
     */
    public function handleStaleIfError(?CachedResponse $cached, string $method, string $uri): ?Response
    {
        if ($cached === null) {
            return null;
        }

        $staleIfError = $this->options['stale_if_error'] ?? 0;
        if ($staleIfError <= 0) {
            return null;
        }

        if (! $cached->isUsableAsStale($staleIfError)) {
            return null;
        }

        $response = $this->createResponseFromCached($cached);

        $this->logEvent('STALE-IF-ERROR', $method, $uri);

        return $response->withHeader('X-Cache-Status', 'STALE-IF-ERROR');
    }

    /**
     * Convenience alias matching legacy trait naming.
     *
     * @param  array<string, mixed>  $requestOptions
     */
    public function cacheResponse(string $method, string $uri, ResponseInterface $response, array $requestOptions = []): void
    {
        $this->storeResponse($method, $uri, $response, $requestOptions);
    }

    /**
     * Generate a cache key for the request.
     *
     * @param  array<string, mixed>  $options
     */
    public function generateKey(string $method, string $uri, array $options = []): string
    {
        // Check for custom cache key
        $cacheConfig = $options['cache'] ?? [];
        if (is_array($cacheConfig) && isset($cacheConfig['key'])) {
            return $this->keyGenerator->generateCustom($cacheConfig['key']);
        }

        return $this->keyGenerator->generate($method, $uri, $options);
    }

    /**
     * Clear the entire cache.
     */
    public function clear(): void
    {
        $this->cache->clear();
    }

    /**
     * Delete a specific cache entry.
     *
     * @param  array<string, mixed>  $options
     */
    public function delete(string $method, string $uri, array $options = []): bool
    {
        $key = $this->generateKey($method, $uri, $options);

        return $this->cache->delete($key);
    }

    /**
     * Check if the request method is cacheable.
     */
    private function isCacheableMethod(string $method): bool
    {
        $cacheableMethods = $this->options['cache_methods'] ?? ['GET', 'HEAD'];

        return in_array(strtoupper($method), $cacheableMethods, true);
    }

    /**
     * Check if the response status code is cacheable.
     */
    private function isCacheableStatusCode(int $statusCode): bool
    {
        $cacheableStatusCodes = $this->options['cache_status_codes'] ?? [200];

        return in_array($statusCode, $cacheableStatusCodes, true);
    }

    /**
     * Determine if a response should be stored.
     *
     * @param  array<string, mixed>  $requestOptions
     */
    private function shouldStoreResponse(
        string $method,
        ResponseInterface $response,
        CacheControl $cacheControl,
        array $requestOptions = [],
    ): bool {
        if (! $this->isCacheableMethod($method)) {
            return false;
        }

        if (! $this->isCacheableStatusCode($response->getStatusCode())) {
            return false;
        }

        // Check respect_headers setting
        $cacheConfig = $requestOptions['cache'] ?? [];
        $respectHeaders = is_array($cacheConfig)
            ? ($cacheConfig['respect_headers'] ?? ($this->options['respect_cache_headers'] ?? true))
            : ($this->options['respect_cache_headers'] ?? true);

        if (! $respectHeaders) {
            return true;
        }

        $isShared = is_array($cacheConfig)
            ? ($cacheConfig['is_shared_cache'] ?? ($this->options['is_shared_cache'] ?? false))
            : ($this->options['is_shared_cache'] ?? false);

        return $cacheControl->shouldCache($response, $isShared);
    }

    /**
     * Calculate TTL for a response.
     *
     * @param  array<string, mixed>  $requestOptions
     */
    private function calculateTtl(
        ResponseInterface $response,
        CacheControl $cacheControl,
        array $requestOptions = [],
    ): int {
        // Check for per-request TTL
        $cacheConfig = $requestOptions['cache'] ?? [];
        if (is_array($cacheConfig) && isset($cacheConfig['ttl'])) {
            return (int) $cacheConfig['ttl'];
        }

        // Get TTL from Cache-Control headers
        if ($this->options['respect_cache_headers'] ?? true) {
            $isSharedCache = $this->options['is_shared_cache'] ?? false;
            $headerTtl = $cacheControl->getTtl($response, $isSharedCache);
            if ($headerTtl !== null) {
                return $headerTtl;
            }
        }

        // Fall back to default TTL
        return $this->options['default_ttl'] ?? 3600;
    }

    /**
     * Create a Response from a CachedResponse.
     */
    private function createResponseFromCached(CachedResponse $cached): Response
    {
        return new Response(
            $cached->getStatusCode(),
            $cached->getHeaders(),
            $cached->getBody()
        );
    }

    /**
     * Log a cache event.
     */
    private function logEvent(string $status, string $method, string $uri): void
    {
        $this->logger->log($this->logLevel, 'Cache event', [
            'status' => $status,
            'method' => strtoupper($method),
            'uri' => $uri,
        ]);
    }

    /**
     * Log a cache bypass reason.
     */
    private function logBypass(string $reason): void
    {
        $this->logger->debug('Cache bypassed', ['reason' => $reason]);
    }
}
