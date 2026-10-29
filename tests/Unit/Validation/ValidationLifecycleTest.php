<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use Fetch\Cache\MemoryCache;
use Fetch\Http\ClientHandler;
use Fetch\Interfaces\AsyncTransport;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;
use Fetch\Validation\RequestValidationException;
use Fetch\Validation\ResponseValidationException;
use Fetch\Validation\ValidationConfigurationException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\Utils;
use Matrix\Exceptions\CancellationException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use React\EventLoop\Loop;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

use function Matrix\Support\all;
use function Matrix\Support\await;

final class ValidationLifecycleTest extends TestCase
{
    public function test_invalid_request_payload_fails_before_transport_dispatch(): void
    {
        $mock = new MockHandler([new Psr7Response(200)]);
        $handler = $this->handler($mock);
        $handler->withRequestSchema($this->userSchema());

        try {
            $handler->post('https://example.test/users', ['name' => 42]);
            self::fail('An invalid outgoing payload should be rejected.');
        } catch (RequestValidationException $exception) {
            self::assertStringNotContainsString('42', $exception->getMessage());
            self::assertCount(1, $mock);
        }
    }

    public function test_invalid_response_is_not_cached_and_is_not_retried(): void
    {
        $mock = new MockHandler([
            new Psr7Response(200, ['Content-Type' => 'application/json'], '{"name":42}'),
            new Psr7Response(200, ['Content-Type' => 'application/json'], '{"name":42}'),
        ]);
        $handler = $this->handler($mock);
        $handler->withCache(new MemoryCache)->withResponseSchema($this->userSchema());

        try {
            $handler->get('https://example.test/users/1');
            self::fail('An invalid response should be rejected.');
        } catch (ResponseValidationException) {
            self::assertCount(1, $mock, 'Schema failures must not trigger retries or cache persistence.');
        }

        try {
            $handler->get('https://example.test/users/1');
            self::fail('The second request must dispatch because the invalid response was not cached.');
        } catch (ResponseValidationException) {
            self::assertCount(0, $mock);
        }
    }

    public function test_async_request_and_response_validation_fail_as_promise_rejections(): void
    {
        $mock = new MockHandler([new Psr7Response(200, ['Content-Type' => 'application/json'], '{"name":42}')]);
        $handler = $this->handler($mock);
        $handler->withRequestSchema($this->userSchema())
            ->withResponseSchema($this->userSchema())
            ->async();

        $invalidRequest = $handler->post('https://example.test/users', ['name' => 42])
            ->catch(static fn (\Throwable $error): \Throwable => $error);
        $requestError = await($invalidRequest);
        self::assertInstanceOf(RequestValidationException::class, $requestError);
        self::assertCount(1, $mock);

        $validRequest = $handler->post('https://example.test/users', ['name' => 'Ada'])
            ->catch(static fn (\Throwable $error): \Throwable => $error);
        $responseError = await($validRequest);
        self::assertInstanceOf(ResponseValidationException::class, $responseError);
        self::assertCount(0, $mock);
    }

