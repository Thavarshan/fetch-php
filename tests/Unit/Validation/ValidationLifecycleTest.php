<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use Fetch\Cache\MemoryCache;
use Fetch\Enum\ContentType;
use Fetch\Http\ClientHandler;
use Fetch\Http\GuzzleAsyncTransport;
use Fetch\Interfaces\AsyncTransport;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;
use Fetch\Validation\RequestValidationException;
use Fetch\Validation\ResponseValidationException;
use Fetch\Validation\ValidationConfigurationException;
use Fetch\Validation\ValidationDependencyException;
use Fetch\Validation\ValidationManager;
use Fetch\Validation\ValidatorInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\Utils;
use Matrix\Exceptions\CancellationException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Loop;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

use function Matrix\Support\all;
use function Matrix\Support\await;

final class ValidationLifecycleTest extends TestCase
{
    public function test_adapter_errors_preserve_their_types(): void
    {
        foreach ([false, true] as $async) {
            foreach ([
                new ValidationDependencyException('Missing fixture adapter.'),
                new ValidationConfigurationException('Invalid fixture schema.'),
            ] as $failure) {
                MockServer::fake([
                    'GET https://example.test/schema' =>
                        MockResponse::json(['id' => 42]),
                ]);
                $validator = $this->createMock(ValidatorInterface::class);
                $validator->method('validate')->willThrowException($failure);
                $handler = new ClientHandler(
                    validationManager: new ValidationManager($validator),
                );
                $handler->withResponseSchema(['type' => 'object'])
                    ->async($async);
                $caught = null;

                try {
                    $response = $handler->get('https://example.test/schema');
                    if ($response instanceof PromiseInterface) {
                        await($response);
                    }
                } catch (\Throwable $exception) {
                    $caught = $exception;
                }

                self::assertSame($failure, $caught);
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        MockServer::resetInstance();
    }

    protected function tearDown(): void
    {
        MockServer::resetInstance();
        parent::tearDown();
    }

    public function test_invalid_request_fails_before_dispatch(): void
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

    public function test_json_preserves_object_and_array_shapes(): void
    {
        $mock = new MockHandler([
            new Psr7Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"empty":{},"sequential":{"0":"first","1":"second"},'
                . '"nested":[{}]}',
            ),
        ]);
        $handler = $this->handler($mock);
        $handler->withResponseSchema([
            'type' => 'object',
            'properties' => [
                'empty' => ['type' => 'object'],
                'sequential' => ['type' => 'object'],
                'nested' => [
                    'type' => 'array',
                    'items' => ['type' => 'object'],
                ],
            ],
        ]);

        self::assertSame(
            200,
            $handler->get('https://example.test/users')->getStatusCode(),
        );
        self::assertCount(0, $mock);
    }

    public function test_object_does_not_satisfy_array_schema(): void
    {
        $handler = $this->handler(
            new MockHandler([
                new Psr7Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    '{}',
                ),
            ]),
        );
        $handler->withResponseSchema(['type' => 'array']);

        $this->expectException(ResponseValidationException::class);
        $handler->get('https://example.test/users');
    }

    public function test_request_json_preserves_object_shapes(): void
    {
        $handler = $this->handler(new MockHandler([new Psr7Response(204)]));
        $handler->withRequestSchema([
            'type' => 'object',
            'properties' => [
                'empty' => ['type' => 'object'],
                'sequential' => ['type' => 'object'],
                'nested' => [
                    'type' => 'array',
                    'items' => ['type' => 'object'],
                ],
            ],
        ]);
        $json =
            '{"empty":{},"sequential":{"0":"first","1":"second"},'
                . '"nested":[{}]}';

        $response = $handler->request(
            'POST',
            'https://example.test/users',
            $json,
            ContentType::JSON,
        );

        self::assertSame(204, $response->getStatusCode());
    }

    public function test_validates_psr_stream_before_dispatch(): void
    {
        $body = Utils::streamFor('invalid-prefix{"name":"Ada"}');
        $body->seek(strlen('invalid-prefix'));
        $handler = $this->handler(new MockHandler([new Psr7Response(204)]));
        $handler->withRequestSchema($this->userSchema());

        $response = $handler->request(
            'POST',
            'https://example.test/users',
            $body,
            ContentType::JSON,
        );

        self::assertSame(204, $response->getStatusCode());
    }

