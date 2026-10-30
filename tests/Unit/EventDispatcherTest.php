<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Events\EventDispatcher;
use Fetch\Events\RequestEvent;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

/**
 * Checks listener registration, priority ordering, and failure isolation.
 */
class EventDispatcherTest extends TestCase
{
    /**
     * Creates a request event with stable data for listener tests.
     */
    private function event(): RequestEvent
    {
        return new RequestEvent(
            new Request('GET', 'https://example.com'),
            'corr-1',
            123.0,
        );
    }

    public function test_dispatches_to_registered_listener(): void
    {
        $dispatcher = new EventDispatcher;
        $received = null;

        $dispatcher->addListener(RequestEvent::NAME, function (
            RequestEvent $e,
        ) use (&$received) {
            $received = $e->getCorrelationId();
        });

        $dispatcher->dispatch($this->event());

        $this->assertSame('corr-1', $received);
    }

    public function test_no_listeners_is_a_noop(): void
    {
        $dispatcher = new EventDispatcher;

        $dispatcher->dispatch($this->event());

        $this->assertFalse($dispatcher->hasListeners(RequestEvent::NAME));
    }

    public function test_priority_orders_high_to_low(): void
    {
        $dispatcher = new EventDispatcher;
        $order = [];

        $dispatcher->addListener(
            RequestEvent::NAME,
            function () use (&$order) {
                $order[] = 'low';
            },
            1,
        );
        $dispatcher->addListener(
            RequestEvent::NAME,
            function () use (&$order) {
                $order[] = 'high';
            },
            10,
        );

        $dispatcher->dispatch($this->event());

        $this->assertSame(['high', 'low'], $order);
    }

    public function test_equal_priority_runs_in_registration_order(): void
    {
        $dispatcher = new EventDispatcher;
        $order = [];

        $dispatcher->addListener(RequestEvent::NAME, function () use (&$order) {
            $order[] = 'first';
        });
        $dispatcher->addListener(RequestEvent::NAME, function () use (&$order) {
            $order[] = 'second';
        });

        $dispatcher->dispatch($this->event());

        $this->assertSame(['first', 'second'], $order);
    }

    public function test_listener_exception_is_isolated_and_logged(): void
    {
        $logs = [];
        $logger = new
        /**
         * Collects event-dispatcher log messages in a shared test buffer.
         */
        class($logs) extends AbstractLogger {
            /**
             * Stores the log buffer shared with the anonymous test logger.
             */
            public function __construct(private array &$logs) {}

            /**
             * Appends each logger message to the shared test buffer.
             */
            public function log($level, $message, array $context = []): void
            {
                $this->logs[] = (string) $message;
            }
        };

        $dispatcher = new EventDispatcher($logger);
        $secondRan = false;

        $dispatcher->addListener(RequestEvent::NAME, function () {
            throw new \RuntimeException('boom');
        });
        $dispatcher->addListener(RequestEvent::NAME, function () use (
            &$secondRan,
        ) {
            $secondRan = true;
        });

        $dispatcher->dispatch($this->event());

        $this->assertTrue(
            $secondRan,
            'A throwing listener must not stop the rest.',
        );
        $this->assertNotEmpty($logs);
    }

    public function test_remove_listeners_for_event(): void
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addListener(RequestEvent::NAME, fn () => null);

        $dispatcher->removeListeners(RequestEvent::NAME);

        $this->assertFalse($dispatcher->hasListeners(RequestEvent::NAME));
    }

    public function test_remove_all_listeners(): void
    {
        $dispatcher = new EventDispatcher;
        $dispatcher->addListener(RequestEvent::NAME, fn () => null);
        $dispatcher->addListener('response.received', fn () => null);

        $dispatcher->removeListeners();

        $this->assertFalse($dispatcher->hasListeners(RequestEvent::NAME));
        $this->assertFalse($dispatcher->hasListeners('response.received'));
    }

    public function test_get_listeners_flattened_in_order(): void
    {
        $dispatcher = new EventDispatcher;
        $a = fn () => null;
        $b = fn () => null;
        $dispatcher->addListener(RequestEvent::NAME, $a, 1);
        $dispatcher->addListener(RequestEvent::NAME, $b, 10);

        $this->assertSame(
            [$b, $a],
            $dispatcher->getListeners(RequestEvent::NAME),
        );
    }
}
