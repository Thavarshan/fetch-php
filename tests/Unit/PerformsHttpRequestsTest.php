<?php

declare(strict_types=1);

namespace Tests\Unit;

use Closure;
use Fetch\Concerns\PerformsHttpRequests;
use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Http\Response;
use Fetch\Interfaces\Response as ResponseInterface;
use PHPUnit\Framework\TestCase;
use React\Promise\PromiseInterface;

class PerformsHttpRequestsTest extends TestCase
{
    public function test_head(): void
    {
        $uri = 'https://api.example.com/test';

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri) {
            $this->assertEquals(Method::HEAD->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEmpty($queryParams);
            $this->assertEmpty($requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->head($uri);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_get_without_query_params(): void
    {
        $uri = 'https://api.example.com/test';

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri) {
            $this->assertEquals(Method::GET->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEmpty($queryParams);
            $this->assertEmpty($requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->get($uri);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_get_with_query_params(): void
    {
        $uri = 'https://api.example.com/test';
        $queryParams = ['param1' => 'value1', 'param2' => 'value2'];

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri) {
            $this->assertEquals(Method::GET->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEquals(['param1' => 'value1', 'param2' => 'value2'], $queryParams);
            $this->assertEmpty($requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->get($uri, $queryParams);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_post_without_body(): void
    {
        $uri = 'https://api.example.com/test';

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri) {
            $this->assertEquals(Method::POST->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEmpty($queryParams);
            $this->assertEmpty($requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->post($uri);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_post_with_body_default_content_type(): void
    {
        $uri = 'https://api.example.com/test';
        $body = ['name' => 'test', 'value' => 123];

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri, $body) {
            $this->assertEquals(Method::POST->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEmpty($queryParams);
            $this->assertEquals([
                'body' => $body,
                'contentType' => 'application/json',
            ], $requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->post($uri, $body);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_post_with_body_custom_content_type(): void
    {
        $uri = 'https://api.example.com/test';
        $body = 'test body content';
        $contentType = ContentType::TEXT;

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri, $body, $contentType) {
            $this->assertEquals(Method::POST->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEmpty($queryParams);
            $this->assertEquals([
                'body' => $body,
                'contentType' => $contentType,
            ], $requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->post($uri, $body, $contentType);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_patch_without_body(): void
    {
        $uri = 'https://api.example.com/test';

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri) {
            $this->assertEquals(Method::PATCH->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEmpty($queryParams);
            $this->assertEmpty($requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->patch($uri);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_patch_with_body_default_content_type(): void
    {
        $uri = 'https://api.example.com/test';
        $body = ['name' => 'test', 'value' => 123];

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri, $body) {
            $this->assertEquals(Method::PATCH->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEmpty($queryParams);
            $this->assertEquals([
                'body' => $body,
                'contentType' => 'application/json',
            ], $requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->patch($uri, $body);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_patch_with_body_custom_content_type(): void
    {
        $uri = 'https://api.example.com/test';
        $body = 'test body content';
        $contentType = ContentType::TEXT;

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri, $body, $contentType) {
            $this->assertEquals(Method::PATCH->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEmpty($queryParams);
            $this->assertEquals([
                'body' => $body,
                'contentType' => $contentType,
            ], $requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->patch($uri, $body, $contentType);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_put_without_body(): void
    {
        $uri = 'https://api.example.com/test';

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri) {
            $this->assertEquals(Method::PUT->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEmpty($queryParams);
            $this->assertEmpty($requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->put($uri);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_put_with_body_default_content_type(): void
    {
        $uri = 'https://api.example.com/test';
        $body = ['name' => 'test', 'value' => 123];

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri, $body) {
            $this->assertEquals(Method::PUT->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEmpty($queryParams);
            $this->assertEquals([
                'body' => $body,
                'contentType' => 'application/json',
            ], $requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->put($uri, $body);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_put_with_body_custom_content_type(): void
    {
        $uri = 'https://api.example.com/test';
        $body = 'test body content';
        $contentType = ContentType::TEXT;

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri, $body, $contentType) {
            $this->assertEquals(Method::PUT->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEmpty($queryParams);
            $this->assertEquals([
                'body' => $body,
                'contentType' => $contentType,
            ], $requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->put($uri, $body, $contentType);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_delete_without_body(): void
    {
        $uri = 'https://api.example.com/test';

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri) {
            $this->assertEquals(Method::DELETE->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEmpty($queryParams);
            $this->assertEmpty($requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->delete($uri);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_delete_with_body_default_content_type(): void
    {
        $uri = 'https://api.example.com/test';
        $body = ['name' => 'test', 'value' => 123];

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri, $body) {
            $this->assertEquals(Method::DELETE->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEmpty($queryParams);
            $this->assertEquals([
                'body' => $body,
                'contentType' => 'application/json',
            ], $requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->delete($uri, $body);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_delete_with_body_custom_content_type(): void
    {
        $uri = 'https://api.example.com/test';
        $body = 'test body content';
        $contentType = ContentType::TEXT;

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri, $body, $contentType) {
            $this->assertEquals(Method::DELETE->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEmpty($queryParams);
            $this->assertEquals([
                'body' => $body,
                'contentType' => $contentType,
            ], $requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->delete($uri, $body, $contentType);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    public function test_options(): void
    {
        $uri = 'https://api.example.com/test';

        $verifyCallback = function ($method, $requestUri, $queryParams, $requestConfig) use ($uri) {
            $this->assertEquals(Method::OPTIONS->value, $method);
            $this->assertEquals($uri, $requestUri);
            $this->assertEmpty($queryParams);
            $this->assertEmpty($requestConfig);
        };

        $instance = $this->createTraitImplementation($verifyCallback);
        $response = $instance->options($uri);

        $this->assertSame($instance->getMockResponse(), $response);
    }

    /**
     * Create an instance of the trait implementation for testing.
     *
     * @param  \Closure  $verifyCallback  The callback to verify parameters passed to finalizeRequest
     * @return object An instance of an anonymous class using the trait
     */
    private function createTraitImplementation(Closure $verifyCallback)
    {
        // Create an anonymous class that uses the trait
        return new class($verifyCallback)
        {
            use PerformsHttpRequests;

            private Closure $verifyCallback;

            private array $queryParams = [];

            private array $requestConfig = [];

            private Response $mockResponse;

            public function __construct(Closure $verifyCallback)
            {
                $this->verifyCallback = $verifyCallback;
                $this->mockResponse = new Response(200, [], 'Test response');
            }

            // Mock the behavior of the methods that the trait depends on
            public function withQueryParameters(array $params): self
            {
                $this->queryParams = array_merge($this->queryParams, $params);

                return $this;
            }

            public function configurePostableRequest(mixed $body, $contentType): void
            {
                $this->requestConfig = [
                    'body' => $body,
                    'contentType' => $contentType,
                ];
            }

            public function finalizeRequest(string $method, string $uri): ResponseInterface|PromiseInterface
            {
                // Call the verifyCallback to verify method and uri
                ($this->verifyCallback)($method, $uri, $this->queryParams, $this->requestConfig);

                return $this->mockResponse;
            }

            // Helper methods for the test to verify state
            public function getQueryParams(): array
            {
                return $this->queryParams;
            }

            public function getRequestConfig(): array
            {
                return $this->requestConfig;
            }

            public function getMockResponse(): ResponseInterface
            {
                return $this->mockResponse;
            }
        };
    }
}
