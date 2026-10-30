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
    /**
     * HTTP/2 options local to this manager and its owning handler.
     */
    private ?Http2Configuration $http2Config = null;

    /**
     * Legacy enablement flag; it does not configure request transport.
     */
    private bool $poolingEnabled = false;

    /**
     * Initialize process-wide legacy pool services only when absent.
     */
    public static function initialize(
        ?PoolConfiguration $configuration = null,
    ): void {
        if (! GlobalServices::hasConnectionPool()) {
            GlobalServices::initialize($configuration);
        }
    }

    /**
     * Stores the supplied custom connection-pool configuration.
     *
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

    /**
     * Set handler-local HTTP/2 settings without changing shared pool state.
     *
     * @param  array<string, mixed>|bool  $configuration
     */
    public function withHttp2(array|bool $configuration): void
    {
        $this->http2Config = is_bool($configuration)
            ? new Http2Configuration(enabled: $configuration)
            : Http2Configuration::fromArray($configuration);
    }

    /**
     * Return the process-wide legacy pool when it has been initialized.
     */
    public function getConnectionPool(): ?ConnectionPool
    {
        return GlobalServices::hasConnectionPool()
            ? GlobalServices::getConnectionPool()
            : null;
    }

    /**
     * Return the process-wide DNS cache when it has been initialized.
     */
    public function getDnsCache(): ?DnsCache
    {
        return GlobalServices::hasDnsCache()
            ? GlobalServices::getDnsCache()
            : null;
    }

    /**
     * Return diagnostic counters from the shared legacy services.
     *
     * @return array<string, mixed>
     */
    public function getDebugStats(): array
    {
        return GlobalServices::getStats();
    }

    /**
     * Return the HTTP/2 configuration attached to this manager.
     */
    public function getHttp2Config(): ?Http2Configuration
    {
        return $this->http2Config;
    }

    /**
     * Report whether the legacy pool was enabled and is configured.
     */
    public function isPoolingEnabled(): bool
    {
        if (! $this->poolingEnabled) {
            return false;
        }

        return $this->getConnectionPool()?->isEnabled() ?? false;
    }

    /**
     * Report whether handler-local HTTP/2 settings are enabled.
     */
    public function isHttp2Enabled(): bool
    {
        return $this->http2Config?->isEnabled() ?? false;
    }

    /**
     * Return counters from the legacy pool, or an explicitly disabled state.
     *
     * @return array<string, mixed>
     */
    public function getPoolStats(): array
    {
        return $this->getConnectionPool()?->getStats() ?? ['enabled' => false];
    }

    /**
     * Return DNS-cache counters with its enabled state, when initialized.
     *
     * @return array<string, mixed>
     */
    public function getDnsCacheStats(): array
    {
        $dnsCache = $this->getDnsCache();

        return $dnsCache === null
            ? ['enabled' => false]
            : array_merge(['enabled' => true], $dnsCache->getStats());
    }

    /**
     * Clear one hostname entry or all entries in the shared DNS cache.
     */
    public function clearDnsCache(?string $hostname = null): void
    {
        GlobalServices::clearDnsCache($hostname);
    }

    /**
     * Close connections owned by the legacy pool service.
     */
    public function closeAllConnections(): void
    {
        GlobalServices::closeAllConnections();
    }

    /**
     * Reset shared legacy services while preserving their default options.
     */
    public function reset(): void
    {
        GlobalServices::reset(preserveDefaults: true);
        $this->poolingEnabled = false;
    }

    /**
     * Apply enabled HTTP/2 version and cURL options to a Guzzle option set.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     *
     * @throws RuntimeException When HTTP/2 is requested without cURL support
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

    /**
     * Check whether either shared legacy pool service has been initialized.
     */
    public function isInitialized(): bool
    {
        return GlobalServices::hasConnectionPool() ||
            GlobalServices::hasDnsCache();
    }

    /**
     * Return cURL options derived from the local HTTP/2 configuration.
     *
     * @return array<int, mixed>
     */
    public function getHttp2CurlOptions(): array
    {
        return $this->http2Config?->getCurlOptions() ?? [];
    }

    /**
     * Resolve a hostname through the legacy cache, returning null on failure.
     */
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
