<?php

declare(strict_types=1);

namespace Tests\Integration;

use Fetch\Enum\Method;
use Fetch\Http\Client;
use Fetch\Http\ClientHandler;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Psr7\Request;
use Tests\TestCase;

/**
 * Verifies per-request options do not leak across shared handler calls.
 *
 * The cases include PSR-18 calls made while the handler is in async mode,
 * which must remain synchronous under that client contract.
 */
final class ClientRequestOptionIsolationTest extends TestCase
{
    public function test_psr18_stays_sync_with_an_async_handler(): void
    {
        $guzzle = new GuzzleClient([
            'handler' => HandlerStack::create(new MockHandler([
                new GuzzleResponse(200, [], 'synchronous'),
            ])),
        ]);
        $handler = ClientHandler::createWithClient($guzzle)->async();
        $client = new Client($handler);

        $response = $client->sendRequest(new Request(
            'GET',
            'https://api.example.test/status',
        ));

        self::assertSame('synchronous', (string) $response->getBody());
        self::assertTrue($handler->isAsync());
    }

    /**
     * Resets the global client and mock server before each test.
     */
    protected function setUp(): void
    {
        MockServer::resetInstance();
        fetch_client(null, true);
    }

    /**
     * Resets global client state after each option-isolation test.
     */
    protected function tearDown(): void
    {
        MockServer::resetInstance();
        fetch_client(null, true);
        parent::tearDown();
    }

    public function test_query_options_do_not_leak_between_global_calls(): void
    {
        MockServer::fake([
            'GET https://api.example.test/users*' => MockResponse::json([
                'users' => [],
            ]),
            'POST https://api.example.test/users' => MockResponse::created([
                'id' => 7,
            ]),
        ]);
        MockServer::preventStrayRequests();

        $users = get('https://api.example.test/users', ['active' => true]);
        $created = post('https://api.example.test/users', ['name' => 'Ada']);

        $this->assertSame(['users' => []], $users->json());
        $this->assertSame(['id' => 7], $created->json());
        MockServer::assertSent('POST https://api.example.test/users', 1);
    }

    public function test_client_query_options_do_not_leak_between_calls(): void
    {
        MockServer::fake([
            'GET https://api.example.test/users?active=1' => MockResponse::json(
                ['active' => true],
            ),
            'GET https://api.example.test/users' => MockResponse::json([
                'active' => null,
            ]),
        ]);
        MockServer::preventStrayRequests();

        $client = new Client;
        $filtered = $client->get('https://api.example.test/users', [
            'active' => 1,
        ]);
        $all = $client->get('https://api.example.test/users');

        $this->assertSame(['active' => true], $filtered->json());
        $this->assertSame(['active' => null], $all->json());
    }

    public function test_fetch_base_uri_does_not_mutate_global_handler(): void
    {
        MockServer::fake([
            'GET https://api.example.test/users' => MockResponse::json([
                'users' => [],
            ]),
        ]);
        MockServer::preventStrayRequests();

        $response = fetch('/users', ['base_uri' => 'https://api.example.test']);

        $this->assertSame(['users' => []], $response->json());
        $this->assertArrayNotHasKey(
            'base_uri',
            fetch_client()->getHandler()->getOptions(),
        );
    }

    public function test_streaming_query_parameters_are_appended_once(): void
    {
        $history = [];
        $stack = HandlerStack::create(
            new MockHandler([
                new GuzzleResponse(
                    200,
                    ['Content-Type' => 'text/plain'],
                    "ready\n",
                ),
            ]),
        );
        $stack->push(Middleware::history($history));
        $handler = ClientHandler::createWithClient(
            new GuzzleClient(['handler' => $stack]),
        );

        $stream = $handler->stream(
            Method::GET,
            'https://api.example.test/logs',
            [
                'query' => ['cursor' => 'next'],
            ],
        );

        $this->assertSame(
            'cursor=next',
            $history[0]['request']->getUri()->getQuery(),
        );
        $this->assertSame(
            ['ready'],
            iterator_to_array($stream->lines(), false),
        );
    }
}
