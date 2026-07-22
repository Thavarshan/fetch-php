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
    public const NAME = 'request.redirecting';

    /**
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

    public function getName(): string
    {
        return self::NAME;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getRedirectCount(): int
    {
        return $this->redirectCount;
    }
}
