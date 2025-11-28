<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use Fetch\Events\ErrorEvent;
use Fetch\Events\RedirectEvent;
use Fetch\Events\RequestEvent;
use Fetch\Events\ResponseEvent;
use Fetch\Events\RetryEvent;
use Fetch\Events\TimeoutEvent;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FetchEventsTest extends TestCase
{
    public function test_request_event()
    {
        $request = new Request('GET', 'https://example.com/api');
        $correlationId = 'corr-123';
        $timestamp = microtime(true);
        $context = ['user' => 'test'];
        $options = ['timeout' => 30];

        $event = new RequestEvent($request, $correlationId, $timestamp, $context, $options);

        $this->assertSame($request, $event->getRequest());
        $this->assertEquals($correlationId, $event->getCorrelationId());
        $this->assertEquals($timestamp, $event->getTimestamp());
        $this->assertEquals($context, $event->getContext());
        $this->assertEquals($options, $event->getOptions());
        $this->assertEquals('request.sending', $event->getName());
    }

    public function test_response_event()
    {
        $request = new Request('GET', 'https://example.com/api');
        $response = new Response(200, ['Content-Type' => 'application/json'], '{"success":true}');
        $correlationId = 'corr-123';
        $timestamp = microtime(true);
        $duration = 0.5;
        $context = ['cached' => false];

        $event = new ResponseEvent($request, $response, $correlationId, $timestamp, $duration, $context);

        $this->assertSame($request, $event->getRequest());
        $this->assertSame($response, $event->getResponse());
        $this->assertEquals($correlationId, $event->getCorrelationId());
        $this->assertEquals($timestamp, $event->getTimestamp());
        $this->assertEquals($duration, $event->getDuration());
        $this->assertEquals(500, $event->getLatency()); // 0.5 seconds = 500ms
        $this->assertEquals($context, $event->getContext());
        $this->assertEquals('response.received', $event->getName());
    }

    public function test_error_event()
    {
        $request = new Request('GET', 'https://example.com/api');
        $exception = new RuntimeException('Connection failed');
        $correlationId = 'corr-123';
        $timestamp = microtime(true);
        $attempt = 2;
        $response = new Response(500);
        $context = ['retry_count' => 2];

        $event = new ErrorEvent($request, $exception, $correlationId, $timestamp, $attempt, $response, $context);

        $this->assertSame($request, $event->getRequest());
        $this->assertSame($exception, $event->getException());
        $this->assertEquals($correlationId, $event->getCorrelationId());
        $this->assertEquals($timestamp, $event->getTimestamp());
        $this->assertEquals($attempt, $event->getAttempt());
        $this->assertSame($response, $event->getResponse());
        $this->assertEquals($context, $event->getContext());
        $this->assertEquals('error.occurred', $event->getName());
    }

    public function test_error_event_retryable_with_retryable_status()
    {
        $request = new Request('GET', 'https://example.com/api');
        $exception = new RuntimeException('Server error');
        $response = new Response(503); // Service Unavailable

        $event = new ErrorEvent($request, $exception, 'corr', microtime(true), 1, $response);

        $this->assertTrue($event->isRetryable());
    }

    public function test_error_event_not_retryable_with_client_error()
    {
        $request = new Request('GET', 'https://example.com/api');
        $exception = new RuntimeException('Not found');
        $response = new Response(404); // Not Found

        $event = new ErrorEvent($request, $exception, 'corr', microtime(true), 1, $response);

        $this->assertFalse($event->isRetryable());
    }

    public function test_error_event_retryable_without_response()
    {
        $request = new Request('GET', 'https://example.com/api');
        $exception = new RuntimeException('Network error');

        $event = new ErrorEvent($request, $exception, 'corr', microtime(true), 1, null);

        // Network errors without response are retryable
        $this->assertTrue($event->isRetryable());
    }

    public function test_retry_event()
    {
        $request = new Request('GET', 'https://example.com/api');
        $previousException = new RuntimeException('First attempt failed');
        $correlationId = 'corr-123';
        $timestamp = microtime(true);
        $attempt = 2;
        $maxAttempts = 3;
        $delay = 1000;
        $context = ['reason' => 'timeout'];

        $event = new RetryEvent(
            $request,
            $previousException,
            $attempt,
            $maxAttempts,
            $delay,
            $correlationId,
            $timestamp,
            $context
        );

        $this->assertSame($request, $event->getRequest());
        $this->assertSame($previousException, $event->getPreviousException());
        $this->assertEquals($attempt, $event->getAttempt());
        $this->assertEquals($maxAttempts, $event->getMaxAttempts());
        $this->assertEquals($delay, $event->getDelay());
        $this->assertEquals($correlationId, $event->getCorrelationId());
        $this->assertEquals($timestamp, $event->getTimestamp());
        $this->assertEquals($context, $event->getContext());
        $this->assertEquals('request.retrying', $event->getName());
    }

    public function test_retry_event_is_last_attempt()
    {
        $request = new Request('GET', 'https://example.com/api');
        $exception = new RuntimeException('Failed');

        $lastAttemptEvent = new RetryEvent($request, $exception, 3, 3, 1000, 'corr', microtime(true));
        $notLastAttemptEvent = new RetryEvent($request, $exception, 2, 3, 1000, 'corr', microtime(true));

        $this->assertTrue($lastAttemptEvent->isLastAttempt());
        $this->assertFalse($notLastAttemptEvent->isLastAttempt());
    }

    public function test_timeout_event()
    {
        $request = new Request('GET', 'https://example.com/api');
        $correlationId = 'corr-123';
        $timestamp = microtime(true);
        $timeout = 30;
        $elapsed = 31.5;
        $context = ['operation' => 'api_call'];

        $event = new TimeoutEvent($request, $timeout, $elapsed, $correlationId, $timestamp, $context);

        $this->assertSame($request, $event->getRequest());
        $this->assertEquals($timeout, $event->getTimeout());
        $this->assertEquals($elapsed, $event->getElapsed());
        $this->assertEquals($correlationId, $event->getCorrelationId());
        $this->assertEquals($timestamp, $event->getTimestamp());
        $this->assertEquals($context, $event->getContext());
        $this->assertEquals('request.timeout', $event->getName());
    }

    public function test_redirect_event()
    {
        $request = new Request('GET', 'https://example.com/old-path');
        $response = new Response(301, ['Location' => 'https://example.com/new-path']);
        $correlationId = 'corr-123';
        $timestamp = microtime(true);
        $location = 'https://example.com/new-path';
        $redirectCount = 2;
        $context = ['reason' => 'permanent'];

        $event = new RedirectEvent(
            $request,
            $response,
            $location,
            $redirectCount,
            $correlationId,
            $timestamp,
            $context
        );

        $this->assertSame($request, $event->getRequest());
        $this->assertSame($response, $event->getResponse());
        $this->assertEquals($location, $event->getLocation());
        $this->assertEquals($redirectCount, $event->getRedirectCount());
        $this->assertEquals($correlationId, $event->getCorrelationId());
        $this->assertEquals($timestamp, $event->getTimestamp());
        $this->assertEquals($context, $event->getContext());
        $this->assertEquals('request.redirecting', $event->getName());
    }
}
