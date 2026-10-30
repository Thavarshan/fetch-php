<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Http\Message\RequestInterface;

/**
 * Base class for all request/response lifecycle events.
 *
 * Every event carries the request it relates to, a correlation ID that is
 * stable across all events for a single logical request, the time it was
 * created, and an optional bag of extra context.
 */
abstract class FetchEvent
{
    /**
     * Creates a fetch event with its name and immutable context data.
     *
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        protected readonly RequestInterface $request,
        protected readonly string $correlationId,
        protected readonly float $timestamp,
        protected readonly array $context = [],
    ) {}

    /**
     * The dot-delimited event name (e.g. "request.sending").
     */
    abstract public function getName(): string;

    /**
     * Return the PSR-7 request associated with this lifecycle event.
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Return the identifier shared by events from one logical request.
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Return the event creation time as a Unix timestamp.
     */
    public function getTimestamp(): float
    {
        return $this->timestamp;
    }

    /**
     * Returns the context captured when this event was created.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
