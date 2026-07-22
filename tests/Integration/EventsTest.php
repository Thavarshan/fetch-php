<?php

declare(strict_types=1);

namespace Tests\Integration;

use Fetch\Events\ErrorEvent;
use Fetch\Events\RedirectEvent;
use Fetch\Events\RequestEvent;
use Fetch\Events\ResponseEvent;
use Fetch\Events\RetryEvent;
use Fetch\Events\TimeoutEvent;
use Fetch\Http\ClientHandler;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response as PsrResponse;
use PHPUnit\Framework\TestCase;

use function Matrix\Support\await;

class EventsTest extends TestCase
{
    private function handler(array $responses): ClientHandler
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $guzzle = new GuzzleClient(['handler' => $stack]);

        return (new ClientHandler)->setHttpClient($guzzle);
    }

    public function test_request_and_response_events_fire_with_shared_correlation_id(): void
    {
        $handler = $this->handler([new PsrResponse(200, [], 'ok')]);

        $requestEvent = null;
        $responseEvent = null;

        $handler
            ->onRequest(function (RequestEvent $e) use (&$requestEvent) {
                $requestEvent = $e;
            })
            ->onResponse(function (ResponseEvent $e) use (&$responseEvent) {
                $responseEvent = $e;
            });

        $handler->get('https://example.com/users');

        $this->assertInstanceOf(RequestEvent::class, $requestEvent);
        $this->assertInstanceOf(ResponseEvent::class, $responseEvent);
        $this->assertSame('GET', $requestEvent->getRequest()->getMethod());
        $this->assertSame(200, $responseEvent->getResponse()->getStatusCode());
        $this->assertGreaterThanOrEqual(0, $responseEvent->getLatency());

        // Both events share the request's correlation id.
        $this->assertNotSame('', $requestEvent->getCorrelationId());
        $this->assertSame($requestEvent->getCorrelationId(), $responseEvent->getCorrelationId());
    }

    public function test_error_and_timeout_events_fire_on_failure(): void
    {
        $handler = $this->handler([
            new ConnectException('cURL error 28: Operation timed out', new PsrRequest('GET', 'https://example.com')),
        ]);
        // Disable retries at the option level so the connect failure surfaces on
        // the first attempt rather than being retried into an empty mock queue.
        $handler->withOptions(['retries' => 0]);

        $errorEvent = null;
        $timeoutEvent = null;

        $handler
            ->onError(function (ErrorEvent $e) use (&$errorEvent) {
                $errorEvent = $e;
            })
            ->onTimeout(function (TimeoutEvent $e) use (&$timeoutEvent) {
                $timeoutEvent = $e;
            });

        try {
            $handler->get('https://example.com/slow');
            $this->fail('Expected the request to throw.');
        } catch (\Throwable) {
            // expected
        }

        $this->assertInstanceOf(ErrorEvent::class, $errorEvent);
        $this->assertInstanceOf(TimeoutEvent::class, $timeoutEvent);
    }

    public function test_retry_event_fires_between_attempts(): void
    {
        $handler = $this->handler([
            new PsrResponse(503),
            new PsrResponse(200, [], 'recovered'),
        ]);
        $handler->retry(2, 1);

        $retryEvents = [];
        $handler->onRetry(function (RetryEvent $e) use (&$retryEvents) {
            $retryEvents[] = $e;
        });

        $response = $handler->get('https://example.com/flaky');

        $this->assertSame(200, $response->status());
        $this->assertNotEmpty($retryEvents);
        $this->assertSame(1, $retryEvents[0]->getAttempt());
        $this->assertGreaterThan(0, $retryEvents[0]->getMaxAttempts());
    }

    public function test_redirect_event_fires_when_following_redirects(): void
    {
        $handler = $this->handler([
            new PsrResponse(301, ['Location' => 'https://example.com/new']),
            new PsrResponse(200, [], 'final'),
        ]);

        $redirectEvent = null;
        $handler->onRedirect(function (RedirectEvent $e) use (&$redirectEvent) {
            $redirectEvent = $e;
        });

        $response = $handler->get('https://example.com/old');

        $this->assertSame(200, $response->status());
        $this->assertInstanceOf(RedirectEvent::class, $redirectEvent);
        $this->assertSame('https://example.com/new', $redirectEvent->getLocation());
        $this->assertSame(1, $redirectEvent->getRedirectCount());
    }

    public function test_priority_orders_listeners(): void
    {
        $handler = $this->handler([new PsrResponse(200)]);
        $order = [];

        $handler
            ->onResponse(function () use (&$order) {
                $order[] = 'low';
            }, 1)
            ->onResponse(function () use (&$order) {
                $order[] = 'high';
            }, 10);

        $handler->get('https://example.com/x');

        $this->assertSame(['high', 'low'], $order);
    }

    public function test_hooks_array_registration(): void
    {
        $handler = $this->handler([new PsrResponse(200)]);
        $fired = [];

        $handler->hooks([
            'before_send' => function () use (&$fired) {
                $fired[] = 'before';
            },
            'after_response' => function () use (&$fired) {
                $fired[] = 'after';
            },
        ]);

        $handler->get('https://example.com/x');

        $this->assertSame(['before', 'after'], $fired);
    }

    public function test_events_fire_in_async_mode(): void
    {
        $handler = $this->handler([new PsrResponse(200, [], 'ok')]);
        $responseEvent = null;

        $handler->onResponse(function (ResponseEvent $e) use (&$responseEvent) {
            $responseEvent = $e;
        });

        await($handler->async()->get('https://example.com/x'));

        $this->assertInstanceOf(ResponseEvent::class, $responseEvent);
        $this->assertSame(200, $responseEvent->getResponse()->getStatusCode());
    }

    public function test_no_listeners_means_no_overhead_path(): void
    {
        // Sanity: a request with no listeners still works normally.
        $handler = $this->handler([new PsrResponse(204)]);

        $response = $handler->get('https://example.com/x');

        $this->assertSame(204, $response->status());
    }
}
