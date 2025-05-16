<?php

namespace Tests\Unit;

use Fetch\Http\ClientHandler;
use PHPUnit\Framework\TestCase;

class HandlesUrisTest extends TestCase
{
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new class extends ClientHandler
        {
            public function exposeBuildFullUri(string $uri): string
            {
                return $this->buildFullUri($uri);
            }

            public function exposeIsAbsoluteUrl(string $uri): bool
            {
                return $this->isAbsoluteUrl($uri);
            }

            public function exposeAppendQueryParameters(string $uri, array $params): string
            {
                return $this->appendQueryParameters($uri, $params);
            }
        };
    }

    public function test_absolute_url_detection(): void
    {
        $this->assertTrue($this->handler->exposeIsAbsoluteUrl('https://example.com'));
        $this->assertTrue($this->handler->exposeIsAbsoluteUrl('http://example.com'));
        $this->assertTrue($this->handler->exposeIsAbsoluteUrl('https://example.com/path'));
        $this->assertFalse($this->handler->exposeIsAbsoluteUrl('/path'));
        $this->assertFalse($this->handler->exposeIsAbsoluteUrl('path'));
        $this->assertFalse($this->handler->exposeIsAbsoluteUrl('example.com'));
    }

    public function test_build_full_uri_with_absolute_url(): void
    {
        $url = 'https://example.com/path';
        $this->assertEquals($url, $this->handler->exposeBuildFullUri($url));
    }

    public function test_build_full_uri_with_base_uri(): void
    {
        $this->handler->baseUri('https://example.com');
        $this->assertEquals('https://example.com/path', $this->handler->exposeBuildFullUri('/path'));
        $this->assertEquals('https://example.com/path', $this->handler->exposeBuildFullUri('path'));
    }

    public function test_build_full_uri_with_query_parameters(): void
    {
        $this->handler->withQueryParameters(['foo' => 'bar', 'baz' => 'qux']);
        $this->assertEquals(
            'https://example.com/path?foo=bar&baz=qux',
            $this->handler->exposeBuildFullUri('https://example.com/path')
        );
    }

    public function test_build_full_uri_with_base_uri_and_query_parameters(): void
    {
        $this->handler->baseUri('https://example.com');
        $this->handler->withQueryParameters(['foo' => 'bar']);
        $this->assertEquals('https://example.com/path?foo=bar', $this->handler->exposeBuildFullUri('/path'));
    }

    public function test_appending_query_parameters(): void
    {
        // Test with URL without existing query parameters
        $this->assertEquals(
            'https://example.com/path?foo=bar',
            $this->handler->exposeAppendQueryParameters('https://example.com/path', ['foo' => 'bar'])
        );

        // Test with URL that already has query parameters
        $this->assertEquals(
            'https://example.com/path?existing=value&foo=bar',
            $this->handler->exposeAppendQueryParameters('https://example.com/path?existing=value', ['foo' => 'bar'])
        );

        // Test with URL ending with a question mark
        $this->assertEquals(
            'https://example.com/path?foo=bar',
            $this->handler->exposeAppendQueryParameters('https://example.com/path?', ['foo' => 'bar'])
        );

        // Test with URL containing a fragment
        $this->assertEquals(
            'https://example.com/path?foo=bar#fragment',
            $this->handler->exposeAppendQueryParameters('https://example.com/path#fragment', ['foo' => 'bar'])
        );
    }

    public function test_build_full_uri_throws_exception_for_empty_uri(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->handler->exposeBuildFullUri('');
    }

    public function test_build_full_uri_throws_exception_for_relative_uri_without_base(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->handler->exposeBuildFullUri('/path');
    }
}
