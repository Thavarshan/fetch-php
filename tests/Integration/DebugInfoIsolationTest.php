<?php

namespace Tests\Integration;

use Fetch\Http\ClientHandler;
use Fetch\Support\FetchProfiler;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;

use function Matrix\Support\await;

class DebugInfoIsolationTest extends TestCase
{
    public function test_debug_info_is_attached_to_each_response(): void
    {
        // Create mock responses
        $mockHandler = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['id' => 1])),
            new GuzzleResponse(200, [], json_encode(['id' => 2])),
            new GuzzleResponse(200, [], json_encode(['id' => 3])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');
        $handler->withDebug(true);

        // Fire three sync requests
        $response1 = $handler->get('/endpoint1');
        $response2 = $handler->get('/endpoint2');
        $response3 = $handler->get('/endpoint3');

        // Each response should have its own debug info
        $this->assertTrue($response1->hasDebugInfo());
        $this->assertTrue($response2->hasDebugInfo());
        $this->assertTrue($response3->hasDebugInfo());

        $debug1 = $response1->getDebugInfo();
        $debug2 = $response2->getDebugInfo();
        $debug3 = $response3->getDebugInfo();

        // Debug info should be different instances
        $this->assertNotSame($debug1, $debug2);
        $this->assertNotSame($debug2, $debug3);
        $this->assertNotSame($debug1, $debug3);

        // Each should contain the correct request URI
        $this->assertStringContainsString('endpoint1', $debug1->getRequestData()['uri']);
        $this->assertStringContainsString('endpoint2', $debug2->getRequestData()['uri']);
        $this->assertStringContainsString('endpoint3', $debug3->getRequestData()['uri']);
    }

    public function test_concurrent_async_requests_have_isolated_debug_info(): void
    {
        // Create mock responses
        $mockHandler = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['id' => 1])),
            new GuzzleResponse(200, [], json_encode(['id' => 2])),
            new GuzzleResponse(200, [], json_encode(['id' => 3])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');
        $handler->withDebug(true);

        // Fire three concurrent async requests
        $promises = [
            'req1' => $handler->async()->get('/async1'),
            'req2' => $handler->async()->get('/async2'),
            'req3' => $handler->async()->get('/async3'),
        ];

        $responses = await(\Matrix\Support\all($promises));

        // Each response should have its own debug info
        $this->assertTrue($responses['req1']->hasDebugInfo());
        $this->assertTrue($responses['req2']->hasDebugInfo());
        $this->assertTrue($responses['req3']->hasDebugInfo());

        $debug1 = $responses['req1']->getDebugInfo();
        $debug2 = $responses['req2']->getDebugInfo();
        $debug3 = $responses['req3']->getDebugInfo();

        // Debug info should be different instances
        $this->assertNotSame($debug1, $debug2);
        $this->assertNotSame($debug2, $debug3);
        $this->assertNotSame($debug1, $debug3);

        // Each should contain the correct request URI
        $this->assertStringContainsString('async1', $debug1->getRequestData()['uri']);
        $this->assertStringContainsString('async2', $debug2->getRequestData()['uri']);
        $this->assertStringContainsString('async3', $debug3->getRequestData()['uri']);
    }

    public function test_debug_info_contains_timing_data(): void
    {
        // Create mock response
        $mockHandler = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['success' => true])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');
        $handler->withDebug(true);

        $response = $handler->get('/api/test');

        $this->assertTrue($response->hasDebugInfo());

        $debugInfo = $response->getDebugInfo();
        $debugArray = $debugInfo->toArray();

        // Should have request info
        $this->assertArrayHasKey('request', $debugArray);
        $this->assertEquals('GET', $debugArray['request']['method']);
        $this->assertStringContainsString('/api/test', $debugArray['request']['uri']);

        // Should have response info
        $this->assertArrayHasKey('response', $debugArray);
        $this->assertEquals(200, $debugArray['response']['status_code']);

        // Should have performance metrics
        $this->assertArrayHasKey('performance', $debugArray);
        $this->assertArrayHasKey('total_time', $debugArray['performance']);
        $this->assertGreaterThan(0, $debugArray['performance']['total_time']);
    }

    public function test_debug_info_attached_to_profiled_requests(): void
    {
        // Create mock responses
        $mockHandler = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['id' => 1])),
            new GuzzleResponse(200, [], json_encode(['id' => 2])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $profiler = new FetchProfiler();

        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');
        $handler->withProfiler($profiler);
        $handler->withDebug(true); // Enable debug mode to capture debug info

        // Fire two requests
        $response1 = $handler->get('/endpoint1');
        $response2 = $handler->get('/endpoint2');

        // Both responses should have debug info
        $this->assertTrue($response1->hasDebugInfo());
        $this->assertTrue($response2->hasDebugInfo());

        // Profiler should have tracked both requests
        $profiles = $profiler->getAllProfiles();
        $this->assertCount(2, $profiles);

        // Each response's debug info should be independent
        $debug1 = $response1->getDebugInfo();
        $debug2 = $response2->getDebugInfo();

        $this->assertNotSame($debug1, $debug2);
        $this->assertStringContainsString('endpoint1', $debug1->getRequestData()['uri']);
        $this->assertStringContainsString('endpoint2', $debug2->getRequestData()['uri']);
    }

    public function test_response_without_debug_mode_has_no_debug_info(): void
    {
        // Create mock response
        $mockHandler = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['success' => true])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');
        // Don't enable debug mode

        $response = $handler->get('/api/test');

        // Should not have debug info when debug mode is disabled
        $this->assertFalse($response->hasDebugInfo());
        $this->assertNull($response->getDebugInfo());
    }

    public function test_getLastDebugInfo_still_works_for_backward_compatibility(): void
    {
        // Create mock response
        $mockHandler = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['success' => true])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');
        $handler->withDebug(true);

        $response = $handler->get('/api/test');

        // Old API: getLastDebugInfo() should still work
        $lastDebugInfo = $handler->getLastDebugInfo();
        $this->assertNotNull($lastDebugInfo);

        // New API: response should also have debug info
        $responseDebugInfo = $response->getDebugInfo();
        $this->assertNotNull($responseDebugInfo);

        // They should be the same instance (for backward compatibility)
        $this->assertSame($lastDebugInfo, $responseDebugInfo);
    }

    public function test_mixed_sync_and_async_requests_have_isolated_debug_info(): void
    {
        // Create mock responses
        $mockHandler = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['type' => 'sync'])),
            new GuzzleResponse(200, [], json_encode(['type' => 'async1'])),
            new GuzzleResponse(200, [], json_encode(['type' => 'async2'])),
            new GuzzleResponse(200, [], json_encode(['type' => 'sync2'])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');
        $handler->withDebug(true);

        // Fire sync request
        $syncResponse1 = $handler->get('/sync1');
        $this->assertInstanceOf(\Fetch\Http\Response::class, $syncResponse1);

        // Clone handler for async requests to avoid mode mutation
        $asyncHandler = clone $handler;

        // Fire concurrent async requests
        $promises = [
            'async1' => $asyncHandler->async()->get('/async1'),
            'async2' => $asyncHandler->async()->get('/async2'),
        ];
        $asyncResponses = await(\Matrix\Support\all($promises));

        // Fire another sync request on original handler
        $syncResponse2 = $handler->get('/sync2');
        $this->assertInstanceOf(\Fetch\Http\Response::class, $syncResponse2);

        // All responses should have their own debug info
        $this->assertTrue($syncResponse1->hasDebugInfo());
        $this->assertTrue($asyncResponses['async1']->hasDebugInfo());
        $this->assertTrue($asyncResponses['async2']->hasDebugInfo());
        $this->assertTrue($syncResponse2->hasDebugInfo());

        // Each debug info should contain the correct URI
        $this->assertStringContainsString('sync1', $syncResponse1->getDebugInfo()->getRequestData()['uri']);
        $this->assertStringContainsString('async1', $asyncResponses['async1']->getDebugInfo()->getRequestData()['uri']);
        $this->assertStringContainsString('async2', $asyncResponses['async2']->getDebugInfo()->getRequestData()['uri']);
        $this->assertStringContainsString('sync2', $syncResponse2->getDebugInfo()->getRequestData()['uri']);
    }
}
