<?php

namespace Tests\Integration;

use Fetch\Http\ClientHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;

use function Matrix\Support\await;

/**
 * Tests for concurrent request handling safety.
 *
 * These tests verify that a single handler instance can be safely reused
 * across multiple concurrent async requests without state interference.
 */
class ConcurrentRequestsTest extends TestCase
{
    public function test_concurrent_async_requests_with_different_options(): void
    {
        // Create a mock handler with different responses
        $mockHandler = new MockHandler([
            new GuzzleResponse(200, ['X-Request' => '1'], json_encode(['id' => 1, 'name' => 'Request 1'])),
            new GuzzleResponse(201, ['X-Request' => '2'], json_encode(['id' => 2, 'name' => 'Request 2'])),
            new GuzzleResponse(202, ['X-Request' => '3'], json_encode(['id' => 3, 'name' => 'Request 3'])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        // Create a single handler instance
        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');

        // Fire three async requests with different per-request options on the SAME handler instance
        // Using sendRequest with per-request options instead of withOptions (which mutates state)
        $promise1 = $handler->sendRequest('GET', '/endpoint1', [
            'async' => true,
            'retries' => 1,
            'timeout' => 10,
            'query' => ['query1' => 'value1'],
        ]);

        $promise2 = $handler->sendRequest('GET', '/endpoint2', [
            'async' => true,
            'retries' => 3,
            'timeout' => 20,
            'query' => ['query2' => 'value2'],
        ]);

        $promise3 = $handler->sendRequest('GET', '/endpoint3', [
            'async' => true,
            'retries' => 5,
            'timeout' => 30,
            'query' => ['query3' => 'value3'],
        ]);

        // Await all promises
        $response1 = await($promise1);
        $response2 = await($promise2);
        $response3 = await($promise3);

        // Assert that each response matches its expected values
        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals('1', $response1->getHeaderLine('X-Request'));
        $this->assertEquals(['id' => 1, 'name' => 'Request 1'], $response1->json());

        $this->assertEquals(201, $response2->getStatusCode());
        $this->assertEquals('2', $response2->getHeaderLine('X-Request'));
        $this->assertEquals(['id' => 2, 'name' => 'Request 2'], $response2->json());

        $this->assertEquals(202, $response3->getStatusCode());
        $this->assertEquals('3', $response3->getHeaderLine('X-Request'));
        $this->assertEquals(['id' => 3, 'name' => 'Request 3'], $response3->json());
    }

    public function test_concurrent_requests_do_not_interfere_with_handler_state(): void
    {
        // Create mock responses
        $mockHandler = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['result' => 'sync-request'])),
            new GuzzleResponse(200, [], json_encode(['result' => 'async-request'])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        // Create handler with default options
        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');
        $handler->retry(2);  // Default retry count
        $handler->timeout(15);  // Default timeout

        // Before requests: verify handler state
        $this->assertEquals(2, $handler->getMaxRetries());
        $this->assertEquals(15, $handler->getEffectiveTimeout());

        // Fire sync request with different per-request options
        $response1 = $handler->sendRequest('GET', '/test1', [
            'retries' => 7,
            'timeout' => 90,
        ]);

        // After sync request: verify handler state was NOT mutated
        $this->assertEquals(2, $handler->getMaxRetries(), 'Handler maxRetries should remain unchanged after sync request');
        $this->assertEquals(15, $handler->getEffectiveTimeout(), 'Handler timeout should remain unchanged after sync request');

        // Fire async request with different per-request options (uses same handler instance)
        $promise2 = $handler->sendRequest('GET', '/test2', [
            'async' => true,
            'retries' => 10,
            'timeout' => 60,
        ]);

        // Await async response
        $response2 = await($promise2);

        // After async request: verify handler state was NOT mutated
        $this->assertEquals(2, $handler->getMaxRetries(), 'Handler maxRetries should remain unchanged after async request');
        $this->assertEquals(15, $handler->getEffectiveTimeout(), 'Handler timeout should remain unchanged after async request');

        // Verify responses are correct
        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals(['result' => 'sync-request'], $response1->json());

        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertEquals(['result' => 'async-request'], $response2->json());
    }

    public function test_handler_can_be_cloned_for_different_configurations(): void
    {
        // Create mock responses
        $mockHandler = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['config' => 'handler1'])),
            new GuzzleResponse(200, [], json_encode(['config' => 'handler2'])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        // Create base handler
        $handler1 = ClientHandler::createWithClient($guzzleClient);
        $handler1->baseUri('https://api.example.com');
        $handler1->retry(3);

        // Clone handler with different options
        $handler2 = $handler1->withClonedOptions(['retries' => 10, 'timeout' => 99]);

        // Verify that handler1 state is unchanged
        $this->assertEquals(3, $handler1->getMaxRetries());

        // Verify that handler2 has new state
        $this->assertEquals(10, $handler2->getMaxRetries());
        $this->assertEquals(99, $handler2->getEffectiveTimeout());

        // Fire requests from both handlers
        $response1 = $handler1->get('/test1');
        $response2 = $handler2->get('/test2');

        $this->assertEquals(['config' => 'handler1'], $response1->json());
        $this->assertEquals(['config' => 'handler2'], $response2->json());
    }

    public function test_debug_info_does_not_leak_between_concurrent_requests(): void
    {
        // Create mock responses with different data
        $mockHandler = new MockHandler([
            new GuzzleResponse(200, ['X-Debug' => 'req1'], json_encode(['debug' => 'request1'])),
            new GuzzleResponse(200, ['X-Debug' => 'req2'], json_encode(['debug' => 'request2'])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        // Create handler with debug enabled
        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');
        $handler->withDebug(true);

        // Fire two requests sequentially (for now, to test debug info capture)
        $response1 = $handler->get('/test1');
        $debugInfo1 = $handler->getLastDebugInfo();

        $response2 = $handler->get('/test2');
        $debugInfo2 = $handler->getLastDebugInfo();

        // Verify that debug info was captured for both requests
        $this->assertNotNull($debugInfo1, 'Debug info should be captured for first request');
        $this->assertNotNull($debugInfo2, 'Debug info should be captured for second request');

        // Verify that debug info is different for each request
        $this->assertNotSame($debugInfo1, $debugInfo2, 'Debug info should be different objects');

        // Verify that the most recent debug info is from request2
        $this->assertSame($debugInfo2, $handler->getLastDebugInfo());
    }

    public function test_retries_are_isolated_per_request(): void
    {
        // Create handler that will fail twice, then succeed
        $mockHandler = new MockHandler([
            new GuzzleResponse(503, [], 'Service Unavailable'),
            new GuzzleResponse(503, [], 'Service Unavailable'),
            new GuzzleResponse(200, [], json_encode(['success' => true, 'attempt' => 3])),
            new GuzzleResponse(200, [], json_encode(['success' => true, 'attempt' => 1])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');

        // First request with retries enabled - should retry until success
        // Per-request retry config should override handler defaults
        $response1 = $handler->sendRequest('GET', '/test1', [
            'retries' => 5,
        ]);

        $this->assertEquals(200, $response1->getStatusCode());
        $data1 = $response1->json();
        $this->assertEquals(3, $data1['attempt'], 'Request should have succeeded on 3rd attempt after 2 retries');

        // Second request with no retries - should succeed immediately (no retries even if it fails)
        $response2 = $handler->sendRequest('GET', '/test2', [
            'retries' => 0,
        ]);

        $this->assertEquals(200, $response2->getStatusCode());
        $data2 = $response2->json();
        $this->assertEquals(1, $data2['attempt'], 'Request should have succeeded on first attempt');
    }
}
