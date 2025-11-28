<?php

declare(strict_types=1);

namespace Fetch\Pool;

use GuzzleHttp\ClientInterface;

/**
 * Manages connection pools across multiple hosts.
 */
class ConnectionPool
{
    /**
     * Pools indexed by host:port:ssl key.
     *
     * @var array<string, HostConnectionPool>
     */
    protected array $pools = [];

    /**
     * Active connections indexed by object ID.
     *
     * @var array<int, Connection>
     */
    protected array $activeConnections = [];

    /**
     * Connection metrics.
     *
     * @var array<string, int|float>
     */
    protected array $metrics = [
        'connections_created' => 0,
        'connections_reused' => 0,
        'total_requests' => 0,
        'total_latency' => 0.0,
    ];

    /**
     * Create a new connection pool manager.
     *
     * @param  PoolConfiguration  $config  Pool configuration
     */
    public function __construct(
        protected PoolConfiguration $config,
    ) {}

    /**
     * Create a pool from a configuration array.
     *
     * @param  array<string, mixed>  $config  Configuration array
     * @return static New pool instance
     */
    public static function fromArray(array $config): static
    {
        return new static(PoolConfiguration::fromArray($config));
    }

    /**
     * Get a connection for the specified host.
     *
     * @param  string  $host  The host
     * @param  int  $port  The port
     * @param  bool  $ssl  Whether SSL is enabled
     * @return Connection A connection to use
     */
    public function getConnection(string $host, int $port = 80, bool $ssl = false): Connection
    {
        $key = $this->getPoolKey($host, $port, $ssl);

        if (! isset($this->pools[$key])) {
            $this->pools[$key] = new HostConnectionPool(
                host: $host,
                port: $port,
                ssl: $ssl,
                config: $this->config,
            );
        }

        $connection = $this->pools[$key]->borrowConnection();
        $this->activeConnections[spl_object_id($connection)] = $connection;

        // Update metrics
        $this->metrics['total_requests']++;

        return $connection;
    }

    /**
     * Get a connection from a URL.
     *
     * @param  string  $url  The URL to connect to
     * @return Connection A connection to use
     */
    public function getConnectionFromUrl(string $url): Connection
    {
        $parsed = parse_url($url);

        $host = $parsed['host'] ?? 'localhost';
        $ssl = ($parsed['scheme'] ?? 'http') === 'https';
        $defaultPort = $ssl ? 443 : 80;
        $port = $parsed['port'] ?? $defaultPort;

        return $this->getConnection($host, $port, $ssl);
    }

    /**
     * Release a connection back to the pool.
     *
     * @param  Connection  $connection  The connection to release
     */
    public function releaseConnection(Connection $connection): void
    {
        $id = spl_object_id($connection);

        if (isset($this->activeConnections[$id])) {
            unset($this->activeConnections[$id]);

            $key = $connection->getKey();
            $poolKey = $this->normalizePoolKey($key);

            if (isset($this->pools[$poolKey])) {
                $this->pools[$poolKey]->returnConnection($connection);
                $this->metrics['connections_reused']++;
            } else {
                $connection->close();
            }
        }
    }

    /**
     * Close a specific connection.
     *
     * @param  Connection  $connection  The connection to close
     */
    public function closeConnection(Connection $connection): void
    {
        $id = spl_object_id($connection);

        if (isset($this->activeConnections[$id])) {
            unset($this->activeConnections[$id]);
        }

        $connection->close();
    }

    /**
     * Get an HTTP client for the specified URL.
     *
     * This returns the underlying Guzzle client from the pooled connection.
     *
     * @param  string  $url  The URL to connect to
     * @return ClientInterface|null The HTTP client or null if not available
     */
    public function getClientForUrl(string $url): ?ClientInterface
    {
        $connection = $this->getConnectionFromUrl($url);

        return $connection->getClient();
    }

    /**
     * Record connection latency for metrics.
     *
     * @param  string  $host  The host
     * @param  int  $port  The port
     * @param  float  $latency  Latency in milliseconds
     */
    public function recordLatency(string $host, int $port, float $latency): void
    {
        $this->metrics['total_latency'] += $latency;
    }

    /**
     * Get pool statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $stats = [
            'enabled' => $this->config->isEnabled(),
            'total_pools' => count($this->pools),
            'active_connections' => count($this->activeConnections),
            'connections_created' => $this->metrics['connections_created'],
            'connections_reused' => $this->metrics['connections_reused'],
            'total_requests' => $this->metrics['total_requests'],
            'average_latency' => $this->calculateAverageLatency(),
            'reuse_rate' => $this->calculateReuseRate(),
            'pools' => [],
        ];

        foreach ($this->pools as $key => $pool) {
            $stats['pools'][$key] = $pool->getStats();
        }

        return $stats;
    }

    /**
     * Check if the pool is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * Get the pool configuration.
     */
    public function getConfig(): PoolConfiguration
    {
        return $this->config;
    }

    /**
     * Close all connections in all pools.
     */
    public function closeAll(): void
    {
        foreach ($this->activeConnections as $connection) {
            $connection->close();
        }
        $this->activeConnections = [];

        foreach ($this->pools as $pool) {
            $pool->closeAll();
        }
        $this->pools = [];
    }

    /**
     * Get the pool key for a host:port:ssl combination.
     *
     * @param  string  $host  The host
     * @param  int  $port  The port
     * @param  bool  $ssl  Whether SSL is enabled
     * @return string The pool key
     */
    protected function getPoolKey(string $host, int $port, bool $ssl): string
    {
        $scheme = $ssl ? 'https' : 'http';

        return "{$scheme}://{$host}:{$port}";
    }

    /**
     * Normalize a connection key to a pool key.
     *
     * @param  string  $key  The connection key
     * @return string The normalized pool key
     */
    protected function normalizePoolKey(string $key): string
    {
        return $key;
    }

    /**
     * Calculate average latency across all requests.
     *
     * @return float Average latency in milliseconds
     */
    protected function calculateAverageLatency(): float
    {
        $totalRequests = (int) $this->metrics['total_requests'];
        if ($totalRequests === 0) {
            return 0.0;
        }

        return (float) $this->metrics['total_latency'] / $totalRequests;
    }

    /**
     * Calculate the connection reuse rate.
     *
     * @return float Reuse rate (0.0 to 1.0)
     */
    protected function calculateReuseRate(): float
    {
        $totalRequests = (int) $this->metrics['total_requests'];
        if ($totalRequests === 0) {
            return 0.0;
        }

        return (float) $this->metrics['connections_reused'] / $totalRequests;
    }
}