    public function test_validates_resource_body_before_dispatch(): void
    {
        $body = fopen('php://temp', 'r+');
        self::assertIsResource($body);
        fwrite($body, 'invalid-prefix{"name":"Ada"}');
        fseek($body, strlen('invalid-prefix'));

        $handler = $this->handler(new MockHandler([new Psr7Response(204)]));
        $handler->withRequestSchema($this->userSchema());
        $response = $handler->request(
            'POST',
            'https://example.test/users',
            $body,
            ContentType::JSON,
        );

        self::assertSame(204, $response->getStatusCode());
    }

    public function test_validates_raw_json_after_middleware_reads(): void
    {
        $mock = new MockHandler([new Psr7Response(204)]);
        $handler = $this->handler($mock);
        $handler->withRequestSchema($this->userSchema());
        $handler->addMiddleware(static function (
            RequestInterface $request,
            callable $next,
        ): mixed {
            $request->getBody()->getContents();

            return $next($request);
        });

        $response = $handler->request(
            'POST',
            'https://example.test/users',
            '{"name":"Ada"}',
            ContentType::JSON,
        );

        self::assertSame(204, $response->getStatusCode());
    }

    public function test_validates_in_place_middleware_edits(): void
    {
        $mock = new MockHandler([new Psr7Response(204)]);
        $handler = $this->handler($mock);
        $handler->withRequestSchema($this->userSchema());
        $handler->addMiddleware(static function (
            RequestInterface $request,
            callable $next,
        ): mixed {
            $request->getBody()->write('{"name":"Grace"}');

            return $next($request);
        });

        $response = $handler->request(
            'POST',
            'https://example.test/users',
            '{"name":"Ada"}',
            ContentType::JSON,
        );

        self::assertSame(204, $response->getStatusCode());
        self::assertSame(
            '{"name":"Grace"}',
            (string) $mock->getLastRequest()?->getBody(),
        );
    }

    public function test_contract_preserves_multipart_boundary(): void
    {
        $mock = new MockHandler([new Psr7Response(204)]);
        $spec = <<<'YAML'
        openapi: 3.0.3
        info:
          title: Upload API
          version: 1.0.0
        paths:
          /upload:
            post:
              requestBody:
                required: true
                content:
                  multipart/form-data:
                    schema:
                      type: object
                      required: [title]
                      properties:
                        title:
                          type: string
              responses:
                '204':
                  description: Uploaded
        YAML;

        $this->withOpenApiSpec($spec, function (string $path) use (
            $mock,
        ): void {
            $handler = $this->handler($mock);
            $handler->withOpenApiSpec($path)->validateContracts();

            $response = $handler->post(
                'https://example.test/upload',
                [['name' => 'title', 'contents' => 'quarterly report']],
                ContentType::MULTIPART,
            );
            $request = $mock->getLastRequest();

            self::assertSame(204, $response->getStatusCode());
            self::assertNotNull($request);
            self::assertMatchesRegularExpression(
                '/^multipart\/form-data; boundary=(.+)$/',
                $request->getHeaderLine('Content-Type'),
            );
            self::assertStringContainsString(
                'name="title"',
                (string) $request->getBody(),
            );
            self::assertStringContainsString(
                'quarterly report',
                (string) $request->getBody(),
            );
        });
    }

    public function test_contract_includes_basic_auth_options(): void
    {
        $mock = new MockHandler([new Psr7Response(200)]);
        $spec = <<<'YAML'
        openapi: 3.0.3
        info:
          title: Secure API
          version: 1.0.0
        components:
          securitySchemes:
            basicAuth:
              type: http
              scheme: basic
        paths:
          /secure:
            get:
              security:
                - basicAuth: []
              responses:
                '200':
                  description: Secure response
        YAML;

        $this->withOpenApiSpec($spec, function (string $path) use (
            $mock,
        ): void {
            $handler = $this->handler($mock);
            $handler->withOpenApiSpec($path)->validateContracts();

            $response = $handler->sendRequest(
                'GET',
                'https://example.test/secure',
                ['auth' => ['fixture-user', 'fixture-password']],
            );

            self::assertSame(200, $response->getStatusCode());
            self::assertSame(
                'Basic '.base64_encode('fixture-user:fixture-password'),
                $mock->getLastRequest()?->getHeaderLine('Authorization'),
            );
        });
    }

