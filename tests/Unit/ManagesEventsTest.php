<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Concerns\ManagesEvents;
use Fetch\Events\ErrorEvent;
use Fetch\Events\RedirectEvent;
use Fetch\Events\RequestEvent;
use Fetch\Events\ResponseEvent;
use Fetch\Events\RetryEvent;
use Fetch\Events\TimeoutEvent;
use PHPUnit\Framework\TestCase;

class ManagesEventsTest extends TestCase
{
    private function handler(): object
    {
        return new class
        {
            use ManagesEvents;
        };
    }

    public function test_on_registers_listener_and_is_fluent(): void
    {
        $handler = $this->handler();
        $listener = fn () => null;

        $this->assertSame($handler, $handler->on(RequestEvent::NAME, $listener));
        $this->assertTrue($handler->getEventDispatcher()->hasListeners(RequestEvent::NAME));
    }

    public function test_on_helpers_map_to_event_names(): void
    {
        $handler = $this->handler();

        $handler->onRequest(fn () => null);
        $handler->onResponse(fn () => null);
        $handler->onError(fn () => null);
        $handler->onRetry(fn () => null);
        $handler->onTimeout(fn () => null);
        $handler->onRedirect(fn () => null);

        $dispatcher = $handler->getEventDispatcher();

        $this->assertTrue($dispatcher->hasListeners(RequestEvent::NAME));
        $this->assertTrue($dispatcher->hasListeners(ResponseEvent::NAME));
        $this->assertTrue($dispatcher->hasListeners(ErrorEvent::NAME));
        $this->assertTrue($dispatcher->hasListeners(RetryEvent::NAME));
        $this->assertTrue($dispatcher->hasListeners(TimeoutEvent::NAME));
        $this->assertTrue($dispatcher->hasListeners(RedirectEvent::NAME));
    }

    public function test_hooks_maps_aliases_to_event_names(): void
    {
        $handler = $this->handler();

        $handler->hooks([
            'before_send' => fn () => null,
            'after_response' => fn () => null,
            'on_error' => fn () => null,
            'on_retry' => fn () => null,
            'on_timeout' => fn () => null,
            'on_redirect' => fn () => null,
        ]);

        $dispatcher = $handler->getEventDispatcher();

        $this->assertTrue($dispatcher->hasListeners(RequestEvent::NAME));
        $this->assertTrue($dispatcher->hasListeners(ResponseEvent::NAME));
        $this->assertTrue($dispatcher->hasListeners(ErrorEvent::NAME));
        $this->assertTrue($dispatcher->hasListeners(RetryEvent::NAME));
        $this->assertTrue($dispatcher->hasListeners(TimeoutEvent::NAME));
        $this->assertTrue($dispatcher->hasListeners(RedirectEvent::NAME));
    }

    public function test_hooks_passes_through_unknown_names_verbatim(): void
    {
        $handler = $this->handler();

        $handler->hooks(['custom.event' => fn () => null]);

        $this->assertTrue($handler->getEventDispatcher()->hasListeners('custom.event'));
    }

    public function test_generates_correlation_ids(): void
    {
        $handler = $this->handler();

        $a = $handler->generateCorrelationId();
        $b = $handler->generateCorrelationId();

        $this->assertNotSame('', $a);
        $this->assertNotSame($a, $b);
    }
}
