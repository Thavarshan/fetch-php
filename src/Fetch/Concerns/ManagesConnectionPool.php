<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Interfaces\ClientHandler;
use Fetch\Pool\ConnectionPool;
use Fetch\Pool\DnsCache;
use Fetch\Pool\Http2Configuration;
use Fetch\Pool\PoolConfiguration;
use Fetch\Support\GlobalServices;
use RuntimeException;

/**
 * Trait for managing connection pooling and HTTP/2 support.
 *
 * Note: The connection pool and DNS cache are managed by GlobalServices
 * to enable connection sharing across all ClientHandler instances. This is
 * intentional to maximize connection reuse. However, be aware that:
 * - Configuration changes affect all handlers globally
 * - In multi-threaded environments, proper synchronization may be needed
 * - Use GlobalServices::reset() to isolate test environments
 */
trait ManagesConnectionPool
{
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
        if (! GlobalServices::hasConnectionPool()) {
            GlobalServices::initialize($config);
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

        // Configure via GlobalServices
        GlobalServices::configurePool($config);

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

        $this->applyHttp2Options();

        return $this;
    }

    /**
     * Get the connection pool instance.
     *
     * @return ConnectionPool|null The pool instance or null if not configured
     */
    public function getConnectionPool(): ?ConnectionPool
    {
        return GlobalServices::hasConnectionPool() ? GlobalServices::getConnectionPool() : null;
    }

    /**
     * Get the DNS cache instance.
     *
     * @return DnsCache|null The DNS cache or null if not configured
     */
    public function getDnsCache(): ?DnsCache
    {
        return GlobalServices::hasDnsCache() ? GlobalServices::getDnsCache() : null;
    }

    /**
     * Get lightweight connection diagnostics for debugging/profiling.
     *
     * @return array<string, mixed>
     */
    public function getConnectionDebugStats(): array
    {
        return GlobalServices::getStats();
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
        if (! $this->poolingEnabled) {
            return false;
        }

        $pool = $this->getConnectionPool();

        return $pool !== null && $pool->isEnabled();
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
        $pool = $this->getConnectionPool();

        if ($pool === null) {
            return ['enabled' => false];
        }

        return $pool->getStats();
    }

    /**
     * Get DNS cache statistics.
     *
     * @return array<string, mixed>
     */
    public function getDnsCacheStats(): array
    {
        $dnsCache = $this->getDnsCache();

        if ($dnsCache === null) {
            return ['enabled' => false];
        }

        return array_merge(['enabled' => true], $dnsCache->getStats());
    }

    /**
     * Clear the DNS cache.
     *
     * @param  string|null  $hostname  Specific hostname to clear, or null for all
     * @return $this
     */
    public function clearDnsCache(?string $hostname = null): ClientHandler
    {
        GlobalServices::clearDnsCache($hostname);

        return $this;
    }

    /**
     * Close all pooled connections.
     *
     * @return $this
     */
    public function closeAllConnections(): ClientHandler
    {
        GlobalServices::closeAllConnections();

        return $this;
    }

    /**
     * Reset the global connection pool and DNS cache.
     *
     * @return $this
     */
    public function resetPool(): ClientHandler
    {
        GlobalServices::reset(preserveDefaults: true);
        $this->poolingEnabled = false;

        return $this;
    }

    /**
     * Apply HTTP/2-specific options to the handler in a single place with validation.
     */
    protected function applyHttp2Options(): void
    {
        if (! $this->isHttp2Enabled()) {
            return;
        }

        // Validate environment support
        if (! defined('CURL_HTTP_VERSION_2_0')) {
            throw new RuntimeException('HTTP/2 requested but CURL_HTTP_VERSION_2_0 is not available in this environment.');
        }

        $curlOptions = $this->http2Config?->getCurlOptions() ?? [];
        if (! empty($curlOptions)) {
            $existingCurl = $this->options['curl'] ?? [];
            // Use + operator to preserve numeric keys (CURL constants)
            // Give priority to existing user-provided curl options to avoid silent overrides
            $this->options['curl'] = $curlOptions + $existingCurl;
        }

        // Set HTTP version if user hasn't provided one
        if (! isset($this->options['version'])) {
            $this->options['version'] = 2.0;
        }
    }

    /**
     * Check if pool/DNS cache have been initialized.
     */
    protected function isPoolInitialized(): bool
    {
        return GlobalServices::hasConnectionPool() || GlobalServices::hasDnsCache();
    }

    /**
     * Get cURL options for HTTP/2 support.
     *
     * @return array<int, mixed>
     */
    protected function getHttp2CurlOptions(): array
    {
        if ($this->http2Config === null) {
            return [];
        }

        return $this->http2Config->getCurlOptions();
    }

    /**
     * Resolve a hostname using the DNS cache.
     *
     * Returns null if DNS cache is not configured or if DNS resolution fails.
     * This method silently catches exceptions to allow fallback behavior.
     *
     * @param  string  $hostname  The hostname to resolve
     * @return string|null The resolved IP address, or null if not available
     */
    protected function resolveHostname(string $hostname): ?string
    {
        $dnsCache = $this->getDnsCache();

        if ($dnsCache === null) {
            return null;
        }

        try {
            return $dnsCache->resolveFirst($hostname);
        } catch (\Throwable) {
            return null;
        }
    }
}
