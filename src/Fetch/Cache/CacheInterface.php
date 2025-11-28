<?php

declare(strict_types=1);

namespace Fetch\Cache;

/**
 * Interface for cache backends used by the HTTP client.
 */
interface CacheInterface
{
    /**
     * Get a cached response by key.
     *
     * @param  string  $key  The cache key
     * @return CachedResponse|null The cached response, or null if not found
     */
    public function get(string $key): ?CachedResponse;

    /**
     * Store a response in the cache.
     *
     * @param  string  $key  The cache key
     * @param  CachedResponse  $cachedResponse  The response to cache
     * @param  int|null  $ttl  Time to live in seconds
     * @return bool True if the item was stored, false otherwise
     */
    public function set(string $key, CachedResponse $cachedResponse, ?int $ttl = null): bool;

    /**
     * Delete a cached response by key.
     *
     * @param  string  $key  The cache key
     * @return bool True if the item was deleted, false otherwise
     */
    public function delete(string $key): bool;

    /**
     * Check if a key exists in the cache.
     *
     * @param  string  $key  The cache key
     * @return bool True if the key exists, false otherwise
     */
    public function has(string $key): bool;

    /**
     * Clear all cached responses.
     */
    public function clear(): void;

    /**
     * Remove expired entries from the cache.
     *
     * @return int The number of entries removed
     */
    public function prune(): int;
}
