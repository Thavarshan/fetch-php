<?php

namespace Tests\Integration;

use Fetch\Enum\Method;
use Fetch\Http\Client;
use Fetch\Http\ClientHandler;
use Fetch\Http\Response;
use Fetch\Interfaces\Response as ResponseInterface;
use PHPUnit\Framework\TestCase;
use React\Promise\PromiseInterface;

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
}
