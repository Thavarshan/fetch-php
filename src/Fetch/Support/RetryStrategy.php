<?php

declare(strict_types=1);

namespace Fetch\Support;

use Fetch\Exceptions\RequestException as FetchRequestException;
use Fetch\Interfaces\Response as ResponseInterface;
use GuzzleHttp\Exception\ConnectException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Handles retry logic for failed HTTP requests.
 *
 * This service encapsulates the retry strategy including:
 * - Exponential backoff with jitter
 * - Retryable status code detection
 * - Retryable exception detection
 * - Maximum retry limits
 *
 * **Design Decisions:**
 *
 * 1. **Exponential backoff**: Each retry doubles the delay to avoid
 *    overwhelming the server during transient failures.
 *
 * 2. **Jitter**: Random variation (0-100%) is added to delays to prevent
 *    thundering herd problems when many clients retry simultaneously.
 *
 * 3. **Maximum delay cap**: Delays are capped at 30 seconds to prevent
 *    excessively long waits.
 *
 * 4. **Stateless**: This service is stateless and can be shared across requests.
 */
final class RetryStrategy
{
    /**
     * Maximum delay in milliseconds (30 seconds).
     */
    private const MAX_DELAY_MS = 30000;

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
    private const DEFAULT_RETRYABLE_STATUS_CODES = [
        408, 429, 500, 502, 503,
        504, 507, 509, 520, 521,
        522, 523, 525, 527, 530,
    ];

    /**
     * Default retryable exception types.
     *
     * @var array<class-string<\Throwable>>
     */
    private const DEFAULT_RETRYABLE_EXCEPTIONS = [
        ConnectException::class,
    ];

    /**
     * Maximum number of retry attempts.
     */
    private int $maxRetries;

    /**
     * Base delay between retries in milliseconds.
     */
    private int $baseDelayMs;

    /**
     * Status codes that should trigger a retry.
     *
     * @var array<int>
     */
    private array $retryableStatusCodes;

    /**
     * Exception types that should trigger a retry.
     *
     * @var array<class-string<\Throwable>>
     */
    private array $retryableExceptions;

    /**
     * Logger for retry events.
     */
    private LoggerInterface $logger;

    /**
     * Create a new retry strategy.
     *
     * @param  int  $maxRetries  Maximum retry attempts (0 = no retries)
     * @param  int  $baseDelayMs  Base delay in milliseconds
     * @param  array<int>|null  $retryableStatusCodes  Status codes to retry
     * @param  array<class-string<\Throwable>>|null  $retryableExceptions  Exceptions to retry
     * @param  LoggerInterface|null  $logger  Logger for events
     */
    public function __construct(
        int $maxRetries = 1,
        int $baseDelayMs = 100,
        ?array $retryableStatusCodes = null,
        ?array $retryableExceptions = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->maxRetries = max(0, $maxRetries);
        $this->baseDelayMs = max(0, $baseDelayMs);
        $this->retryableStatusCodes = $retryableStatusCodes ?? self::DEFAULT_RETRYABLE_STATUS_CODES;
        $this->retryableExceptions = $retryableExceptions ?? self::DEFAULT_RETRYABLE_EXCEPTIONS;
        $this->logger = $logger ?? new NullLogger;
    }

    /**
     * Create a retry strategy from an options array.
     *
     * @param  array<string, mixed>  $options
     */
    public static function fromOptions(array $options, ?LoggerInterface $logger = null): self
    {
        return new self(
            maxRetries: (int) ($options['retries'] ?? $options['max_retries'] ?? 1),
            baseDelayMs: (int) ($options['retry_delay'] ?? 100),
            retryableStatusCodes: $options['retry_status_codes'] ?? null,
            retryableExceptions: $options['retry_exceptions'] ?? null,
            logger: $logger
        );
    }

    /**
     * Create a strategy with no retries.
     */
    public static function disabled(): self
    {
        return new self(maxRetries: 0);
    }

    /**
     * Execute a request with retry logic.
     *
     * @param  callable(): ResponseInterface  $request  The request callable
     * @param  callable|null  $onRetry  Optional callback for retry events
     * @return ResponseInterface The successful response
     *
     * @throws \Throwable If all retries fail
     */
    public function execute(callable $request, ?callable $onRetry = null): ResponseInterface
    {
        $exceptions = [];
        $attempts = $this->maxRetries;

        for ($attempt = 0; $attempt <= $attempts; $attempt++) {
            try {
                return $request();
            } catch (\Throwable $e) {
                $exceptions[] = $e;

                // If this was the last attempt, break
                if ($attempt === $attempts) {
                    break;
                }

                // Only retry on retryable errors
                if (! $this->isRetryable($e)) {
                    throw $e;
                }

                // Calculate delay with exponential backoff and jitter
                $delay = $this->calculateDelay($attempt);

                // Log and notify about retry
                $this->logRetry($attempt + 1, $attempts, $e);
                if ($onRetry !== null) {
                    $onRetry($attempt + 1, $attempts, $e, $delay);
                }

                // Sleep before retry
                usleep($delay * 1000);
            }
        }

        // All retries failed - throw enhanced exception
        $lastException = end($exceptions) ?: new RuntimeException('Request failed after all retries');

        return $this->handleFinalFailure($attempts, $lastException);
    }

