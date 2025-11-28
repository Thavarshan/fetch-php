<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Http\Message\RequestInterface;

/**
 * Event dispatched before an HTTP request is sent.
 */
class RequestEvent extends FetchEvent
{
    /**
     * Create a new request event instance.
     *
     * @param  RequestInterface  $request  The HTTP request being sent
     * @param  string  $correlationId  Unique identifier for correlating related events
     * @param  float  $timestamp  Unix timestamp with microseconds when the event occurred
     * @param  array<string, mixed>  $context  Additional contextual data
     * @param  array<string, mixed>  $options  Request options being used
     */
    public function __construct(
        RequestInterface $request,
        string $correlationId,
        float $timestamp,
        array $context = [],
        protected array $options = []
    ) {
        parent::__construct($request, $correlationId, $timestamp, $context);
    }

    /**
     * Get the event name.
     */
    public function getName(): string
    {
        return 'request.sending';
    }

    /**
     * Get the request options being used.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
