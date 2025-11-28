<?php

declare(strict_types=1);

namespace Fetch\Events;

/**
 * Interface for event dispatchers that handle HTTP lifecycle events.
 */
interface EventDispatcherInterface
{
    /**
     * Add a listener for a specific event.
     *
     * @param  string  $eventName  The name of the event to listen for
     * @param  callable  $listener  The callback to invoke when the event is dispatched
     * @param  int  $priority  Higher priority listeners are called first (default: 0)
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void;

    /**
     * Remove a listener for a specific event.
     *
     * @param  string  $eventName  The name of the event
     * @param  callable  $listener  The callback to remove
     */
    public function removeListener(string $eventName, callable $listener): void;

    /**
     * Dispatch an event to all registered listeners.
     *
     * @param  FetchEvent  $event  The event to dispatch
     */
    public function dispatch(FetchEvent $event): void;

    /**
     * Check if there are any listeners registered for an event.
     *
     * @param  string  $eventName  The name of the event
     * @return bool True if there are listeners registered
     */
    public function hasListeners(string $eventName): bool;

    /**
     * Get all listeners for a specific event.
     *
     * @param  string  $eventName  The name of the event
     * @return array<callable> The registered listeners, sorted by priority
     */
    public function getListeners(string $eventName): array;

    /**
     * Remove all listeners for a specific event or all events.
     *
     * @param  string|null  $eventName  The event name, or null to clear all listeners
     */
    public function clearListeners(?string $eventName = null): void;
}
