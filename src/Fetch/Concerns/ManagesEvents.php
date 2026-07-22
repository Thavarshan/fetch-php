<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Events\ErrorEvent;
use Fetch\Events\EventDispatcher;
use Fetch\Events\FetchEvent;
use Fetch\Events\RedirectEvent;
use Fetch\Events\RequestEvent;
use Fetch\Events\ResponseEvent;
use Fetch\Events\RetryEvent;
use Fetch\Events\TimeoutEvent;
use Fetch\Interfaces\EventDispatcher as EventDispatcherInterface;
use Fetch\Support\RequestContext;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Throwable;

/**
 * Adds a request/response lifecycle event system to a client handler.
 *
 * Exposes fluent `on*` registration helpers and drives an {@see EventDispatcher}
 * that fires {@see FetchEvent}s at each stage of a request. Dispatch is fully
 * lazy and guarded: when no listener is registered for an event, no event object
 * is even constructed, so the overhead is a single array check on the hot path.
 */
trait ManagesEvents
{
    protected ?EventDispatcherInterface $eventDispatcher = null;

    /**
     * Get (lazily creating) the event dispatcher.
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        if ($this->eventDispatcher === null) {
            $logger = isset($this->logger) ? $this->logger : null;
            $this->eventDispatcher = new EventDispatcher($logger);
        }

        return $this->eventDispatcher;
    }

    /**
     * Register a listener for a named lifecycle event.
     *
     * @return $this
     */
    public function on(string $eventName, callable $listener, int $priority = 0): static
    {
        $this->getEventDispatcher()->addListener($eventName, $listener, $priority);

        return $this;
    }

