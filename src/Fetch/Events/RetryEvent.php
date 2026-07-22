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
    public const NAME = 'request.retrying';

    /**
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

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPreviousException(): Throwable
    {
        return $this->previousException;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }

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

    public function isLastAttempt(): bool
    {
        return $this->attempt >= $this->maxAttempts;
    }
}
