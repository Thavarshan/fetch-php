<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Http\Message\RequestInterface;

/**
 * Event dispatched when an HTTP request times out.
 */
class TimeoutEvent extends FetchEvent
{
    /**
     * Create a new timeout event instance.
     *
     * @param  RequestInterface  $request  The HTTP request that timed out
     * @param  int  $timeout  The configured timeout in seconds
     * @param  float  $elapsed  The actual elapsed time in seconds
     * @param  string  $correlationId  Unique identifier for correlating related events
     * @param  float  $timestamp  Unix timestamp with microseconds when the event occurred
     * @param  array<string, mixed>  $context  Additional contextual data
     */
    public function __construct(
        RequestInterface $request,
        protected int $timeout,
        protected float $elapsed,
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
        return 'request.timeout';
    }

    /**
     * Get the configured timeout in seconds.
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Get the actual elapsed time in seconds.
     */
    public function getElapsed(): float
    {
        return $this->elapsed;
    }
}
