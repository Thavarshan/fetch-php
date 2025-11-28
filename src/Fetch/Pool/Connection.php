<?php

declare(strict_types=1);

namespace Fetch\Pool;

use GuzzleHttp\ClientInterface;

/**
 * Represents a pooled connection to a specific host.
 */
class Connection
{
    /**
     * The timestamp when this connection was created.
     */
    protected float $createdAt;

    /**
     * The timestamp when this connection was last used.
     */
    protected float $lastUsedAt;

    /**
     * Number of active requests on this connection.
     */
    protected int $activeRequests = 0;

    /**
     * Whether the connection has been closed.
     */
    protected bool $closed = false;

    /**
     * Create a new pooled connection.
     *
     * @param  string  $host  The host this connection is for
     * @param  int  $port  The port number
     * @param  bool  $ssl  Whether SSL is enabled
     * @param  ClientInterface|null  $client  The underlying HTTP client
     */
    public function __construct(
        protected string $host,
        protected int $port,
        protected bool $ssl,
        protected ?ClientInterface $client = null,
    ) {
        $this->createdAt = microtime(true);
        $this->lastUsedAt = $this->createdAt;
    }

    /**
     * Get the host this connection is for.
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Get the port number.
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Check if SSL is enabled.
     */
    public function isSsl(): bool
    {
        return $this->ssl;
    }

    /**
     * Get the underlying HTTP client.
     */
    public function getClient(): ?ClientInterface
    {
        return $this->client;
    }

    /**
     * Set the underlying HTTP client.
     *
     * @param  ClientInterface  $client  The HTTP client
     * @return $this
     */
    public function setClient(ClientInterface $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get the timestamp when this connection was created.
     */
    public function getCreatedAt(): float
    {
        return $this->createdAt;
    }

    /**
     * Get the timestamp when this connection was last used.
     */
    public function getLastUsedAt(): float
    {
        return $this->lastUsedAt;
    }

    /**
     * Mark the connection as being used.
     *
     * @return $this
     */
    public function markUsed(): self
    {
        $this->lastUsedAt = microtime(true);

        return $this;
    }

    /**
     * Get the number of active requests.
     */
    public function getActiveRequestCount(): int
    {
        return $this->activeRequests;
    }

    /**
     * Increment the active request count.
     *
     * @return $this
     */
    public function incrementActiveRequests(): self
    {
        $this->activeRequests++;

        return $this;
    }

    /**
     * Decrement the active request count.
     *
     * @return $this
     */
    public function decrementActiveRequests(): self
    {
        if ($this->activeRequests > 0) {
            $this->activeRequests--;
        }

        return $this;
    }

    /**
     * Check if the connection is alive and usable.
     */
    public function isAlive(): bool
    {
        return ! $this->closed && $this->client !== null;
    }

    /**
     * Check if the connection can be reused.
     *
     * @param  int  $keepAliveTimeout  Keep-alive timeout in seconds
     */
    public function isReusable(int $keepAliveTimeout = 30): bool
    {
        if (! $this->isAlive()) {
            return false;
        }

        $idleTime = microtime(true) - $this->lastUsedAt;

        return $idleTime < $keepAliveTimeout;
    }

    /**
     * Close the connection.
     */
    public function close(): void
    {
        $this->closed = true;
        $this->client = null;
    }

    /**
     * Get the connection key (host:port:ssl).
     */
    public function getKey(): string
    {
        $scheme = $this->ssl ? 'https' : 'http';

        return "{$scheme}://{$this->host}:{$this->port}";
    }
}
