<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Default implementation of the event dispatcher for HTTP lifecycle events.
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * Registered listeners grouped by event name and priority.
     *
     * Structure: [eventName => [priority => [listeners]]]
     *
     * @var array<string, array<int, array<callable>>>
     */
    private array $listeners = [];

    /**
     * Cached sorted listeners by event name.
     *
     * @var array<string, array<callable>>
     */
    private array $sorted = [];

    /**
     * Logger for recording listener errors.
     */
    private LoggerInterface $logger;

    /**
     * Create a new event dispatcher instance.
     *
     * @param  LoggerInterface|null  $logger  Optional logger for error reporting
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger;
    }

    /**
     * Add a listener for a specific event.
     *
     * @param  string  $eventName  The name of the event to listen for
     * @param  callable  $listener  The callback to invoke when the event is dispatched
     * @param  int  $priority  Higher priority listeners are called first (default: 0)
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][$priority][] = $listener;

        // Clear the sorted cache for this event
        unset($this->sorted[$eventName]);
    }

    /**
     * Remove a listener for a specific event.
     *
     * @param  string  $eventName  The name of the event
     * @param  callable  $listener  The callback to remove
     */
    public function removeListener(string $eventName, callable $listener): void
    {
        if (! isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            foreach ($listeners as $key => $registeredListener) {
                if ($registeredListener === $listener) {
                    unset($this->listeners[$eventName][$priority][$key]);

                    // Clean up empty priority arrays
                    if (empty($this->listeners[$eventName][$priority])) {
                        unset($this->listeners[$eventName][$priority]);
                    }

                    // Clean up empty event arrays
                    if (empty($this->listeners[$eventName])) {
                        unset($this->listeners[$eventName]);
                    }

                    // Clear the sorted cache for this event
                    unset($this->sorted[$eventName]);

                    return;
                }
            }
        }
    }

    /**
     * Dispatch an event to all registered listeners.
     *
     * @param  FetchEvent  $event  The event to dispatch
     */
    public function dispatch(FetchEvent $event): void
    {
        $eventName = $event->getName();
        $listeners = $this->getListeners($eventName);

        foreach ($listeners as $listener) {
            try {
                $listener($event);
            } catch (Throwable $e) {
                // Log listener errors but don't stop event propagation
                $this->logger->error('Event listener error', [
                    'event' => $eventName,
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->getCorrelationId(),
                ]);
            }
        }
    }

    /**
     * Check if there are any listeners registered for an event.
     *
     * @param  string  $eventName  The name of the event
     * @return bool True if there are listeners registered
     */
    public function hasListeners(string $eventName): bool
    {
        return ! empty($this->listeners[$eventName]);
    }

    /**
     * Get all listeners for a specific event, sorted by priority.
     *
     * @param  string  $eventName  The name of the event
     * @return array<callable> The registered listeners, sorted by priority (highest first)
     */
    public function getListeners(string $eventName): array
    {
        if (! isset($this->listeners[$eventName])) {
            return [];
        }

        // Return cached sorted listeners if available
        if (isset($this->sorted[$eventName])) {
            return $this->sorted[$eventName];
        }

        // Sort by priority (highest first)
        $prioritized = $this->listeners[$eventName];
        krsort($prioritized);

        // Flatten the array
        $sorted = [];
        foreach ($prioritized as $listeners) {
            foreach ($listeners as $listener) {
                $sorted[] = $listener;
            }
        }

        // Cache and return
        $this->sorted[$eventName] = $sorted;

        return $sorted;
    }

    /**
     * Remove all listeners for a specific event or all events.
     *
     * @param  string|null  $eventName  The event name, or null to clear all listeners
     */
    public function clearListeners(?string $eventName = null): void
    {
        if ($eventName === null) {
            $this->listeners = [];
            $this->sorted = [];
        } else {
            unset($this->listeners[$eventName], $this->sorted[$eventName]);
        }
    }
}
