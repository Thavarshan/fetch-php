<?php

declare(strict_types=1);

namespace Fetch\Pool;

use Fetch\Exceptions\NetworkException;
use GuzzleHttp\Psr7\Request;

/**
 * DNS caching for improved performance.
 */
class DnsCache
{
    /**
     * DNS cache entries.
     *
     * @var array<string, array{addresses: array<string>, expires_at: int}>
     */
    protected array $cache = [];

    /**
     * Create a new DNS cache instance.
     *
     * @param  int  $ttl  Time-to-live for cached entries in seconds
     */
    public function __construct(
        protected int $ttl = 300,
    ) {}

    /**
     * Resolve a hostname to IP addresses.
     *
     * @param  string  $hostname  The hostname to resolve
     * @return array<string> Array of IP addresses
     *
     * @throws NetworkException If DNS resolution fails
     */
    public function resolve(string $hostname): array
    {
        $cacheKey = $hostname;

        // Check cache first
        if (isset($this->cache[$cacheKey]) && ! $this->isExpired($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey]['addresses'];
        }

        // Perform DNS lookup
        $addresses = $this->performDnsLookup($hostname);

        // Cache the result
        $this->cache[$cacheKey] = [
            'addresses' => $addresses,
            'expires_at' => time() + $this->ttl,
        ];

        return $addresses;
    }

    /**
     * Get the first resolved IP address for a hostname.
     *
     * @param  string  $hostname  The hostname to resolve
     * @return string The first IP address
     *
     * @throws NetworkException If DNS resolution fails
     */
    public function resolveFirst(string $hostname): string
    {
        $addresses = $this->resolve($hostname);

        return $addresses[0];
    }

    /**
     * Clear the cache for a specific hostname or all hostnames.
     *
     * @param  string|null  $hostname  Hostname to clear, or null for all
     */
    public function clear(?string $hostname = null): void
    {
        if ($hostname === null) {
            $this->cache = [];
        } else {
            unset($this->cache[$hostname]);
        }
    }

    /**
     * Get cache statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $validEntries = 0;
        $expiredEntries = 0;

        foreach ($this->cache as $entry) {
            if ($this->isExpired($entry)) {
                $expiredEntries++;
            } else {
                $validEntries++;
            }
        }

        return [
            'total_entries' => count($this->cache),
            'valid_entries' => $validEntries,
            'expired_entries' => $expiredEntries,
            'ttl' => $this->ttl,
        ];
    }

    /**
     * Set the TTL for new cache entries.
     *
     * @param  int  $ttl  Time-to-live in seconds
     * @return $this
     */
    public function setTtl(int $ttl): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    /**
     * Prune expired entries from the cache.
     *
     * @return int Number of entries removed
     */
    public function prune(): int
    {
        $removed = 0;

        foreach ($this->cache as $key => $entry) {
            if ($this->isExpired($entry)) {
                unset($this->cache[$key]);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Check if a cache entry is expired.
     *
     * @param  array{addresses: array<string>, expires_at: int}  $entry  Cache entry
     * @return bool Whether the entry is expired
     */
    protected function isExpired(array $entry): bool
    {
        return time() >= $entry['expires_at'];
    }

    /**
     * Perform actual DNS lookup.
     *
     * @param  string  $hostname  The hostname to resolve
     * @return array<string> Array of IP addresses
     *
     * @throws NetworkException If DNS resolution fails
     */
    protected function performDnsLookup(string $hostname): array
    {
        $addresses = [];

        // Try IPv4 (A records)
        $ipv4 = @dns_get_record($hostname, DNS_A);
        if ($ipv4 !== false) {
            foreach ($ipv4 as $record) {
                if (isset($record['ip'])) {
                    $addresses[] = $record['ip'];
                }
            }
        }

        // Try IPv6 (AAAA records)
        $ipv6 = @dns_get_record($hostname, DNS_AAAA);
        if ($ipv6 !== false) {
            foreach ($ipv6 as $record) {
                if (isset($record['ipv6'])) {
                    $addresses[] = $record['ipv6'];
                }
            }
        }

        // If no DNS records found, try gethostbyname
        if (empty($addresses)) {
            $ip = gethostbyname($hostname);
            // gethostbyname returns the hostname unchanged if resolution fails
            if ($ip !== $hostname) {
                $addresses[] = $ip;
            }
        }

        if (empty($addresses)) {
            // Encode hostname for use in URL (handle IDN and special characters)
            $safeHost = rawurlencode($hostname);
            throw new NetworkException(
                "Failed to resolve hostname: {$hostname}",
                new Request('GET', "https://{$safeHost}/")
            );
        }

        return $addresses;
    }
}
