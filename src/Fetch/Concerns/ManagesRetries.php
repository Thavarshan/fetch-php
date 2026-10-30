<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Exceptions\RequestException as FetchRequestException;
use Fetch\Interfaces\ClientHandler;
use Fetch\Interfaces\Response as ResponseInterface;
use Fetch\Support\RequestContext;
use Fetch\Support\RetryDefaults;
use Fetch\Support\RetryStrategy;
use InvalidArgumentException;

/**
 * Exposes fluent retry configuration and delegates retry execution.
 *
 * A request context takes precedence over handler-level settings so retries
 * remain isolated when one handler processes concurrent requests.
 */
trait ManagesRetries
{
    /**
     * The maximum number of retries before giving up.
     */
    protected ?int $maxRetries = null;

    /**
     * The initial delay between retries in milliseconds.
     */
    protected ?int $retryDelay = null;

    /**
     * The status codes that should be retried.
     *
     * @var array<int>
     */
    protected array $retryableStatusCodes = RetryDefaults::STATUS_CODES;

    /**
     * The exceptions that should be retried.
     *
     * @var array<class-string<\Throwable>>
     */
    protected array $retryableExceptions = RetryDefaults::EXCEPTIONS;

    /**
     * Set the retry logic for the request.
     *
     * @param  int  $retries  Maximum number of retry attempts
     * @param  int  $delay  Initial delay in milliseconds
     * @return $this
     *
     * @throws InvalidArgumentException If the parameters are invalid
     */
    public function retry(int $retries, int $delay = 100): ClientHandler
    {
        if ($retries < 0) {
            throw new InvalidArgumentException(
                'Retries must be a non-negative integer',
            );
        }

        if ($delay < 0) {
            throw new InvalidArgumentException(
                'Delay must be a non-negative integer',
            );
        }

        $this->maxRetries = $retries;
        $this->retryDelay = $delay;

        return $this;
    }

    /**
     * Set the status codes that should be retried.
     *
     * @param  array<int>  $statusCodes  HTTP status codes
     * @return $this
     */
    public function retryStatusCodes(array $statusCodes): ClientHandler
    {
        $normalizedStatusCodes = array_map('intval', $statusCodes);

        foreach ($normalizedStatusCodes as $statusCode) {
            if ($statusCode < 100 || $statusCode > 599) {
                throw new InvalidArgumentException(
                    "Invalid HTTP status code: {$statusCode}. "
                        .'Status codes must be in the range 100-599.',
                );
            }
        }

        $this->retryableStatusCodes = $normalizedStatusCodes;

        return $this;
    }

    /**
     * Set the exception types that should be retried.
     *
     * @param  array<class-string<\Throwable>>  $exceptions  Exception class
     *                                                       names
     * @return $this
     */
    public function retryExceptions(array $exceptions): ClientHandler
    {
        $this->retryableExceptions = $exceptions;

        return $this;
    }

    /**
     * Get the current maximum retries setting.
     *
     * @return int The maximum retries
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries ?? self::DEFAULT_RETRIES;
    }

    /**
     * Get the current retry delay setting.
     *
     * @return int The retry delay in milliseconds
     */
    public function getRetryDelay(): int
    {
        return $this->retryDelay ?? self::DEFAULT_RETRY_DELAY;
    }

    /**
     * Get the retryable status codes.
     *
     * @return array<int> The retryable HTTP status codes
     */
    public function getRetryableStatusCodes(): array
    {
        return $this->retryableStatusCodes;
    }

    /**
     * Get the retryable exception types.
     *
     * @return array<class-string<\Throwable>> The retryable exception classes
     */
    public function getRetryableExceptions(): array
    {
        return $this->retryableExceptions;
    }

