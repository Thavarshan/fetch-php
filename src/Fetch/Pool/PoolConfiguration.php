<?php

declare(strict_types=1);

namespace Fetch\Pool;

/**
 * Configuration class for connection pool settings.
 */
class PoolConfiguration
{
    /**
     * Default maximum connections across all hosts.
     */
    public const DEFAULT_MAX_CONNECTIONS = 100;

    /**
     * Default maximum connections per host.
     */
    public const DEFAULT_MAX_PER_HOST = 6;

    /**
     * Default maximum idle connections per host.
     */
    public const DEFAULT_MAX_IDLE_PER_HOST = 3;

    /**
     * Default keep-alive timeout in seconds.
     */
    public const DEFAULT_KEEP_ALIVE_TIMEOUT = 30;

    /**
     * Default connection timeout in seconds.
     */
    public const DEFAULT_CONNECTION_TIMEOUT = 10;

    /**
     * Default warmup connections count.
     */
    public const DEFAULT_WARMUP_CONNECTIONS = 0;

    /**
     * Default DNS cache TTL in seconds.
     */
    public const DEFAULT_DNS_CACHE_TTL = 300;

    /**
     * Create a new pool configuration instance.
     *
     * @param  bool  $enabled  Whether connection pooling is enabled
     * @param  int  $maxConnections  Maximum total connections
     * @param  int  $maxPerHost  Maximum connections per host
     * @param  int  $maxIdlePerHost  Maximum idle connections per host
     * @param  int  $keepAliveTimeout  Keep-alive timeout in seconds
     * @param  int  $connectionTimeout  Connection timeout in seconds
     * @param  string  $strategy  Connection selection strategy
     * @param  bool  $connectionWarmup  Whether to pre-warm connections
     * @param  int  $warmupConnections  Number of connections to pre-warm
     * @param  int  $dnsCacheTtl  DNS cache TTL in seconds
     */
    public function __construct(
        protected bool $enabled = true,
        protected int $maxConnections = self::DEFAULT_MAX_CONNECTIONS,
        protected int $maxPerHost = self::DEFAULT_MAX_PER_HOST,
        protected int $maxIdlePerHost = self::DEFAULT_MAX_IDLE_PER_HOST,
        protected int $keepAliveTimeout = self::DEFAULT_KEEP_ALIVE_TIMEOUT,
        protected int $connectionTimeout = self::DEFAULT_CONNECTION_TIMEOUT,
        protected string $strategy = 'least_connections',
        protected bool $connectionWarmup = false,
        protected int $warmupConnections = self::DEFAULT_WARMUP_CONNECTIONS,
        protected int $dnsCacheTtl = self::DEFAULT_DNS_CACHE_TTL,
    ) {}

    /**
     * Create a configuration instance from an array.
     *
     * @param  array<string, mixed>  $config  Configuration array
     * @return static New configuration instance
     */
    public static function fromArray(array $config): static
    {
        return new static(
            enabled: (bool) ($config['enabled'] ?? true),
            maxConnections: (int) ($config['max_connections'] ?? self::DEFAULT_MAX_CONNECTIONS),
            maxPerHost: (int) ($config['max_per_host'] ?? self::DEFAULT_MAX_PER_HOST),
            maxIdlePerHost: (int) ($config['max_idle_per_host'] ?? self::DEFAULT_MAX_IDLE_PER_HOST),
            keepAliveTimeout: (int) ($config['keep_alive_timeout'] ?? self::DEFAULT_KEEP_ALIVE_TIMEOUT),
            connectionTimeout: (int) ($config['connection_timeout'] ?? self::DEFAULT_CONNECTION_TIMEOUT),
            strategy: (string) ($config['strategy'] ?? 'least_connections'),
            connectionWarmup: (bool) ($config['connection_warmup'] ?? false),
            warmupConnections: (int) ($config['warmup_connections'] ?? self::DEFAULT_WARMUP_CONNECTIONS),
            dnsCacheTtl: (int) ($config['dns_cache_ttl'] ?? self::DEFAULT_DNS_CACHE_TTL),
        );
    }

    /**
     * Check if connection pooling is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get maximum total connections.
     */
    public function getMaxConnections(): int
    {
        return $this->maxConnections;
    }

    /**
     * Get maximum connections per host.
     */
    public function getMaxPerHost(): int
    {
        return $this->maxPerHost;
    }

    /**
     * Get maximum idle connections per host.
     */
    public function getMaxIdlePerHost(): int
    {
        return $this->maxIdlePerHost;
    }

    /**
     * Get keep-alive timeout in seconds.
     */
    public function getKeepAliveTimeout(): int
    {
        return $this->keepAliveTimeout;
    }

    /**
     * Get connection timeout in seconds.
     */
    public function getConnectionTimeout(): int
    {
        return $this->connectionTimeout;
    }

    /**
     * Get connection selection strategy.
     */
    public function getStrategy(): string
    {
        return $this->strategy;
    }

    /**
     * Check if connection warmup is enabled.
     */
    public function isConnectionWarmupEnabled(): bool
    {
        return $this->connectionWarmup;
    }

    /**
     * Get number of connections to pre-warm.
     */
    public function getWarmupConnections(): int
    {
        return $this->warmupConnections;
    }

    /**
     * Get DNS cache TTL in seconds.
     */
    public function getDnsCacheTtl(): int
    {
        return $this->dnsCacheTtl;
    }

    /**
     * Convert configuration to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'max_connections' => $this->maxConnections,
            'max_per_host' => $this->maxPerHost,
            'max_idle_per_host' => $this->maxIdlePerHost,
            'keep_alive_timeout' => $this->keepAliveTimeout,
            'connection_timeout' => $this->connectionTimeout,
            'strategy' => $this->strategy,
            'connection_warmup' => $this->connectionWarmup,
            'warmup_connections' => $this->warmupConnections,
            'dns_cache_ttl' => $this->dnsCacheTtl,
        ];
    }
}
