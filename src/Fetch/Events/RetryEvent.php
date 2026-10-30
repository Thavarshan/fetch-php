<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * Dispatched before a retry attempt is made.
 */
final class RetryEvent extends FetchEvent
{
    /**
     * Stable identifier used to register retry listeners.
     */
    public const NAME = 'request.retrying';

    /**
     * Creates a retry event with the failed request and retry details.
     *
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        RequestInterface $request,
        protected readonly Throwable $previousException,
        protected readonly int $attempt,
        protected readonly int $maxAttempts,
        protected readonly int $delay,
        string $correlationId,
        float $timestamp,
        array $context = [],
    ) {
        parent::__construct($request, $correlationId, $timestamp, $context);
    }

    /**
     * Return the listener key for this event type.
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * Return the failure that triggered this retry.
     */
    public function getPreviousException(): Throwable
    {
        return $this->previousException;
    }

    /**
     * Return the one-based current attempt number.
     */
    public function getAttempt(): int
    {
        return $this->attempt;
    }

    /**
     * Return the configured maximum number of attempts.
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Delay before the next attempt, in milliseconds.
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * Check whether the current attempt reaches the configured limit.
     */
    public function isLastAttempt(): bool
    {
        return $this->attempt >= $this->maxAttempts;
    }
}
