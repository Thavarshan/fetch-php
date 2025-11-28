<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Events\EventDispatcher;
use Fetch\Events\EventDispatcherInterface;
use Fetch\Events\FetchEvent;

/**
 * Trait for adding hook/event support to HTTP clients.
 */
trait SupportsHooks
{
    /**
     * The event dispatcher for handling HTTP lifecycle events.
     */
    protected ?EventDispatcherInterface $eventDispatcher = null;

    /**
     * Hook name mappings from shorthand to full event names.
     *
     * @var array<string, string>
     */
    protected static array $hookNameMappings = [
        'before_send' => 'request.sending',
        'after_response' => 'response.received',
        'on_error' => 'error.occurred',
        'on_retry' => 'request.retrying',
        'on_timeout' => 'request.timeout',
        'on_redirect' => 'request.redirecting',
    ];

    /**
     * Register a callback for when a request is about to be sent.
     *
     * @param  callable  $callback  The callback to invoke
     * @param  int  $priority  Higher priority callbacks are called first
     * @return $this
     */
    public function onRequest(callable $callback, int $priority = 0): static
    {
        $this->getEventDispatcher()->addListener('request.sending', $callback, $priority);

        return $this;
    }

    /**
     * Register a callback for when a response is received.
     *
     * @param  callable  $callback  The callback to invoke
     * @param  int  $priority  Higher priority callbacks are called first
     * @return $this
     */
    public function onResponse(callable $callback, int $priority = 0): static
    {
        $this->getEventDispatcher()->addListener('response.received', $callback, $priority);

        return $this;
    }

    /**
     * Register a callback for when an error occurs.
     *
     * @param  callable  $callback  The callback to invoke
     * @param  int  $priority  Higher priority callbacks are called first
     * @return $this
     */
    public function onError(callable $callback, int $priority = 0): static
    {
        $this->getEventDispatcher()->addListener('error.occurred', $callback, $priority);

        return $this;
    }

    /**
     * Register a callback for when a request is being retried.
     *
     * @param  callable  $callback  The callback to invoke
     * @param  int  $priority  Higher priority callbacks are called first
     * @return $this
     */
    public function onRetry(callable $callback, int $priority = 0): static
    {
        $this->getEventDispatcher()->addListener('request.retrying', $callback, $priority);

        return $this;
    }

    /**
     * Register a callback for when a request times out.
     *
     * @param  callable  $callback  The callback to invoke
     * @param  int  $priority  Higher priority callbacks are called first
     * @return $this
     */
    public function onTimeout(callable $callback, int $priority = 0): static
    {
        $this->getEventDispatcher()->addListener('request.timeout', $callback, $priority);

        return $this;
    }

    /**
     * Register a callback for when a request is being redirected.
     *
     * @param  callable  $callback  The callback to invoke
     * @param  int  $priority  Higher priority callbacks are called first
     * @return $this
     */
    public function onRedirect(callable $callback, int $priority = 0): static
    {
        $this->getEventDispatcher()->addListener('request.redirecting', $callback, $priority);

        return $this;
    }

    /**
     * Register a callback for a specific event.
     *
     * @param  string  $eventName  The event name to listen for
     * @param  callable  $callback  The callback to invoke
     * @param  int  $priority  Higher priority callbacks are called first
     * @return $this
     */
    public function when(string $eventName, callable $callback, int $priority = 0): static
    {
        $this->getEventDispatcher()->addListener($eventName, $callback, $priority);

        return $this;
    }

    /**
     * Register multiple hooks at once.
     *
     * @param  array<string, callable>  $hooks  Array of hook name => callback pairs
     * @return $this
     */
    public function hooks(array $hooks): static
    {
        foreach ($hooks as $hook => $callback) {
            $eventName = $this->normalizeHookName($hook);
            $this->getEventDispatcher()->addListener($eventName, $callback);
        }

        return $this;
    }

    /**
     * Get the event dispatcher instance.
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = new EventDispatcher($this->logger ?? null);
        }

        return $this->eventDispatcher;
    }

    /**
     * Set a custom event dispatcher.
     *
     * @param  EventDispatcherInterface  $dispatcher  The event dispatcher to use
     * @return $this
     */
    public function setEventDispatcher(EventDispatcherInterface $dispatcher): static
    {
        $this->eventDispatcher = $dispatcher;

        return $this;
    }

    /**
     * Check if event hooks are registered.
     *
     * @param  string|null  $eventName  The event name, or null to check any
     */
    public function hasHooks(?string $eventName = null): bool
    {
        if ($this->eventDispatcher === null) {
            return false;
        }

        if ($eventName === null) {
            // Check if any listeners are registered
            $events = [
                'request.sending',
                'response.received',
                'error.occurred',
                'request.retrying',
                'request.timeout',
                'request.redirecting',
            ];

            foreach ($events as $event) {
                if ($this->eventDispatcher->hasListeners($event)) {
                    return true;
                }
            }

            return false;
        }

        return $this->eventDispatcher->hasListeners($eventName);
    }

    /**
     * Clear all event hooks.
     *
     * @param  string|null  $eventName  The event name, or null to clear all
     * @return $this
     */
    public function clearHooks(?string $eventName = null): static
    {
        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->clearListeners($eventName);
        }

        return $this;
    }

    /**
     * Dispatch an event to all registered listeners.
     *
     * @param  FetchEvent  $event  The event to dispatch
     */
    protected function dispatchEvent(FetchEvent $event): void
    {
        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatch($event);
        }
    }

    /**
     * Normalize a hook name to the full event name.
     *
     * @param  string  $hookName  The shorthand or full hook name
     * @return string The normalized event name
     */
    protected function normalizeHookName(string $hookName): string
    {
        return self::$hookNameMappings[$hookName] ?? $hookName;
    }

    /**
     * Generate a correlation ID for tracking related events.
     */
    protected function generateCorrelationId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
