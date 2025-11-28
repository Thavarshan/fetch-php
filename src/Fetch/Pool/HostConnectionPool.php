<?php

declare(strict_types=1);

namespace Fetch\Pool;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use SplQueue;

/**
 * Manages connections to a specific host.
 */
class HostConnectionPool
{
    /**
     * Queue of available connections.
     *
     * @var SplQueue<Connection>
     */
    protected SplQueue $availableConnections;

    /**
     * Total connections created for this host.
     */
    protected int $totalCreated = 0;

    /**
     * Total connections borrowed from this pool.
     */
    protected int $totalBorrowed = 0;

    /**
     * Total connections returned to this pool.
     */
    protected int $totalReturned = 0;

    /**
     * Create a new host connection pool.
     *
     * @param  string  $host  The host
     * @param  int  $port  The port
     * @param  bool  $ssl  Whether SSL is enabled
     * @param  PoolConfiguration  $config  Pool configuration
     */
    public function __construct(
        protected string $host,
        protected int $port,
        protected bool $ssl,
        protected PoolConfiguration $config,
    ) {
        /** @var SplQueue<Connection> $queue */
        $queue = new SplQueue;
        $this->availableConnections = $queue;

        if ($this->config->isConnectionWarmupEnabled()) {
            $this->warmupConnections();
        }
    }

    /**
     * Borrow a connection from the pool.
     *
     * @return Connection A connection to use
     */
    public function borrowConnection(): Connection
    {
        $this->totalBorrowed++;

        // Try to get an existing connection from the pool
        while (! $this->availableConnections->isEmpty()) {
            $connection = $this->availableConnections->dequeue();

            if ($connection->isReusable($this->config->getKeepAliveTimeout())) {
                $connection->markUsed();
                $connection->incrementActiveRequests();

                return $connection;
            }

            // Connection is stale, close it
            $connection->close();
        }

        // No available connections, create a new one
        return $this->createConnection();
    }

    /**
     * Return a connection to the pool.
     *
     * @param  Connection  $connection  The connection to return
     */
    public function returnConnection(Connection $connection): void
    {
        $this->totalReturned++;
        $connection->decrementActiveRequests();

        // Only keep connections that are still reusable and within limits
        if ($connection->isReusable($this->config->getKeepAliveTimeout())
            && $this->availableConnections->count() < $this->config->getMaxIdlePerHost()) {
            $this->availableConnections->enqueue($connection);
        } else {
            $connection->close();
        }
    }

    /**
     * Create a new connection.
     *
     * @return Connection The new connection
     */
    protected function createConnection(): Connection
    {
        $this->totalCreated++;

        $connection = new Connection(
            host: $this->host,
            port: $this->port,
            ssl: $this->ssl,
        );

        $client = $this->createHttpClient();
        $connection->setClient($client);
        $connection->incrementActiveRequests();

        return $connection;
    }

    /**
     * Create an HTTP client for this host.
     *
     * @return ClientInterface The HTTP client
     */
    protected function createHttpClient(): ClientInterface
    {
        $scheme = $this->ssl ? 'https' : 'http';
        $baseUri = "{$scheme}://{$this->host}:{$this->port}";

        return new GuzzleClient([
            'base_uri' => $baseUri,
            RequestOptions::CONNECT_TIMEOUT => $this->config->getConnectionTimeout(),
            RequestOptions::HTTP_ERRORS => false,
            // Enable HTTP/2 if available
            'version' => 2.0,
            // Connection reuse settings
            'curl' => [
                // Enable connection reuse
                CURLOPT_TCP_KEEPALIVE => 1,
                CURLOPT_TCP_KEEPIDLE => $this->config->getKeepAliveTimeout(),
                CURLOPT_TCP_KEEPINTVL => 10,
            ],
        ]);
    }

    /**
     * Pre-warm connections for this host.
     */
    protected function warmupConnections(): void
    {
        $warmupCount = min(
            $this->config->getWarmupConnections(),
            $this->config->getMaxPerHost()
        );

        for ($i = 0; $i < $warmupCount; $i++) {
            try {
                $connection = $this->createConnection();
                $connection->decrementActiveRequests(); // Was incremented in createConnection
                $this->availableConnections->enqueue($connection);
            } catch (\Throwable) {
                // Warmup failure is not critical - stop warming up but continue
                break;
            }
        }
    }

    /**
     * Get the number of available connections.
     */
    public function getAvailableCount(): int
    {
        return $this->availableConnections->count();
    }

    /**
     * Get pool statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'ssl' => $this->ssl,
            'available' => $this->availableConnections->count(),
            'total_created' => $this->totalCreated,
            'total_borrowed' => $this->totalBorrowed,
            'total_returned' => $this->totalReturned,
            'success_rate' => $this->totalBorrowed > 0
                ? $this->totalReturned / $this->totalBorrowed
                : 1.0,
        ];
    }

    /**
     * Close all connections in the pool.
     */
    public function closeAll(): void
    {
        while (! $this->availableConnections->isEmpty()) {
            $connection = $this->availableConnections->dequeue();
            $connection->close();
        }
    }
}
