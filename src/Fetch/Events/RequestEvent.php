<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Http\Message\RequestInterface;

/**
 * Dispatched just before a request is sent (after middleware, before the
 * built-in mock/cache/transport layers).
 */
final class RequestEvent extends FetchEvent
{
    /**
     * Stable identifier used to register request listeners.
     */
    public const NAME = 'request.sending';

    /**
     * Creates a request event with the request and dispatch context.
     *
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        RequestInterface $request,
        string $correlationId,
        float $timestamp,
        array $context = [],
        protected readonly array $options = [],
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
     * Returns the request options captured for this dispatch event.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
