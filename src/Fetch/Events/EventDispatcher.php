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
     * @var array<string, array<int, array<int, callable>>>
     */
    private array $listeners = [];

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][$priority][] = $listener;

        // Keep priorities ordered high-to-low so dispatch can iterate directly.
        krsort($this->listeners[$eventName]);
    }

    public function removeListeners(?string $eventName = null): void
    {
        if ($eventName === null) {
            $this->listeners = [];

            return;
        }

        unset($this->listeners[$eventName]);
    }

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

    public function hasListeners(string $eventName): bool
    {
        return ! empty($this->listeners[$eventName]);
    }

    /**
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
