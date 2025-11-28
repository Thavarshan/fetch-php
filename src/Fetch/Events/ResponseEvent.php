<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Event dispatched after an HTTP response is received.
 */
class ResponseEvent extends FetchEvent
{
    /**
     * Create a new response event instance.
     *
     * @param  RequestInterface  $request  The HTTP request that was sent
     * @param  ResponseInterface  $response  The HTTP response received
     * @param  string  $correlationId  Unique identifier for correlating related events
     * @param  float  $timestamp  Unix timestamp with microseconds when the event occurred
     * @param  float  $duration  Time in seconds the request took to complete
     * @param  array<string, mixed>  $context  Additional contextual data
     */
    public function __construct(
        RequestInterface $request,
        protected ResponseInterface $response,
        string $correlationId,
        float $timestamp,
        protected float $duration,
        array $context = []
    ) {
        parent::__construct($request, $correlationId, $timestamp, $context);
    }

    /**
     * Get the event name.
     */
    public function getName(): string
    {
        return 'response.received';
    }

    /**
     * Get the HTTP response.
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get the request duration in seconds.
     */
    public function getDuration(): float
    {
        return $this->duration;
    }

    /**
     * Get the request latency in milliseconds.
     */
    public function getLatency(): int
    {
        return (int) ($this->duration * 1000);
    }
}
