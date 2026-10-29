<?php

namespace Tests\Unit;

use Fetch\Enum\ContentType;
use Fetch\Http\Client;
use Fetch\Support\RequestOptions;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;
use PHPUnit\Framework\TestCase;

class HelperFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset the fetch client to avoid state between tests
        fetch_client(null, true);
        MockServer::resetInstance();
    }

    protected function tearDown(): void
    {
        MockServer::resetInstance();
        fetch_client(null, true);
        parent::tearDown();
    }

    public function test_fetch_client_returns_client_instance(): void
    {
        $client = fetch_client();
        $this->assertInstanceOf(Client::class, $client);
    }

    public function test_fetch_client_with_options(): void
    {
        $options = ['timeout' => 60];
        $client = fetch_client($options);

        $handler = $client->getHandler();
        $this->assertEquals(60, $handler->getEffectiveTimeout());
    }

    public function test_process_request_options(): void
    {
        $options = [
            'method' => 'POST',
            'headers' => ['X-Custom' => 'value'],
            'json' => ['name' => 'John'],
            'query' => ['page' => 1],
            'timeout' => 30,
        ];

        $processed = process_request_options($options);

        $this->assertEquals('POST', $processed['method']);
        $this->assertEquals(['X-Custom' => 'value', 'Content-Type' => 'application/json'], $processed['headers']);
        $this->assertEquals(['name' => 'John'], $processed['json']);
        $this->assertEquals(['page' => 1], $processed['query']);
        $this->assertEquals(30, $processed['timeout']);
    }

    public function test_extract_body_and_content_type(): void
    {
        // Test with JSON
        [$body, $contentType] = extract_body_and_content_type(['json' => ['name' => 'John']]);
        $this->assertEquals(['name' => 'John'], $body);
        $this->assertEquals(ContentType::JSON, $contentType);

        // Test with form
        [$body, $contentType] = extract_body_and_content_type(['form' => ['name' => 'John']]);
        $this->assertEquals(['name' => 'John'], $body);
        $this->assertEquals(ContentType::FORM_URLENCODED, $contentType);

        // Test with raw body
        [$body, $contentType] = extract_body_and_content_type([
            'body' => 'raw content',
            'content_type' => 'text/plain',
        ]);

        $this->assertEquals('raw content', $body);
        $this->assertEquals('text/plain', $contentType->value);

        [$body, $contentType] = extract_body_and_content_type([
            'body' => 'raw header body',
            'headers' => ['Content-Type' => ['text/plain']],
        ]);
        $this->assertSame('raw header body', $body);
        $this->assertSame(ContentType::TEXT, $contentType);

        [$body, $contentType] = extract_body_and_content_type([
            'json' => ['winner' => true],
            'form' => ['ignored' => true],
            'body' => 'ignored',
        ]);
        $this->assertSame(['winner' => true], $body);
        $this->assertSame(ContentType::JSON, $contentType);
    }

    public function test_base_uri_preserves_form_and_multipart_array_bodies(): void
    {
        MockServer::fake([
            'https://example.com/submit' => MockResponse::ok(),
        ]);

        $formData = ['name' => 'Ada Lovelace'];
        $multipartData = [['name' => 'field', 'contents' => 'multipart value']];

        $this->assertSame(
            $formData,
            RequestOptions::normalizeBodyOptions([
                'body' => $formData,
                'content_type' => ContentType::FORM_URLENCODED,
            ])['form_params']
        );
        $this->assertSame(
            $multipartData,
            RequestOptions::normalizeBodyOptions([
                'body' => $multipartData,
                'content_type' => ContentType::MULTIPART,
            ])['multipart']
        );

        fetch('/submit', [
            'base_uri' => 'https://example.com',
            'method' => 'POST',
            'body' => ['name' => 'Ada Lovelace'],
            'content_type' => ContentType::FORM_URLENCODED,
        ]);

        MockServer::assertSent(function ($request): bool {
            return (string) $request->getBody() === 'name=Ada+Lovelace'
                && $request->getHeaderLine('Content-Type') === ContentType::FORM_URLENCODED->value;
        });

        fetch('/submit', [
            'base_uri' => 'https://example.com',
            'method' => 'POST',
            'body' => [['name' => 'field', 'contents' => 'multipart value']],
            'content_type' => ContentType::MULTIPART,
        ]);

        MockServer::assertSent('POST https://example.com/submit', 2);
    }

    public function test_request_method(): void
    {
        MockServer::fake([
            'https://example.com/users*' => MockResponse::json(['users' => []]),
        ]);

        $response = request_method('GET', 'https://example.com/users', ['page' => 1], [], true);

        $this->assertSame(['users' => []], $response->json());
    }
}