    public function test_contract_includes_matching_jar_cookies(): void
    {
        $mock = new MockHandler([new Psr7Response(200)]);
        $cookies = CookieJar::fromArray(
            ['session' => 'fixture-session'],
            'example.test',
        );
        $spec = <<<'YAML'
        openapi: 3.0.3
        info:
          title: Cookie API
          version: 1.0.0
        components:
          securitySchemes:
            sessionCookie:
              type: apiKey
              in: cookie
              name: session
        paths:
          /secure:
            get:
              security:
                - sessionCookie: []
              parameters:
                - name: session
                  in: cookie
                  required: true
                  schema:
                    type: string
              responses:
                '200':
                  description: Secure response
        YAML;

        $this->withOpenApiSpec($spec, function (string $path) use (
            $mock,
            $cookies,
        ): void {
            $handler = $this->handler($mock);
            $handler->withOpenApiSpec($path)->validateContracts();

            $response = $handler->sendRequest(
                'GET',
                'https://example.test/secure',
                ['cookies' => $cookies],
            );

            self::assertSame(200, $response->getStatusCode());
            self::assertSame(
                'session=fixture-session',
                $mock->getLastRequest()?->getHeaderLine('Cookie'),
            );
        });
    }

    public function test_contract_uses_replaced_query_option(): void
    {
        $mock = new MockHandler([new Psr7Response(204)]);
        $spec = <<<'YAML'
        openapi: 3.0.3
        info:
          title: Query API
          version: 1.0.0
        paths:
          /items:
            get:
              parameters:
                - name: required
                  in: query
                  required: true
                  schema:
                    type: string
              responses:
                '204':
                  description: Empty response
        YAML;

        $this->withOpenApiSpec($spec, function (string $path) use (
            $mock,
        ): void {
            $handler = $this->handler($mock);
            $handler->withOpenApiSpec($path)->validateContracts();

            try {
                $handler->sendRequest(
                    'GET',
                    'https://example.test/items?required=yes',
                    ['query' => ['page' => 1]],
                );
                self::fail(
                    'A query parameter replaced by Guzzle should not '
                        .'satisfy the original contract parameter.',
                );
            } catch (RequestValidationException) {
                self::assertCount(
                    1,
                    $mock,
                    'Invalid query options must fail before transport '
                        .'dispatch.',
                );
            }
        });
    }

    public function test_contract_and_transport_share_query(): void
    {
        $mock = new MockHandler([new Psr7Response(204)]);
        $spec = <<<'YAML'
        openapi: 3.0.3
        info:
          title: Query API
          version: 1.0.0
        paths:
          /items:
            get:
              parameters:
                - name: search
                  in: query
                  required: true
                  schema:
                    type: string
              responses:
                '204':
                  description: Empty response
        YAML;

        $this->withOpenApiSpec($spec, function (string $path) use (
            $mock,
        ): void {
            $handler = $this->handler($mock);
            $handler->withOpenApiSpec($path)->validateContracts();

            $response = $handler->get('https://example.test/items?old=yes', [
                'search' => 'two words',
            ]);

            self::assertSame(204, $response->getStatusCode());
            self::assertSame(
                'search=two%20words',
                $mock->getLastRequest()?->getUri()->getQuery(),
            );
        });
    }

    public function test_unvalidated_upload_is_not_buffered(): void
    {
        $reads = 0;
        $body = Utils::streamFor(static function (int $length) use (
            &$reads,
        ): ?string {
            $reads++;

            return $reads === 1 ? 'streamed upload' : null;
        });
        $mock = new MockHandler([new Psr7Response(204)]);
        $handler = $this->handler($mock);

        $response = $handler->post(
            'https://example.test/upload',
            $body,
            ContentType::TEXT,
        );

        self::assertSame(204, $response->getStatusCode());
        self::assertSame(
            0,
            $reads,
            'Requests without validation must not pull upload stream '
                .'contents into memory.',
        );
        self::assertSame($body, $mock->getLastRequest()?->getBody());
    }

