<?php

declare(strict_types=1);

namespace Fetch\Enum;

/**
 * Common HTTP method tokens, backed by their uppercase wire representation.
 *
 * String conversion is case-normalized; the body helper reflects FetchPHP's
 * supported convenience methods and does not define all HTTP semantics.
 */
enum Method: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case TRACE = 'TRACE';
    case CONNECT = 'CONNECT';

    /**
     * Get the method from a string.
     */
    public static function fromString(string $method): self
    {
        return self::from(strtoupper($method));
    }

    /**
     * Try to get the method from a string, or return default.
     */
    public static function tryFromString(
        string $method,
        ?self $default = null,
    ): ?self {
        return self::tryFrom(strtoupper($method)) ?? $default;
    }

    /**
     * Determine if the method supports a request body.
     */
    public function supportsRequestBody(): bool
    {
        // CONNECT request bodies are intentionally not enabled by default; HTTP
        // clients normally use this method only to establish a proxy tunnel.
        return in_array($this, [
            self::POST,
            self::PUT,
            self::PATCH,
            self::DELETE,
        ]);
    }
}