    /**
     * Implement retry logic for the request with exponential backoff.
     *
     * This method accepts an optional RequestContext to read retry
     * configuration
     * from per-request context instead of handler state, making it safe for
     * concurrent usage.
     * All retry settings (maxRetries, retryDelay, retryableStatusCodes,
     * retryableExceptions)
     * are read from the context when provided, falling back to handler state
     * otherwise.
     *
     * @param  RequestContext|null  $context  The request context (optional)
     * @param  callable  $request  The request to execute
     * @return ResponseInterface The response after successful execution
     *
     * @throws FetchRequestException If the request fails after all retries
     * @throws \RuntimeException If something unexpected happens
     */
    protected function retryRequest(
        ?RequestContext $context,
        callable $request,
    ): ResponseInterface {
        $strategy = $this->createRetryStrategy($context);

        return $strategy->execute($request, function (
            int $attempt,
            int $maxAttempts,
            \Throwable $exception,
            int $delayMs,
        ) use ($context): void {
            $this->notifyRetry(
                $attempt,
                $maxAttempts,
                $exception,
                $delayMs,
                $context,
            );
        });
    }

    /**
     * Build a retry strategy from request settings or handler defaults.
     */
    protected function createRetryStrategy(
        ?RequestContext $context,
    ): RetryStrategy {
        $maxRetries = $context?->getMaxRetries() ?? $this->getMaxRetries();
        $baseDelayMs = $context?->getRetryDelay() ?? $this->getRetryDelay();

        // Read retryable status codes and exceptions from context, falling back
        // to handler state
        $retryableStatusCodes =
            $context !== null
                ? $context->getRetryableStatusCodes()
                : $this->retryableStatusCodes;

        $retryableExceptions =
            $context !== null
                ? $context->getRetryableExceptions()
                : $this->retryableExceptions;

        return new RetryStrategy(
            maxRetries: $maxRetries,
            baseDelayMs: $baseDelayMs,
            retryableStatusCodes: $retryableStatusCodes,
            retryableExceptions: $retryableExceptions,
            logger: $this->logger,
        );
    }

    /**
     * Log a retry and dispatch its lifecycle event when context is available.
     */
    protected function notifyRetry(
        int $attempt,
        int $maxAttempts,
        \Throwable $exception,
        int $delayMs,
        ?RequestContext $context,
    ): void {
        $this->logRetryAttempt(
            $attempt,
            $maxAttempts,
            $exception,
            $delayMs,
            $context,
        );
        if ($context !== null) {
            $this->emitRetryEvent(
                $context,
                $exception,
                $attempt,
                $maxAttempts,
                $delayMs,
            );
        }
    }

    /**
     * Log a retry attempt with context information.
     *
     * @param  int  $attempt  Current attempt number
     * @param  int  $maxAttempts  Maximum number of attempts
     * @param  \Throwable  $exception  The exception that triggered the retry
     * @param  int  $delayMs  The delay before the next attempt in milliseconds
     * @param  RequestContext|null  $context  Optional request context for
     *                                        additional info
     */
    protected function logRetryAttempt(
        int $attempt,
        int $maxAttempts,
        \Throwable $exception,
        int $delayMs,
        ?RequestContext $context = null,
    ): void {
        $this->logRetry($attempt, $maxAttempts, $exception);
    }

    /**
     * Calculate backoff delay with exponential growth and jitter.
     *
     * Delegates to RetryStrategy for the actual calculation to avoid
     * duplication.
     *
     * @param  int  $baseDelay  The base delay in milliseconds
     * @param  int  $attempt  The current attempt number (0-based)
     * @return int The calculated delay in milliseconds
     */
    protected function calculateBackoffDelay(int $baseDelay, int $attempt): int
    {
        $strategy = new RetryStrategy(baseDelayMs: $baseDelay);

        return $strategy->calculateDelay($attempt);
    }

    /**
     * Determine if an error is retryable.
     *
     * Delegates to RetryStrategy for the actual check to avoid duplication.
     *
     * @param  \Throwable  $e  The exception to check
     * @param  RequestContext|null  $context  Optional context for per-request
     *                                        retry config
     * @return bool Whether the error is retryable
     */
    protected function isRetryableError(
        \Throwable $e,
        ?RequestContext $context = null,
    ): bool {
        return $this->createRetryStrategy($context)->isRetryable($e);
    }
}
