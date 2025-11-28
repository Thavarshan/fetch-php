<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Http\Message\RequestInterface;

/**
 * Base class for all Fetch HTTP events.
 *
 * Provides common functionality and properties for HTTP lifecycle events.
 */
abstract class FetchEvent
{
    /**
     * Create a new event instance.
     *
     * @param  RequestInterface  $request  The HTTP request associated with this event
     * @param  string  $correlationId  Unique identifier for correlating related events
     * @param  float  $timestamp  Unix timestamp with microseconds when the event occurred
     * @param  array<string, mixed>  $context  Additional contextual data
     */
    public function __construct(
        protected RequestInterface $request,
        protected string $correlationId,
        protected float $timestamp,
        protected array $context = []
    ) {}

    /**
     * Get the HTTP request associated with this event.
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Get the correlation ID for this event.
     *
     * The correlation ID is used to relate multiple events that belong
     * to the same HTTP request lifecycle.
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Get the timestamp when this event occurred.
     *
     * @return float Unix timestamp with microseconds
     */
    public function getTimestamp(): float
    {
        return $this->timestamp;
    }

    /**
     * Get the additional context data for this event.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get the name of this event.
     *
     * Event names follow the format: category.action
     * Examples: request.sending, response.received, error.occurred
     */
    abstract public function getName(): string;
}
