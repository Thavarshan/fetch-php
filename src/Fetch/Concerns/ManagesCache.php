<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Cache\CacheControl;
use Fetch\Cache\CachedResponse;
use Fetch\Cache\CacheInterface;
use Fetch\Cache\CacheKeyGenerator;
use Fetch\Cache\MemoryCache;
use Fetch\Http\Response;
use Fetch\Interfaces\ClientHandler;
use Fetch\Interfaces\Response as ResponseInterface;

/**
 * Trait for managing HTTP caching.
 *
 * Note: Caching is only supported for synchronous requests. Asynchronous requests
 * bypass the cache entirely and do not check or store cached responses.
 */
trait ManagesCache
{
    /**
     * The cache backend.
     */
    protected ?CacheInterface $cache = null;

    /**
     * The cache key generator.
     */
    protected ?CacheKeyGenerator $cacheKeyGenerator = null;

    /**
     * Cache configuration options.
     *
     * @var array<string, mixed>
     */
    protected array $cacheOptions = [
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
     * Enable caching with optional configuration.
     *
     * @param  array<string, mixed>  $options  Cache options
     */
    public function withCache(?CacheInterface $cache = null, array $options = []): ClientHandler
    {
        $this->cache = $cache ?? new MemoryCache;
        $this->cacheOptions = array_merge($this->cacheOptions, $options);

        // Initialize the cache key generator with vary headers
        $varyHeaders = $this->cacheOptions['vary_headers'] ?? [];
        $this->cacheKeyGenerator = new CacheKeyGenerator('fetch:', $varyHeaders);

        return $this;
    }

    /**
     * Disable caching.
     */
    public function withoutCache(): ClientHandler
    {
        $this->cache = null;
        $this->cacheKeyGenerator = null;

        return $this;
    }

    /**
     * Get the cache instance.
     */
    public function getCache(): ?CacheInterface
    {
        return $this->cache;
    }

    /**
     * Check if caching is enabled.
     */
    public function isCacheEnabled(): bool
    {
        return $this->cache !== null;
    }

    /**
     * Check if the request method is cacheable.
     */
    protected function isCacheableMethod(string $method): bool
    {
        $cacheableMethods = $this->cacheOptions['cache_methods'] ?? ['GET', 'HEAD'];

        return in_array(strtoupper($method), $cacheableMethods, true);
    }

    /**
     * Check if the response status code is cacheable.
     */
    protected function isCacheableStatusCode(int $statusCode): bool
    {
        $cacheableStatusCodes = $this->cacheOptions['cache_status_codes'] ?? [200];

        return in_array($statusCode, $cacheableStatusCodes, true);
    }

    /**
     * Generate a cache key for the request.
     *
     * @param  array<string, mixed>  $options  Request options
     */
    protected function generateCacheKey(string $method, string $uri, array $options = []): string
    {
        // Check for custom cache key
        $cacheConfig = $options['cache'] ?? [];
        if (is_array($cacheConfig) && isset($cacheConfig['key'])) {
            return $this->getCacheKeyGenerator()->generateCustom($cacheConfig['key']);
        }

        return $this->getCacheKeyGenerator()->generate($method, $uri, $options);
    }

    /**
     * Get the cache key generator.
     */
    protected function getCacheKeyGenerator(): CacheKeyGenerator
    {
        if ($this->cacheKeyGenerator === null) {
            $this->cacheKeyGenerator = new CacheKeyGenerator('fetch:', $this->cacheOptions['vary_headers'] ?? []);
        }

        return $this->cacheKeyGenerator;
    }

    /**
     * Try to get a cached response.
     *
     * @param  array<string, mixed>  $options  Request options
     * @return array{response: Response|null, cached: CachedResponse|null, status: string}
     */
    protected function getCachedResponse(string $method, string $uri, array $options = []): array
    {
        if ($this->cache === null || ! $this->isCacheableMethod($method)) {
            return ['response' => null, 'cached' => null, 'status' => 'BYPASS'];
        }

        // Check for force refresh
        $cacheConfig = $options['cache'] ?? [];
        if (is_array($cacheConfig) && ($cacheConfig['force_refresh'] ?? false)) {
            return ['response' => null, 'cached' => null, 'status' => 'REFRESH'];
        }

        $key = $this->generateCacheKey($method, $uri, $options);
        $cached = $this->cache->get($key);

        if ($cached === null) {
            return ['response' => null, 'cached' => null, 'status' => 'MISS'];
        }

        // Check if fresh
        if ($cached->isFresh()) {
            $response = $this->createResponseFromCached($cached);
            $response = $response->withHeader('X-Cache-Status', 'HIT');

            return ['response' => $response, 'cached' => $cached, 'status' => 'HIT'];
        }

        // Check for stale-while-revalidate
        $staleWhileRevalidate = $this->cacheOptions['stale_while_revalidate'] ?? 0;
        if ($staleWhileRevalidate > 0 && $cached->isUsableAsStale($staleWhileRevalidate)) {
            $response = $this->createResponseFromCached($cached);
            $response = $response->withHeader('X-Cache-Status', 'STALE');

            return ['response' => $response, 'cached' => $cached, 'status' => 'STALE'];
        }

        return ['response' => null, 'cached' => $cached, 'status' => 'EXPIRED'];
    }

    /**
     * Store a response in the cache.
     *
     * @param  array<string, mixed>  $options  Request options
     */
    protected function cacheResponse(string $method, string $uri, ResponseInterface $response, array $options = []): void
    {
        if ($this->cache === null || ! $this->isCacheableMethod($method)) {
            return;
        }

        if (! $this->isCacheableStatusCode($response->getStatusCode())) {
            return;
        }

        // Check Cache-Control headers
        $cacheControl = CacheControl::fromResponse($response);

        if ($this->cacheOptions['respect_cache_headers'] ?? true) {
            $isSharedCache = $this->cacheOptions['is_shared_cache'] ?? false;
            if (! $cacheControl->shouldCache($response, $isSharedCache)) {
                return;
            }
        }

        // Calculate TTL
        $ttl = $this->calculateTtl($response, $cacheControl, $options);
        if ($ttl !== null && $ttl <= 0) {
            return;
        }

        $key = $this->generateCacheKey($method, $uri, $options);
        $cachedResponse = CachedResponse::fromResponse($response, null);

        $this->cache->set($key, $cachedResponse, $ttl);
    }

    /**
     * Calculate the TTL for a response.
     *
     * @param  array<string, mixed>  $options  Request options
     */
    protected function calculateTtl(ResponseInterface $response, CacheControl $cacheControl, array $options = []): ?int
    {
        // Check for per-request TTL
        $cacheConfig = $options['cache'] ?? [];
        if (is_array($cacheConfig) && isset($cacheConfig['ttl'])) {
            return (int) $cacheConfig['ttl'];
        }

        // Get TTL from Cache-Control headers
        if ($this->cacheOptions['respect_cache_headers'] ?? true) {
            $isSharedCache = $this->cacheOptions['is_shared_cache'] ?? false;
            $headerTtl = $cacheControl->getTtl($response, $isSharedCache);
            if ($headerTtl !== null) {
                return $headerTtl;
            }
        }

        // Fall back to default TTL
        return $this->cacheOptions['default_ttl'] ?? 3600;
    }

    /**
     * Add conditional headers to a request based on cached response.
     *
     * @param  array<string, mixed>  $options  Request options
     * @return array<string, mixed> Modified options
     */
    protected function addConditionalHeaders(array $options, ?CachedResponse $cached): array
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
     */
    protected function handleNotModified(CachedResponse $cached, ResponseInterface $response): Response
    {
        // Create a new response with the cached body but potentially updated headers
        $headers = $cached->getHeaders();

        // Update headers from the 304 response
        foreach ($response->getHeaders() as $name => $values) {
            // Don't copy certain headers from the 304
            if (in_array(strtolower($name), ['content-length', 'content-encoding', 'transfer-encoding'], true)) {
                continue;
            }
            $headers[$name] = $values;
        }

        $newResponse = new Response(
            $cached->getStatusCode(),
            $headers,
            $cached->getBody()
        );

        return $newResponse->withHeader('X-Cache-Status', 'REVALIDATED');
    }

    /**
     * Create a Response from a CachedResponse.
     */
    protected function createResponseFromCached(CachedResponse $cached): Response
    {
        return new Response(
            $cached->getStatusCode(),
            $cached->getHeaders(),
            $cached->getBody()
        );
    }

    /**
     * Handle stale-if-error: serve stale response on error.
     */
    protected function handleStaleIfError(?CachedResponse $cached): ?Response
    {
        if ($cached === null) {
            return null;
        }

        $staleIfError = $this->cacheOptions['stale_if_error'] ?? 0;
        if ($staleIfError <= 0) {
            return null;
        }

        if (! $cached->isUsableAsStale($staleIfError)) {
            return null;
        }

        $response = $this->createResponseFromCached($cached);

        return $response->withHeader('X-Cache-Status', 'STALE-IF-ERROR');
    }
}
