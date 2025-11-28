<?php

namespace Tests\Unit;

use Fetch\Support\DebugInfo;
use GuzzleHttp\Psr7\Response as Psr7Response;
use PHPUnit\Framework\TestCase;

class DebugInfoTest extends TestCase
{
    public function test_create_with_basic_request_data(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            ['headers' => ['Authorization' => 'Bearer token']],
            null,
            ['total_time' => 100.5],
            1024
        );

        $this->assertInstanceOf(DebugInfo::class, $debugInfo);
        $this->assertEquals(['total_time' => 100.5], $debugInfo->getTimings());
        $this->assertEquals(1024, $debugInfo->getMemoryUsage());
    }

    public function test_create_with_response(): void
    {
        $response = new Psr7Response(200, ['Content-Type' => 'application/json'], '{"data":"test"}');

        $debugInfo = DebugInfo::create(
            'POST',
            'https://example.com/api',
            ['headers' => ['Content-Type' => 'application/json'], 'body' => ['test' => 'data']],
            $response
        );

        $this->assertSame($response, $debugInfo->getResponse());
        $requestData = $debugInfo->getRequestData();
        $this->assertEquals('POST', $requestData['method']);
        $this->assertEquals('https://example.com/api', $requestData['uri']);
    }

    public function test_format_request(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            ['headers' => ['Accept' => 'application/json']],
        );

        $formatted = $debugInfo->formatRequest();

        $this->assertEquals('GET', $formatted['method']);
        $this->assertEquals('https://example.com/api', $formatted['uri']);
        $this->assertArrayHasKey('headers', $formatted);
    }

    public function test_format_request_with_disabled_headers(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            ['headers' => ['Accept' => 'application/json']],
        );

        $formatted = $debugInfo->formatRequest(['request_headers' => false]);

        $this->assertArrayNotHasKey('headers', $formatted);
    }

    public function test_format_response(): void
    {
        $response = new Psr7Response(200, ['Content-Type' => 'application/json'], '{"status":"ok"}');

        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            [],
            $response
        );

        $formatted = $debugInfo->formatResponse();

        $this->assertEquals(200, $formatted['status_code']);
        $this->assertEquals('OK', $formatted['reason_phrase']);
        $this->assertArrayHasKey('headers', $formatted);
        $this->assertArrayHasKey('body', $formatted);
    }

    public function test_format_response_returns_null_when_no_response(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            []
        );

        $this->assertNull($debugInfo->formatResponse());
    }

    public function test_format_response_body_truncation(): void
    {
        $longBody = str_repeat('x', 2000);
        $response = new Psr7Response(200, [], $longBody);

        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            [],
            $response
        );

        // Default truncation is 1024 bytes
        $formatted = $debugInfo->formatResponse(['response_body' => 100]);

        $this->assertStringContainsString('... (truncated)', $formatted['body']);
        $this->assertLessThan(200, strlen($formatted['body']));
    }

    public function test_to_array(): void
    {
        $response = new Psr7Response(200, [], '{"data":"test"}');

        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            [],
            $response,
            ['total_time' => 50.0],
            2048
        );

        $array = $debugInfo->toArray();

        $this->assertArrayHasKey('request', $array);
        $this->assertArrayHasKey('response', $array);
        $this->assertArrayHasKey('performance', $array);
        $this->assertArrayHasKey('memory', $array);
        $this->assertEquals(2048, $array['memory']['bytes']);
    }

    public function test_dump_returns_json_string(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            []
        );

        $json = $debugInfo->dump();

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('request', $decoded);
    }

    public function test_to_string_returns_json(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            []
        );

        $string = (string) $debugInfo;

        $this->assertJson($string);
    }

    public function test_get_and_set_default_options(): void
    {
        $originalOptions = DebugInfo::getDefaultOptions();

        DebugInfo::setDefaultOptions(['response_body' => false]);
        $newOptions = DebugInfo::getDefaultOptions();

        $this->assertFalse($newOptions['response_body']);

        // Restore original options
        DebugInfo::setDefaultOptions($originalOptions);
    }

    public function test_format_body_with_array(): void
    {
        $debugInfo = DebugInfo::create(
            'POST',
            'https://example.com/api',
            ['body' => ['key' => 'value']],
        );

        $formatted = $debugInfo->formatRequest();

        // Array bodies are JSON-encoded for readability
        $this->assertArrayHasKey('body', $formatted);
    }

    public function test_memory_formatting(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            [],
            null,
            [],
            1024 * 1024 // 1MB
        );

        $array = $debugInfo->toArray();

        $this->assertArrayHasKey('memory', $array);
        $this->assertEquals('1 MB', $array['memory']['formatted']);
    }

    public function test_json_body_option_is_captured(): void
    {
        $debugInfo = DebugInfo::create(
            'POST',
            'https://example.com/api',
            ['json' => ['name' => 'test', 'value' => 123]],
        );

        $requestData = $debugInfo->getRequestData();

        $this->assertEquals(['name' => 'test', 'value' => 123], $requestData['body']);
    }
}
