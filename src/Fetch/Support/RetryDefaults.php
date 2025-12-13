<?php

declare(strict_types=1);

namespace Fetch\Support;

use GuzzleHttp\Exception\ConnectException;

/**
 * Centralized defaults for retry configuration.
 *
 * This class serves as the single source of truth for all retry-related
 * default values, eliminating duplication across RequestContext,
 * RetryStrategy, and ManagesRetries.
 *
 * @internal
 */
final class RetryDefaults
{
    /**
     * Maximum delay between retries in milliseconds (30 seconds).
     */
    public const MAX_DELAY_MS = 30000;

    /**
     * Default number of retry attempts.
     */
    public const MAX_RETRIES = 1;

    /**
     * Default delay between retries in milliseconds.
     */
    public const RETRY_DELAY = 100;

    /**
     * Default retryable HTTP status codes.
     *
     * These indicate temporary server-side issues that may resolve on retry:
     * - 408: Request Timeout
     * - 429: Too Many Requests
     * - 500: Internal Server Error
     * - 502: Bad Gateway
     * - 503: Service Unavailable
     * - 504: Gateway Timeout
     * - 507: Insufficient Storage
     * - 509: Bandwidth Limit Exceeded
     * - 520-530: Cloudflare errors
     *
     * @var array<int>
     */
    public const STATUS_CODES = [
        408, 429, 500, 502, 503,
        504, 507, 509, 520, 521,
        522, 523, 525, 527, 530,
    ];

    /**
     * Default retryable exception class names.
     *
     * @var array<class-string<\Throwable>>
     */
    public const EXCEPTIONS = [
        ConnectException::class,
    ];

    /**
     * Prevent instantiation of this utility class.
     */
    private function __construct() {}
}
