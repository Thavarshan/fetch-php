<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Dispatched when a request ultimately fails with an exception.
 */
final class ErrorEvent extends FetchEvent
{
    /**
     * Stable identifier used to register error listeners.
     */
    public const NAME = 'error.occurred';

    /**
     * Creates an error event with its exception and request context.
     *
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        RequestInterface $request,
        protected readonly Throwable $exception,
        string $correlationId,
        float $timestamp,
        protected readonly int $attempt = 1,
        protected readonly ?ResponseInterface $response = null,
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
     * Return the exception that ended the request.
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }

    /**
     * Return the request attempt on which the failure occurred.
     */
    public function getAttempt(): int
    {
        return $this->attempt;
    }

    /**
     * Return an associated response, when the failure produced one.
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
