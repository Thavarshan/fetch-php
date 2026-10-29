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
use Tests\TestCase;

final class ClientRequestOptionIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        MockServer::resetInstance();
        fetch_client(null, true);
    }

    protected function tearDown(): void
    {
        MockServer::resetInstance();
        fetch_client(null, true);
        parent::tearDown();
    }

    public function test_request_query_options_do_not_leak_to_the_next_global_helper_call(): void
    {
        MockServer::fake([
            'GET https://api.example.test/users*' => MockResponse::json(['users' => []]),
            'POST https://api.example.test/users' => MockResponse::created(['id' => 7]),
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
            'GET https://api.example.test/users?active=1' => MockResponse::json(['active' => true]),
            'GET https://api.example.test/users' => MockResponse::json(['active' => null]),
        ]);
        MockServer::preventStrayRequests();

        $client = new Client;
        $filtered = $client->get('https://api.example.test/users', ['active' => 1]);
        $all = $client->get('https://api.example.test/users');

        $this->assertSame(['active' => true], $filtered->json());
        $this->assertSame(['active' => null], $all->json());
    }

    public function test_base_uri_passed_to_fetch_does_not_mutate_the_global_handler(): void
    {
        MockServer::fake([
            'GET https://api.example.test/users' => MockResponse::json(['users' => []]),
        ]);
        MockServer::preventStrayRequests();

        $response = fetch('/users', ['base_uri' => 'https://api.example.test']);

        $this->assertSame(['users' => []], $response->json());
        $this->assertArrayNotHasKey('base_uri', fetch_client()->getHandler()->getOptions());
    }

    public function test_streaming_query_parameters_are_appended_once(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new GuzzleResponse(200, ['Content-Type' => 'text/plain'], "ready\n"),
        ]));
        $stack->push(Middleware::history($history));
        $handler = ClientHandler::createWithClient(new GuzzleClient(['handler' => $stack]));

        $stream = $handler->stream(Method::GET, 'https://api.example.test/logs', [
            'query' => ['cursor' => 'next'],
        ]);

        $this->assertSame('cursor=next', $history[0]['request']->getUri()->getQuery());
        $this->assertSame(['ready'], iterator_to_array($stream->lines(), false));
    }
}
