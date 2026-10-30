<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Events\ErrorEvent;
use Fetch\Events\FetchEvent;
use Fetch\Events\RedirectEvent;
use Fetch\Events\RequestEvent;
use Fetch\Events\ResponseEvent;
use Fetch\Events\RetryEvent;
use Fetch\Events\TimeoutEvent;

/**
 * Registers listeners for request lifecycle events emitted by a handler.
 */
interface EventAware
{
    /**
     * Register a listener for a named lifecycle event.
     *
     * @param  callable  $listener  Receives a {@see FetchEvent} subclass.
     */
    public function on(
        string $eventName,
        callable $listener,
        int $priority = 0,
    ): self;

    /**
     * Register multiple listeners keyed by event name or hook alias.
     *
     * Recognised aliases: before_send, after_response, on_error, on_retry,
     * on_timeout, on_redirect.
     *
     * @param  array<string, callable>  $hooks
     */
    public function hooks(array $hooks): self;

    /**
     * Registers a listener for requests dispatched by this client.
     *
     * @param  callable(RequestEvent): void  $listener
     */
    public function onRequest(callable $listener, int $priority = 0): self;

    /**
     * Registers a listener for responses received by this client.
     *
     * @param  callable(ResponseEvent): void  $listener
     */
    public function onResponse(callable $listener, int $priority = 0): self;

    /**
     * Registers a listener for request failures reported by this client.
     *
     * @param  callable(ErrorEvent): void  $listener
     */
    public function onError(callable $listener, int $priority = 0): self;

    /**
     * Registers a listener for retries initiated by this client.
     *
     * @param  callable(RetryEvent): void  $listener
     */
    public function onRetry(callable $listener, int $priority = 0): self;

    /**
     * Registers a listener for request timeouts reported by this client.
     *
     * @param  callable(TimeoutEvent): void  $listener
     */
    public function onTimeout(callable $listener, int $priority = 0): self;

    /**
     * Registers a listener for redirects followed by this client.
     *
     * @param  callable(RedirectEvent): void  $listener
     */
    public function onRedirect(callable $listener, int $priority = 0): self;

    /**
     * Get the underlying event dispatcher.
     */
    public function getEventDispatcher(): EventDispatcher;
}
