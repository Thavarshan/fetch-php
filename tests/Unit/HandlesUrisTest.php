<?php

namespace Tests\Unit;

use Fetch\Http\ClientHandler;
use Fetch\Support\RequestContext;
use PHPUnit\Framework\TestCase;

/**
 * Covers URI normalization, base paths, queries, and legacy compatibility.
 */
class HandlesUrisTest extends TestCase
{
    /**
     * Handler subclass exposing URI helpers to the tests.
     */
    private $handler;

    /**
     * Creates a handler exposing protected URI helpers to these tests.
     */
    protected function setUp(): void
    {
        $this->handler = new
        /**
         * Exposes protected URI helpers through a minimal handler subclass.
         */
        class extends ClientHandler {
            /**
             * Exposes legacy URI resolution for direct unit assertions.
             */
            public function exposeLegacyBuildFullUri(string $uri): string
            {
                return $this->buildFullUri($uri);
            }

            /**
             * Exposes URI resolution using a normalized request context.
             */
            public function exposeBuildFullUriFromContext(string $uri): string
            {
                $options = array_merge($this->options, ['uri' => $uri]);

                return $this->buildFullUriFromContext(
                    RequestContext::fromOptions($options),
                );
            }

            /**
             * Exposes absolute-URL detection for direct unit assertions.
             */
            public function exposeIsAbsoluteUrl(string $uri): bool
            {
                return $this->isAbsoluteUrl($uri);
            }

            /**
             * Exposes query-string merging for direct unit assertions.
             */
            public function exposeAppendQueryParameters(
                string $uri,
                array $params,
            ): string {
                return $this->appendQueryParameters($uri, $params);
            }
        };
    }

    public function test_absolute_url_detection(): void
    {
        $this->assertTrue(
            $this->handler->exposeIsAbsoluteUrl('https://example.com'),
        );
        $this->assertTrue(
            $this->handler->exposeIsAbsoluteUrl('http://example.com'),
        );
        $this->assertTrue(
            $this->handler->exposeIsAbsoluteUrl('https://example.com/path'),
        );
        $this->assertFalse($this->handler->exposeIsAbsoluteUrl('/path'));
        $this->assertFalse($this->handler->exposeIsAbsoluteUrl('path'));
        $this->assertFalse($this->handler->exposeIsAbsoluteUrl('example.com'));
    }

    public function test_build_full_uri_with_absolute_url(): void
    {
        $url = 'https://example.com/path';
        $this->assertSame(
            $url,
            $this->handler->exposeBuildFullUriFromContext($url),
        );
    }

    public function test_build_full_uri_with_base_uri(): void
    {
        $this->handler->baseUri('https://example.com');
        $this->assertEquals(
            'https://example.com/path',
            $this->handler->exposeBuildFullUriFromContext('/path'),
        );
        $this->assertEquals(
            'https://example.com/path',
            $this->handler->exposeBuildFullUriFromContext('path'),
        );
    }

    public function test_build_full_uri_with_query_parameters(): void
    {
        $this->handler->withQueryParameters(['foo' => 'bar', 'baz' => 'qux']);
        $this->assertEquals(
            'https://example.com/path?foo=bar&baz=qux',
            $this->handler->exposeBuildFullUriFromContext(
                'https://example.com/path',
            ),
        );
    }

    public function test_build_full_uri_appends_query_to_base_uri(): void
    {
        $this->handler->baseUri('https://example.com');
        $this->handler->withQueryParameters(['foo' => 'bar']);
        $this->assertEquals(
            'https://example.com/path?foo=bar',
            $this->handler->exposeBuildFullUriFromContext('/path'),
        );
    }

    public function test_appending_query_parameters(): void
    {
        // Test with URL without existing query parameters
        $this->assertEquals(
            'https://example.com/path?foo=bar',
            $this->handler->exposeAppendQueryParameters(
                'https://example.com/path',
                ['foo' => 'bar'],
            ),
        );

        // Test with URL that already has query parameters
        $this->assertEquals(
            'https://example.com/path?existing=value&foo=bar',
            $this->handler->exposeAppendQueryParameters(
                'https://example.com/path?existing=value',
                ['foo' => 'bar'],
            ),
        );

        // Test with URL ending with a question mark
        $this->assertEquals(
            'https://example.com/path?foo=bar',
            $this->handler->exposeAppendQueryParameters(
                'https://example.com/path?',
                ['foo' => 'bar'],
            ),
        );

        // Test with URL containing a fragment
        $this->assertEquals(
            'https://example.com/path?foo=bar#fragment',
            $this->handler->exposeAppendQueryParameters(
                'https://example.com/path#fragment',
                ['foo' => 'bar'],
            ),
        );
    }

    public function test_build_full_uri_throws_exception_for_empty_uri(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->handler->exposeBuildFullUriFromContext('');
    }

    public function test_build_full_uri_rejects_relative_without_base(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->handler->exposeBuildFullUriFromContext('/path');
    }

    public function test_legacy_uri_method_deprecation_and_result(): void
    {
        $warnings = [];
        set_error_handler(
            static function (int $severity, string $message) use (
                &$warnings,
            ): bool {
                if ($severity !== E_USER_DEPRECATED) {
                    return false;
                }

                $warnings[] = $message;

                return true;
            },
        );

        try {
            $uri = $this->handler->exposeLegacyBuildFullUri(
                'https://example.com/path',
            );
        } finally {
            restore_error_handler();
        }

        self::assertSame('https://example.com/path', $uri);
        self::assertSame(1, count($warnings));
        self::assertStringContainsString('buildFullUri()', $warnings[0]);
    }
}
