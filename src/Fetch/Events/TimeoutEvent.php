<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Http\Message\RequestInterface;

/**
 * Dispatched when a request fails due to a timeout.
 *
 * Fired alongside {@see ErrorEvent} so listeners interested specifically in
 * timeouts can react without inspecting exception types.
 */
final class TimeoutEvent extends FetchEvent
{
    public const NAME = 'request.timeout';

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        RequestInterface $request,
        protected readonly int $timeout,
        protected readonly float $elapsed,
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

    /**
     * The configured timeout in seconds.
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * The elapsed time before the timeout was detected, in seconds.
     */
    public function getElapsed(): float
    {
        return $this->elapsed;
    }
}
