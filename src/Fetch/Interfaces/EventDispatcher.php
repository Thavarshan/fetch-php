<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Events\FetchEvent;

interface EventDispatcher
{
    /**
     * Register a listener for an event name. Higher priority runs first.
     *
     * @param  callable(FetchEvent): void  $listener
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void;

    /**
     * Remove all listeners for an event, or every listener when null.
     */
    public function removeListeners(?string $eventName = null): void;

    /**
     * Dispatch an event to its registered listeners in priority order.
     */
    public function dispatch(FetchEvent $event): void;

    /**
     * Whether any listener is registered for the given event name.
     */
    public function hasListeners(string $eventName): bool;

    /**
     * Get the listeners registered for an event, in execution order.
     *
     * @return array<int, callable>
     */
    public function getListeners(string $eventName): array;
}
