<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Exceptions\RequestException as FetchRequestException;
use Fetch\Interfaces\ClientHandler;
use Fetch\Interfaces\Response as ResponseInterface;
use Fetch\Support\RetryStrategy;
use GuzzleHttp\Exception\ConnectException;
use Psr\Log\NullLogger;

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
    protected array $retryableStatusCodes = [
        408, 429, 500, 502, 503,
        504, 507, 509, 520, 521,
        522, 523, 525, 527, 530,
    ];

    /**
     * The exceptions that should be retried.
     *
     * @var array<class-string<\Throwable>>
     */
    protected array $retryableExceptions = [
        ConnectException::class,
    ];

    /**
     * Set the retry logic for the request.
     *
     * @param  int  $retries  Maximum number of retry attempts
     * @param  int  $delay  Initial delay in milliseconds
     * @return $this
     *
     * @throws \InvalidArgumentException If the parameters are invalid
     */
    public function retry(int $retries, int $delay = 100): ClientHandler
    {
        if ($retries < 0) {
            throw new \InvalidArgumentException('Retries must be a non-negative integer');
        }

        if ($delay < 0) {
            throw new \InvalidArgumentException('Delay must be a non-negative integer');
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
        $this->retryableStatusCodes = array_map('intval', $statusCodes);

        return $this;
    }

    /**
     * Set the exception types that should be retried.
     *
     * @param  array<class-string<\Throwable>>  $exceptions  Exception class names
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
     * This method accepts an optional RequestContext to read retry configuration
     * from per-request context instead of handler state, making it safe for concurrent usage.
     * All retry settings (maxRetries, retryDelay, retryableStatusCodes, retryableExceptions)
     * are read from the context when provided, falling back to handler state otherwise.
     *
     * @param  \Fetch\Support\RequestContext|null  $context  The request context (optional)
     * @param  callable  $request  The request to execute
     * @return ResponseInterface The response after successful execution
     *
     * @throws FetchRequestException If the request fails after all retries
     * @throws \RuntimeException If something unexpected happens
     */
    protected function retryRequest(?\Fetch\Support\RequestContext $context, callable $request): ResponseInterface
    {
        // Read retry config from context if provided, otherwise fall back to handler state
        $maxRetries = $context?->getMaxRetries() ?? $this->getMaxRetries();
        $baseDelayMs = $context?->getRetryDelay() ?? $this->getRetryDelay();

        // Read retryable status codes and exceptions from context, falling back to handler state
        $retryableStatusCodes = $context !== null
            ? $context->getRetryableStatusCodes()
            : $this->retryableStatusCodes;

        $retryableExceptions = $context !== null
            ? $context->getRetryableExceptions()
            : $this->retryableExceptions;

        $strategy = new RetryStrategy(
            maxRetries: $maxRetries,
            baseDelayMs: $baseDelayMs,
            retryableStatusCodes: $retryableStatusCodes,
            retryableExceptions: $retryableExceptions,
            logger: $this->logger ?? new NullLogger
        );

        return $strategy->execute(
            $request,
            function (int $attempt, int $maxAttempts, \Throwable $exception, int $delayMs) use ($context): void {
                $this->logRetryAttempt($attempt, $maxAttempts, $exception, $delayMs, $context);
            }
        );
    }

    /**
     * Log a retry attempt with context information.
     *
     * @param  int  $attempt  Current attempt number
     * @param  int  $maxAttempts  Maximum number of attempts
     * @param  \Throwable  $exception  The exception that triggered the retry
     * @param  int  $delayMs  The delay before the next attempt in milliseconds
     * @param  \Fetch\Support\RequestContext|null  $context  Optional request context for additional info
     */
    protected function logRetryAttempt(
        int $attempt,
        int $maxAttempts,
        \Throwable $exception,
        int $delayMs,
        ?\Fetch\Support\RequestContext $context = null,
    ): void {
        if (method_exists($this, 'logRetry')) {
            $this->logRetry($attempt, $maxAttempts, $exception);
        }
    }

    /**
     * Calculate backoff delay with exponential growth and jitter.
     *
     * @param  int  $baseDelay  The base delay in milliseconds
     * @param  int  $attempt  The current attempt number (0-based)
     * @return int The calculated delay in milliseconds
     */
    protected function calculateBackoffDelay(int $baseDelay, int $attempt): int
    {
        // Exponential backoff: baseDelay * 2^attempt
        $exponentialDelay = $baseDelay * (2 ** $attempt);

        // Add jitter: random value between 0-100% of the calculated delay
        $jitter = mt_rand(0, 100) / 100; // Random value between 0 and 1
        $delay = (int) ($exponentialDelay * (1 + $jitter));

        // Cap the maximum delay at 30 seconds (30000ms)
        return min($delay, 30000);
    }

    /**
     * Determine if an error is retryable.
     *
     * @param  \Throwable  $e  The exception to check
     * @param  \Fetch\Support\RequestContext|null  $context  Optional context for per-request retry config
     * @return bool Whether the error is retryable
     */
    protected function isRetryableError(\Throwable $e, ?\Fetch\Support\RequestContext $context = null): bool
    {
        // Use context values if provided, otherwise fall back to handler state
        $retryableStatusCodes = $context !== null
            ? $context->getRetryableStatusCodes()
            : $this->retryableStatusCodes;

        $retryableExceptions = $context !== null
            ? $context->getRetryableExceptions()
            : $this->retryableExceptions;

        $statusCode = null;

        // Try to extract status code from a Fetch RequestException
        if ($e instanceof FetchRequestException && $e->getResponse()) {
            $statusCode = $e->getResponse()->getStatusCode();
        } elseif (method_exists($e, 'getResponse')) {
            // Guzzle RequestException also has getResponse()
            $response = $e->getResponse();
            if ($response && method_exists($response, 'getStatusCode')) {
                /** @var \Psr\Http\Message\ResponseInterface $response */
                $statusCode = $response->getStatusCode();
            }
        }

        // Check if the status code is in our list of retryable codes
        $isRetryableStatusCode = $statusCode !== null && in_array($statusCode, $retryableStatusCodes, true);

        // Check if the exception or its previous is one of our retryable exception types
        $isRetryableException = false;
        $exception = $e;

        while ($exception) {
            if (in_array(get_class($exception), $retryableExceptions, true)) {
                $isRetryableException = true;
                break;
            }
            $exception = $exception->getPrevious();
        }

        return $isRetryableStatusCode || $isRetryableException;
    }
}
