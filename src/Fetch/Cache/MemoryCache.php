<?php

declare(strict_types=1);

namespace Fetch\Cache;

/**
 * In-memory cache implementation for single request lifecycle.
 */
class MemoryCache implements CacheInterface
{
    /**
     * The cache storage.
     *
     * @var array<string, array{response: CachedResponse, expires_at: int|null}>
     */
    private array $cache = [];

    /**
     * Maximum number of items in the cache.
     */
    private int $maxItems;

    /**
     * Default TTL in seconds.
     */
    private int $defaultTtl;

    /**
     * Create a new memory cache instance.
     *
     * @param  int  $maxItems  Maximum number of items to store
     * @param  int  $defaultTtl  Default TTL in seconds
     */
    public function __construct(int $maxItems = 1000, int $defaultTtl = 3600)
    {
        $this->maxItems = $maxItems;
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): ?CachedResponse
    {
        if (! isset($this->cache[$key])) {
            return null;
        }

        $entry = $this->cache[$key];

        // Check if the entry has expired
        if ($entry['expires_at'] !== null && time() > $entry['expires_at']) {
            unset($this->cache[$key]);

            return null;
        }

        return $entry['response'];
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, CachedResponse $response, ?int $ttl = null): void
    {
        // Ensure we don't exceed max items
        if (count($this->cache) >= $this->maxItems && ! isset($this->cache[$key])) {
            $this->evictOldest();
        }

        $ttl = $ttl ?? $this->defaultTtl;

        // Handle negative TTL (already expired)
        if ($ttl < 0) {
            $expiresAt = time() + $ttl; // Will be in the past
        } elseif ($ttl > 0) {
            $expiresAt = time() + $ttl;
        } else {
            $expiresAt = null; // TTL of 0 means no expiration
        }

        $this->cache[$key] = [
            'response' => $response,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        if (! isset($this->cache[$key])) {
            return false;
        }

        $entry = $this->cache[$key];

        // Check if the entry has expired
        if ($entry['expires_at'] !== null && time() > $entry['expires_at']) {
            unset($this->cache[$key]);

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->cache = [];
    }

    /**
     * {@inheritdoc}
     */
    public function prune(): int
    {
        $now = time();
        $count = 0;

        foreach ($this->cache as $key => $entry) {
            if ($entry['expires_at'] !== null && $now > $entry['expires_at']) {
                unset($this->cache[$key]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Evict the oldest entry from the cache.
     */
    private function evictOldest(): void
    {
        // Find the oldest entry
        $oldestKey = null;
        $oldestTime = PHP_INT_MAX;

        foreach ($this->cache as $key => $entry) {
            $createdAt = $entry['response']->getCreatedAt();
            if ($createdAt < $oldestTime) {
                $oldestTime = $createdAt;
                $oldestKey = $key;
            }
        }

        if ($oldestKey !== null) {
            unset($this->cache[$oldestKey]);
        }
    }

    /**
     * Get the number of items in the cache.
     */
    public function count(): int
    {
        return count($this->cache);
    }

    /**
     * Get cache statistics.
     *
     * @return array{items: int, max_items: int, default_ttl: int}
     */
    public function getStats(): array
    {
        return [
            'items' => count($this->cache),
            'max_items' => $this->maxItems,
            'default_ttl' => $this->defaultTtl,
        ];
    }
}