    /**
     * Register multiple listeners keyed by event name or hook alias.
     *
     * @param  array<string, callable>  $hooks
     * @return $this
     */
    public function hooks(array $hooks): static
    {
        foreach ($hooks as $name => $listener) {
            $this->on($this->normalizeHookName($name), $listener);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function onRequest(callable $listener, int $priority = 0): static
    {
        return $this->on(RequestEvent::NAME, $listener, $priority);
    }

    /**
     * @return $this
     */
    public function onResponse(callable $listener, int $priority = 0): static
    {
        return $this->on(ResponseEvent::NAME, $listener, $priority);
    }

    /**
     * @return $this
     */
    public function onError(callable $listener, int $priority = 0): static
    {
        return $this->on(ErrorEvent::NAME, $listener, $priority);
    }

    /**
     * @return $this
     */
    public function onRetry(callable $listener, int $priority = 0): static
    {
        return $this->on(RetryEvent::NAME, $listener, $priority);
    }

    /**
     * @return $this
     */
    public function onTimeout(callable $listener, int $priority = 0): static
    {
        return $this->on(TimeoutEvent::NAME, $listener, $priority);
    }

    /**
     * @return $this
     */
    public function onRedirect(callable $listener, int $priority = 0): static
    {
        return $this->on(RedirectEvent::NAME, $listener, $priority);
    }

    /**
     * Generate a correlation ID for a request.
     */
    public function generateCorrelationId(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Map a hook alias to its canonical event name.
     */
    protected function normalizeHookName(string $name): string
    {
        return match ($name) {
            'before_send', 'request', 'onRequest' => RequestEvent::NAME,
            'after_response', 'response', 'onResponse' => ResponseEvent::NAME,
            'on_error', 'error', 'onError' => ErrorEvent::NAME,
            'on_retry', 'retry', 'onRetry' => RetryEvent::NAME,
            'on_timeout', 'timeout', 'onTimeout' => TimeoutEvent::NAME,
            'on_redirect', 'redirect', 'onRedirect' => RedirectEvent::NAME,
            default => $name,
        };
    }

    /**
     * Whether any listener is registered for the given event.
     */
    protected function hasEventListeners(string $eventName): bool
    {
        return $this->eventDispatcher !== null && $this->eventDispatcher->hasListeners($eventName);
    }

    /**
     * Dispatch an event if a dispatcher exists.
     */
    protected function dispatchEvent(FetchEvent $event): void
    {
        $this->eventDispatcher?->dispatch($event);
    }

    /**
     * Resolve the correlation ID carried on the request context.
     */
    protected function eventCorrelationId(RequestContext $context): string
    {
        $id = $context->getOption('__correlation_id');

        return is_string($id) ? $id : '';
    }

    /**
     * Build a representative PSR-7 request for an event.
     *
     * @param  array<string, string|string[]>  $headers
     */
    protected function eventRequest(string $method, string $uri, array $headers = []): RequestInterface
    {
        return new GuzzleRequest($method, $uri, $headers);
    }

    /**
     * Dispatch the `request.sending` event.
     */
    protected function emitRequestEvent(RequestInterface $request, RequestContext $context, float $timestamp): void
    {
        if (! $this->hasEventListeners(RequestEvent::NAME)) {
            return;
        }

        $this->dispatchEvent(new RequestEvent(
            $request,
            $this->eventCorrelationId($context),
            $timestamp,
            [],
            $context->toArray(),
        ));
    }

    /**
     * Dispatch the `response.received` event.
     */
    protected function emitResponseEvent(RequestInterface $request, PsrResponseInterface $response, RequestContext $context, float $startTime): void
    {
        if (! $this->hasEventListeners(ResponseEvent::NAME)) {
            return;
        }

        $now = microtime(true);

        $this->dispatchEvent(new ResponseEvent(
            $request,
            $response,
            $this->eventCorrelationId($context),
            $now,
            $now - $startTime,
        ));
    }

    /**
     * Dispatch the `error.occurred` event, plus `request.timeout` when the
     * failure is a timeout.
     */
    protected function emitErrorEvent(RequestInterface $request, Throwable $exception, RequestContext $context, float $startTime, ?PsrResponseInterface $response = null, int $attempt = 1): void
    {
        $now = microtime(true);

        if ($this->hasEventListeners(ErrorEvent::NAME)) {
            $this->dispatchEvent(new ErrorEvent(
                $request,
                $exception,
                $this->eventCorrelationId($context),
                $now,
                $attempt,
                $response,
            ));
        }

        if ($this->isTimeoutException($exception) && $this->hasEventListeners(TimeoutEvent::NAME)) {
            $this->dispatchEvent(new TimeoutEvent(
                $request,
                $context->getTimeout(),
                $now - $startTime,
                $this->eventCorrelationId($context),
                $now,
            ));
        }
    }

    /**
     * Dispatch the `request.retrying` event.
     */
    protected function emitRetryEvent(RequestContext $context, Throwable $previous, int $attempt, int $maxAttempts, int $delayMs): void
    {
        if (! $this->hasEventListeners(RetryEvent::NAME)) {
            return;
        }

        $request = $this->eventRequest($context->getMethod(), $context->getUri(), $context->getHeaders());

        $this->dispatchEvent(new RetryEvent(
            $request,
            $previous,
            $attempt,
            $maxAttempts,
            $delayMs,
            $this->eventCorrelationId($context),
            microtime(true),
        ));
    }

    /**
     * Dispatch the `request.redirecting` event.
     */
    protected function emitRedirectEvent(RequestInterface $request, PsrResponseInterface $response, string $location, int $redirectCount, RequestContext $context): void
    {
        if (! $this->hasEventListeners(RedirectEvent::NAME)) {
            return;
        }

        $this->dispatchEvent(new RedirectEvent(
            $request,
            $response,
            $location,
            $redirectCount,
            $this->eventCorrelationId($context),
            microtime(true),
        ));
    }

    /**
     * Inject an `on_redirect` hook into Guzzle's allow_redirects config so
     * redirects fire {@see RedirectEvent}s. Existing redirect settings are
     * preserved; defaults fill in any missing keys.
     *
     * @param  array<string, mixed>  $guzzleOptions
     * @return array<string, mixed>
     */
    protected function attachRedirectListener(array $guzzleOptions, RequestInterface $request, RequestContext $context): array
    {
        $redirects = $guzzleOptions['allow_redirects'] ?? true;

        // Redirects explicitly disabled: nothing to observe.
        if ($redirects === false) {
            return $guzzleOptions;
        }

        $config = (is_array($redirects) ? $redirects : []) + [
            'max' => 5,
            'protocols' => ['http', 'https'],
            'strict' => false,
            'referer' => false,
            'track_redirects' => false,
        ];

        $count = 0;
        $config['on_redirect'] = function ($req, $response, $uri) use (&$count, $request, $context): void {
            $count++;
            $this->emitRedirectEvent($request, $response, (string) $uri, $count, $context);
        };

        $guzzleOptions['allow_redirects'] = $config;

        return $guzzleOptions;
    }

    /**
     * Determine whether an exception (or any of its causes) is a timeout.
     */
    protected function isTimeoutException(Throwable $exception): bool
    {
        $current = $exception;

        do {
            if ($current instanceof ConnectException) {
                return true;
            }

            $message = strtolower($current->getMessage());
            if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
                return true;
            }

            $current = $current->getPrevious();
        } while ($current !== null);

        return false;
    }
}
