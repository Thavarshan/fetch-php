<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Event dispatched when an error occurs during an HTTP request.
 */
class ErrorEvent extends FetchEvent
{
    /**
     * Create a new error event instance.
     *
     * @param  RequestInterface  $request  The HTTP request that caused the error
     * @param  Throwable  $exception  The exception that was thrown
     * @param  string  $correlationId  Unique identifier for correlating related events
     * @param  float  $timestamp  Unix timestamp with microseconds when the event occurred
     * @param  int  $attempt  The attempt number (1-based) when the error occurred
     * @param  ResponseInterface|null  $response  The HTTP response if one was received
     * @param  array<string, mixed>  $context  Additional contextual data
     */
    public function __construct(
        RequestInterface $request,
        protected Throwable $exception,
        string $correlationId,
        float $timestamp,
        protected int $attempt = 1,
        protected ?ResponseInterface $response = null,
        array $context = []
    ) {
        parent::__construct($request, $correlationId, $timestamp, $context);
    }

    /**
     * Get the event name.
     */
    public function getName(): string
    {
        return 'error.occurred';
    }

    /**
     * Get the exception that was thrown.
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }

    /**
     * Get the attempt number when the error occurred.
     */
    public function getAttempt(): int
    {
        return $this->attempt;
    }

    /**
     * Get the HTTP response if one was received.
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Check if this error is retryable.
     *
     * An error is considered retryable if it's a network error,
     * timeout, or returns a retryable HTTP status code.
     */
    public function isRetryable(): bool
    {
        // Check if response has a retryable status code
        if ($this->response !== null) {
            $statusCode = $this->response->getStatusCode();
            $retryableCodes = [408, 429, 500, 502, 503, 504, 507, 509, 520, 521, 522, 523, 525, 527, 530];

            return in_array($statusCode, $retryableCodes, true);
        }

        // Network errors are typically retryable
        return true;
    }
}
