<?php

declare(strict_types=1);

namespace Fetch\Enum;

enum Method: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';

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
    public static function tryFromString(string $method, ?self $default = null): ?self
    {
        return self::tryFrom(strtoupper($method)) ?? $default;
    }

    /**
     * Determine if the method supports a request body.
     */
    public function supportsRequestBody(): bool
    {
        return in_array($this, [self::POST, self::PUT, self::PATCH, self::DELETE]);
    }
}
