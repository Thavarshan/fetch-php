<?php

declare(strict_types=1);

namespace Fetch\Pool;

use Fetch\Support\GlobalServices;
use RuntimeException;

/**
 * Coordinates legacy pool diagnostics and handler-local HTTP/2 settings.
 *
 * The pool and DNS cache are not connected to request execution. HTTP/2
 * settings remain local to this manager instance.
 */
final class ConnectionPoolManager
{
    private ?Http2Configuration $http2Config = null;

    private bool $poolingEnabled = false;

    public static function initialize(
        ?PoolConfiguration $configuration = null,
    ): void {
        if (! GlobalServices::hasConnectionPool()) {
            GlobalServices::initialize($configuration);
        }
    }

    /**
     * @param  array<string, mixed>|bool  $configuration
     *
     * @deprecated since 4.1.0 Pool settings do not affect request transport.
     */
    public function withConnectionPool(array|bool $configuration): void
    {
        if (is_bool($configuration)) {
            $this->poolingEnabled = $configuration;

            return;
        }

        $this->poolingEnabled = true;
        GlobalServices::configurePool($configuration);
    }

    /** @param array<string, mixed>|bool $configuration */
    public function withHttp2(array|bool $configuration): void
    {
        $this->http2Config = is_bool($configuration)
            ? new Http2Configuration(enabled: $configuration)
            : Http2Configuration::fromArray($configuration);
    }

    public function getConnectionPool(): ?ConnectionPool
    {
        return GlobalServices::hasConnectionPool()
            ? GlobalServices::getConnectionPool()
            : null;
    }

    public function getDnsCache(): ?DnsCache
    {
        return GlobalServices::hasDnsCache()
            ? GlobalServices::getDnsCache()
            : null;
    }

    /** @return array<string, mixed> */
    public function getDebugStats(): array
    {
        return GlobalServices::getStats();
    }

    public function getHttp2Config(): ?Http2Configuration
    {
        return $this->http2Config;
    }

    public function isPoolingEnabled(): bool
    {
        if (! $this->poolingEnabled) {
            return false;
        }

        return $this->getConnectionPool()?->isEnabled() ?? false;
    }

    public function isHttp2Enabled(): bool
    {
        return $this->http2Config?->isEnabled() ?? false;
    }

    /** @return array<string, mixed> */
    public function getPoolStats(): array
    {
        return $this->getConnectionPool()?->getStats() ?? ['enabled' => false];
    }

    /** @return array<string, mixed> */
    public function getDnsCacheStats(): array
    {
        $dnsCache = $this->getDnsCache();

        return $dnsCache === null
            ? ['enabled' => false]
            : array_merge(['enabled' => true], $dnsCache->getStats());
    }

    public function clearDnsCache(?string $hostname = null): void
    {
        GlobalServices::clearDnsCache($hostname);
    }

    public function closeAllConnections(): void
    {
        GlobalServices::closeAllConnections();
    }

    public function reset(): void
    {
        GlobalServices::reset(preserveDefaults: true);
        $this->poolingEnabled = false;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function applyHttp2Options(array $options): array
    {
        if (! $this->isHttp2Enabled()) {
            return $options;
        }

        if (! defined('CURL_HTTP_VERSION_2_0')) {
            throw new RuntimeException(
                'HTTP/2 requested but CURL_HTTP_VERSION_2_0 is not '
                    .'available in this environment.',
            );
        }

        $curlOptions = $this->http2Config?->getCurlOptions() ?? [];
        if ($curlOptions !== []) {
            $options['curl'] = $curlOptions + ($options['curl'] ?? []);
        }

        $options['version'] ??= 2.0;

        return $options;
    }

    public function isInitialized(): bool
    {
        return GlobalServices::hasConnectionPool() ||
            GlobalServices::hasDnsCache();
    }

    /** @return array<int, mixed> */
    public function getHttp2CurlOptions(): array
    {
        return $this->http2Config?->getCurlOptions() ?? [];
    }

    public function resolveHostname(string $hostname): ?string
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
