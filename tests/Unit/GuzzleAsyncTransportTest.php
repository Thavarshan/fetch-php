<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Exceptions\AsyncTransportException;
use Fetch\Http\ClientHandler;
use Fetch\Http\GuzzleAsyncTransport;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

use function Matrix\Support\await;

class GuzzleAsyncTransportTest extends TestCase
{
    public function test_transport_refuses_queue_only_clients_before_dispatch_unless_explicitly_enabled(): void
    {
        $mock = new MockHandler([new Response(200)]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $transport = new GuzzleAsyncTransport($client);
        $promise = $transport->requestAsync('GET', 'https://example.test/', []);
        $observed = $promise->catch(static fn (\Throwable $error): \Throwable => $error);

        $reason = await($observed);

        self::assertInstanceOf(AsyncTransportException::class, $reason);
        self::assertCount(1, $mock, 'The Guzzle handler must not be invoked without a loop driver.');
    }

    public function test_handler_preserves_the_explicit_missing_driver_exception(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler)]);
        $handler = ClientHandler::createWithClient($client, new GuzzleAsyncTransport($client));
        $promise = $handler->async()->get('https://example.test/');
        $reason = await($promise->catch(static fn (\Throwable $error): \Throwable => $error));

        self::assertInstanceOf(AsyncTransportException::class, $reason);
    }
}
