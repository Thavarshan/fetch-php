<?php

namespace Tests\Unit;

use Fetch\Support\RequestOptions;
use PHPUnit\Framework\TestCase;

/**
 * Covers normalization and precedence of Guzzle request options.
 */
class RequestOptionsTest extends TestCase
{
    public function test_normalize_max_retries_to_retries(): void
    {
        $options = [
            'max_retries' => 5,
            'timeout' => 30,
        ];

        $normalized = RequestOptions::normalizeOptionKeys($options);

        $this->assertArrayHasKey('retries', $normalized);
        $this->assertEquals(5, $normalized['retries']);
        $this->assertArrayNotHasKey('max_retries', $normalized);
    }

    public function test_normalize_keeps_explicit_retries(): void
    {
        $options = [
            'retries' => 3,
            'timeout' => 30,
        ];

        $normalized = RequestOptions::normalizeOptionKeys($options);

        $this->assertArrayHasKey('retries', $normalized);
        $this->assertEquals(3, $normalized['retries']);
        $this->assertArrayNotHasKey('max_retries', $normalized);
    }

    public function test_normalize_prefers_retries_over_max_retries(): void
    {
        $options = [
            'retries' => 3,
            'max_retries' => 5, // Should be ignored
            'timeout' => 30,
        ];

        $normalized = RequestOptions::normalizeOptionKeys($options);

        $this->assertArrayHasKey('retries', $normalized);
        $this->assertEquals(
            3,
            $normalized['retries'],
            'retries should take precedence',
        );
        $this->assertArrayNotHasKey('max_retries', $normalized);
    }

    public function test_normalize_option_keys_preserves_other_options(): void
    {
        $options = [
            'max_retries' => 5,
            'timeout' => 30,
            'base_uri' => 'https://api.example.com',
            'headers' => ['X-Custom' => 'value'],
        ];

        $normalized = RequestOptions::normalizeOptionKeys($options);

        $this->assertEquals(30, $normalized['timeout']);
        $this->assertEquals('https://api.example.com', $normalized['base_uri']);
        $this->assertEquals(['X-Custom' => 'value'], $normalized['headers']);
    }

    public function test_merge_normalizes_option_keys(): void
    {
        $defaults = [
            'max_retries' => 3,
            'timeout' => 10,
        ];

        $override = [
            'timeout' => 20,
        ];

        $merged = RequestOptions::merge($defaults, $override);

        // Should have normalized max_retries to retries
        $this->assertArrayHasKey('retries', $merged);
        $this->assertEquals(3, $merged['retries']);
        $this->assertArrayNotHasKey('max_retries', $merged);
        $this->assertEquals(20, $merged['timeout']);
    }

    public function test_merge_prefers_retries_over_max_retries(): void
    {
        $options1 = [
            'max_retries' => 5,
        ];

        $options2 = [
            'retries' => 10,
        ];

        $merged = RequestOptions::merge($options1, $options2);

        // Later option (retries) should win
        $this->assertEquals(10, $merged['retries']);
        $this->assertArrayNotHasKey('max_retries', $merged);
    }

    public function test_backward_compatibility_max_retries_still_works(): void
    {
        // Simulate old code using max_retries
        $options = [
            'max_retries' => 7,
            'retry_delay' => 200,
        ];

        $normalized = RequestOptions::normalizeOptionKeys($options);

        // Should be normalized to 'retries'
        $this->assertEquals(7, $normalized['retries']);
        $this->assertEquals(200, $normalized['retry_delay']);
    }

    public function test_normalize_body_options(): void
    {
        // Test json takes precedence
        $options = [
            'json' => ['foo' => 'bar'],
            'body' => 'ignored',
        ];

        $normalized = RequestOptions::normalizeBodyOptions($options);

        $this->assertArrayHasKey('json', $normalized);
        $this->assertArrayNotHasKey('body', $normalized);
    }

    public function test_body_representations_follow_documented_precedence(
    ): void {
        $json = ['nested' => ['value' => true]];
        $form = ['form' => 'value'];
        $multipart = [['name' => 'upload', 'contents' => 'file']];

        $normalized = RequestOptions::normalizeBodyOptions([
            'json' => $json,
            'form_params' => $form,
            'multipart' => $multipart,
            'body' => 'raw',
        ]);

        self::assertSame($json, $normalized['json']);
        self::assertArrayNotHasKey('form_params', $normalized);
        self::assertArrayNotHasKey('multipart', $normalized);
        self::assertArrayNotHasKey('body', $normalized);

        $normalized = RequestOptions::normalizeBodyOptions([
            'form' => $form,
            'multipart' => $multipart,
            'body' => 'raw',
        ]);

        self::assertSame($form, $normalized['form_params']);
        self::assertArrayNotHasKey('multipart', $normalized);
        self::assertArrayNotHasKey('body', $normalized);

        $normalized = RequestOptions::normalizeBodyOptions([
            'multipart' => $multipart,
            'body' => 'raw',
        ]);

        self::assertSame($multipart, $normalized['multipart']);
        self::assertArrayNotHasKey('body', $normalized);
    }

