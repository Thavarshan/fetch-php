<?php

declare(strict_types=1);

namespace Tests\Integration;

use Fetch\Cache\MemoryCache;
use Fetch\Http\Client;
use Fetch\Http\ClientHandler;
use Fetch\Support\FetchProfiler;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;
use Fetch\Testing\Recorder;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use React\Promise\PromiseInterface;

use function Matrix\Support\await;

class DebugFlowTest extends TestCase
{
    protected function setUp(): void
    {
        MockServer::resetInstance();
        Recorder::resetInstance();
    }

    protected function tearDown(): void
    {
        MockServer::resetInstance();
        Recorder::resetInstance();
    }

    public function test_debug_info_recorded_for_sync_request(): void
    {
        $handler = $this->makeMockHandler();
        $handler->withDebug(true);

        $client = new Client($handler);
        $response = $client->get('https://example.com/users');

        $this->assertSame(200, $response->getStatusCode());

        $debug = $handler->getLastDebugInfo();
        $this->assertNotNull($debug);
        $this->assertSame('GET', $debug->getRequestData()['method']);
        $this->assertSame('https://example.com/users', $debug->getRequestData()['uri']);
        $this->assertSame(200, $debug->getResponse()?->getStatusCode());
        $this->assertArrayHasKey('total_time', $debug->getTimings());
    }

    public function test_debug_info_for_cached_response(): void
    {
        $handler = $this->makeMockHandler([
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"cached":1}'),
            new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"cached":2}'),
        ]);
        $handler->baseUri('https://api.example.com');
        $handler->withDebug(true);
        $handler->withCache(new MemoryCache);

        $client = new Client($handler);

        $first = $client->get('/cached');
        $second = $client->get('/cached');

        $this->assertSame($first->body(), $second->body());
        $debug = $handler->getLastDebugInfo();
        $this->assertNotNull($debug);
        $this->assertSame('GET', $debug->getRequestData()['method']);
        $this->assertSame('https://api.example.com/cached', $debug->getRequestData()['uri']);
    }

    public function test_debug_info_for_mocked_response(): void
    {
        $handler = $this->makeMockHandler();
        $handler->withDebug(true);

        MockServer::fake([
            'https://example.com/mock' => MockResponse::json(['mocked' => true]),
        ]);

        $client = new Client($handler);
        $response = $client->get('https://example.com/mock');

        $this->assertSame(200, $response->getStatusCode());
        $debug = $handler->getLastDebugInfo();
        $this->assertNotNull($debug);
        $this->assertSame('https://example.com/mock', $debug->getRequestData()['uri']);
        $this->assertSame(200, $debug->getResponse()?->getStatusCode());
    }

    public function test_profiler_attached_and_toggle_debug_off(): void
    {
        $handler = $this->makeMockHandler();
        $profiler = new FetchProfiler;

        $handler->withProfiler($profiler)->withDebug(false);
        $client = new Client($handler);
        $client->get('https://example.com/no-debug');

        $this->assertNull($handler->getLastDebugInfo(), 'Debug info should not be recorded when disabled');
        $this->assertCount(1, $profiler->getAllProfiles());
    }

    public function test_async_request_captures_debug_info(): void
    {
        $handler = $this->makeMockHandler();
        $handler->async()->withDebug(true);

        $promise = $handler->get('https://example.com/async');

        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $response = await($promise);

        $this->assertSame(200, $response->getStatusCode());
        $debug = $handler->getLastDebugInfo();
        $this->assertNotNull($debug);
        $this->assertSame('https://example.com/async', $debug->getRequestData()['uri']);
    }

    /**
     * @param  array<int, GuzzleResponse>|null  $responses
     */
    private function makeMockHandler(?array $responses = null): ClientHandler
    {
        $mockResponses = $responses ?? [new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"ok":true}')];

        return new class($mockResponses) extends ClientHandler
        {
            public function __construct(private array $mockResponses)
            {
                parent::__construct();
            }

            public function getHttpClient(): \GuzzleHttp\ClientInterface
            {
                if ($this->httpClient === null) {
                    $mock = new MockHandler($this->mockResponses);
                    $stack = HandlerStack::create($mock);
                    $this->httpClient = new GuzzleClient(['handler' => $stack, 'http_errors' => false]);
                }

                return $this->httpClient;
            }
        };
    }
}
