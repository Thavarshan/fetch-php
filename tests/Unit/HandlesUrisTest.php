<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Http\ClientHandler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class HandlesUrisTest extends TestCase
{
    public function test_get_full_uri_with_empty_uri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URI cannot be empty');

        $handler = new ClientHandler;
        // Access the protected method through debug() which calls getFullUri()
        $handler->debug();
    }

    public function test_get_full_uri_with_only_base_uri(): void
    {
        $handler = new ClientHandler(options: [
            'base_uri' => 'https://api.example.com',
        ]);

        $this->assertEquals('https://api.example.com/', $handler->debug()['uri']);
    }

    public function test_get_full_uri_with_only_relative_uri(): void
    {
        $handler = new ClientHandler(options: [
            'uri' => '/endpoint',
        ]);

        // We expect the leading slash to be preserved
        $this->assertEquals('/endpoint', $handler->debug()['uri']);
    }

    public function test_get_full_uri_with_base_and_relative_uri(): void
    {
        $handler = new ClientHandler(options: [
            'base_uri' => 'https://api.example.com',
            'uri' => '/endpoint',
        ]);

        $this->assertEquals('https://api.example.com/endpoint', $handler->debug()['uri']);
    }

    public function test_get_full_uri_with_absolute_uri(): void
    {
        $handler = new ClientHandler(options: [
            'base_uri' => 'https://api.example.com',
            'uri' => 'https://other-api.example.com/endpoint',
        ]);

        // The absolute URI should take precedence over the base URI
        $this->assertEquals('https://other-api.example.com/endpoint', $handler->debug()['uri']);
    }

    public function test_get_full_uri_with_query_parameters(): void
    {
        $handler = new ClientHandler(options: [
            'base_uri' => 'https://api.example.com',
            'uri' => '/endpoint',
            'query' => ['param1' => 'value1', 'param2' => 'value2'],
        ]);

        $this->assertEquals(
            'https://api.example.com/endpoint?param1=value1&param2=value2',
            $handler->debug()['uri']
        );
    }

    public function test_is_absolute_url(): void
    {
        $handler = new ClientHandler;

        $this->assertTrue(
            $this->invokeProtectedMethod($handler, 'isAbsoluteUrl', ['https://api.example.com'])
        );
        $this->assertTrue(
            $this->invokeProtectedMethod($handler, 'isAbsoluteUrl', ['http://example.com/path'])
        );
        $this->assertFalse(
            $this->invokeProtectedMethod($handler, 'isAbsoluteUrl', ['/relative/path'])
        );
        $this->assertFalse(
            $this->invokeProtectedMethod($handler, 'isAbsoluteUrl', ['relative/path'])
        );
        $this->assertFalse(
            $this->invokeProtectedMethod($handler, 'isAbsoluteUrl', [''])
        );
    }

    public function test_combine_base_with_relative_uri_empty_base(): void
    {
        $handler = new ClientHandler;

        // We expect the leading slash to be preserved when no base URI is provided
        $this->assertEquals(
            '/relative/path',
            $this->invokeProtectedMethod($handler, 'combineBaseWithRelativeUri', ['', '/relative/path'])
        );

        $this->assertEquals(
            'path',
            $this->invokeProtectedMethod($handler, 'combineBaseWithRelativeUri', ['', 'path'])
        );
    }

    public function test_combine_base_with_relative_uri_invalid_base(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base URI: invalid-base');

        $handler = new ClientHandler;
        $this->invokeProtectedMethod($handler, 'combineBaseWithRelativeUri', ['invalid-base', '/path']);
    }

    public function test_combine_base_with_relative_uri_valid_base(): void
    {
        $handler = new ClientHandler;

        // Base without trailing slash, path with leading slash
        $this->assertEquals(
            'https://api.example.com/path',
            $this->invokeProtectedMethod(
                $handler,
                'combineBaseWithRelativeUri',
                ['https://api.example.com', '/path']
            )
        );

        // Base with trailing slash, path without leading slash
        $this->assertEquals(
            'https://api.example.com/path',
            $this->invokeProtectedMethod(
                $handler,
                'combineBaseWithRelativeUri',
                ['https://api.example.com/', 'path']
            )
        );

        // Base with trailing slash, path with leading slash
        $this->assertEquals(
            'https://api.example.com/path',
            $this->invokeProtectedMethod(
                $handler,
                'combineBaseWithRelativeUri',
                ['https://api.example.com/', '/path']
            )
        );
    }

    public function test_append_query_parameters_no_params(): void
    {
        $handler = new ClientHandler;

        $this->assertEquals(
            'https://api.example.com/path',
            $this->invokeProtectedMethod(
                $handler,
                'appendQueryParameters',
                ['https://api.example.com/path', []]
            )
        );
    }

    public function test_append_query_parameters_with_params(): void
    {
        $handler = new ClientHandler;

        // Simple case: URI without existing query
        $this->assertEquals(
            'https://api.example.com/path?param1=value1&param2=value2',
            $this->invokeProtectedMethod(
                $handler,
                'appendQueryParameters',
                ['https://api.example.com/path', ['param1' => 'value1', 'param2' => 'value2']]
            )
        );

        // URI already has query parameters
        $this->assertEquals(
            'https://api.example.com/path?existing=value&param1=value1&param2=value2',
            $this->invokeProtectedMethod(
                $handler,
                'appendQueryParameters',
                ['https://api.example.com/path?existing=value', ['param1' => 'value1', 'param2' => 'value2']]
            )
        );

        // URI ends with a question mark
        $this->assertEquals(
            'https://api.example.com/path?param1=value1&param2=value2',
            $this->invokeProtectedMethod(
                $handler,
                'appendQueryParameters',
                ['https://api.example.com/path?', ['param1' => 'value1', 'param2' => 'value2']]
            )
        );
    }

    public function test_normalize_uri(): void
    {
        $handler = new ClientHandler;

        // HTTP URL with multiple slashes
        $this->assertEquals(
            'http://example.com/path/to/resource',
            $this->invokeProtectedMethod(
                $handler,
                'normalizeUri',
                ['http://example.com//path///to/resource']
            )
        );

        // HTTPS URL with multiple slashes
        $this->assertEquals(
            'https://example.com/path/to/resource',
            $this->invokeProtectedMethod(
                $handler,
                'normalizeUri',
                ['https://example.com//path///to/resource']
            )
        );

        // Relative path with multiple slashes
        $this->assertEquals(
            '/path/to/resource',
            $this->invokeProtectedMethod(
                $handler,
                'normalizeUri',
                ['/path///to//resource']
            )
        );
    }

    public function test_resolve_uri_with_absolute_uri(): void
    {
        $handler = new ClientHandler;

        $this->assertEquals(
            'https://other-api.example.com/path',
            $this->invokeProtectedMethod(
                $handler,
                'resolveUri',
                ['https://api.example.com', 'https://other-api.example.com/path']
            )
        );
    }

    public function test_resolve_uri_with_absolute_path(): void
    {
        $handler = new ClientHandler;

        $this->assertEquals(
            'https://api.example.com/absolute/path',
            $this->invokeProtectedMethod(
                $handler,
                'resolveUri',
                ['https://api.example.com/base/path', '/absolute/path']
            )
        );

        // Test with base URI that includes port
        $this->assertEquals(
            'https://api.example.com:8080/absolute/path',
            $this->invokeProtectedMethod(
                $handler,
                'resolveUri',
                ['https://api.example.com:8080/base/path', '/absolute/path']
            )
        );
    }

    public function test_resolve_uri_with_relative_path(): void
    {
        $handler = new ClientHandler;

        $this->assertEquals(
            'https://api.example.com/relative/path',
            $this->invokeProtectedMethod(
                $handler,
                'resolveUri',
                ['https://api.example.com', 'relative/path']
            )
        );
    }

    public function test_append_query_parameters_edge_cases(): void
    {
        $handler = new ClientHandler;

        // Query parameters with special characters
        // Note: PHP's http_build_query() encodes spaces as '+' by default, which is valid
        $this->assertEquals(
            'https://api.example.com/path?param=value+with+spaces&special=%21%40%23%24',
            $this->invokeProtectedMethod(
                $handler,
                'appendQueryParameters',
                ['https://api.example.com/path', ['param' => 'value with spaces', 'special' => '!@#$']]
            )
        );

        // Array query parameters
        $this->assertEquals(
            'https://api.example.com/path?arr%5B0%5D=value1&arr%5B1%5D=value2',
            $this->invokeProtectedMethod(
                $handler,
                'appendQueryParameters',
                ['https://api.example.com/path', ['arr' => ['value1', 'value2']]]
            )
        );
    }

    public function test_handling_base_uri_with_port(): void
    {
        $handler = new ClientHandler(options: [
            'base_uri' => 'https://api.example.com:8080',
            'uri' => '/endpoint',
        ]);

        $this->assertEquals('https://api.example.com:8080/endpoint', $handler->debug()['uri']);
    }

    public function test_handling_uri_with_fragment(): void
    {
        $handler = new ClientHandler(options: [
            'uri' => 'https://api.example.com/path#fragment',
            'query' => ['param' => 'value'],
        ]);

        // The correct behavior is to add query parameters before the fragment
        $this->assertEquals('https://api.example.com/path?param=value#fragment', $handler->debug()['uri']);
    }

    public function test_handling_uri_with_query_and_fragment(): void
    {
        $handler = new ClientHandler(options: [
            'uri' => 'https://api.example.com/path?existing=value#fragment',
            'query' => ['param' => 'new'],
        ]);

        // Query parameters should be added to existing query before the fragment
        $this->assertEquals('https://api.example.com/path?existing=value&param=new#fragment', $handler->debug()['uri']);
    }

    /**
     * Invoke a protected method on the ClientHandler class.
     *
     * @template T
     *
     * @param  array<int, mixed>  $parameters
     * @return T
     *
     * @throws \ReflectionException
     */
    protected function invokeProtectedMethod(ClientHandler $object, string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
