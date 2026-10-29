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
     */
    public function withConnectionPool(array|bool $config = true): self;

    /**
     * @param  array<string, mixed>|bool  $config
     */
    public function withHttp2(array|bool $config = true): self;

    public function getConnectionPool(): ?ConnectionPool;

    public function getDnsCache(): ?DnsCache;

    public function getHttp2Config(): ?Http2Configuration;

    public function isPoolingEnabled(): bool;

    public function isHttp2Enabled(): bool;

    /**
     * @return array<string, mixed>
     */
    public function getPoolStats(): array;

    /**
     * @return array<string, mixed>
     */
    public function getDnsCacheStats(): array;

    public function clearDnsCache(?string $hostname = null): self;

    public function closeAllConnections(): self;

    public function resetPool(): self;
}
