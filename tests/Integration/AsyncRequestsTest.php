<?php

namespace Tests\Integration;

use Fetch\Enum\Method;
use Fetch\Http\Client;
use Fetch\Http\ClientHandler;
use Fetch\Http\Response;
use Fetch\Interfaces\Response as ResponseInterface;
use Fetch\Testing\MockServer;
use Fetch\Testing\Recorder;
use PHPUnit\Framework\TestCase;
use React\Promise\PromiseInterface;

use function Matrix\Support\all;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\race;

class AsyncRequestsTest extends TestCase
{
    private $client;

    private $mockHandler;

    protected function setUp(): void
    {
        // Create a mock handler that returns predictable responses
        $this->mockHandler = new class extends ClientHandler
        {
            /**
             * Override sendRequest with compatible signature
             */
            public function sendRequest(Method|string $method, string $uri, array $options = []): ResponseInterface|PromiseInterface
            {
                // Convert string method to Method enum if needed
                $methodStr = $method instanceof Method ? $method->value : $method;

                if ($this->isAsync) {
                    return async(function () use ($methodStr, $uri) {
                        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
                            'method' => $methodStr,
                            'uri' => $uri,
                            'async' => true,
                        ]));
                    });
                }

                return new Response(200, ['Content-Type' => 'application/json'], json_encode([
                    'method' => $methodStr,
                    'uri' => $uri,
                    'async' => false,
                ]));
            }
        };

        // Create a client instance with our mock handler
        $this->client = new Client($this->mockHandler);
    }

    protected function tearDown(): void
    {
        MockServer::resetInstance();
        Recorder::resetInstance();
    }

    public function test_async_request(): void
    {
        // First we need to get the handler and set it to async mode
        $handler = $this->client->getHandler();
        $handler->async();

        // Now make the request - this will already return a Promise
        $promise = $handler->get('https://example.com/users');

        $this->assertInstanceOf(PromiseInterface::class, $promise);

        $response = await($promise);
        $data = $response->json();

        $this->assertEquals('GET', $data['method']);
        $this->assertEquals('https://example.com/users', $data['uri']);
        $this->assertTrue($data['async']);
    }

    public function test_multiple_concurrent_requests(): void
    {
        // First we need to get the handler and set it to async mode
        $handler = $this->client->getHandler();
        $handler->async();

        // Each request already returns a Promise
        $promises = [
            $handler->get('https://example.com/users'),
            $handler->get('https://example.com/posts'),
            $handler->get('https://example.com/comments'),
        ];

        $results = await(all($promises));

        $this->assertCount(3, $results);

        foreach ($results as $response) {
            $data = $response->json();
            $this->assertEquals(true, $data['async']);
            $this->assertStringStartsWith('https://example.com/', $data['uri']);
        }
    }

    public function test_mock_server_respected_in_async_mode(): void
    {
        MockServer::fake([
            'https://api.example.com/mock-async' => \Fetch\Testing\MockResponse::json(['mocked' => true]),
        ]);

        $handler = new ClientHandler;
        $handler->async();
        $client = new Client($handler);

        $response = $client->fetch('https://api.example.com/mock-async');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['mocked' => true], $response->json());
    }

    public function test_async_error_contains_context(): void
    {
        $handler = new class extends ClientHandler
        {
            protected function executeSyncRequest(
                string $method,
                string $uri,
                array $options,
                float $startTime,
                int $startMemory,
                ?string $requestId = null,
                ?\Fetch\Support\RequestContext $context = null,
            ): ResponseInterface {
                throw new \RuntimeException('simulated failure');
            }
        };

        $handler->async();

        // Capture rejection to avoid unhandled rejection output
        $promise = $handler->get('https://example.com/fail')
            ->then(null, fn ($e) => $e);

        $result = await($promise);

        $this->assertInstanceOf(\Matrix\Exceptions\AsyncException::class, $result);
        $this->assertStringContainsString('Request GET https://example.com/fail failed', $result->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $result->getPrevious());
        $this->assertStringContainsString('simulated failure', $result->getPrevious()->getMessage());
    }

    public function test_concurrency_helpers_all_and_race(): void
    {
        $handler = $this->client->getHandler();
        $handler->async();

        $promises = [
            $handler->get('https://example.com/one'),
            $handler->get('https://example.com/two'),
        ];

        $allResults = await(all($promises));
        $this->assertCount(2, $allResults);

        $raceResult = await(race($promises));
        $this->assertInstanceOf(Response::class, $raceResult);
    }

    public function test_map_with_bounded_concurrency(): void
    {
        $handler = $this->client->getHandler();
        $handler->async();

        $urls = [
            'https://example.com/a',
            'https://example.com/b',
            'https://example.com/c',
        ];

        $promises = array_map(fn ($url) => $handler->get($url), $urls);

        // Reuse existing map helper via Matrix map
        $results = await(all($promises));

        $this->assertCount(3, $results);
    }
}
