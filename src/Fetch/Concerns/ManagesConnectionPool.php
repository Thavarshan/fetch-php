<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Interfaces\ClientHandler;
use Fetch\Pool\ConnectionPool;
use Fetch\Pool\DnsCache;
use Fetch\Pool\Http2Configuration;
use Fetch\Pool\PoolConfiguration;

/**
 * Trait for managing connection pooling and HTTP/2 support.
 */
trait ManagesConnectionPool
{
    /**
     * The connection pool instance.
     */
    protected static ?ConnectionPool $connectionPool = null;

    /**
     * The DNS cache instance.
     */
    protected static ?DnsCache $dnsCache = null;

    /**
     * The HTTP/2 configuration.
     */
    protected ?Http2Configuration $http2Config = null;

    /**
     * Whether connection pooling is enabled for this handler.
     */
    protected bool $poolingEnabled = false;

    /**
     * Initialize connection pooling with default configuration.
     *
     * @param  PoolConfiguration|null  $config  Optional pool configuration
     */
    protected static function initializePool(?PoolConfiguration $config = null): void
    {
        if (self::$connectionPool === null) {
            $poolConfig = $config ?? new PoolConfiguration;
            self::$connectionPool = new ConnectionPool($poolConfig);
            self::$dnsCache = new DnsCache($poolConfig->getDnsCacheTtl());
        }
    }

    /**
     * Configure connection pooling for this handler.
     *
     * @param  array<string, mixed>|bool  $config  Pool configuration or boolean to enable/disable
     * @return $this
     */
    public function withConnectionPool(array|bool $config = true): ClientHandler
    {
        if (is_bool($config)) {
            $this->poolingEnabled = $config;

            return $this;
        }

        $this->poolingEnabled = true;

        // Initialize or update the global pool
        self::$connectionPool = ConnectionPool::fromArray($config);

        // Initialize DNS cache if TTL is specified
        if (isset($config['dns_cache_ttl'])) {
            self::$dnsCache = new DnsCache((int) $config['dns_cache_ttl']);
        }

        return $this;
    }

    /**
     * Configure HTTP/2 for this handler.
     *
     * @param  array<string, mixed>|bool  $config  HTTP/2 configuration or boolean to enable/disable
     * @return $this
     */
    public function withHttp2(array|bool $config = true): ClientHandler
    {
        if (is_bool($config)) {
            $this->http2Config = new Http2Configuration(enabled: $config);
        } else {
            $this->http2Config = Http2Configuration::fromArray($config);
        }

        // Apply HTTP/2 curl options to the handler options
        if ($this->http2Config->isEnabled()) {
            $curlOptions = $this->http2Config->getCurlOptions();
            if (! empty($curlOptions)) {
                $existingCurl = $this->options['curl'] ?? [];
                // Use + operator to preserve integer keys (CURL constants)
                // and give priority to existing options over defaults
                $this->options['curl'] = $existingCurl + $curlOptions;
            }

            // Set HTTP version in options
            $this->options['version'] = 2.0;
        }

        return $this;
    }

    /**
     * Get the connection pool instance.
     *
     * @return ConnectionPool|null The pool instance or null if not configured
     */
    public function getConnectionPool(): ?ConnectionPool
    {
        return self::$connectionPool;
    }

    /**
     * Get the DNS cache instance.
     *
     * @return DnsCache|null The DNS cache or null if not configured
     */
    public function getDnsCache(): ?DnsCache
    {
        return self::$dnsCache;
    }

    /**
     * Get the HTTP/2 configuration.
     *
     * @return Http2Configuration|null The HTTP/2 config or null if not configured
     */
    public function getHttp2Config(): ?Http2Configuration
    {
        return $this->http2Config;
    }

    /**
     * Check if connection pooling is enabled.
     */
    public function isPoolingEnabled(): bool
    {
        return $this->poolingEnabled && self::$connectionPool !== null && self::$connectionPool->isEnabled();
    }

    /**
     * Check if HTTP/2 is enabled.
     */
    public function isHttp2Enabled(): bool
    {
        return $this->http2Config !== null && $this->http2Config->isEnabled();
    }

    /**
     * Get connection pool statistics.
     *
     * @return array<string, mixed>
     */
    public function getPoolStats(): array
    {
        if (self::$connectionPool === null) {
            return ['enabled' => false];
        }

        return self::$connectionPool->getStats();
    }

    /**
     * Get DNS cache statistics.
     *
     * @return array<string, mixed>
     */
    public function getDnsCacheStats(): array
    {
        if (self::$dnsCache === null) {
            return ['enabled' => false];
        }

        return array_merge(['enabled' => true], self::$dnsCache->getStats());
    }

    /**
     * Clear the DNS cache.
     *
     * @param  string|null  $hostname  Specific hostname to clear, or null for all
     * @return $this
     */
    public function clearDnsCache(?string $hostname = null): ClientHandler
    {
        if (self::$dnsCache !== null) {
            self::$dnsCache->clear($hostname);
        }

        return $this;
    }

    /**
     * Close all pooled connections.
     *
     * @return $this
     */
    public function closeAllConnections(): ClientHandler
    {
        if (self::$connectionPool !== null) {
            self::$connectionPool->closeAll();
        }

        return $this;
    }

    /**
     * Reset the global connection pool and DNS cache.
     *
     * @return $this
     */
    public function resetPool(): ClientHandler
    {
        if (self::$connectionPool !== null) {
            self::$connectionPool->closeAll();
        }
        self::$connectionPool = null;
        self::$dnsCache = null;
        $this->poolingEnabled = false;

        return $this;
    }


    /**
     * Resolve a hostname using the DNS cache.
     *
     * @param  string  $hostname  The hostname to resolve
     * @return string|null The resolved IP address or null
     */
    protected function resolveHostname(string $hostname): ?string
    {
        if (self::$dnsCache === null) {
            return null;
        }

        try {
            return self::$dnsCache->resolveFirst($hostname);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Example method: Get a connection using DNS cache to resolve the hostname.
     *
     * @param string $hostname
     * @return mixed|null
     */
    protected function getConnectionWithDnsCache(string $hostname)
    {
        $resolvedIp = $this->resolveHostname($hostname);
        $target = $resolvedIp ?? $hostname;

        if (self::$connectionPool === null) {
            // Optionally, initialize the pool if not already done
            self::initializePool();
        }

        // Example: get a connection from the pool using the resolved IP or hostname
        // This assumes ConnectionPool has a getConnection($target) method
        return self::$connectionPool ? self::$connectionPool->getConnection($target) : null;
    }
}