    public function test_body_precedence_uses_case_insensitive_headers(): void
    {
        $normalized = RequestOptions::normalizeBodyOptions([
            'body' => ['nested' => ['value' => 'present']],
            'headers' => [
                'content-type' => 'application/x-www-form-urlencoded',
            ],
        ]);

        self::assertSame(
            ['nested' => ['value' => 'present']],
            $normalized['form_params'],
        );
        self::assertArrayNotHasKey('body', $normalized);
        self::assertSame(
            'application/x-www-form-urlencoded',
            RequestOptions::getHeaderValue(
                $normalized['headers'],
                'Content-Type',
            ),
        );
    }

    public function test_request_body_options_override_client_defaults(): void
    {
        $merged = RequestOptions::merge(
            ['json' => ['default' => true]],
            ['body' => 'request payload'],
        );

        self::assertSame('request payload', $merged['body']);
        self::assertArrayNotHasKey('json', $merged);
    }

    public function test_null_request_body_clears_client_body_defaults(): void
    {
        $merged = RequestOptions::merge(
            ['json' => ['default' => true]],
            ['body' => null],
        );

        self::assertArrayNotHasKey('json', $merged);
        self::assertArrayHasKey('body', $merged);
        self::assertNull($merged['body']);
    }

    public function test_extract_body_returns_empty_raw_payload(): void
    {
        self::assertSame(
            ['', null],
            RequestOptions::extractBodyAndContentType(['body' => '']),
        );
    }

    public function test_null_body_is_treated_as_no_payload(): void
    {
        self::assertSame(
            [null, null],
            RequestOptions::extractBodyAndContentType(['body' => null]),
        );
    }

    public function test_multipart_removes_case_insensitive_content_type(): void
    {
        $normalized = RequestOptions::normalizeBodyOptions([
            'multipart' => [['name' => 'upload', 'contents' => 'file']],
            'headers' => ['content-type' => 'multipart/form-data'],
        ]);

        self::assertArrayHasKey('multipart', $normalized);
        self::assertArrayNotHasKey('Content-Type', $normalized['headers']);
        self::assertArrayNotHasKey('content-type', $normalized['headers']);
    }

    public function test_array_body_multipart_removes_lowercase_type_header(
    ): void {
        $normalized = RequestOptions::normalizeBodyOptions([
            'body' => [['name' => 'upload', 'contents' => 'fixture']],
            'headers' => ['content-type' => 'multipart/form-data'],
        ]);

        self::assertArrayHasKey('multipart', $normalized);
        self::assertArrayNotHasKey('Content-Type', $normalized['headers']);
        self::assertArrayNotHasKey('content-type', $normalized['headers']);
    }

    public function test_merge_deep_merges_headers(): void
    {
        $options1 = [
            'headers' => ['X-Header-1' => 'value1'],
        ];

        $options2 = [
            'headers' => ['X-Header-2' => 'value2'],
        ];

        $merged = RequestOptions::merge($options1, $options2);

        $this->assertArrayHasKey('X-Header-1', $merged['headers']);
        $this->assertArrayHasKey('X-Header-2', $merged['headers']);
        $this->assertEquals('value1', $merged['headers']['X-Header-1']);
        $this->assertEquals('value2', $merged['headers']['X-Header-2']);
    }

    public function test_merge_deep_merges_query_parameters(): void
    {
        $options1 = [
            'query' => ['page' => 1],
        ];

        $options2 = [
            'query' => ['limit' => 10],
        ];

        $merged = RequestOptions::merge($options1, $options2);

        $this->assertArrayHasKey('page', $merged['query']);
        $this->assertArrayHasKey('limit', $merged['query']);
        $this->assertEquals(1, $merged['query']['page']);
        $this->assertEquals(10, $merged['query']['limit']);
    }

    public function test_validate_accepts_valid_options(): void
    {
        $options = [
            'timeout' => 30,
            'retries' => 3,
            'retry_delay' => 100,
            'base_uri' => 'https://api.example.com',
        ];

        // Should not throw
        RequestOptions::validate($options);

        $this->assertTrue(true); // Assertion to confirm no exception
    }

    public function test_validate_throws_on_negative_timeout(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Timeout must be a non-negative integer');

        RequestOptions::validate(['timeout' => -1]);
    }

    public function test_validate_throws_on_negative_retries(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Retries must be a non-negative integer');

        RequestOptions::validate(['retries' => -1]);
    }

    public function test_validate_throws_on_invalid_base_uri(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base URI');

        RequestOptions::validate(['base_uri' => 'not-a-valid-url']);
    }
}
