<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Interfaces\Response;
use Fetch\Support\DebugInfo;
use Fetch\Support\RequestContext;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * Request lifecycle operations supplied by the ClientHandler facade.
 *
 * @phpstan-type MockRequestHook \Closure(
 *     string,
 *     string,
 *     array<string, mixed>,
 * ): (Response|null)
 * @phpstan-type CaptureDebugSnapshotHook \Closure(
 *     string,
 *     string,
 *     array<string, mixed>,
 *     ResponseInterface|null,
 *     float,
 *     int,
 *     array<string, mixed>,
 * ): (DebugInfo|null)
 * @phpstan-type EventRequestHook \Closure(
 *     string,
 *     string,
 *     array<string, mixed>,
 * ): RequestInterface
 * @phpstan-type EmitRequestEventHook \Closure(
 *     RequestInterface,
 *     RequestContext,
 *     float,
 * ): void
 * @phpstan-type EmitResponseEventHook \Closure(
 *     RequestInterface,
 *     ResponseInterface,
 *     RequestContext,
 *     float,
 * ): void
 * @phpstan-type EmitErrorEventHook \Closure(
 *     RequestInterface,
 *     Throwable,
 *     RequestContext,
 *     float,
 * ): void
 * @phpstan-type AttachRedirectListenerHook \Closure(
 *     array<string, mixed>,
 *     RequestInterface,
 *     RequestContext,
 * ): array<string, mixed>
 * @phpstan-type NotifyRetryHook \Closure(
 *     int,
 *     int,
 *     Throwable,
 *     int,
 *     RequestContext|null,
 * ): void
 */
final readonly class RequestCoordinatorHooks
{
    /**
     * @param  MockRequestHook  $mockRequest
     * @param  \Closure(string, string): (string|null)  $startProfiling
     * @param  \Closure(string|null, string): void  $recordProfilingEvent
     * @param  \Closure(string|null, int|null): void  $endProfiling
     * @param  \Closure(): array<string, mixed>  $connectionStats
     * @param  CaptureDebugSnapshotHook  $captureDebugSnapshot
     * @param  \Closure(string, string, array<string, mixed>): void  $logRequest
     * @param  \Closure(Response, float): void  $logResponse
     * @param  EventRequestHook  $eventRequest
     * @param  EmitRequestEventHook  $emitRequestEvent
     * @param  EmitResponseEventHook  $emitResponseEvent
     * @param  EmitErrorEventHook  $emitErrorEvent
     * @param  \Closure(): bool  $hasRedirectListeners
     * @param  AttachRedirectListenerHook  $attachRedirectListener
     * @param  NotifyRetryHook  $notifyRetry
     * @param  \Closure(Throwable, string, string): Throwable  $withErrorContext
     * @param  \Closure(string, string, Throwable): void  $logAsyncFailure
     */
    public function __construct(
        public \Closure $mockRequest,
        public \Closure $startProfiling,
        public \Closure $recordProfilingEvent,
        public \Closure $endProfiling,
        public \Closure $connectionStats,
        public \Closure $captureDebugSnapshot,
        public \Closure $logRequest,
        public \Closure $logResponse,
        public \Closure $eventRequest,
        public \Closure $emitRequestEvent,
        public \Closure $emitResponseEvent,
        public \Closure $emitErrorEvent,
        public \Closure $hasRedirectListeners,
        public \Closure $attachRedirectListener,
        public \Closure $notifyRetry,
        public \Closure $withErrorContext,
        public \Closure $logAsyncFailure,
    ) {}
}
