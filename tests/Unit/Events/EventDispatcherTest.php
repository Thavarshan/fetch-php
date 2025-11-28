<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use Fetch\Events\EventDispatcher;
use Fetch\Events\FetchEvent;
use Fetch\Events\RequestEvent;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EventDispatcherTest extends TestCase
{
    protected EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new EventDispatcher;
    }

    public function test_add_listener()
    {
        $called = false;
        $callback = function () use (&$called) {
            $called = true;
        };

        $this->dispatcher->addListener('request.sending', $callback);

        $this->assertTrue($this->dispatcher->hasListeners('request.sending'));
    }

    public function test_dispatch_calls_listeners()
    {
        $called = false;
        $receivedEvent = null;

        $this->dispatcher->addListener('request.sending', function (FetchEvent $event) use (&$called, &$receivedEvent) {
            $called = true;
            $receivedEvent = $event;
        });

        $request = new Request('GET', 'https://example.com/api');
        $event = new RequestEvent($request, 'corr-123', microtime(true));

        $this->dispatcher->dispatch($event);

        $this->assertTrue($called);
        $this->assertSame($event, $receivedEvent);
    }

    public function test_multiple_listeners_are_called()
    {
        $order = [];

        $this->dispatcher->addListener('response.received', function () use (&$order) {
            $order[] = 'first';
        });

        $this->dispatcher->addListener('response.received', function () use (&$order) {
            $order[] = 'second';
        });

        $request = new Request('GET', 'https://example.com/api');
        $event = new RequestEvent($request, 'corr-123', microtime(true));
        // Set the event name by using a real RequestEvent (which has 'request.sending')
        // We need to test with an event that matches the listener
        $this->dispatcher->dispatch($event);

        // Nothing was called because we registered for 'response.received' but dispatched 'request.sending'
        $this->assertEmpty($order);

        // Now let's test properly
        $this->dispatcher->clearListeners();

        $this->dispatcher->addListener('request.sending', function () use (&$order) {
            $order[] = 'first';
        });

        $this->dispatcher->addListener('request.sending', function () use (&$order) {
            $order[] = 'second';
        });

        $this->dispatcher->dispatch($event);

        $this->assertEquals(['first', 'second'], $order);
    }

    public function test_priority_ordering()
    {
        $order = [];

        $this->dispatcher->addListener('request.sending', function () use (&$order) {
            $order[] = 'low';
        }, 1);

        $this->dispatcher->addListener('request.sending', function () use (&$order) {
            $order[] = 'high';
        }, 10);

        $this->dispatcher->addListener('request.sending', function () use (&$order) {
            $order[] = 'medium';
        }, 5);

        $request = new Request('GET', 'https://example.com/api');
        $event = new RequestEvent($request, 'corr-123', microtime(true));

        $this->dispatcher->dispatch($event);

        $this->assertEquals(['high', 'medium', 'low'], $order);
    }

    public function test_remove_listener()
    {
        $callback = function () {};

        $this->dispatcher->addListener('request.sending', $callback);
        $this->assertTrue($this->dispatcher->hasListeners('request.sending'));

        $this->dispatcher->removeListener('request.sending', $callback);
        $this->assertFalse($this->dispatcher->hasListeners('request.sending'));
    }

    public function test_has_listeners_returns_false_for_empty()
    {
        $this->assertFalse($this->dispatcher->hasListeners('nonexistent.event'));
    }

    public function test_get_listeners_returns_empty_array_for_no_listeners()
    {
        $listeners = $this->dispatcher->getListeners('nonexistent.event');
        $this->assertEmpty($listeners);
    }

    public function test_get_listeners_returns_sorted_listeners()
    {
        $low = function () {};
        $high = function () {};
        $medium = function () {};

        $this->dispatcher->addListener('request.sending', $low, 1);
        $this->dispatcher->addListener('request.sending', $high, 10);
        $this->dispatcher->addListener('request.sending', $medium, 5);

        $listeners = $this->dispatcher->getListeners('request.sending');

        $this->assertCount(3, $listeners);
        $this->assertSame($high, $listeners[0]);
        $this->assertSame($medium, $listeners[1]);
        $this->assertSame($low, $listeners[2]);
    }

    public function test_clear_listeners_for_specific_event()
    {
        $this->dispatcher->addListener('request.sending', function () {});
        $this->dispatcher->addListener('response.received', function () {});

        $this->dispatcher->clearListeners('request.sending');

        $this->assertFalse($this->dispatcher->hasListeners('request.sending'));
        $this->assertTrue($this->dispatcher->hasListeners('response.received'));
    }

    public function test_clear_all_listeners()
    {
        $this->dispatcher->addListener('request.sending', function () {});
        $this->dispatcher->addListener('response.received', function () {});

        $this->dispatcher->clearListeners();

        $this->assertFalse($this->dispatcher->hasListeners('request.sending'));
        $this->assertFalse($this->dispatcher->hasListeners('response.received'));
    }

    public function test_listener_error_does_not_stop_propagation()
    {
        $secondCalled = false;

        $this->dispatcher->addListener('request.sending', function () {
            throw new \RuntimeException('Error in listener');
        });

        $this->dispatcher->addListener('request.sending', function () use (&$secondCalled) {
            $secondCalled = true;
        });

        $request = new Request('GET', 'https://example.com/api');
        $event = new RequestEvent($request, 'corr-123', microtime(true));

        // Should not throw, just log
        $this->dispatcher->dispatch($event);

        $this->assertTrue($secondCalled);
    }

    public function test_listener_error_is_logged()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Event listener error', $this->callback(function ($context) {
                return isset($context['event']) &&
                       isset($context['error']) &&
                       isset($context['correlation_id']);
            }));

        $dispatcher = new EventDispatcher($logger);

        $dispatcher->addListener('request.sending', function () {
            throw new \RuntimeException('Error in listener');
        });

        $request = new Request('GET', 'https://example.com/api');
        $event = new RequestEvent($request, 'corr-123', microtime(true));

        $dispatcher->dispatch($event);
    }

    public function test_remove_nonexistent_listener_does_not_error()
    {
        // Should not throw
        $this->dispatcher->removeListener('nonexistent.event', function () {});
        $this->assertFalse($this->dispatcher->hasListeners('nonexistent.event'));
    }

    public function test_listeners_are_cached_after_sorting()
    {
        $callback = function () {};
        $this->dispatcher->addListener('request.sending', $callback, 1);

        // First call should cache
        $listeners1 = $this->dispatcher->getListeners('request.sending');

        // Second call should return cached
        $listeners2 = $this->dispatcher->getListeners('request.sending');

        $this->assertSame($listeners1, $listeners2);
    }

    public function test_cache_is_cleared_on_add()
    {
        $callback1 = function () {};
        $callback2 = function () {};

        $this->dispatcher->addListener('request.sending', $callback1, 1);
        $this->dispatcher->getListeners('request.sending'); // Cache

        $this->dispatcher->addListener('request.sending', $callback2, 10);
        $listeners = $this->dispatcher->getListeners('request.sending');

        // New listener should be first (higher priority)
        $this->assertSame($callback2, $listeners[0]);
    }
}
