<?php

declare(strict_types=1);

namespace Fetch\Support;

use Fetch\Enum\Method;
use Fetch\Http\ClientHandler;
use Fetch\Pool\ConnectionPool;
use Fetch\Pool\DnsCache;
use Fetch\Pool\PoolConfiguration;

/**
 * Manages global/shared services for the Fetch HTTP client.
 *
 * This class provides explicit lifecycle management for services that are
 * intentionally shared across ClientHandler instances to enable connection
 * reuse and consistent default configurations.
 *
 * **Why Global State Exists:**
 * - ConnectionPool: Connection reuse across handlers reduces latency and resource usage
 * - DnsCache: DNS resolution caching improves performance for repeated requests
 * - DefaultOptions: Consistent configuration baseline for all handlers
 *
 * **Thread Safety:**
 * This class is designed for single-threaded PHP environments. In multi-threaded
 * or async environments with parallel execution, external synchronization may be needed.
 *
 * **Testing:**
 * Always call `GlobalServices::reset()` between tests to ensure isolation.
 *
 * @internal This class is for internal use. Users should interact with ClientHandler methods.
 */
final class GlobalServices
{
    /**
     * The shared connection pool instance.
     */
    private static ?ConnectionPool $connectionPool = null;

    /**
     * The shared DNS cache instance.
     */
    private static ?DnsCache $dnsCache = null;

    /**
     * Default options for all ClientHandler instances.
     *
     * @var array<string, mixed>
     */
    private static array $defaultOptions = [];

    /**
     * Whether the services have been explicitly initialized.
     */
    private static bool $initialized = false;

    /**
     * Prevent instantiation of this utility class.
     */
    private function __construct() {}

    /**
     * Initialize global services with optional configuration.
     *
     * This method is idempotent - calling it multiple times with the same
     * configuration has no effect after the first initialization.
     *
     * @param  PoolConfiguration|null  $poolConfig  Optional pool configuration
     * @param  array<string, mixed>  $defaultOptions  Default handler options
     */
    public static function initialize(
        ?PoolConfiguration $poolConfig = null,
        array $defaultOptions = [],
    ): void {
        if (self::$initialized) {
            return;
        }

        $poolConfig = $poolConfig ?? new PoolConfiguration;
        self::$connectionPool = new ConnectionPool($poolConfig);
        self::$dnsCache = new DnsCache($poolConfig->getDnsCacheTtl());

        self::$defaultOptions = array_merge(
            self::getFactoryDefaults(),
            $defaultOptions
        );

        self::$initialized = true;
    }

    /**
     * Get the factory default options.
     *
     * These are the immutable baseline defaults that cannot be changed.
     *
     * @return array<string, mixed>
     */
    public static function getFactoryDefaults(): array
    {
        return Defaults::options();
    }

    /**
     * Get the connection pool instance.
     *
     * Initializes with defaults if not already initialized.
     */
    public static function getConnectionPool(): ConnectionPool
    {
        if (self::$connectionPool === null) {
            self::initialize();
        }

        /** @var ConnectionPool $pool */
        $pool = self::$connectionPool;

        return $pool;
    }

    /**
     * Set the connection pool instance directly.
     *
     * @internal Used by ManagesConnectionPool trait for backward compatibility
     */
    public static function setConnectionPool(?ConnectionPool $pool): void
    {
        self::$connectionPool = $pool;
    }

    /**
     * Get the DNS cache instance.
     *
     * Initializes with defaults if not already initialized.
     */
    public static function getDnsCache(): DnsCache
    {
        if (self::$dnsCache === null) {
            self::initialize();
        }

        /** @var DnsCache $cache */
        $cache = self::$dnsCache;

        return $cache;
    }

    /**
     * Set the DNS cache instance directly.
     *
     * @internal Used by ManagesConnectionPool trait for backward compatibility
     */
    public static function setDnsCache(?DnsCache $cache): void
    {
        self::$dnsCache = $cache;
    }

    /**
     * Check if the connection pool has been configured.
     */
    public static function hasConnectionPool(): bool
    {
        return self::$connectionPool !== null;
    }

    /**
     * Check if the DNS cache has been configured.
     */
    public static function hasDnsCache(): bool
    {
        return self::$dnsCache !== null;
    }

    /**
     * Configure the connection pool.
     *
     * @param  PoolConfiguration|array<string, mixed>  $config  Pool configuration
     */
    public static function configurePool(PoolConfiguration|array $config): void
    {
        $poolConfig = is_array($config)
            ? PoolConfiguration::fromArray($config)
            : $config;

        // Close existing connections before reconfiguring
        if (self::$connectionPool !== null) {
            self::$connectionPool->closeAll();
        }

        self::$connectionPool = new ConnectionPool($poolConfig);
        self::$dnsCache = new DnsCache($poolConfig->getDnsCacheTtl());
        self::$initialized = true;
    }

    /**
     * Get the current default options.
     *
     * Always returns factory defaults merged with any custom defaults,
     * regardless of initialization state.
     *
     * @return array<string, mixed>
     */
    public static function getDefaultOptions(): array
    {
        return array_merge(self::getFactoryDefaults(), self::$defaultOptions);
    }

    /**
     * Set default options that will be merged with factory defaults.
     *
     * @param  array<string, mixed>  $options  Options to set as defaults
     */
    public static function setDefaultOptions(array $options): void
    {
        self::$defaultOptions = array_merge(self::$defaultOptions, $options);
    }

    /**
     * Reset all global services to their uninitialized state.
     *
     * **Important:** This method should be called:
     * - Between tests to ensure isolation
     * - When reconfiguring the entire application
     * - In long-running processes before shutdown
     *
     * This method also resets:
     * - ClientHandler::$defaultOptions (legacy static)
     *
     * @param  bool  $preserveDefaults  Whether to keep custom default options
     */
    public static function reset(bool $preserveDefaults = false): void
    {
        if (self::$connectionPool !== null) {
            self::$connectionPool->closeAll();
            self::$connectionPool = null;
        }

        self::$dnsCache = null;
        self::$initialized = false;

        if (! $preserveDefaults) {
            self::$defaultOptions = [];
            // Reset ClientHandler's legacy static defaults only when defaults are not preserved
            ClientHandler::resetDefaultOptions();
        }
    }

    /**
     * Check if global services have been initialized.
     */
    public static function isInitialized(): bool
    {
        return self::$initialized;
    }

    /**
     * Get statistics about the current state of global services.
     *
     * @return array<string, mixed>
     */
    public static function getStats(): array
    {
        return [
            'initialized' => self::$initialized,
            'connection_pool' => self::$connectionPool?->getStats() ?? ['enabled' => false],
            'dns_cache' => self::$dnsCache !== null
                ? array_merge(['enabled' => true], self::$dnsCache->getStats())
                : ['enabled' => false],
            'default_options_customized' => ! empty(self::$defaultOptions),
        ];
    }

    /**
     * Close all pooled connections without fully resetting.
     *
     * Useful for graceful shutdown or connection cleanup.
     */
    public static function closeAllConnections(): void
    {
        self::$connectionPool?->closeAll();
    }

    /**
     * Clear the DNS cache.
     *
     * @param  string|null  $hostname  Specific hostname to clear, or null for all
     */
    public static function clearDnsCache(?string $hostname = null): void
    {
        self::$dnsCache?->clear($hostname);
    }
}
