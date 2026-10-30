<?php

declare(strict_types=1);

namespace Fetch\Events;

use Fetch\Interfaces\EventDispatcher as EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * A small, priority-aware event dispatcher for the request lifecycle.
 *
 * Listeners are grouped by event name and priority; higher priority runs
 * first, and listeners of equal priority run in registration order. A listener
 * that throws is isolated: the error is logged (when a logger is available) and
 * the remaining listeners still run, so observability code can never break a
 * request.
 */
final class EventDispatcher implements EventDispatcherInterface
{
    /**
     * Stores registered listeners grouped by event and priority.
     *
     * @var array<string, array<int, array<int, callable>>>
     */
    private array $listeners = [];

    /**
     * Create a dispatcher that optionally logs isolated listener failures.
     */
    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * Register a listener; higher priorities run before lower priorities.
     *
     * @param  callable(FetchEvent): void  $listener
     */
    public function addListener(
        string $eventName,
        callable $listener,
        int $priority = 0,
    ): void {
        $this->listeners[$eventName][$priority][] = $listener;

        // Keep priorities ordered high-to-low so dispatch can iterate directly.
        krsort($this->listeners[$eventName]);
    }

    /**
     * Remove listeners for one event, or clear the complete registry.
     */
    public function removeListeners(?string $eventName = null): void
    {
        if ($eventName === null) {
            $this->listeners = [];

            return;
        }

        unset($this->listeners[$eventName]);
    }

    /**
     * Dispatch an event and log listener failures without interrupting peers.
     */
    public function dispatch(FetchEvent $event): void
    {
        $eventName = $event->getName();

        if (! isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $priorityGroup) {
            foreach ($priorityGroup as $listener) {
                try {
                    $listener($event);
                } catch (Throwable $e) {
                    $this->logger?->error('Event listener threw an exception', [
                        'event' => $eventName,
                        'error' => $e->getMessage(),
                        'exception_class' => $e::class,
                    ]);
                }
            }
        }
    }

    /**
     * Check whether listeners are registered for an event name.
     */
    public function hasListeners(string $eventName): bool
    {
        return ! empty($this->listeners[$eventName]);
    }

    /**
     * Returns listeners for an event name in their dispatch order.
     *
     * @return array<int, callable>
     */
    public function getListeners(string $eventName): array
    {
        if (! isset($this->listeners[$eventName])) {
            return [];
        }

        $flattened = [];
        foreach ($this->listeners[$eventName] as $priorityGroup) {
            foreach ($priorityGroup as $listener) {
                $flattened[] = $listener;
            }
        }

        return $flattened;
    }
}