    public function test_invalid_response_is_not_cached_or_retried(): void
    {
        $mock = new MockHandler([
            new Psr7Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"name":42}',
            ),
            new Psr7Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"name":42}',
            ),
        ]);
        $handler = $this->handler($mock);
        $handler
            ->withCache(new MemoryCache)
            ->withResponseSchema($this->userSchema());

        try {
            $handler->get('https://example.test/users/1');
            self::fail('An invalid response should be rejected.');
        } catch (ResponseValidationException) {
            self::assertCount(
                1,
                $mock,
                'Schema failures must not trigger retries or cache '
                    .'persistence.',
            );
        }

        try {
            $handler->get('https://example.test/users/1');
            self::fail(
                'The second request must dispatch because the invalid '
                    .'response was not cached.',
            );
        } catch (ResponseValidationException) {
            self::assertCount(0, $mock);
        }
    }

    public function test_async_validation_rejects_promises(): void
    {
        $mock = new MockHandler([
            new Psr7Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"name":42}',
            ),
        ]);
        $handler = $this->handler($mock);
        $handler
            ->withRequestSchema($this->userSchema())
            ->withResponseSchema($this->userSchema())
            ->async();

        $invalidRequest = $handler
            ->post('https://example.test/users', ['name' => 42])
            ->catch(static fn (\Throwable $error): \Throwable => $error);
        $requestError = await($invalidRequest);
        self::assertInstanceOf(
            RequestValidationException::class,
            $requestError,
        );
        self::assertCount(1, $mock);

        $validRequest = $handler
            ->post('https://example.test/users', ['name' => 'Ada'])
            ->catch(static fn (\Throwable $error): \Throwable => $error);
        $responseError = await($validRequest);
        self::assertInstanceOf(
            ResponseValidationException::class,
            $responseError,
        );
        self::assertCount(0, $mock);
    }

    public function test_async_middleware_transforms_before_dispatch(): void
    {
        $state = (object) [
            'dispatches' => 0,
            'request_body' => null,
            'request_events' => 0,
            'response_events' => 0,
            'error_events' => 0,
        ];
        $transport = new class($state) implements AsyncTransport {
            public function __construct(private \stdClass $state) {}

            public function requestAsync(
                string $method,
                string $uri,
                array $options,
            ): PromiseInterface {
                $this->state->dispatches++;
                $this->state->request_body = isset($options['body'])
                    ? (string) $options['body']
                    : null;

                return \React\Promise\resolve(new Psr7Response(204));
            }
        };
        $handler = new ClientHandler;
        $handler
            ->setAsyncTransport($transport)
            ->withRequestSchema($this->userSchema())
            ->async();
        $handler->onRequest(static function () use ($state): void {
            $state->request_events++;
        });
        $handler->onResponse(static function () use ($state): void {
            $state->response_events++;
        });
        $handler->onError(static function () use ($state): void {
            $state->error_events++;
        });
        $handler->addMiddleware(static function (
            RequestInterface $request,
            callable $next,
        ): PromiseInterface {
            $deferred = new Deferred;
            Loop::futureTick(static function () use (
                $request,
                $next,
                $deferred,
            ): void {
                $contents = '{"name":"Ada"}';
                $body = Utils::streamFor(static function (int $length) use (
                    &$contents,
                ): ?string {
                    if ($contents === '') {
                        return null;
                    }

                    $chunk = $contents;
                    $contents = '';

                    return $chunk;
                });
                $result = $next($request->withBody($body));
                self::assertInstanceOf(PromiseInterface::class, $result);
                $result->then(
                    static fn (mixed $response): mixed => $deferred->resolve(
                        $response,
                    ),
                    static fn (\Throwable $error): mixed => $deferred->reject(
                        $error,
                    ),
                );
            });

            return $deferred->promise();
        });

        $result = await(
            $handler
                ->post('https://example.test/users', ['name' => 42])
                ->catch(static fn (\Throwable $error): \Throwable => $error),
        );

        self::assertInstanceOf(ResponseInterface::class, $result);
        self::assertSame(1, $state->dispatches);
        self::assertSame('{"name":"Ada"}', $state->request_body);
        self::assertSame(1, $state->request_events);
        self::assertSame(1, $state->response_events);
        self::assertSame(0, $state->error_events);
    }

    public function test_async_short_circuit_validates_latest_request(): void
    {
        $state = (object) [
            'dispatches' => 0,
            'request_events' => 0,
            'response_events' => 0,
            'error_events' => 0,
        ];
        $transport = new class($state) implements AsyncTransport {
            public function __construct(private \stdClass $state) {}

            public function requestAsync(
                string $method,
                string $uri,
                array $options,
            ): PromiseInterface {
                $this->state->dispatches++;

                return \React\Promise\resolve(new Psr7Response(204));
            }
        };
        $handler = new ClientHandler;
        $handler
            ->setAsyncTransport($transport)
            ->withRequestSchema($this->userSchema())
            ->async();
        $handler->onRequest(static function () use ($state): void {
            $state->request_events++;
        });
        $handler->onResponse(static function () use ($state): void {
            $state->response_events++;
        });
        $handler->onError(static function () use ($state): void {
            $state->error_events++;
        });
        $handler->addMiddleware(static function (
            RequestInterface $request,
            callable $next,
        ): PromiseInterface {
            $deferred = new Deferred;
            Loop::futureTick(static function () use (
                $request,
                $next,
                $deferred,
            ): void {
                $deferred->resolve(
                    $next(
                        $request->withBody(Utils::streamFor('{"name":"Ada"}')),
                    ),
                );
            });

            return $deferred->promise();
        }, 10);
        $handler->addMiddleware(
            static fn (
                RequestInterface $request,
                callable $next,
            ): Psr7Response => new Psr7Response(204),
            1,
        );

        $result = await(
            $handler
                ->post('https://example.test/users', ['name' => 42])
                ->catch(static fn (\Throwable $error): \Throwable => $error),
        );

        self::assertInstanceOf(ResponseInterface::class, $result);
        self::assertSame(
            0,
            $state->dispatches,
            'The downstream middleware response should short-circuit '
                .'transport.',
        );
        self::assertSame(1, $state->request_events);
        self::assertSame(1, $state->response_events);
        self::assertSame(0, $state->error_events);
    }

    public function test_validates_mock_and_short_circuit_responses(): void
    {
        $handler = new ClientHandler;
        $handler->withResponseSchema($this->userSchema());
        $handler->addMiddleware(
            static fn (
                RequestInterface $request,
                callable $next,
            ): Psr7Response => new Psr7Response(
                200,
                ['Content-Type' => 'application/json'],
                '{"name":42}',
            ),
        );

        $this->expectException(ResponseValidationException::class);
        $handler->get('https://example.test/users/1');
    }

    public function test_async_short_circuit_rejects_invalid_response(): void
    {
        MockServer::fake([
            'https://example.test/mock' => MockResponse::json(['name' => 42]),
        ]);

        try {
            $mockHandler = new ClientHandler;
            $mockHandler->withResponseSchema($this->userSchema())->async();
            $mockPromise = $mockHandler->get('https://example.test/mock');
            self::assertInstanceOf(PromiseInterface::class, $mockPromise);

            $mockError = await(
                $mockPromise->catch(
                    static fn (\Throwable $error): \Throwable => $error,
                ),
            );
            self::assertInstanceOf(
                ResponseValidationException::class,
                $mockError,
            );

            $middlewareHandler = new ClientHandler;
            $middlewareHandler
                ->withResponseSchema($this->userSchema())
                ->async();
            $middlewareHandler->addMiddleware(
                static fn (
                    RequestInterface $request,
                    callable $next,
                ): Psr7Response => new Psr7Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    '{"name":42}',
                ),
            );

            $middlewarePromise = $middlewareHandler->get(
                'https://example.test/short-circuit',
            );
            self::assertInstanceOf(PromiseInterface::class, $middlewarePromise);
            $middlewareError = await(
                $middlewarePromise->catch(
                    static fn (\Throwable $error): \Throwable => $error,
                ),
            );
            self::assertInstanceOf(
                ResponseValidationException::class,
                $middlewareError,
            );
        } finally {
            MockServer::resetInstance();
        }
    }

    public function test_short_circuit_validates_after_middleware(): void
    {
        $handler = new ClientHandler;
        $handler->withRequestSchema($this->userSchema());
        $handler->addMiddleware(
            static fn (
                RequestInterface $request,
                callable $next,
            ): Psr7Response => new Psr7Response(204),
        );

        $this->expectException(RequestValidationException::class);
        $handler->post('https://example.test/users', ['name' => 42]);
    }

    public function test_middleware_transforms_before_validation(): void
    {
        $history = [];
        $stack = HandlerStack::create(
            new MockHandler([
                new Psr7Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    '{"name":"Ada"}',
                ),
            ]),
        );
        $stack->push(Middleware::history($history));
        $handler = ClientHandler::createWithClient(
            new GuzzleClient([
                'handler' => $stack,
                'http_errors' => false,
            ]),
        );
        $handler
            ->withRequestSchema($this->userSchema())
            ->withResponseSchema($this->userSchema());
        $handler->addMiddleware(static function (
            RequestInterface $request,
            callable $next,
        ): mixed {
            $payload = json_decode(
                (string) $request->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            $payload['name'] = trim($payload['name']);
            $request = $request->withBody(
                Utils::streamFor(json_encode($payload, JSON_THROW_ON_ERROR)),
            );
            $response = $next($request);

            $transformResponse = static function (
                Psr7Response $response,
            ): Psr7Response {
                $data = json_decode(
                    (string) $response->getBody(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
                $data['name'] = strtolower($data['name']);

                return $response->withBody(
                    Utils::streamFor(json_encode($data, JSON_THROW_ON_ERROR)),
                );
            };

            return $response instanceof PromiseInterface
                ? $response->then($transformResponse)
                : $transformResponse($response);
        });

        $response = $handler->post('https://example.test/users', [
            'name' => ' Ada ',
        ]);

        self::assertSame(
            '{"name":"Ada"}',
            (string) $history[0]['request']->getBody(),
        );
        self::assertSame(['name' => 'ada'], $response->json());
    }

    public function test_streaming_refuses_validation(): void
    {
        $handler = new ClientHandler;
        $handler->withResponseSchema($this->userSchema());

        $this->expectException(ValidationConfigurationException::class);
        $this->expectExceptionMessage(
            'cannot be schema-validated without buffering',
        );

        $handler->stream('GET', 'https://example.test/events');
    }

    public function test_async_transport_allows_concurrent_progress(): void
    {
        $state = (object) [
            'active' => 0,
            'maximum_active' => 0,
            'loop_progressed' => false,
        ];
        $transport = new class($state) implements AsyncTransport {
            public function __construct(private \stdClass $state) {}

            public function requestAsync(
                string $method,
                string $uri,
                array $options,
            ): PromiseInterface {
                $deferred = new Deferred;
                $this->state->active++;
                $this->state->maximum_active = max(
                    $this->state->maximum_active,
                    $this->state->active,
                );
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

    public function test_cancellation_reaches_async_transport(): void
    {
        $state = (object) ['cancelled' => false];
        $transport = new class($state) implements AsyncTransport {
            public function __construct(private \stdClass $state) {}

            public function requestAsync(
                string $method,
                string $uri,
                array $options,
            ): PromiseInterface {
                $deferred = new Deferred(function (
                    callable $resolve,
                    callable $reject,
                ): void {
                    $this->state->cancelled = true;
                    $reject(new CancellationException);
                });

                return $deferred->promise();
            }
        };

        $handler = new ClientHandler;
        $handler->setAsyncTransport($transport);
        $promise = $handler->sendRequest('GET', 'https://example.test/slow', [
            'async' => true,
        ]);
        $observed = $promise->catch(
            static fn (\Throwable $error): \Throwable => $error,
        );
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

        return ClientHandler::createWithClient(
            $client,
            new GuzzleAsyncTransport($client, supportsQueueOnlyHandlers: true),
        );
    }

    private function withOpenApiSpec(string $contents, callable $test): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fetch-openapi-');
        if ($path === false) {
            self::fail('Could not create a temporary OpenAPI file.');
        }

        try {
            file_put_contents($path, $contents);
            $test($path);
        } finally {
            unlink($path);
        }
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
