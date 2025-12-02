<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

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

    public function getConnectionPool(): ?\Fetch\Pool\ConnectionPool;

    public function getDnsCache(): ?\Fetch\Pool\DnsCache;

    public function getHttp2Config(): ?\Fetch\Pool\Http2Configuration;

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
