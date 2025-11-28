<?php

declare(strict_types=1);

namespace Fetch\Cache;

use Psr\Http\Message\ResponseInterface;

/**
 * Represents a cached HTTP response with metadata.
 */
class CachedResponse
{
    /**
     * Create a new cached response.
     *
     * @param  int  $statusCode  HTTP status code
     * @param  array<string, array<int, string>>  $headers  Response headers
     * @param  string  $body  Response body
     * @param  int  $createdAt  Timestamp when the response was cached
     * @param  int|null  $expiresAt  Timestamp when the response expires (null = never expires)
     * @param  string|null  $etag  ETag value from the response
     * @param  string|null  $lastModified  Last-Modified header value
     * @param  array<string, mixed>  $metadata  Additional metadata
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly array $headers,
        private readonly string $body,
        private readonly int $createdAt,
        private readonly ?int $expiresAt = null,
        private readonly ?string $etag = null,
        private readonly ?string $lastModified = null,
        private readonly array $metadata = []
    ) {}

    /**
     * Create a cached response from a PSR-7 response.
     *
     * @param  ResponseInterface  $response  The PSR-7 response
     * @param  int|null  $ttl  Time to live in seconds
     * @return self
     */
    public static function fromResponse(ResponseInterface $response, ?int $ttl = null): self
    {
        $now = time();
        $expiresAt = $ttl !== null ? $now + $ttl : null;

        // Extract ETag
        $etag = $response->hasHeader('ETag')
            ? $response->getHeaderLine('ETag')
            : null;

        // Extract Last-Modified
        $lastModified = $response->hasHeader('Last-Modified')
            ? $response->getHeaderLine('Last-Modified')
            : null;

        return new self(
            statusCode: $response->getStatusCode(),
            headers: $response->getHeaders(),
            body: (string) $response->getBody(),
            createdAt: $now,
            expiresAt: $expiresAt,
            etag: $etag,
            lastModified: $lastModified
        );
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get all headers.
     *
     * @return array<string, array<int, string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get a specific header.
     *
     * @return array<int, string>
     */
    public function getHeader(string $name): array
    {
        // Headers are case-insensitive
        foreach ($this->headers as $headerName => $values) {
            if (strcasecmp($headerName, $name) === 0) {
                return $values;
            }
        }

        return [];
    }

    /**
     * Get a header line (comma-separated values).
     */
    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * Check if a header exists.
     */
    public function hasHeader(string $name): bool
    {
        foreach (array_keys($this->headers) as $headerName) {
            if (strcasecmp($headerName, $name) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the response body.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get the timestamp when the response was cached.
     */
    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    /**
     * Get the expiration timestamp.
     */
    public function getExpiresAt(): ?int
    {
        return $this->expiresAt;
    }

    /**
     * Get the ETag value.
     */
    public function getETag(): ?string
    {
        return $this->etag;
    }

    /**
     * Get the Last-Modified value.
     */
    public function getLastModified(): ?string
    {
        return $this->lastModified;
    }

    /**
     * Get additional metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Check if the cached response has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return time() > $this->expiresAt;
    }

    /**
     * Check if the cached response is fresh.
     */
    public function isFresh(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Get the age of the cached response in seconds.
     */
    public function getAge(): int
    {
        return time() - $this->createdAt;
    }

    /**
     * Get the remaining TTL in seconds.
     *
     * @return int|null The remaining TTL, or null if no expiration is set
     */
    public function getRemainingTtl(): ?int
    {
        if ($this->expiresAt === null) {
            return null;
        }

        return max(0, $this->expiresAt - time());
    }

    /**
     * Check if the response can be used for stale-while-revalidate.
     *
     * @param  int  $maxStale  Maximum staleness allowed in seconds
     */
    public function isUsableAsStale(int $maxStale): bool
    {
        if ($this->expiresAt === null) {
            return true;
        }

        return time() <= ($this->expiresAt + $maxStale);
    }

    /**
     * Serialize the cached response for storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status_code' => $this->statusCode,
            'headers' => $this->headers,
            'body' => $this->body,
            'created_at' => $this->createdAt,
            'expires_at' => $this->expiresAt,
            'etag' => $this->etag,
            'last_modified' => $this->lastModified,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Create a cached response from a serialized array.
     *
     * @param  array<string, mixed>  $data  The serialized data
     * @return self|null Returns null if the data is invalid
     */
    public static function fromArray(array $data): ?self
    {
        if (! isset($data['status_code'], $data['headers'], $data['body'], $data['created_at'])) {
            return null;
        }

        return new self(
            statusCode: (int) $data['status_code'],
            headers: (array) $data['headers'],
            body: (string) $data['body'],
            createdAt: (int) $data['created_at'],
            expiresAt: isset($data['expires_at']) ? (int) $data['expires_at'] : null,
            etag: $data['etag'] ?? null,
            lastModified: $data['last_modified'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }
}
