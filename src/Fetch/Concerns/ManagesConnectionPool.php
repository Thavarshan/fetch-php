<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Http\ClientHandler;
use Fetch\Pool\ConnectionPool;
use Fetch\Pool\ConnectionPoolManager;
use Fetch\Pool\DnsCache;
use Fetch\Pool\Http2Configuration;
use Fetch\Pool\PoolConfiguration;

/**
 * ClientHandler facade for legacy pool and HTTP/2 configuration.
 */
trait ManagesConnectionPool
{
    /**
     * Initialize shared legacy pool services with optional configuration.
     */
    protected static function initializePool(
        ?PoolConfiguration $config = null,
    ): void {
        ConnectionPoolManager::initialize($config);
    }

    /**
     * Stores the supplied custom connection-pool configuration.
     *
     * @param  array<string, mixed>|bool  $config
     *
     * @deprecated since 4.1.0 Pool configuration does not affect transports.
     */
    public function withConnectionPool(array|bool $config = true): ClientHandler
    {
        $this->connectionPoolManager->withConnectionPool($config);

        return $this;
    }

    /**
     * Stores the supplied HTTP/2 configuration for the handler.
     *
     * @param  array<string, mixed>|bool  $config
     */
    public function withHttp2(array|bool $config = true): ClientHandler
    {
        $this->connectionPoolManager->withHttp2($config);
        $this->options = $this->connectionPoolManager->applyHttp2Options(
            $this->options,
        );

        return $this;
    }

    /**
     * Returns the configured custom pool, which is not used by the transport.
     *
     * @deprecated since 4.1.0 The pool is not used by request transport.
     */
    public function getConnectionPool(): ?ConnectionPool
    {
        return $this->connectionPoolManager->getConnectionPool();
    }

    /**
     * Returns the configured DNS cache, which is not used by the transport.
     *
     * @deprecated since 4.1.0 DNS results do not affect request transport.
     */
    public function getDnsCache(): ?DnsCache
    {
        return $this->connectionPoolManager->getDnsCache();
    }

    /**
     * Returns statistics from the standalone custom pool.
     *
     * @return array<string, mixed>
     */
    public function getConnectionDebugStats(): array
    {
        return $this->connectionPoolManager->getDebugStats();
    }

    /**
     * Return this handler's HTTP/2 configuration, when one is set.
     */
    public function getHttp2Config(): ?Http2Configuration
    {
        return $this->connectionPoolManager->getHttp2Config();
    }

    /**
     * Reports the configured flag; request transport does not use the pool.
     *
     * @deprecated since 4.1.0 This reports configuration only.
     */
    public function isPoolingEnabled(): bool
    {
        return $this->connectionPoolManager->isPoolingEnabled();
    }

    /**
     * Report whether this handler's HTTP/2 option is enabled.
     */
    public function isHttp2Enabled(): bool
    {
        return $this->connectionPoolManager->isHttp2Enabled();
    }

    /**
     * Returns statistics from the standalone custom pool.
     *
     * @return array<string, mixed>
     *
     * @deprecated since 4.1.0 These counters do not report socket reuse.
     */
    public function getPoolStats(): array
    {
        return $this->connectionPoolManager->getPoolStats();
    }

    /**
     * Returns statistics for the configured DNS cache.
     *
     * @return array<string, mixed>
     *
     * @deprecated since 4.1.0 The cache is not used by request transport.
     */
    public function getDnsCacheStats(): array
    {
        return $this->connectionPoolManager->getDnsCacheStats();
    }

    /**
     * Clears cached DNS entries from the standalone DNS cache.
     *
     * @deprecated since 4.1.0 DNS cache is not used by request transport.
     */
    public function clearDnsCache(?string $hostname = null): ClientHandler
    {
        $this->connectionPoolManager->clearDnsCache($hostname);

        return $this;
    }

    /**
     * Closes connections owned by the standalone custom pool.
     *
     * @deprecated since 4.1.0 The custom pool does not own transport sockets.
     */
    public function closeAllConnections(): ClientHandler
    {
        $this->connectionPoolManager->closeAllConnections();

        return $this;
    }

    /**
     * Resets state held by the standalone custom pool.
     *
     * @deprecated since 4.1.0 The custom pool does not own transport sockets.
     */
    public function resetPool(): ClientHandler
    {
        $this->connectionPoolManager->reset();

        return $this;
    }

    /**
     * Merge configured HTTP/2 options into this handler's Guzzle options.
     */
    protected function applyHttp2Options(): void
    {
        $this->options = $this->connectionPoolManager->applyHttp2Options(
            $this->options,
        );
    }

    /**
     * Check whether shared legacy pool or DNS-cache services are initialized.
     */
    protected function isPoolInitialized(): bool
    {
        return $this->connectionPoolManager->isInitialized();
    }

    /**
     * Builds cURL options for the configured HTTP/2 settings.
     *
     * @return array<int, mixed>
     */
    protected function getHttp2CurlOptions(): array
    {
        return $this->connectionPoolManager->getHttp2CurlOptions();
    }

    /**
     * Resolve a hostname from the legacy DNS cache, if it is available.
     */
    protected function resolveHostname(string $hostname): ?string
    {
        return $this->connectionPoolManager->resolveHostname($hostname);
    }
}
