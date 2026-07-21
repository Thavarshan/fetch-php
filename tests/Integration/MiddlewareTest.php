<?php

declare(strict_types=1);

namespace Tests\Integration;

use Fetch\Http\ClientHandler;
use Fetch\Middleware\AddHeadersMiddleware;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;

use function Matrix\Support\await;

class MiddlewareTest extends TestCase
{
    private MockHandler $mock;

    private function handler(array $responses): ClientHandler
    {
        $this->mock = new MockHandler($responses);
        $stack = HandlerStack::create($this->mock);
        $guzzle = new GuzzleClient(['handler' => $stack]);

        return (new ClientHandler)->setHttpClient($guzzle);
    }

    public function test_middleware_injects_header_into_real_request(): void
    {
        $handler = $this->handler([new PsrResponse(200, [], 'ok')]);
        $handler->addMiddleware(new AddHeadersMiddleware(['X-Api-Version' => '2.1']));

        $handler->get('https://example.com/users');

        $this->assertSame('2.1', $this->mock->getLastRequest()->getHeaderLine('X-Api-Version'));
    }

    public function test_middleware_can_short_circuit_without_hitting_transport(): void
    {
        // No responses queued: if the transport is touched, MockHandler throws.
        $handler = $this->handler([]);
        $handler->addMiddleware(
            fn (RequestInterface $request, callable $next) => new PsrResponse(418, [], 'cached')
        );

        $response = $handler->get('https://example.com/teapot');

        $this->assertSame(418, $response->getStatusCode());
        $this->assertNull($this->mock->getLastRequest());
    }

    public function test_middleware_can_inspect_and_replace_response(): void
    {
        $handler = $this->handler([new PsrResponse(200, [], 'body')]);
        $handler->addMiddleware(function (RequestInterface $request, callable $next) {
            $response = $next($request);

            return $response->withHeader('X-Wrapped', 'yes');
        });

        $response = $handler->get('https://example.com/x');

        $this->assertSame('yes', $response->getHeaderLine('X-Wrapped'));
    }

    public function test_priority_controls_execution_order_end_to_end(): void
    {
        $handler = $this->handler([new PsrResponse(200)]);
        $order = [];

        $handler->addMiddleware(function (RequestInterface $r, callable $n) use (&$order) {
            $order[] = 'low';

            return $n($r);
        }, 1);

        $handler->addMiddleware(function (RequestInterface $r, callable $n) use (&$order) {
            $order[] = 'high';

            return $n($r);
        }, 10);

        $handler->get('https://example.com/x');

        $this->assertSame(['high', 'low'], $order);
    }

    public function test_middleware_can_rewrite_request_body(): void
    {
        $handler = $this->handler([new PsrResponse(200)]);
        $handler->addMiddleware(
            fn (RequestInterface $request, callable $next) => $next($request->withBody(Utils::streamFor('rewritten')))
        );

        $handler->post('https://example.com/x', ['original' => true]);

        $this->assertSame('rewritten', (string) $this->mock->getLastRequest()->getBody());
    }

    public function test_middleware_applies_in_async_mode(): void
    {
        $handler = $this->handler([new PsrResponse(200, [], 'ok')]);
        $handler->addMiddleware(new AddHeadersMiddleware(['X-Async' => 'yes']));

        $result = $handler->async()->get('https://example.com/x');

        $this->assertInstanceOf(PromiseInterface::class, $result);

        $response = await($result);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('yes', $this->mock->getLastRequest()->getHeaderLine('X-Async'));
    }

    public function test_when_conditionally_adds_middleware(): void
    {
        $handler = $this->handler([new PsrResponse(200), new PsrResponse(200)]);

        $handler
            ->when(true, fn (ClientHandler $h) => $h->addMiddleware(new AddHeadersMiddleware(['X-On' => '1'])))
            ->unless(true, fn (ClientHandler $h) => $h->addMiddleware(new AddHeadersMiddleware(['X-Off' => '1'])));

        $handler->get('https://example.com/x');

        $this->assertSame('1', $this->mock->getLastRequest()->getHeaderLine('X-On'));
        $this->assertFalse($this->mock->getLastRequest()->hasHeader('X-Off'));
    }

    public function test_without_middleware_removes_registered_middleware(): void
    {
        $handler = $this->handler([new PsrResponse(200)]);
        $handler->addMiddleware(new AddHeadersMiddleware(['X-Gone' => '1']));
        $handler->withoutMiddleware(AddHeadersMiddleware::class);

        $handler->get('https://example.com/x');

        $this->assertFalse($this->mock->getLastRequest()->hasHeader('X-Gone'));
    }
}
