<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Pool\ConnectionPool;
use Fetch\Pool\DnsCache;
use Fetch\Pool\Http2Configuration;

/**
 * Exposes legacy pool diagnostics and per-handler HTTP/2 options.
 */
interface PoolAwareHandler
{
    /**
     * Stores the supplied custom connection-pool configuration.
     *
     * @param  array<string, mixed>|bool  $config
     *
     * @deprecated since 4.1.0 Pool configuration does not affect transports.
     */
    public function withConnectionPool(array|bool $config = true): self;

    /**
     * Stores the supplied HTTP/2 configuration for the handler.
     *
     * @param  array<string, mixed>|bool  $config
     */
    public function withHttp2(array|bool $config = true): self;

    /**
     * Returns the configured custom pool, which is not used by the transport.
     *
     * @deprecated since 4.1.0 The pool is not used by request transport.
     */
    public function getConnectionPool(): ?ConnectionPool;

    /**
     * Returns the configured DNS cache, which is not used by the transport.
     *
     * @deprecated since 4.1.0 DNS results do not affect request transport.
     */
    public function getDnsCache(): ?DnsCache;

    /**
     * Return the configured HTTP/2 settings, when present.
     */
    public function getHttp2Config(): ?Http2Configuration;

    /**
     * Reports the configured flag; request transport does not use the pool.
     *
     * @deprecated since 4.1.0 This reports configuration only.
     */
    public function isPoolingEnabled(): bool;

    /**
     * Report whether HTTP/2 was enabled in the handler configuration.
     */
    public function isHttp2Enabled(): bool;

    /**
     * Returns statistics from the standalone custom pool.
     *
     * @return array<string, mixed>
     *
     * @deprecated since 4.1.0 These counters do not report socket reuse.
     */
    public function getPoolStats(): array;

    /**
     * Returns statistics for the configured DNS cache.
     *
     * @return array<string, mixed>
     *
     * @deprecated since 4.1.0 The cache is not used by request transport.
     */
    public function getDnsCacheStats(): array;

    /**
     * Clears cached DNS entries from the standalone DNS cache.
     *
     * @deprecated since 4.1.0 DNS cache is not used by request transport.
     */
    public function clearDnsCache(?string $hostname = null): self;

    /**
     * Closes connections owned by the standalone custom pool.
     *
     * @deprecated since 4.1.0 The custom pool does not own transport sockets.
     */
    public function closeAllConnections(): self;

    /**
     * Resets state held by the standalone custom pool.
     *
     * @deprecated since 4.1.0 The custom pool does not own transport sockets.
     */
    public function resetPool(): self;
}