    public function test_mocked_and_middleware_short_circuit_responses_are_validated(): void
    {
        $handler = new ClientHandler;
        $handler->withResponseSchema($this->userSchema());
        $handler->addMiddleware(static fn (RequestInterface $request, callable $next): Psr7Response => new Psr7Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"name":42}',
        ));

        $this->expectException(ResponseValidationException::class);
        $handler->get('https://example.test/users/1');
    }

    public function test_async_mock_and_middleware_short_circuits_reject_with_validation_errors(): void
    {
        MockServer::fake([
            'https://example.test/mock' => MockResponse::json(['name' => 42]),
        ]);

        try {
            $mockHandler = new ClientHandler;
            $mockHandler->withResponseSchema($this->userSchema())->async();
            $mockPromise = $mockHandler->get('https://example.test/mock');
            self::assertInstanceOf(PromiseInterface::class, $mockPromise);

            $mockError = await($mockPromise->catch(static fn (\Throwable $error): \Throwable => $error));
            self::assertInstanceOf(ResponseValidationException::class, $mockError);

            $middlewareHandler = new ClientHandler;
            $middlewareHandler->withResponseSchema($this->userSchema())->async();
            $middlewareHandler->addMiddleware(static fn (RequestInterface $request, callable $next): Psr7Response => new Psr7Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"name":42}',
            ));

            $middlewarePromise = $middlewareHandler->get('https://example.test/short-circuit');
            self::assertInstanceOf(PromiseInterface::class, $middlewarePromise);
            $middlewareError = await($middlewarePromise->catch(static fn (\Throwable $error): \Throwable => $error));
            self::assertInstanceOf(ResponseValidationException::class, $middlewareError);
        } finally {
            MockServer::resetInstance();
        }
    }

    public function test_short_circuit_still_validates_the_request_after_middleware(): void
    {
        $handler = new ClientHandler;
        $handler->withRequestSchema($this->userSchema());
        $handler->addMiddleware(static fn (RequestInterface $request, callable $next): Psr7Response => new Psr7Response(204));

        $this->expectException(RequestValidationException::class);
        $handler->post('https://example.test/users', ['name' => 42]);
    }

    public function test_explicit_middleware_transformations_run_outside_validation(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new Psr7Response(200, ['Content-Type' => 'application/json'], '{"name":"Ada"}'),
        ]));
        $stack->push(\GuzzleHttp\Middleware::history($history));
        $handler = ClientHandler::createWithClient(new GuzzleClient([
            'handler' => $stack,
            'http_errors' => false,
        ]));
        $handler->withRequestSchema($this->userSchema())
            ->withResponseSchema($this->userSchema());
        $handler->addMiddleware(static function (RequestInterface $request, callable $next): mixed {
            $payload = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $payload['name'] = trim($payload['name']);
            $request = $request->withBody(Utils::streamFor(json_encode($payload, JSON_THROW_ON_ERROR)));
            $response = $next($request);

            $transformResponse = static function (Psr7Response $response): Psr7Response {
                $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
                $data['name'] = strtolower($data['name']);

                return $response->withBody(Utils::streamFor(json_encode($data, JSON_THROW_ON_ERROR)));
            };

            return $response instanceof PromiseInterface
                ? $response->then($transformResponse)
                : $transformResponse($response);
        });

        $response = $handler->post('https://example.test/users', ['name' => ' Ada ']);

        self::assertSame('{"name":"Ada"}', (string) $history[0]['request']->getBody());
        self::assertSame(['name' => 'ada'], $response->json());
    }

    public function test_streaming_refuses_configured_response_validation(): void
    {
        $handler = new ClientHandler;
        $handler->withResponseSchema($this->userSchema());

        $this->expectException(ValidationConfigurationException::class);
        $this->expectExceptionMessage('cannot be schema-validated without buffering');

        $handler->stream('GET', 'https://example.test/events');
    }

    public function test_async_transport_allows_event_loop_progress_and_concurrent_requests(): void
    {
        $state = (object) ['active' => 0, 'maximum_active' => 0, 'loop_progressed' => false];
        $transport = new class($state) implements AsyncTransport
        {
            public function __construct(private \stdClass $state) {}

            public function requestAsync(string $method, string $uri, array $options): PromiseInterface
            {
                $deferred = new Deferred;
                $this->state->active++;
                $this->state->maximum_active = max($this->state->maximum_active, $this->state->active);
                Loop::addTimer(0.01, function () use ($deferred): void {
                    $this->state->active--;
                    $deferred->resolve(new Psr7Response(200, [], 'ok'));
                });

                return $deferred->promise();
            }
        };

        $handler = new ClientHandler;
        $handler->setAsyncTransport($transport);
        $handler->async();
        $first = $handler->get('https://example.test/one');
        $second = $handler->get('https://example.test/two');
        Loop::addTimer(0.001, static function () use ($state): void {
            $state->loop_progressed = true;
        });

        $responses = await(all([$first, $second]));

        self::assertCount(2, $responses);
        self::assertSame(2, $state->maximum_active);
        self::assertTrue($state->loop_progressed);
    }

    public function test_async_request_cancellation_reaches_the_configured_transport(): void
    {
        $state = (object) ['cancelled' => false];
        $transport = new class($state) implements AsyncTransport
        {
            public function __construct(private \stdClass $state) {}

            public function requestAsync(string $method, string $uri, array $options): PromiseInterface
            {
                $deferred = new Deferred(function (callable $resolve, callable $reject): void {
                    $this->state->cancelled = true;
                    $reject(new CancellationException);
                });

                return $deferred->promise();
            }
        };

        $handler = new ClientHandler;
        $handler->setAsyncTransport($transport);
        $promise = $handler->sendRequest('GET', 'https://example.test/slow', ['async' => true]);
        $observed = $promise->catch(static fn (\Throwable $error): \Throwable => $error);
        Loop::futureTick(static function () use ($promise): void {
            $promise->cancel();
        });

        $reason = await($observed);

        self::assertInstanceOf(CancellationException::class, $reason);
        self::assertTrue($state->cancelled);
    }

    private function handler(MockHandler $mock): ClientHandler
    {
        $client = new GuzzleClient([
            'handler' => HandlerStack::create($mock),
            'http_errors' => false,
        ]);

        return ClientHandler::createWithClient($client, new \Fetch\Http\GuzzleAsyncTransport($client, supportsQueueOnlyHandlers: true));
    }

    /** @return array<string, mixed> */
    private function userSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['name'],
            'properties' => ['name' => ['type' => 'string']],
        ];
    }
}
