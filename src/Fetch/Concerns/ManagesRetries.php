<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Interfaces\ClientHandler;
use Fetch\Interfaces\Response as ResponseInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

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
     * @var array<class-string<Throwable>>
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
     * @throws InvalidArgumentException If the parameters are invalid
     */
    public function retry(int $retries, int $delay = 100): ClientHandler
    {
        if ($retries < 0) {
            throw new InvalidArgumentException('Retries must be a non-negative integer');
        }

        if ($delay < 0) {
            throw new InvalidArgumentException('Delay must be a non-negative integer');
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
     * @param  array<class-string<Throwable>>  $exceptions  Exception class names
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
     * @return array<class-string<Throwable>> The retryable exception classes
     */
    public function getRetryableExceptions(): array
    {
        return $this->retryableExceptions;
    }

    /**
     * Implement retry logic for the request with exponential backoff.
     *
     * @param  callable  $request  The request to execute
     * @return ResponseInterface The response after successful execution
     *
     * @throws RequestException If the request fails after all retries
     * @throws RuntimeException If something unexpected happens
     */
    protected function retryRequest(callable $request): ResponseInterface
    {
        $attempts = $this->maxRetries ?? self::DEFAULT_RETRIES;
        $delay = $this->retryDelay ?? self::DEFAULT_RETRY_DELAY;
        $exceptions = [];

        for ($attempt = 0; $attempt <= $attempts; $attempt++) {
            try {
                // Execute the request
                return $request();
            } catch (RequestException $e) {
                // Collect exception for later
                $exceptions[] = $e;

                // If this was the last attempt, break to throw the most recent exception
                if ($attempt === $attempts) {
                    break;
                }

                // Only retry on retryable errors
                if (! $this->isRetryableError($e)) {
                    throw $e;
                }

                // Log the retry for debugging purposes
                if (method_exists($this, 'logRetry')) {
                    $this->logRetry($attempt + 1, $attempts, $e);
                }

                // Calculate delay with exponential backoff and jitter
                $currentDelay = $this->calculateBackoffDelay($delay, $attempt);

                // Sleep before the next retry
                usleep($currentDelay * 1000); // Convert milliseconds to microseconds
            } catch (Throwable $e) {
                // Handle unexpected exceptions (not RequestException)
                throw new RuntimeException(
                    sprintf('Unexpected error during request: %s', $e->getMessage()),
                    (int) $e->getCode(),
                    $e
                );
            }
        }

        // If we got here, all retries failed
        $lastException = end($exceptions) ?: new RuntimeException('Request failed after all retries');

        // Enhanced failure reporting
        if ($lastException instanceof RequestException) {
            $statusCode = $lastException->getCode();
            throw new RuntimeException(
                sprintf(
                    'Request failed after %d attempts with status code %d: %s',
                    $attempts + 1,
                    $statusCode,
                    $lastException->getMessage()
                ),
                $statusCode,
                $lastException
            );
        }

        throw $lastException;
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
     * @param  RequestException  $e  The exception to check
     * @return bool Whether the error is retryable
     */
    protected function isRetryableError(RequestException $e): bool
    {
        $statusCode = $e->getCode();

        // Check if the status code is in our list of retryable codes
        $isRetryableStatusCode = in_array($statusCode, $this->retryableStatusCodes, true);

        // Check if the exception or its previous is one of our retryable exception types
        $isRetryableException = false;
        $exception = $e;

        while ($exception) {
            if (in_array(get_class($exception), $this->retryableExceptions, true)) {
                $isRetryableException = true;
                break;
            }
            $exception = $exception->getPrevious();
        }

        return $isRetryableStatusCode || $isRetryableException;
    }
}
