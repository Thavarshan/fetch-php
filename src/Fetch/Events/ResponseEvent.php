<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Dispatched after a response is received (including cache and mock hits).
 */
final class ResponseEvent extends FetchEvent
{
    public const NAME = 'response.received';

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        RequestInterface $request,
        protected readonly ResponseInterface $response,
        string $correlationId,
        float $timestamp,
        protected readonly float $duration,
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

    /**
     * Duration of the request in seconds.
     */
    public function getDuration(): float
    {
        return $this->duration;
    }

    /**
     * Duration of the request in whole milliseconds.
     */
    public function getLatency(): int
    {
        return (int) round($this->duration * 1000);
    }
}
