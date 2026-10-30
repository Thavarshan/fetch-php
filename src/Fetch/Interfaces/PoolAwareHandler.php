<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Pool\ConnectionPool;
use Fetch\Pool\DnsCache;
use Fetch\Pool\Http2Configuration;

interface PoolAwareHandler
{
    /**
     * @param  array<string, mixed>|bool  $config
     *
     * @deprecated since 4.1.0 Pool configuration does not affect transports.
     */
    public function withConnectionPool(array|bool $config = true): self;

    /**
     * @param  array<string, mixed>|bool  $config
     */
    public function withHttp2(array|bool $config = true): self;

    /** @deprecated since 4.1.0 The pool is not used by request transport. */
    public function getConnectionPool(): ?ConnectionPool;

    /** @deprecated since 4.1.0 DNS results do not affect request transport. */
    public function getDnsCache(): ?DnsCache;

    public function getHttp2Config(): ?Http2Configuration;

    /** @deprecated since 4.1.0 This reports configuration only. */
    public function isPoolingEnabled(): bool;

    public function isHttp2Enabled(): bool;

    /**
     * @return array<string, mixed>
     *
     * @deprecated since 4.1.0 These counters do not report socket reuse.
     */
    public function getPoolStats(): array;

    /**
     * @return array<string, mixed>
     *
     * @deprecated since 4.1.0 The cache is not used by request transport.
     */
    public function getDnsCacheStats(): array;

    /** @deprecated since 4.1.0 DNS cache is not used by request transport. */
    public function clearDnsCache(?string $hostname = null): self;

    /**
     * @deprecated since 4.1.0 The custom pool does not own transport sockets.
     */
    public function closeAllConnections(): self;

    /**
     * @deprecated since 4.1.0 The custom pool does not own transport sockets.
     */
    public function resetPool(): self;
}
