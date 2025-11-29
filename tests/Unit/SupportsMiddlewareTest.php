<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Http\ClientHandler;
use Fetch\Http\MiddlewarePipeline;
use Fetch\Http\Response;
use Fetch\Interfaces\MiddlewareInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use React\Promise\PromiseInterface;

class SupportsMiddlewareTest extends TestCase
{
    private ClientHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ClientHandler;
    }

    public function test_middleware_pipeline_is_lazy_initialized(): void
    {
        $pipeline = $this->handler->getMiddlewarePipeline();

        $this->assertInstanceOf(MiddlewarePipeline::class, $pipeline);
    }

    public function test_has_middleware_returns_false_when_empty(): void
    {
        $this->assertFalse($this->handler->hasMiddleware());
    }

    public function test_has_middleware_returns_true_when_middleware_added(): void
    {
        $middleware = $this->createMockMiddleware();
        $this->handler->addMiddleware($middleware);

        $this->assertTrue($this->handler->hasMiddleware());
    }

    public function test_middleware_can_be_set_with_array(): void
    {
        $middleware1 = $this->createMockMiddleware();
        $middleware2 = $this->createMockMiddleware();

        $this->handler->middleware([$middleware1, $middleware2]);

        $this->assertEquals(2, $this->handler->getMiddlewarePipeline()->count());
    }

    public function test_add_middleware_returns_handler_for_chaining(): void
    {
        $middleware = $this->createMockMiddleware();
        $result = $this->handler->addMiddleware($middleware);

        $this->assertSame($this->handler, $result);
    }

    public function test_add_middleware_with_priority(): void
    {
        $lowPriority = $this->createMockMiddleware();
        $highPriority = $this->createMockMiddleware();

        $this->handler->addMiddleware($lowPriority, 1);
        $this->handler->addMiddleware($highPriority, 10);

        $stack = $this->handler->getMiddlewarePipeline()->getMiddleware();

        // Higher priority should come first
        $this->assertSame($highPriority, $stack[0]['middleware']);
        $this->assertSame($lowPriority, $stack[1]['middleware']);
    }

    public function test_prepend_middleware(): void
    {
        $first = $this->createMockMiddleware();
        $prepended = $this->createMockMiddleware();

        $this->handler->addMiddleware($first);
        $this->handler->prependMiddleware($prepended);

        $stack = $this->handler->getMiddlewarePipeline()->getMiddleware();

        // Prepended should be first
        $this->assertSame($prepended, $stack[0]['middleware']);
    }

    public function test_clear_middleware(): void
    {
        $middleware = $this->createMockMiddleware();
        $this->handler->addMiddleware($middleware);

        $this->handler->clearMiddleware();

        $this->assertFalse($this->handler->hasMiddleware());
    }

    public function test_when_adds_middleware_if_condition_is_true(): void
    {
        $middleware = $this->createMockMiddleware();

        $this->handler->when(true, function ($handler) use ($middleware) {
            $handler->addMiddleware($middleware);
        });

        $this->assertTrue($this->handler->hasMiddleware());
    }

    public function test_when_does_not_add_middleware_if_condition_is_false(): void
    {
        $middleware = $this->createMockMiddleware();

        $this->handler->when(false, function ($handler) use ($middleware) {
            $handler->addMiddleware($middleware);
        });

        $this->assertFalse($this->handler->hasMiddleware());
    }

    public function test_unless_adds_middleware_if_condition_is_false(): void
    {
        $middleware = $this->createMockMiddleware();

        $this->handler->unless(false, function ($handler) use ($middleware) {
            $handler->addMiddleware($middleware);
        });

        $this->assertTrue($this->handler->hasMiddleware());
    }

    public function test_unless_does_not_add_middleware_if_condition_is_true(): void
    {
        $middleware = $this->createMockMiddleware();

        $this->handler->unless(true, function ($handler) use ($middleware) {
            $handler->addMiddleware($middleware);
        });

        $this->assertFalse($this->handler->hasMiddleware());
    }

    public function test_middleware_method_returns_handler_for_chaining(): void
    {
        $middleware = $this->createMockMiddleware();
        $result = $this->handler->middleware([$middleware]);

        $this->assertSame($this->handler, $result);
    }

    public function test_when_returns_handler_for_chaining(): void
    {
        $result = $this->handler->when(true, function () {});

        $this->assertSame($this->handler, $result);
    }

    public function test_unless_returns_handler_for_chaining(): void
    {
        $result = $this->handler->unless(false, function () {});

        $this->assertSame($this->handler, $result);
    }

    protected function createMockMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface
        {
            public function handle(RequestInterface $request, callable $next): Response|PromiseInterface
            {
                return $next($request);
            }
        };
    }
}
