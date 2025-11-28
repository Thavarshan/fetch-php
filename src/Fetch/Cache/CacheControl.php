<?php

declare(strict_types=1);

namespace Fetch\Cache;

use Psr\Http\Message\ResponseInterface;

/**
 * Parses and handles Cache-Control headers according to RFC 7234.
 */
class CacheControl
{
    /**
     * Parsed Cache-Control directives.
     *
     * @var array<string, mixed>
     */
    private array $directives = [];

    /**
     * Create a new CacheControl instance.
     *
     * @param  array<string, mixed>  $directives  The parsed directives
     */
    public function __construct(array $directives = [])
    {
        $this->directives = $directives;
    }

    /**
     * Parse a Cache-Control header string.
     *
     * @param  string  $cacheControl  The Cache-Control header value
     * @return self
     */
    public static function parse(string $cacheControl): self
    {
        $directives = [];

        foreach (explode(',', $cacheControl) as $directive) {
            $directive = trim($directive);
            if ($directive === '') {
                continue;
            }

            $parts = explode('=', $directive, 2);
            $name = strtolower(trim($parts[0]));
            $value = isset($parts[1]) ? trim($parts[1], '"') : true;

            // Convert numeric values
            if (is_string($value) && is_numeric($value)) {
                $value = (int) $value;
            }

            $directives[$name] = $value;
        }

        return new self($directives);
    }

    /**
     * Parse Cache-Control from a response.
     *
     * @param  ResponseInterface  $response  The HTTP response
     * @return self
     */
    public static function fromResponse(ResponseInterface $response): self
    {
        return self::parse($response->getHeaderLine('Cache-Control'));
    }

    /**
     * Determine if the response should be cached.
     *
     * @param  ResponseInterface  $response  The HTTP response
     * @param  bool  $isSharedCache  Whether this is a shared cache
     * @return bool
     */
    public function shouldCache(ResponseInterface $response, bool $isSharedCache = false): bool
    {
        // Don't cache if no-store is set
        if ($this->hasNoStore()) {
            return false;
        }

        // Don't cache private responses in shared cache
        if ($isSharedCache && $this->isPrivate()) {
            return false;
        }

        // Check response status code
        $status = $response->getStatusCode();
        $cacheableStatuses = [200, 203, 204, 206, 300, 301, 404, 405, 410, 414, 501];

        if (! in_array($status, $cacheableStatuses, true)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the response requires validation before being served.
     */
    public function mustRevalidate(): bool
    {
        return $this->has('must-revalidate') || $this->has('proxy-revalidate');
    }

    /**
     * Check if the response should not be cached.
     */
    public function hasNoCache(): bool
    {
        return $this->has('no-cache');
    }

    /**
     * Check if the response should not be stored at all.
     */
    public function hasNoStore(): bool
    {
        return $this->has('no-store');
    }

    /**
     * Check if the response is private.
     */
    public function isPrivate(): bool
    {
        return $this->has('private');
    }

    /**
     * Check if the response is public.
     */
    public function isPublic(): bool
    {
        return $this->has('public');
    }

    /**
     * Get the max-age directive value.
     */
    public function getMaxAge(): ?int
    {
        return $this->getInt('max-age');
    }

    /**
     * Get the s-maxage directive value (for shared caches).
     */
    public function getSharedMaxAge(): ?int
    {
        return $this->getInt('s-maxage');
    }

    /**
     * Get the stale-while-revalidate directive value.
     */
    public function getStaleWhileRevalidate(): ?int
    {
        return $this->getInt('stale-while-revalidate');
    }

    /**
     * Get the stale-if-error directive value.
     */
    public function getStaleIfError(): ?int
    {
        return $this->getInt('stale-if-error');
    }

    /**
     * Calculate the TTL for the response.
     *
     * @param  ResponseInterface  $response  The HTTP response
     * @param  bool  $isSharedCache  Whether this is a shared cache
     * @return int|null The TTL in seconds, or null if not cacheable
     */
    public function getTtl(ResponseInterface $response, bool $isSharedCache = false): ?int
    {
        // For shared caches, s-maxage takes precedence
        if ($isSharedCache) {
            $sMaxAge = $this->getSharedMaxAge();
            if ($sMaxAge !== null) {
                return $sMaxAge;
            }
        }

        // Check max-age directive
        $maxAge = $this->getMaxAge();
        if ($maxAge !== null) {
            return $maxAge;
        }

        // Fall back to Expires header
        $expires = $response->getHeaderLine('Expires');
        if ($expires !== '') {
            $expiresTime = strtotime($expires);
            if ($expiresTime !== false) {
                return max(0, $expiresTime - time());
            }
        }

        return null;
    }

    /**
     * Check if a directive exists.
     */
    public function has(string $directive): bool
    {
        return isset($this->directives[$directive]);
    }

    /**
     * Get a directive value.
     */
    public function get(string $directive): mixed
    {
        return $this->directives[$directive] ?? null;
    }

    /**
     * Get an integer directive value.
     */
    public function getInt(string $directive): ?int
    {
        $value = $this->get($directive);
        if ($value === null) {
            return null;
        }

        return is_int($value) ? $value : (int) $value;
    }

    /**
     * Get all directives.
     *
     * @return array<string, mixed>
     */
    public function getDirectives(): array
    {
        return $this->directives;
    }

    /**
     * Build a Cache-Control header string.
     *
     * @param  array<string, mixed>  $directives  The directives to include
     * @return string
     */
    public static function build(array $directives): string
    {
        $parts = [];

        foreach ($directives as $name => $value) {
            if ($value === true) {
                $parts[] = $name;
            } elseif ($value !== false && $value !== null) {
                $parts[] = "{$name}={$value}";
            }
        }

        return implode(', ', $parts);
    }
}
