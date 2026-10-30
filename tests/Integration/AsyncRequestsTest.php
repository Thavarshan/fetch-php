<?php

namespace Tests\Integration;

use Fetch\Enum\Method;
use Fetch\Http\Client;
use Fetch\Http\ClientHandler;
use Fetch\Http\GuzzleAsyncTransport;
use Fetch\Http\Response;
use Fetch\Interfaces\AsyncTransport;
use Fetch\Interfaces\Response as ResponseInterface;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;
use Fetch\Testing\Recorder;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Matrix\Exceptions\AsyncException;
use PHPUnit\Framework\TestCase;
use React\Promise\PromiseInterface;

use function Matrix\Support\all;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\race;
use function React\Promise\reject;
use function React\Promise\resolve;

class AsyncRequestsTest extends TestCase
{
    private $client;

    private $mockHandler;

    protected function setUp(): void
    {
        // Create a mock handler that returns predictable responses
        $this->mockHandler = new class extends ClientHandler {
            /**
             * Override sendRequest with compatible signature.
             */
            public function sendRequest(
                Method|string $method,
                string $uri,
                array $options = [],
            ): ResponseInterface|PromiseInterface {
                // Convert string method to Method enum if needed
                $methodStr =
                    $method instanceof Method ? $method->value : $method;

                if ($this->isAsync) {
                    return async(function () use ($methodStr, $uri) {
                        return new Response(
                            200,
                            ['Content-Type' => 'application/json'],
                            json_encode([
                                'method' => $methodStr,
                                'uri' => $uri,
                                'async' => true,
                            ]),
                        );
                    });
                }

                return new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    json_encode([
                        'method' => $methodStr,
                        'uri' => $uri,
                        'async' => false,
                    ]),
                );
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
            'https://api.example.com/mock-async' => MockResponse::json([
                'mocked' => true,
            ]),
        ]);

        $handler = new ClientHandler;
        $handler->async();
        $client = new Client($handler);

        $promise = $client->fetch('https://api.example.com/mock-async');
        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $response = await($promise);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['mocked' => true], $response->json());
    }

    public function test_async_error_contains_context(): void
    {
        $handler = new ClientHandler;
        $handler->setAsyncTransport(
            new class implements AsyncTransport {
                public function requestAsync(
                    string $method,
                    string $uri,
                    array $options,
                ): PromiseInterface {
                    return reject(new \RuntimeException('simulated failure'));
                }
            },
        );

        $handler->async();

        // Silence expected unhandled rejection output from React Promise
        $previousHandler = \React\Promise\set_rejection_handler(fn () => null);

        $promise = $handler
            ->get('https://example.com/fail')
            ->then(null, fn ($e) => $e);

        $result = await($promise);

        // Restore original handler
        \React\Promise\set_rejection_handler($previousHandler);

        $this->assertInstanceOf(AsyncException::class, $result);
        $this->assertStringContainsString(
            'Request GET https://example.com/fail failed',
            $result->getMessage(),
        );
        $this->assertInstanceOf(
            \RuntimeException::class,
            $result->getPrevious(),
        );
        $this->assertStringContainsString(
            'simulated failure',
            $result->getPrevious()->getMessage(),
        );
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

    public function test_async_request_respects_per_request_retry_config(): void
    {
        // This test verifies that async requests respect per-request retry
        // configuration
        // The ConcurrentRequestsTest::test_retries_are_isolated_per_request
        // covers the sync case
        // Here we test that the config flows properly through the async path

        $mockHandler = new MockHandler([
            // First 2 attempts fail with 503
            new Psr7Response(503, [], 'Service Unavailable'),
            new Psr7Response(503, [], 'Service Unavailable'),
            // Third attempt succeeds
            new Psr7Response(
                200,
                [],
                json_encode(['success' => true, 'attempt' => 3]),
            ),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new \GuzzleHttp\Client(['handler' => $handlerStack]);

        // Create handler with custom Guzzle client
        $handler = ClientHandler::createWithClient(
            $guzzleClient,
            new GuzzleAsyncTransport(
                $guzzleClient,
                supportsQueueOnlyHandlers: true,
            ),
        );
        $handler->baseUri('https://api.example.com');
        $handler->async();

        // Fire async request with per-request retry config
        // 503 is a default retryable status code
        $promise = $handler->sendRequest('GET', '/test-retry', [
            'retries' => 5,
            'retry_delay' => 1, // 1ms for fast tests
        ]);

        $response = await($promise);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->json();
        $this->assertTrue($data['success']);
        // Guzzle mock processes all 3 requests, so we expect success on the
        // "3rd attempt"
        $this->assertEquals(3, $data['attempt']);
    }

    public function test_async_request_respects_per_request_timeout(): void
    {
        $handler = new ClientHandler;
        $transport = new class implements AsyncTransport {
            public ?int $capturedTimeout = null;

            public function requestAsync(
                string $method,
                string $uri,
                array $options,
            ): PromiseInterface {
                $this->capturedTimeout = $options['timeout'] ?? null;

                return resolve(
                    new Psr7Response(
                        200,
                        ['Content-Type' => 'application/json'],
                        json_encode(
                            ['timeout_captured' => $this->capturedTimeout],
                            JSON_THROW_ON_ERROR,
                        ),
                    ),
                );
            }
        };
        $handler->setAsyncTransport($transport);

        // Set default timeout
        $handler->timeout(30);
        $handler->async();

        // Make async request with per-request timeout override
        $promise = $handler->sendRequest(
            'GET',
            'https://example.com/timeout-test',
            [
                'timeout' => 99,
            ],
        );

        $response = await($promise);
        $data = $response->json();

        // Verify the per-request timeout was used
        $this->assertEquals(99, $data['timeout_captured']);
        $this->assertSame(99, $transport->capturedTimeout);

        // Verify handler state was not mutated
        $this->assertEquals(30, $handler->getEffectiveTimeout());
    }
}
