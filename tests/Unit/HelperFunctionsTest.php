<?php

namespace Tests\Unit;

use Fetch\Enum\ContentType;
use Fetch\Http\Client;
use Fetch\Interfaces\Response as ResponseInterface;
use PHPUnit\Framework\TestCase;

class HelperFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset the fetch client to avoid state between tests
        fetch_client(null, true);
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
        $this->assertEquals(['X-Custom' => 'value'], $processed['headers']);
        $this->assertEquals(['name' => 'John'], $processed['body']);
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
    }

    public function test_request_method(): void
    {
        // Create a mock for the ResponseInterface
        $mockResponse = $this->createMock(ResponseInterface::class);

        // Set up the global fetch function (using Closure::bind or similar technique)
        // This is complex and depends on how your codebase is structured
        // You might need to use PHP's runkit extension or other methods

        // Here's a conceptual example - actual implementation will vary:
        /*
        $this->setFetchCallback(function($url, $options) use ($mockResponse) {
            $this->assertEquals('https://example.com', $url);
            $this->assertEquals('GET', $options['method']);
            $this->assertEquals(['page' => 1], $options['query']);
            return $mockResponse;
        });

        $result = request_method('GET', 'https://example.com', ['page' => 1], [], true);
        $this->assertSame($mockResponse, $result);
        */
    }
}
