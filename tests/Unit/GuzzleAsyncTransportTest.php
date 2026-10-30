<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Exceptions\AsyncTransportException;
use Fetch\Http\ClientHandler;
use Fetch\Http\GuzzleAsyncTransport;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

use function Matrix\Support\await;

class GuzzleAsyncTransportTest extends TestCase
{
    public function test_curl_pump_settles_a_pending_request(): void
    {
        $pending = new Promise;
        $client = $this->createMock(ClientInterface::class);
        $client->method('requestAsync')->willReturn($pending);
        $driver = $this->createMock(CurlMultiHandler::class);
        $driver->expects(self::once())->method('tick')
            ->willReturnCallback(static function () use ($pending): void {
                $pending->resolve(new Response(201));
            });
        $transport = new GuzzleAsyncTransport($client, $driver);

        $response = await($transport->requestAsync(
            'GET',
            'https://example.test/',
            [],
        ));

        self::assertSame(201, $response->getStatusCode());
    }

    public function test_cancellation_stops_the_pump_and_guzzle_request(): void
    {
        $cancelled = false;
        $pending = new Promise(null, static function () use (
            &$cancelled,
        ): void {
            $cancelled = true;
        });
        $client = $this->createMock(ClientInterface::class);
        $client->method('requestAsync')->willReturn($pending);
        $driver = $this->createMock(CurlMultiHandler::class);
        $driver->expects(self::never())->method('tick');
        $transport = new GuzzleAsyncTransport($client, $driver);
        $promise = $transport->requestAsync(
            'GET',
            'https://example.test/',
            [],
        );
        $observed = $promise->catch(static fn (\Throwable $error) => $error);

        $promise->cancel();

        self::assertInstanceOf(
            \Matrix\Exceptions\CancellationException::class,
            await($observed),
        );
        self::assertTrue($cancelled);
        self::assertSame(Promise::REJECTED, $pending->getState());
    }

    public function test_queue_only_clients_require_explicit_opt_in(): void
    {
        $mock = new MockHandler([new Response(200)]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $transport = new GuzzleAsyncTransport($client);
        $promise = $transport->requestAsync('GET', 'https://example.test/', []);
        $observed = $promise->catch(
            static fn (\Throwable $error): \Throwable => $error,
        );

        $reason = await($observed);

        self::assertInstanceOf(AsyncTransportException::class, $reason);
        self::assertCount(
            1,
            $mock,
            'The Guzzle handler must not be invoked without a loop driver.',
        );
    }

    public function test_handler_preserves_missing_driver_exception(): void
    {
        $client = new Client([
            'handler' => HandlerStack::create(new MockHandler),
        ]);
        $handler = ClientHandler::createWithClient(
            $client,
            new GuzzleAsyncTransport($client),
        );
        $promise = $handler->async()->get('https://example.test/');
        $reason = await(
            $promise->catch(static fn (\Throwable $error) => $error),
        );

        self::assertInstanceOf(AsyncTransportException::class, $reason);
    }
}
