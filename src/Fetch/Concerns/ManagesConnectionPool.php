<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Http\ClientHandler;
use Fetch\Pool\ConnectionPool;
use Fetch\Pool\ConnectionPoolManager;
use Fetch\Pool\DnsCache;
use Fetch\Pool\Http2Configuration;
use Fetch\Pool\PoolConfiguration;

/** ClientHandler facade for legacy pool and HTTP/2 configuration. */
trait ManagesConnectionPool
{
    protected static function initializePool(
        ?PoolConfiguration $config = null,
    ): void {
        ConnectionPoolManager::initialize($config);
    }

    /**
     * @param  array<string, mixed>|bool  $config
     *
     * @deprecated since 4.1.0 Pool configuration does not affect transports.
     */
    public function withConnectionPool(array|bool $config = true): ClientHandler
    {
        $this->connectionPoolManager->withConnectionPool($config);

        return $this;
    }

    /** @param array<string, mixed>|bool $config */
    public function withHttp2(array|bool $config = true): ClientHandler
    {
        $this->connectionPoolManager->withHttp2($config);
        $this->options = $this->connectionPoolManager->applyHttp2Options(
            $this->options,
        );

        return $this;
    }

    /** @deprecated since 4.1.0 The pool is not used by request transport. */
    public function getConnectionPool(): ?ConnectionPool
    {
        return $this->connectionPoolManager->getConnectionPool();
    }

    /** @deprecated since 4.1.0 DNS results do not affect request transport. */
    public function getDnsCache(): ?DnsCache
    {
        return $this->connectionPoolManager->getDnsCache();
    }

    /** @return array<string, mixed> */
    public function getConnectionDebugStats(): array
    {
        return $this->connectionPoolManager->getDebugStats();
    }

    public function getHttp2Config(): ?Http2Configuration
    {
        return $this->connectionPoolManager->getHttp2Config();
    }

    /** @deprecated since 4.1.0 This reports configuration only. */
    public function isPoolingEnabled(): bool
    {
        return $this->connectionPoolManager->isPoolingEnabled();
    }

    public function isHttp2Enabled(): bool
    {
        return $this->connectionPoolManager->isHttp2Enabled();
    }

    /**
     * @return array<string, mixed>
     *
     * @deprecated since 4.1.0 These counters do not report socket reuse.
     */
    public function getPoolStats(): array
    {
        return $this->connectionPoolManager->getPoolStats();
    }

    /**
     * @return array<string, mixed>
     *
     * @deprecated since 4.1.0 The cache is not used by request transport.
     */
    public function getDnsCacheStats(): array
    {
        return $this->connectionPoolManager->getDnsCacheStats();
    }

    /** @deprecated since 4.1.0 DNS cache is not used by request transport. */
    public function clearDnsCache(?string $hostname = null): ClientHandler
    {
        $this->connectionPoolManager->clearDnsCache($hostname);

        return $this;
    }

    /**
     * @deprecated since 4.1.0 The custom pool does not own transport sockets.
     */
    public function closeAllConnections(): ClientHandler
    {
        $this->connectionPoolManager->closeAllConnections();

        return $this;
    }

    /**
     * @deprecated since 4.1.0 The custom pool does not own transport sockets.
     */
    public function resetPool(): ClientHandler
    {
        $this->connectionPoolManager->reset();

        return $this;
    }

    protected function applyHttp2Options(): void
    {
        $this->options = $this->connectionPoolManager->applyHttp2Options(
            $this->options,
        );
    }

    protected function isPoolInitialized(): bool
    {
        return $this->connectionPoolManager->isInitialized();
    }

    /** @return array<int, mixed> */
    protected function getHttp2CurlOptions(): array
    {
        return $this->connectionPoolManager->getHttp2CurlOptions();
    }

    protected function resolveHostname(string $hostname): ?string
    {
        return $this->connectionPoolManager->resolveHostname($hostname);
    }
}
