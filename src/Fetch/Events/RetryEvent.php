<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * Event dispatched when an HTTP request is about to be retried.
 */
class RetryEvent extends FetchEvent
{
    /**
     * Create a new retry event instance.
     *
     * @param  RequestInterface  $request  The HTTP request being retried
     * @param  Throwable  $previousException  The exception from the previous attempt
     * @param  int  $attempt  The current attempt number (1-based)
     * @param  int  $maxAttempts  The maximum number of attempts allowed
     * @param  int  $delay  The delay in milliseconds before the retry
     * @param  string  $correlationId  Unique identifier for correlating related events
     * @param  float  $timestamp  Unix timestamp with microseconds when the event occurred
     * @param  array<string, mixed>  $context  Additional contextual data
     */
    public function __construct(
        RequestInterface $request,
        protected Throwable $previousException,
        protected int $attempt,
        protected int $maxAttempts,
        protected int $delay,
        string $correlationId,
        float $timestamp,
        array $context = []
    ) {
        parent::__construct($request, $correlationId, $timestamp, $context);
    }

    /**
     * Get the event name.
     */
    public function getName(): string
    {
        return 'request.retrying';
    }

    /**
     * Get the exception from the previous attempt.
     */
    public function getPreviousException(): Throwable
    {
        return $this->previousException;
    }

    /**
     * Get the current attempt number (1-based).
     */
    public function getAttempt(): int
    {
        return $this->attempt;
    }

    /**
     * Get the maximum number of attempts allowed.
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Get the delay in milliseconds before the retry.
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * Check if this is the last attempt.
     */
    public function isLastAttempt(): bool
    {
        return $this->attempt >= $this->maxAttempts;
    }
}
