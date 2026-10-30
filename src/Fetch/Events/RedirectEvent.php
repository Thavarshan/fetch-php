<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Dispatched when the transport follows an HTTP redirect.
 */
final class RedirectEvent extends FetchEvent
{
    /**
     * Stable identifier used to register redirect listeners.
     */
    public const NAME = 'request.redirecting';

    /**
     * Creates a redirect event with its request and redirect context.
     *
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        RequestInterface $request,
        protected readonly ResponseInterface $response,
        protected readonly string $location,
        protected readonly int $redirectCount,
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
     * Return the response that caused the redirect.
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Return the redirect location supplied by the response.
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * Return the number of redirects followed for the request.
     */
    public function getRedirectCount(): int
    {
        return $this->redirectCount;
    }
}