    /**
     * Check if an error is retryable.
     */
    public function isRetryable(\Throwable $e): bool
    {
        // Check status code from response
        $statusCode = $this->extractStatusCode($e);
        if ($statusCode !== null && $this->isRetryableStatusCode($statusCode)) {
            return true;
        }

        // Check exception type
        return $this->isRetryableException($e);
    }

    /**
     * Check if a status code is retryable.
     */
    public function isRetryableStatusCode(int $statusCode): bool
    {
        return in_array($statusCode, $this->retryableStatusCodes, true);
    }

    /**
     * Check if an exception type is retryable.
     */
    public function isRetryableException(\Throwable $e): bool
    {
        $exception = $e;

        while ($exception !== null) {
            foreach ($this->retryableExceptions as $retryableClass) {
                if ($exception instanceof $retryableClass) {
                    return true;
                }
            }
            $exception = $exception->getPrevious();
        }

        return false;
    }

    /**
     * Calculate the delay for a retry attempt.
     *
     * Uses exponential backoff with jitter:
     * delay = baseDelay * 2^attempt * (1 + random(0, 1))
     *
     * @param  int  $attempt  Zero-based attempt number
     * @return int Delay in milliseconds
     */
    public function calculateDelay(int $attempt): int
    {
        // Exponential backoff: baseDelay * 2^attempt
        $exponentialDelay = $this->baseDelayMs * (2 ** $attempt);

        // Add jitter: 0-100% of the calculated delay
        $jitter = mt_rand(0, 100) / 100;
        $delay = (int) ($exponentialDelay * (1 + $jitter));

        // Cap at maximum delay
        return min($delay, self::MAX_DELAY_MS);
    }

    /**
     * Get the maximum number of retries.
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Get the base delay in milliseconds.
     */
    public function getBaseDelay(): int
    {
        return $this->baseDelayMs;
    }

    /**
     * Get the retryable status codes.
     *
     * @return array<int>
     */
    public function getRetryableStatusCodes(): array
    {
        return $this->retryableStatusCodes;
    }

    /**
     * Get the retryable exception types.
     *
     * @return array<class-string<\Throwable>>
     */
    public function getRetryableExceptions(): array
    {
        return $this->retryableExceptions;
    }

    /**
     * Create a copy with different max retries.
     */
    public function withMaxRetries(int $maxRetries): self
    {
        return new self(
            maxRetries: $maxRetries,
            baseDelayMs: $this->baseDelayMs,
            retryableStatusCodes: $this->retryableStatusCodes,
            retryableExceptions: $this->retryableExceptions,
            logger: $this->logger
        );
    }

    /**
     * Create a copy with different base delay.
     */
    public function withBaseDelay(int $baseDelayMs): self
    {
        return new self(
            maxRetries: $this->maxRetries,
            baseDelayMs: $baseDelayMs,
            retryableStatusCodes: $this->retryableStatusCodes,
            retryableExceptions: $this->retryableExceptions,
            logger: $this->logger
        );
    }

    /**
     * Create a copy with different retryable status codes.
     *
     * @param  array<int>  $statusCodes
     */
    public function withRetryableStatusCodes(array $statusCodes): self
    {
        return new self(
            maxRetries: $this->maxRetries,
            baseDelayMs: $this->baseDelayMs,
            retryableStatusCodes: $statusCodes,
            retryableExceptions: $this->retryableExceptions,
            logger: $this->logger
        );
    }

    /**
     * Create a copy with different retryable exceptions.
     *
     * @param  array<class-string<\Throwable>>  $exceptions
     */
    public function withRetryableExceptions(array $exceptions): self
    {
        return new self(
            maxRetries: $this->maxRetries,
            baseDelayMs: $this->baseDelayMs,
            retryableStatusCodes: $this->retryableStatusCodes,
            retryableExceptions: $exceptions,
            logger: $this->logger
        );
    }

    /**
     * Extract status code from an exception if available.
     */
    private function extractStatusCode(\Throwable $e): ?int
    {
        // Check Fetch RequestException
        if ($e instanceof FetchRequestException && $e->getResponse() !== null) {
            return $e->getResponse()->getStatusCode();
        }

        // Check Guzzle-style exceptions
        if (method_exists($e, 'getResponse')) {
            $response = $e->getResponse();
            if ($response !== null && method_exists($response, 'getStatusCode')) {
                return $response->getStatusCode();
            }
        }

        return null;
    }

    /**
     * Log a retry attempt.
     */
    private function logRetry(int $attempt, int $maxAttempts, \Throwable $e): void
    {
        $this->logger->info('Retrying request', [
            'attempt' => $attempt,
            'max_attempts' => $maxAttempts,
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
        ]);
    }

    /**
     * Handle final failure after all retries exhausted.
     *
     * @return never
     *
     * @throws RuntimeException Always throws
     */
    private function handleFinalFailure(int $attempts, \Throwable $lastException): ResponseInterface
    {
        if ($lastException instanceof FetchRequestException && $lastException->getResponse() !== null) {
            $statusCode = $lastException->getResponse()->getStatusCode();
            throw new RuntimeException(sprintf('Request failed after %d attempts with status code %d: %s', $attempts + 1, $statusCode, $lastException->getMessage()), $statusCode, $lastException);
        }

        throw $lastException;
    }
}
