<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Event dispatched when an HTTP request is being redirected.
 */
class RedirectEvent extends FetchEvent
{
    /**
     * Create a new redirect event instance.
     *
     * @param  RequestInterface  $request  The original HTTP request
     * @param  ResponseInterface  $response  The redirect response
     * @param  string  $location  The target location for the redirect
     * @param  int  $redirectCount  The current redirect count (1-based)
     * @param  string  $correlationId  Unique identifier for correlating related events
     * @param  float  $timestamp  Unix timestamp with microseconds when the event occurred
     * @param  array<string, mixed>  $context  Additional contextual data
     */
    public function __construct(
        RequestInterface $request,
        protected ResponseInterface $response,
        protected string $location,
        protected int $redirectCount,
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
        return 'request.redirecting';
    }

    /**
     * Get the redirect response.
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get the target location for the redirect.
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * Get the current redirect count (1-based).
     */
    public function getRedirectCount(): int
    {
        return $this->redirectCount;
    }
}
