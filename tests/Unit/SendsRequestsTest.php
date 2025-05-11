<?php

declare(strict_types=1);

namespace Tests\Unit;

use Closure;
use Fetch\Concerns\SendsRequests;
use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Http\Request;
use Fetch\Http\Response;
use Fetch\Interfaces\Response as ResponseInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use React\Promise\PromiseInterface;
use ReflectionObject;

class SendsRequestsTest extends TestCase
{
    public function test_request_get(): void
    {
        $uri = 'https://api.example.com/test';

        $verifyCallback = function (RequestInterface $request) use ($uri) {
            $this->assertEquals(Method::GET->value, $request->getMethod());
            $this->assertEquals($uri, (string) $request->getUri());
            $this->assertEmpty((string) $request->getBody()); // Body should not be set for GET
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->request(Method::GET->value, $uri);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_request_post_with_body(): void
    {
        $uri = 'https://api.example.com/test';
        $body = ['name' => 'test', 'value' => 123];

        $verifyCallback = function (RequestInterface $request) use ($uri, $body) {
            $this->assertEquals(Method::POST->value, $request->getMethod());
            $this->assertEquals($uri, (string) $request->getUri());
            $this->assertNotEmpty((string) $request->getBody());
            $this->assertEquals(ContentType::JSON->value, $request->getHeaderLine('Content-Type'));

            // Test request should be instance of our Request class with helper methods
            if ($request instanceof Request) {
                $this->assertEquals($body, $request->getBodyAsJson());
            }
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->request(Method::POST->value, $uri, $body);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_request_with_lowercase_method(): void
    {
        $uri = 'https://api.example.com/test';

        $verifyCallback = function (RequestInterface $request) use ($uri) {
            $this->assertEquals(Method::GET->value, $request->getMethod()); // Should be converted to uppercase
            $this->assertEquals($uri, (string) $request->getUri());
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->request('get', $uri);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_request_with_invalid_method(): void
    {
        $uri = 'https://api.example.com/test';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP method: INVALID');

        $instance = $this->createTraitImplementation(function () {});
        $instance->request('INVALID', $uri);
    }

    public function test_request_with_additional_options(): void
    {
        $uri = 'https://api.example.com/test';
        $options = ['timeout' => 60, 'verify' => false];

        $verifyCallback = function (RequestInterface $request, array $extractedOptions) use ($uri) {
            $this->assertEquals(Method::GET->value, $request->getMethod());
            $this->assertEquals($uri, (string) $request->getUri());
            $this->assertEquals(60, $extractedOptions['timeout']);
            $this->assertFalse($extractedOptions['verify']);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->request(Method::GET->value, $uri, null, ContentType::JSON->value, $options);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_request_with_custom_content_type(): void
    {
        $uri = 'https://api.example.com/test';
        $body = 'test body content';
        $contentType = ContentType::TEXT;

        $verifyCallback = function (RequestInterface $request) use ($uri, $body, $contentType) {
            $this->assertEquals(Method::POST->value, $request->getMethod());
            $this->assertEquals($uri, (string) $request->getUri());
            $this->assertEquals($body, (string) $request->getBody());
            $this->assertEquals($contentType->value, $request->getHeaderLine('Content-Type'));
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->request(Method::POST->value, $uri, $body, $contentType);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_handle_static_method(): void
    {
        $uri = 'https://api.example.com/static';
        // Create an anonymous class with required properties for trait
        $class = new class
        {
            use SendsRequests;

            // Required trait-backed properties with defaults
            private array $options = [];

            private array $preparedOptions = [];

            private ?int $timeout = null;

            private ?int $retries = null;

            private ?int $retryDelay = null;

            private bool $isAsync = false;

            private ?ClientInterface $syncClient = null;

            public static bool $called = false;

            public static RequestInterface $lastRequest;

            protected function sendSyncRequest(RequestInterface $request): ResponseInterface
            {
                self::$called = true;
                self::$lastRequest = $request;

                return new Response(204, [], '');
            }
        };

        $className = get_class($class);
        $response = $className::handle(Method::DELETE->value, $uri, ['timeout' => 42]);

        $this->assertTrue($className::$called);
        $this->assertEquals(Method::DELETE->value, $className::$lastRequest->getMethod());
        $this->assertEquals($uri, (string) $className::$lastRequest->getUri());
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function test_get_sync_client(): void
    {
        $verifyCallback = function () {};
        $instance = $this->createTraitImplementation($verifyCallback);

        // First call returns a Client and sets internal property
        $client1 = $instance->getSyncClient();
        $this->assertInstanceOf(ClientInterface::class, $client1);

        // Ensure default connect timeout is applied
        if ($client1 instanceof Client) {
            $this->assertEquals(
                $instance::DEFAULT_TIMEOUT,
                $client1->getConfig('connect_timeout')
            );
        }

        // Subsequent calls return the same instance
        $client2 = $instance->getSyncClient();
        $this->assertSame($client1, $client2);
    }

    public function test_apply_options_with_client(): void
    {
        $clientMock = $this->createMock(ClientInterface::class);
        $options = ['client' => $clientMock, 'timeout' => 60];

        $verifyCallback = function () {};
        $instance = $this->createTraitImplementation($verifyCallback);

        // Call the protected method using reflection
        $reflection = new ReflectionObject($instance);
        $method = $reflection->getMethod('applyOptions');
        $method->setAccessible(true);
        $method->invoke($instance, $options);

        // Get the options to verify
        $resultOptions = $instance->getOptions();

        // Client should be removed from options
        $this->assertArrayNotHasKey('client', $resultOptions);

        // Timeout should be set
        $this->assertEquals(60, $resultOptions['timeout']);
    }

    public function test_apply_options_with_invalid_client(): void
    {
        $options = ['client' => 'not a client'];

        $verifyCallback = function () {};
        $instance = $this->createTraitImplementation($verifyCallback);

        // Call the protected method using reflection
        $reflection = new ReflectionObject($instance);
        $method = $reflection->getMethod('applyOptions');
        $method->setAccessible(true);

        $this->expectException(InvalidArgumentException::class);
        $method->invoke($instance, $options);
    }

    public function test_apply_options_with_base_uri(): void
    {
        $baseUri = 'https://api.example.com/';
        $options = ['base_uri' => $baseUri];

        $verifyCallback = function () {};
        $instance = $this->createTraitImplementation($verifyCallback);

        // Call the protected method using reflection
        $reflection = new ReflectionObject($instance);
        $method = $reflection->getMethod('applyOptions');
        $method->setAccessible(true);
        $method->invoke($instance, $options);

        // Get the options to verify
        $resultOptions = $instance->getOptions();

        // Base URI should be set without trailing slash
        $this->assertEquals('https://api.example.com', $resultOptions['base_uri']);
    }

    public function test_create_request(): void
    {
        $uri = 'https://api.example.com/test';

        $verifyCallback = function () {};
        $instance = $this->createTraitImplementation($verifyCallback);

        // Call the protected method using reflection
        $reflection = new ReflectionObject($instance);
        $method = $reflection->getMethod('createRequest');
        $method->setAccessible(true);
        $request = $method->invoke($instance, Method::GET->value, $uri);

        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals(Method::GET->value, $request->getMethod());
        $this->assertEquals($uri, (string) $request->getUri());
    }

    public function test_configure_request_body_json(): void
    {
        $body = ['name' => 'test', 'value' => 123];
        $contentType = ContentType::JSON;

        $verifyCallback = function () {};
        $instance = $this->createTraitImplementation($verifyCallback);

        // Create a request to configure
        $request = new Request(Method::POST->value, 'https://api.example.com/test');

        // Call the protected method using reflection
        $reflection = new ReflectionObject($instance);
        $method = $reflection->getMethod('configureRequestBody');
        $method->setAccessible(true);
        $configuredRequest = $method->invoke($instance, $request, $body, $contentType);

        $this->assertInstanceOf(Request::class, $configuredRequest);
        $this->assertEquals(ContentType::JSON->value, $configuredRequest->getHeaderLine('Content-Type'));
        $this->assertEquals($body, $configuredRequest->getBodyAsJson());
    }

    public function test_apply_options_to_request(): void
    {
        $verifyCallback = function () {};
        $instance = $this->createTraitImplementation($verifyCallback);

        // Set some test options
        $instance->withOptions([
            'headers' => ['X-Test' => 'test-value'],
            'query' => ['page' => 1, 'limit' => 10],
            'auth' => ['username', 'password'],
            'token' => 'test-token',
        ]);

        // Create a request to apply options to
        $request = new Request(Method::GET->value, 'https://api.example.com/test');

        // Call the protected method using reflection
        $reflection = new ReflectionObject($instance);
        $method = $reflection->getMethod('applyOptionsToRequest');
        $method->setAccessible(true);
        $configuredRequest = $method->invoke($instance, $request);

        // Verify headers were applied
        $this->assertEquals('test-value', $configuredRequest->getHeaderLine('X-Test'));

        // Verify query parameters were applied
        $this->assertStringContainsString('page=1', (string) $configuredRequest->getUri());
        $this->assertStringContainsString('limit=10', (string) $configuredRequest->getUri());

        // Verify authorization was applied (we'll test just one to keep it simple)
        $this->assertTrue($configuredRequest->hasHeader('Authorization'));
    }

    public function test_extract_options_from_request(): void
    {
        $verifyCallback = function () {};
        $instance = $this->createTraitImplementation($verifyCallback);

        // Create a request with some headers and body
        $request = (new Request(Method::POST->value, 'https://api.example.com/test'))
            ->withHeader('Content-Type', ContentType::JSON->value)
            ->withHeader('X-Test', 'test-value')
            ->withJsonBody(['name' => 'test']);

        // Call the protected method using reflection
        $reflection = new ReflectionObject($instance);
        $method = $reflection->getMethod('extractOptionsFromRequest');
        $method->setAccessible(true);
        $options = $method->invoke($instance, $request);

        // Verify headers were extracted
        $this->assertArrayHasKey('headers', $options);
        $this->assertEquals(ContentType::JSON->value, $options['headers']['Content-Type']);
        $this->assertEquals('test-value', $options['headers']['X-Test']);

        // Verify body was extracted
        $this->assertArrayHasKey('body', $options);
        $this->assertNotEmpty($options['body']);
    }

    public function test_send_request(): void
    {
        $request = new Request(Method::GET->value, 'https://api.example.com/test');

        $verifyCallback = function (RequestInterface $request) {
            $this->assertEquals(Method::GET->value, $request->getMethod());
            $this->assertEquals('https://api.example.com/test', (string) $request->getUri());
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->sendRequest($request);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    /**
     * Create a mock implementation of the trait for testing.
     *
     * @param  \Closure  $verifyCallback  A callback to verify the request parameters
     * @return object An instance of the anonymous class using the trait
     */
    private function createTraitImplementation(Closure $verifyCallback)
    {
        return new class($verifyCallback)
        {
            use SendsRequests;

            private Closure $verifyCallback;

            private array $preparedOptions = [];

            private array $options = [];

            private ?int $timeout = null;

            private ?int $retries = null;

            private ?int $retryDelay = null;

            private bool $isAsync = false;

            private ?ClientInterface $syncClient = null;

            private ResponseInterface $mockResponse;

            public const DEFAULT_TIMEOUT = 30;

            public function __construct(Closure $verifyCallback)
            {
                $this->verifyCallback = $verifyCallback;
                $this->mockResponse = new Response(200, [], 'Test response');
            }

            public function withOptions(array $options): self
            {
                $this->options = array_merge($this->options, $options);

                return $this;
            }

            public function baseUri(string $uri): self
            {
                $this->options['base_uri'] = rtrim($uri, '/');

                return $this;
            }

            public function setSyncClient(ClientInterface $client): self
            {
                $this->syncClient = $client;

                return $this;
            }

            public function getFullUri(): string
            {
                return $this->options['uri'] ?? '';
            }

            protected function retryRequest(callable $callback): mixed
            {
                return $callback();
            }

            protected function sendSyncRequest(RequestInterface $request): ResponseInterface
            {
                ($this->verifyCallback)($request, $this->extractOptionsFromRequest($request));

                return $this->mockResponse;
            }

            protected function sendAsyncRequest(RequestInterface $request): PromiseInterface
            {
                ($this->verifyCallback)($request, $this->extractOptionsFromRequest($request));

                return new class($this->mockResponse) implements PromiseInterface
                {
                    private ResponseInterface $response;

                    public function __construct(ResponseInterface $response)
                    {
                        $this->response = $response;
                    }

                    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
                    {
                        if ($onFulfilled) {
                            $onFulfilled($this->response);
                        }

                        return $this;
                    }

                    public function catch(callable $onRejected): PromiseInterface
                    {
                        return $this;
                    }

                    public function finally(callable $onFulfilledOrRejected): PromiseInterface
                    {
                        $onFulfilledOrRejected();

                        return $this;
                    }

                    public function cancel(): void {}
                };
            }

            public function getMockResponse(): ResponseInterface
            {
                return $this->mockResponse;
            }

            public function getOptions(): array
            {
                return $this->options;
            }

            public function publicCreateRequest(string $method, string $uri): Request
            {
                return $this->createRequest($method, $uri);
            }

            public function publicConfigureRequestBody(Request $request, mixed $body, ContentType|string $contentType): Request
            {
                return $this->configureRequestBody($request, $body, $contentType);
            }

            public function publicApplyOptionsToRequest(Request $request): Request
            {
                return $this->applyOptionsToRequest($request);
            }

            public function publicExtractOptionsFromRequest(RequestInterface $request): array
            {
                return $this->extractOptionsFromRequest($request);
            }
        };
    }
}
