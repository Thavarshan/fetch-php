<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Http\MiddlewarePipeline;
use Fetch\Http\Response;
use Fetch\Interfaces\MiddlewareInterface;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use React\Promise\PromiseInterface;

class MiddlewarePipelineTest extends TestCase
{
    public function test_pipeline_can_be_created_empty(): void
    {
        $pipeline = new MiddlewarePipeline;

        $this->assertTrue($pipeline->isEmpty());
        $this->assertEquals(0, $pipeline->count());
    }

    public function test_pipeline_can_be_created_with_middleware(): void
    {
        $middleware = $this->createMockMiddleware();
        $pipeline = new MiddlewarePipeline([$middleware]);

        $this->assertFalse($pipeline->isEmpty());
        $this->assertEquals(1, $pipeline->count());
    }

    public function test_middleware_can_be_added(): void
    {
        $pipeline = new MiddlewarePipeline;
        $middleware = $this->createMockMiddleware();

        $pipeline->add($middleware);

        $this->assertEquals(1, $pipeline->count());
    }

    public function test_middleware_can_be_prepended(): void
    {
        $middleware1 = $this->createMockMiddleware();
        $middleware2 = $this->createMockMiddleware();

        $pipeline = new MiddlewarePipeline([$middleware1]);
        $pipeline->prepend($middleware2);

        $stack = $pipeline->getMiddleware();

        // Prepended middleware should have higher priority and come first
        $this->assertSame($middleware2, $stack[0]['middleware']);
        $this->assertSame($middleware1, $stack[1]['middleware']);
    }

    public function test_middleware_is_sorted_by_priority(): void
    {
        $lowPriority = $this->createMockMiddleware();
        $highPriority = $this->createMockMiddleware();

        $pipeline = new MiddlewarePipeline;
        $pipeline->add($lowPriority, 1);
        $pipeline->add($highPriority, 10);

        $stack = $pipeline->getMiddleware();

        // Higher priority should come first
        $this->assertSame($highPriority, $stack[0]['middleware']);
        $this->assertSame($lowPriority, $stack[1]['middleware']);
    }

    public function test_handle_executes_core_handler_when_no_middleware(): void
    {
        $pipeline = new MiddlewarePipeline;
        $request = new Request('GET', 'https://example.com');
        $expectedResponse = new Response(200);

        $coreHandler = function (RequestInterface $req) use ($expectedResponse, $request) {
            $this->assertSame($request, $req);

            return $expectedResponse;
        };

        $response = $pipeline->handle($request, $coreHandler);

        $this->assertSame($expectedResponse, $response);
    }

    public function test_middleware_can_modify_request(): void
    {
        $middleware = new class implements MiddlewareInterface
        {
            public function handle(RequestInterface $request, callable $next): Response|PromiseInterface
            {
                // Add a header to the request
                $modifiedRequest = $request->withHeader('X-Custom-Header', 'test-value');

                return $next($modifiedRequest);
            }
        };

        $pipeline = new MiddlewarePipeline([$middleware]);
        $request = new Request('GET', 'https://example.com');

        $receivedRequest = null;
        $coreHandler = function (RequestInterface $req) use (&$receivedRequest) {
            $receivedRequest = $req;

            return new Response(200);
        };

        $pipeline->handle($request, $coreHandler);

        $this->assertNotNull($receivedRequest);
        $this->assertEquals('test-value', $receivedRequest->getHeaderLine('X-Custom-Header'));
    }

    public function test_middleware_can_modify_response(): void
    {
        $middleware = new class implements MiddlewareInterface
        {
            public function handle(RequestInterface $request, callable $next): Response|PromiseInterface
            {
                $response = $next($request);

                // Modify the response
                return $response->withHeader('X-Response-Header', 'modified');
            }
        };

        $pipeline = new MiddlewarePipeline([$middleware]);
        $request = new Request('GET', 'https://example.com');

        $coreHandler = function (RequestInterface $req) {
            return new Response(200);
        };

        $response = $pipeline->handle($request, $coreHandler);

        $this->assertEquals('modified', $response->getHeaderLine('X-Response-Header'));
    }

    public function test_middleware_can_short_circuit(): void
    {
        $shortCircuitMiddleware = new class implements MiddlewareInterface
        {
            public function handle(RequestInterface $request, callable $next): Response|PromiseInterface
            {
                // Return early without calling $next
                return new Response(401, [], 'Unauthorized');
            }
        };

        $pipeline = new MiddlewarePipeline([$shortCircuitMiddleware]);
        $request = new Request('GET', 'https://example.com');

        $coreHandlerCalled = false;
        $coreHandler = function (RequestInterface $req) use (&$coreHandlerCalled) {
            $coreHandlerCalled = true;

            return new Response(200);
        };

        $response = $pipeline->handle($request, $coreHandler);

        $this->assertFalse($coreHandlerCalled);
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_multiple_middleware_executes_in_order(): void
    {
        $order = [];

        $firstMiddleware = new class($order) implements MiddlewareInterface
        {
            public function __construct(private array &$order) {}

            public function handle(RequestInterface $request, callable $next): Response|PromiseInterface
            {
                $this->order[] = 'first-before';
                $response = $next($request);
                $this->order[] = 'first-after';

                return $response;
            }
        };

        $secondMiddleware = new class($order) implements MiddlewareInterface
        {
            public function __construct(private array &$order) {}

            public function handle(RequestInterface $request, callable $next): Response|PromiseInterface
            {
                $this->order[] = 'second-before';
                $response = $next($request);
                $this->order[] = 'second-after';

                return $response;
            }
        };

        $pipeline = new MiddlewarePipeline;
        $pipeline->add($firstMiddleware, 10);   // Higher priority, runs first
        $pipeline->add($secondMiddleware, 5);   // Lower priority, runs second

        $request = new Request('GET', 'https://example.com');
        $coreHandler = function (RequestInterface $req) use (&$order) {
            $order[] = 'core';

            return new Response(200);
        };

        $pipeline->handle($request, $coreHandler);

        $this->assertEquals([
            'first-before',
            'second-before',
            'core',
            'second-after',
            'first-after',
        ], $order);
    }

    public function test_pipeline_can_be_cleared(): void
    {
        $middleware = $this->createMockMiddleware();
        $pipeline = new MiddlewarePipeline([$middleware]);

        $pipeline->clear();

        $this->assertTrue($pipeline->isEmpty());
        $this->assertEquals(0, $pipeline->count());
    }

    public function test_pipeline_accepts_array_with_priority(): void
    {
        $middleware = $this->createMockMiddleware();
        $pipeline = new MiddlewarePipeline([
            ['middleware' => $middleware, 'priority' => 5],
        ]);

        $stack = $pipeline->getMiddleware();

        $this->assertEquals(5, $stack[0]['priority']);
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

    public function test_constructor_throws_exception_for_invalid_middleware(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware must be an instance of MiddlewareInterface');

        new MiddlewarePipeline(['invalid']);
    }

    public function test_constructor_throws_exception_for_array_without_middleware_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new MiddlewarePipeline([['priority' => 5]]);
    }

    public function test_pipeline_accepts_array_with_default_priority(): void
    {
        $middleware = $this->createMockMiddleware();
        $pipeline = new MiddlewarePipeline([
            ['middleware' => $middleware],
        ]);

        $stack = $pipeline->getMiddleware();

        $this->assertEquals(0, $stack[0]['priority']);
    }
}
