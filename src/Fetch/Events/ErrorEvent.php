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
    public const NAME = 'error.occurred';

    /**
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

    public function getName(): string
    {
        return self::NAME;
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
