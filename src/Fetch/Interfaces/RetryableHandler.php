<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

/**
 * Configures which request failures are eligible for retry.
 */
interface RetryableHandler
{
    /**
     * Set retry count and initial delay in milliseconds.
     */
    public function retry(int $retries, int $delay = 100): self;

    /**
     * Sets the HTTP status codes that make a request retryable.
     *
     * @param  array<int>  $statusCodes
     */
    public function retryStatusCodes(array $statusCodes): self;

    /**
     * Sets the exception types that make a request retryable.
     *
     * @param  array<class-string<\Throwable>>  $exceptions
     */
    public function retryExceptions(array $exceptions): self;

    /**
     * Return the effective maximum retry count.
     */
    public function getMaxRetries(): int;

    /**
     * Return the effective initial retry delay in milliseconds.
     */
    public function getRetryDelay(): int;

    /**
     * Returns the HTTP status codes configured for retries.
     *
     * @return array<int>
     */
    public function getRetryableStatusCodes(): array;

    /**
     * Returns the exception types configured for retries.
     *
     * @return array<class-string<\Throwable>>
     */
    public function getRetryableExceptions(): array;
}
