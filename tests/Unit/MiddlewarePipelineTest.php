<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Http\MiddlewarePipeline;
use Fetch\Interfaces\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MiddlewarePipelineTest extends TestCase
{
    private function request(): RequestInterface
    {
        return new Request('GET', 'https://example.com');
    }

    public function test_empty_pipeline_calls_core_directly(): void
    {
        $pipeline = new MiddlewarePipeline([]);

        $response = $pipeline->handle($this->request(), fn () => new Response(200));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_middleware_execute_in_onion_order(): void
    {
        $order = [];

        $make = function (string $tag) use (&$order) {
            return function (RequestInterface $request, callable $next) use (&$order, $tag): ResponseInterface {
                $order[] = "before:$tag";
                $response = $next($request);
                $order[] = "after:$tag";

                return $response;
            };
        };

        $pipeline = new MiddlewarePipeline([$make('a'), $make('b')]);

        $pipeline->handle($this->request(), function () use (&$order) {
            $order[] = 'core';

            return new Response(200);
        });

        $this->assertSame(
            ['before:a', 'before:b', 'core', 'after:b', 'after:a'],
            $order,
        );
    }

    public function test_middleware_can_modify_request(): void
    {
        $add = fn (RequestInterface $request, callable $next) => $next($request->withHeader('X-Test', 'yes'));

        $pipeline = new MiddlewarePipeline([$add]);

        $pipeline->handle($this->request(), function (RequestInterface $request) {
            $this->assertSame('yes', $request->getHeaderLine('X-Test'));

            return new Response(200);
        });
    }

    public function test_middleware_can_short_circuit(): void
    {
        $coreCalled = false;

        $shortCircuit = fn (RequestInterface $request, callable $next) => new Response(418, [], 'teapot');

        $pipeline = new MiddlewarePipeline([$shortCircuit]);

        $response = $pipeline->handle($this->request(), function () use (&$coreCalled) {
            $coreCalled = true;

            return new Response(200);
        });

        $this->assertFalse($coreCalled);
        $this->assertSame(418, $response->getStatusCode());
    }

    public function test_supports_middleware_objects(): void
    {
        $middleware = new class implements Middleware
        {
            public function handle(RequestInterface $request, callable $next): ResponseInterface
            {
                return $next($request->withHeader('X-Object', 'ok'));
            }
        };

        $pipeline = new MiddlewarePipeline([$middleware]);

        $pipeline->handle($this->request(), function (RequestInterface $request) {
            $this->assertSame('ok', $request->getHeaderLine('X-Object'));

            return new Response(200);
        });
    }

    public function test_rejects_callable_returning_invalid_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $bad = fn (RequestInterface $request, callable $next) => 'not a response';

        (new MiddlewarePipeline([$bad]))->handle($this->request(), fn () => new Response(200));
    }
}
