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

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getTimestamp(): float
    {
        return $this->timestamp;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
