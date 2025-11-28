<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Concerns\SupportsHooks;
use Fetch\Events\EventDispatcher;
use Fetch\Events\EventDispatcherInterface;
use Fetch\Events\FetchEvent;
use Fetch\Events\RequestEvent;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SupportsHooksTest extends TestCase
{
    protected object $handler;

    protected function setUp(): void
    {
        parent::setUp();

        // Create anonymous class that uses the trait
        $this->handler = new class
        {
            use SupportsHooks;

            public ?LoggerInterface $logger = null;
        };
    }

    public function test_on_request_registers_listener()
    {
        $callback = function () {};

        $result = $this->handler->onRequest($callback);

        $this->assertSame($this->handler, $result);
        $this->assertTrue($this->handler->hasHooks('request.sending'));
    }

    public function test_on_response_registers_listener()
    {
        $callback = function () {};

        $this->handler->onResponse($callback);

        $this->assertTrue($this->handler->hasHooks('response.received'));
    }

    public function test_on_error_registers_listener()
    {
        $callback = function () {};

        $this->handler->onError($callback);

        $this->assertTrue($this->handler->hasHooks('error.occurred'));
    }

    public function test_on_retry_registers_listener()
    {
        $callback = function () {};

        $this->handler->onRetry($callback);

        $this->assertTrue($this->handler->hasHooks('request.retrying'));
    }

    public function test_on_timeout_registers_listener()
    {
        $callback = function () {};

        $this->handler->onTimeout($callback);

        $this->assertTrue($this->handler->hasHooks('request.timeout'));
    }

    public function test_on_redirect_registers_listener()
    {
        $callback = function () {};

        $this->handler->onRedirect($callback);

        $this->assertTrue($this->handler->hasHooks('request.redirecting'));
    }

    public function test_when_registers_listener()
    {
        $callback = function () {};

        $this->handler->when('custom.event', $callback);

        $this->assertTrue($this->handler->hasHooks('custom.event'));
    }

    public function test_hooks_registers_multiple_listeners()
    {
        $this->handler->hooks([
            'before_send' => function () {},
            'after_response' => function () {},
            'on_error' => function () {},
        ]);

        $this->assertTrue($this->handler->hasHooks('request.sending'));
        $this->assertTrue($this->handler->hasHooks('response.received'));
        $this->assertTrue($this->handler->hasHooks('error.occurred'));
    }

    public function test_hooks_normalizes_shorthand_names()
    {
        $this->handler->hooks([
            'before_send' => function () {},
            'after_response' => function () {},
            'on_error' => function () {},
            'on_retry' => function () {},
            'on_timeout' => function () {},
            'on_redirect' => function () {},
        ]);

        $this->assertTrue($this->handler->hasHooks('request.sending'));
        $this->assertTrue($this->handler->hasHooks('response.received'));
        $this->assertTrue($this->handler->hasHooks('error.occurred'));
        $this->assertTrue($this->handler->hasHooks('request.retrying'));
        $this->assertTrue($this->handler->hasHooks('request.timeout'));
        $this->assertTrue($this->handler->hasHooks('request.redirecting'));
    }

    public function test_hooks_accepts_full_event_names()
    {
        $this->handler->hooks([
            'request.sending' => function () {},
            'response.received' => function () {},
        ]);

        $this->assertTrue($this->handler->hasHooks('request.sending'));
        $this->assertTrue($this->handler->hasHooks('response.received'));
    }

    public function test_has_hooks_returns_false_when_none_registered()
    {
        $this->assertFalse($this->handler->hasHooks());
        $this->assertFalse($this->handler->hasHooks('request.sending'));
    }

    public function test_has_hooks_returns_true_when_any_registered()
    {
        $this->handler->onRequest(function () {});

        $this->assertTrue($this->handler->hasHooks());
    }

    public function test_clear_hooks_clears_specific_event()
    {
        $this->handler->onRequest(function () {});
        $this->handler->onResponse(function () {});

        $this->handler->clearHooks('request.sending');

        $this->assertFalse($this->handler->hasHooks('request.sending'));
        $this->assertTrue($this->handler->hasHooks('response.received'));
    }

    public function test_clear_hooks_clears_all()
    {
        $this->handler->onRequest(function () {});
        $this->handler->onResponse(function () {});

        $this->handler->clearHooks();

        $this->assertFalse($this->handler->hasHooks());
    }

    public function test_get_event_dispatcher_creates_default()
    {
        $dispatcher = $this->handler->getEventDispatcher();

        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
    }

    public function test_set_event_dispatcher()
    {
        $customDispatcher = new EventDispatcher;

        $result = $this->handler->setEventDispatcher($customDispatcher);

        $this->assertSame($this->handler, $result);
        $this->assertSame($customDispatcher, $this->handler->getEventDispatcher());
    }

    public function test_listeners_are_called_with_priority()
    {
        $order = [];

        $this->handler->onRequest(function () use (&$order) {
            $order[] = 'low';
        }, 1);

        $this->handler->onRequest(function () use (&$order) {
            $order[] = 'high';
        }, 10);

        $request = new Request('GET', 'https://example.com/api');
        $event = new RequestEvent($request, 'corr-123', microtime(true));

        // Call dispatchEvent through reflection since it's protected
        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('dispatchEvent');
        $method->setAccessible(true);
        $method->invoke($this->handler, $event);

        $this->assertEquals(['high', 'low'], $order);
    }

    public function test_dispatch_event_does_nothing_without_dispatcher()
    {
        // Create a fresh handler without dispatcher
        $handler = new class
        {
            use SupportsHooks;

            public ?LoggerInterface $logger = null;
        };

        // Verify no dispatcher is set initially
        $reflection = new \ReflectionClass($handler);
        $property = $reflection->getProperty('eventDispatcher');
        $property->setAccessible(true);

        $this->assertNull($property->getValue($handler));

        // dispatchEvent should not throw when no dispatcher
        $request = new Request('GET', 'https://example.com/api');
        $event = new RequestEvent($request, 'corr-123', microtime(true));

        $method = $reflection->getMethod('dispatchEvent');
        $method->setAccessible(true);
        $method->invoke($handler, $event);

        // No exception means success
        $this->assertTrue(true);
    }

    public function test_generate_correlation_id()
    {
        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('generateCorrelationId');
        $method->setAccessible(true);

        $id1 = $method->invoke($this->handler);
        $id2 = $method->invoke($this->handler);

        $this->assertNotEmpty($id1);
        $this->assertNotEmpty($id2);
        $this->assertNotEquals($id1, $id2);
        $this->assertEquals(32, strlen($id1)); // 16 bytes = 32 hex chars
    }

    public function test_fluent_interface_for_all_on_methods()
    {
        $result = $this->handler
            ->onRequest(function () {})
            ->onResponse(function () {})
            ->onError(function () {})
            ->onRetry(function () {})
            ->onTimeout(function () {})
            ->onRedirect(function () {})
            ->when('custom.event', function () {})
            ->hooks(['before_send' => function () {}])
            ->clearHooks('custom.event');

        $this->assertSame($this->handler, $result);
    }
}
