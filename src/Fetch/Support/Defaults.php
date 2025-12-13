<?php

declare(strict_types=1);

namespace Fetch\Support;

use Fetch\Enum\Method;

/**
 * Centralized defaults for all Fetch HTTP client configuration.
 *
 * This class serves as the single source of truth for all default values,
 * eliminating duplication across ClientHandler, GlobalServices, and other classes.
 *
 * @internal
 */
final class Defaults
{
    /**
     * Default HTTP method for requests.
     */
    public const HTTP_METHOD = Method::GET;

    /**
     * Default timeout for requests in seconds.
     */
    public const TIMEOUT = 30;

    /**
     * Default number of retry attempts.
     *
     * @deprecated Use RetryDefaults::MAX_RETRIES instead
     */
    public const RETRIES = RetryDefaults::MAX_RETRIES;

    /**
     * Default delay between retries in milliseconds.
     *
     * @deprecated Use RetryDefaults::RETRY_DELAY instead
     */
    public const RETRY_DELAY = RetryDefaults::RETRY_DELAY;

    /**
     * Prevent instantiation of this utility class.
     */
    private function __construct() {}

    /**
     * Get the factory default options array.
     *
     * @return array<string, mixed>
     */
    public static function options(): array
    {
        return [
            'method' => self::HTTP_METHOD->value,
            'timeout' => self::TIMEOUT,
            'headers' => [],
        ];
    }
}
