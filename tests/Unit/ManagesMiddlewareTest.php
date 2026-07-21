<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Concerns\ManagesMiddleware;
use Fetch\Middleware\AddHeadersMiddleware;
use PHPUnit\Framework\TestCase;

class ManagesMiddlewareTest extends TestCase
{
    private function handler(): object
    {
        return new class
        {
            use ManagesMiddleware;
        };
    }

    public function test_starts_empty(): void
    {
        $handler = $this->handler();

        $this->assertFalse($handler->hasMiddleware());
        $this->assertSame([], $handler->getMiddleware());
    }

    public function test_add_middleware_is_fluent_and_tracked(): void
    {
        $handler = $this->handler();
        $mw = fn ($request, $next) => $next($request);

        $this->assertSame($handler, $handler->addMiddleware($mw));
        $this->assertTrue($handler->hasMiddleware());
        $this->assertSame([$mw], $handler->getMiddleware());
    }

    public function test_priority_orders_highest_first(): void
    {
        $handler = $this->handler();
        $low = fn ($r, $n) => $n($r);
        $high = fn ($r, $n) => $n($r);
        $mid = fn ($r, $n) => $n($r);

        $handler->addMiddleware($low, 1);
        $handler->addMiddleware($high, 10);
        $handler->addMiddleware($mid, 5);

        $this->assertSame([$high, $mid, $low], $handler->getMiddleware());
    }

    public function test_equal_priority_keeps_insertion_order(): void
    {
        $handler = $this->handler();
        $first = fn ($r, $n) => $n($r);
        $second = fn ($r, $n) => $n($r);
        $third = fn ($r, $n) => $n($r);

        $handler->addMiddleware($first);
        $handler->addMiddleware($second);
        $handler->addMiddleware($third);

        $this->assertSame([$first, $second, $third], $handler->getMiddleware());
    }

    public function test_middleware_replaces_stack_and_accepts_priority_pairs(): void
    {
        $handler = $this->handler();
        $a = fn ($r, $n) => $n($r);
        $b = fn ($r, $n) => $n($r);

        $handler->addMiddleware(fn ($r, $n) => $n($r));
        $handler->middleware([[$a, 1], [$b, 100]]);

        $this->assertSame([$b, $a], $handler->getMiddleware());
    }

    public function test_without_middleware_clears_all(): void
    {
        $handler = $this->handler();
        $handler->addMiddleware(fn ($r, $n) => $n($r));

        $handler->withoutMiddleware();

        $this->assertFalse($handler->hasMiddleware());
    }

    public function test_without_middleware_removes_by_class(): void
    {
        $handler = $this->handler();
        $callable = fn ($r, $n) => $n($r);
        $handler->addMiddleware(new AddHeadersMiddleware(['X-A' => '1']));
        $handler->addMiddleware($callable);

        $handler->withoutMiddleware(AddHeadersMiddleware::class);

        $this->assertSame([$callable], $handler->getMiddleware());
    }

    public function test_when_applies_callback_on_truthy_condition(): void
    {
        $handler = $this->handler();

        $handler->when(true, fn ($h) => $h->addMiddleware(fn ($r, $n) => $n($r)));
        $handler->when(false, fn ($h) => $h->addMiddleware(fn ($r, $n) => $n($r)));

        $this->assertCount(1, $handler->getMiddleware());
    }

    public function test_when_passes_resolved_value_and_supports_default(): void
    {
        $handler = $this->handler();
        $seen = null;

        $handler->when(
            fn ($h) => 'resolved',
            function ($h, $value) use (&$seen) {
                $seen = $value;
            },
        );

        $this->assertSame('resolved', $seen);

        $defaultCalled = false;
        $handler->when(false, fn () => null, function () use (&$defaultCalled) {
            $defaultCalled = true;
        });

        $this->assertTrue($defaultCalled);
    }

    public function test_unless_applies_callback_on_falsy_condition(): void
    {
        $handler = $this->handler();

        $handler->unless(false, fn ($h) => $h->addMiddleware(fn ($r, $n) => $n($r)));
        $handler->unless(true, fn ($h) => $h->addMiddleware(fn ($r, $n) => $n($r)));

        $this->assertCount(1, $handler->getMiddleware());
    }
}
