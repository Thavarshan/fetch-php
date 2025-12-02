<?php

namespace Tests\Unit;

use Fetch\Support\DebugInfo;
use GuzzleHttp\Psr7\Response as Psr7Response;
use PHPUnit\Framework\TestCase;

class DebugInfoTest extends TestCase
{
    public function testCreateWithBasicRequestData(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            ['headers' => ['Authorization' => 'Bearer token']],
            null,
            ['total_time' => 100.5],
            [],
            1024
        );

        $this->assertInstanceOf(DebugInfo::class, $debugInfo);
        $this->assertEquals(['total_time' => 100.5], $debugInfo->getTimings());
        $this->assertEquals(1024, $debugInfo->getMemoryUsage());
    }

    public function testCreateWithResponse(): void
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

    public function testFormatRequest(): void
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

    public function testFormatRequestWithDisabledHeaders(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            ['headers' => ['Accept' => 'application/json']],
        );

        $formatted = $debugInfo->formatRequest(['request_headers' => false]);

        $this->assertArrayNotHasKey('headers', $formatted);
    }

    public function testFormatResponse(): void
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

    public function testFormatResponseReturnsNullWhenNoResponse(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            []
        );

        $this->assertNull($debugInfo->formatResponse());
    }

    public function testFormatResponseBodyTruncation(): void
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

    public function testToArray(): void
    {
        $response = new Psr7Response(200, [], '{"data":"test"}');

        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            [],
            $response,
            ['total_time' => 50.0],
            [],
            2048
        );

        $array = $debugInfo->toArray();

        $this->assertArrayHasKey('request', $array);
        $this->assertArrayHasKey('response', $array);
        $this->assertArrayHasKey('performance', $array);
        $this->assertArrayHasKey('memory', $array);
        $this->assertEquals(2048, $array['memory']['bytes']);
    }

    public function testDumpReturnsJsonString(): void
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

    public function testToStringReturnsJson(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            []
        );

        $string = (string) $debugInfo;

        $this->assertJson($string);
    }

    public function testGetAndSetDefaultOptions(): void
    {
        $originalOptions = DebugInfo::getDefaultOptions();

        DebugInfo::setDefaultOptions(['response_body' => false]);
        $newOptions = DebugInfo::getDefaultOptions();

        $this->assertFalse($newOptions['response_body']);

        // Restore original options
        DebugInfo::setDefaultOptions($originalOptions);
    }

    public function testFormatBodyWithArray(): void
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

    public function testMemoryFormatting(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            [],
            null,
            [],
            [],
            1024 * 1024 // 1MB
        );

        $array = $debugInfo->toArray();

        $this->assertArrayHasKey('memory', $array);
        $this->assertEquals('1 MB', $array['memory']['formatted']);
    }

    public function testJsonBodyOptionIsCaptured(): void
    {
        $debugInfo = DebugInfo::create(
            'POST',
            'https://example.com/api',
            ['json' => ['name' => 'test', 'value' => 123]],
        );

        $requestData = $debugInfo->getRequestData();

        $this->assertEquals(['name' => 'test', 'value' => 123], $requestData['body']);
    }

    public function testSensitiveHeadersAreRedactedInRequest(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            [
                'headers' => [
                    'Authorization' => 'Bearer secret-token',
                    'X-Api-Key' => 'my-api-key',
                    'Cookie' => 'session=abc123',
                    'Content-Type' => 'application/json',
                ],
            ],
        );

        $requestData = $debugInfo->getRequestData();
        $headers = $requestData['headers'];

        // Sensitive headers should be redacted
        $this->assertEquals('[REDACTED]', $headers['Authorization']);
        $this->assertEquals('[REDACTED]', $headers['X-Api-Key']);
        $this->assertEquals('[REDACTED]', $headers['Cookie']);

        // Non-sensitive headers should be preserved
        $this->assertEquals('application/json', $headers['Content-Type']);
    }

    public function testSensitiveHeadersAreRedactedInResponse(): void
    {
        $response = new Psr7Response(
            200,
            [
                'Content-Type' => 'application/json',
                'Set-Cookie' => 'session=xyz789; HttpOnly',
                'X-Auth-Token' => 'refresh-token',
            ],
            '{"data":"test"}'
        );

        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            [],
            $response
        );

        $formatted = $debugInfo->formatResponse();
        $headers = $formatted['headers'];

        // Sensitive response headers should be redacted
        $this->assertEquals(['[REDACTED]'], $headers['Set-Cookie']);
        $this->assertEquals(['[REDACTED]'], $headers['X-Auth-Token']);

        // Non-sensitive headers should be preserved
        $this->assertEquals(['application/json'], $headers['Content-Type']);
    }

    public function testAuthOptionIsRedacted(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            [
                'auth' => ['username', 'password'],
                'headers' => ['Accept' => 'application/json'],
            ],
        );

        $requestData = $debugInfo->getRequestData();

        // Auth credentials should not appear in stored data
        // The sanitization strips them before storage
        $this->assertArrayNotHasKey('auth', $requestData);
        $this->assertEquals('application/json', $requestData['headers']['Accept']);
    }

    public function testArrayValuedSensitiveHeadersAreRedacted(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            [
                'headers' => [
                    'Authorization' => ['Bearer token1', 'Bearer token2'],
                    'Content-Type' => 'application/json',
                ],
            ],
        );

        $requestData = $debugInfo->getRequestData();
        $headers = $requestData['headers'];

        // Array-valued sensitive headers should have all values redacted
        $this->assertEquals(['[REDACTED]', '[REDACTED]'], $headers['Authorization']);
        $this->assertEquals('application/json', $headers['Content-Type']);
    }

    public function testCaseInsensitiveHeaderRedaction(): void
    {
        $debugInfo = DebugInfo::create(
            'GET',
            'https://example.com/api',
            [
                'headers' => [
                    'authorization' => 'Bearer token',
                    'AUTHORIZATION' => 'Bearer another-token',
                    'x-API-key' => 'my-key',
                ],
            ],
        );

        $requestData = $debugInfo->getRequestData();
        $headers = $requestData['headers'];

        // Case-insensitive matching should work
        $this->assertEquals('[REDACTED]', $headers['authorization']);
        $this->assertEquals('[REDACTED]', $headers['AUTHORIZATION']);
        $this->assertEquals('[REDACTED]', $headers['x-API-key']);
    }
}
