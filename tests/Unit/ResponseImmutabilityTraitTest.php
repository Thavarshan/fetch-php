<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Enum\ContentType;
use Fetch\Enum\Status;
use Fetch\Http\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

class ResponseImmutabilityTraitTest extends TestCase
{
    public function test_immutability_when_changing_status(): void
    {
        $response = new Response(Status::OK);
        $newResponse = $response->withStatus(Status::CREATED->value);

        $this->assertNotSame($response, $newResponse, 'withStatus should return a new instance');
        $this->assertInstanceOf(Response::class, $newResponse, 'New instance should be of the same type');
        $this->assertEquals(Status::CREATED->value, $newResponse->getStatusCode(), 'Status code should be updated in new instance');
        $this->assertEquals(Status::OK->value, $response->getStatusCode(), 'Original instance should not be modified');
    }

    public function test_immutability_when_changing_status_with_reason(): void
    {
        $response = new Response(Status::OK);
        $newResponse = $response->withStatus(Status::CREATED->value, 'Custom Created');

        $this->assertNotSame($response, $newResponse, 'withStatus should return a new instance');
        $this->assertInstanceOf(Response::class, $newResponse, 'New instance should be of the same type');
        $this->assertEquals(Status::CREATED->value, $newResponse->getStatusCode(), 'Status code should be updated in new instance');
        $this->assertEquals('Custom Created', $newResponse->getReasonPhrase(), 'Reason phrase should be updated in new instance');
        $this->assertEquals(Status::OK->value, $response->getStatusCode(), 'Original instance should not be modified');
    }

    public function test_immutability_when_adding_header(): void
    {
        $response = new Response;
        $newResponse = $response->withAddedHeader('Content-Type', ContentType::JSON->value);

        $this->assertNotSame($response, $newResponse, 'withAddedHeader should return a new instance');
        $this->assertInstanceOf(Response::class, $newResponse, 'New instance should be of the same type');
        $this->assertEquals(ContentType::JSON->value, $newResponse->getHeaderLine('Content-Type'), 'Header should be added in new instance');
        $this->assertFalse($response->hasHeader('Content-Type'), 'Original instance should not be modified');
    }

    public function test_immutability_when_removing_header(): void
    {
        $response = new Response(Status::OK, ['Content-Type' => ContentType::JSON->value]);
        $newResponse = $response->withoutHeader('Content-Type');

        $this->assertNotSame($response, $newResponse, 'withoutHeader should return a new instance');
        $this->assertInstanceOf(Response::class, $newResponse, 'New instance should be of the same type');
        $this->assertFalse($newResponse->hasHeader('Content-Type'), 'Header should be removed in new instance');
        $this->assertTrue($response->hasHeader('Content-Type'), 'Original instance should not be modified');
    }

    public function test_immutability_when_replacing_header(): void
    {
        $response = new Response(Status::OK, ['Content-Type' => ContentType::JSON->value]);
        $newResponse = $response->withHeader('Content-Type', ContentType::HTML->value);

        $this->assertNotSame($response, $newResponse, 'withHeader should return a new instance');
        $this->assertInstanceOf(Response::class, $newResponse, 'New instance should be of the same type');
        $this->assertEquals(ContentType::HTML->value, $newResponse->getHeaderLine('Content-Type'), 'Header should be updated in new instance');
        $this->assertEquals(ContentType::JSON->value, $response->getHeaderLine('Content-Type'), 'Original instance should not be modified');
    }

    public function test_immutability_when_changing_protocol_version(): void
    {
        $response = new Response;
        $newResponse = $response->withProtocolVersion('2.0');

        $this->assertNotSame($response, $newResponse, 'withProtocolVersion should return a new instance');
        $this->assertInstanceOf(Response::class, $newResponse, 'New instance should be of the same type');
        $this->assertEquals('2.0', $newResponse->getProtocolVersion(), 'Protocol version should be updated in new instance');
        $this->assertEquals('1.1', $response->getProtocolVersion(), 'Original instance should not be modified');
    }

    public function test_immutability_when_changing_body(): void
    {
        $response = new Response(Status::OK, [], 'Original body');
        $newResponse = $response->withBody(Utils::streamFor('New body'));

        $this->assertNotSame($response, $newResponse, 'withBody should return a new instance');
        $this->assertInstanceOf(Response::class, $newResponse, 'New instance should be of the same type');
        $this->assertEquals('New body', (string) $newResponse->getBody(), 'Body should be updated in new instance');
        $this->assertEquals('Original body', (string) $response->getBody(), 'Original instance should not be modified');
    }

    public function test_body_contents_preserved_when_changing_body(): void
    {
        $response = new Response(Status::OK, [], 'Original body');
        $newResponse = $response->withBody(Utils::streamFor('New body'));

        // If the Response class implements getBodyAsString() or similar
        if (method_exists($newResponse, 'getBodyAsString')) {
            $this->assertEquals('New body', $newResponse->getBodyAsString(), 'Body contents should be updated in new instance');
        } elseif (method_exists($newResponse, 'bodyContents')) {
            $this->assertEquals('New body', $newResponse->bodyContents, 'Body contents should be updated in new instance');
        } elseif (method_exists($newResponse, 'body')) {
            $this->assertEquals('New body', $newResponse->body(), 'Body contents should be updated in new instance');
        } else {
            // At minimum, check the stream content
            $this->assertEquals('New body', (string) $newResponse->getBody(), 'Body stream should be updated in new instance');
        }
    }

    public function test_reason_phrase_generated_from_status_code(): void
    {
        $response = new Response;
        $newResponse = $response->withStatus(Status::NOT_FOUND->value);

        $this->assertEquals('Not Found', $newResponse->getReasonPhrase(), 'Reason phrase should be generated from status code');
    }

    public function test_protocol_version_preserved_when_changing_status(): void
    {
        $response = new Response(Status::OK, [], '', '2.0');
        $newResponse = $response->withStatus(Status::CREATED->value);

        $this->assertEquals('2.0', $newResponse->getProtocolVersion(), 'Protocol version should be preserved when changing status');
    }

    public function test_headers_preserved_when_changing_status(): void
    {
        $headers = [
            'Content-Type' => ContentType::JSON->value,
            'X-Api-Key' => 'abcd1234',
        ];

        $response = new Response(Status::OK, $headers);
        $newResponse = $response->withStatus(Status::CREATED->value);

        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $newResponse->getHeaderLine($name), "Header '$name' should be preserved");
        }
    }

    public function test_body_preserved_when_changing_status(): void
    {
        $body = '{"status":"success"}';
        $response = new Response(Status::OK, [], $body);
        $newResponse = $response->withStatus(Status::CREATED->value);

        $this->assertEquals($body, (string) $newResponse->getBody(), 'Body should be preserved when changing status');
    }

    public function test_adding_same_header_twice(): void
    {
        $response = new Response;
        $newResponse = $response
            ->withAddedHeader('Vary', 'Accept')
            ->withAddedHeader('Vary', 'Accept-Language');

        $expected = ['Accept', 'Accept-Language'];
        $this->assertEquals($expected, $newResponse->getHeader('Vary'), 'Both headers should be preserved');
    }

    public function test_chained_method_calls_preserve_immutability(): void
    {
        $response = new Response;

        $newResponse = $response
            ->withStatus(Status::CREATED->value)
            ->withHeader('Content-Type', ContentType::JSON->value)
            ->withProtocolVersion('2.0')
            ->withBody(Utils::streamFor('{"created":true}'));

        $this->assertNotSame($response, $newResponse, 'Chained calls should return a new instance');
        $this->assertInstanceOf(Response::class, $newResponse, 'New instance should be of the same type');

        // Verify all properties were updated
        $this->assertEquals(Status::CREATED->value, $newResponse->getStatusCode());
        $this->assertEquals(ContentType::JSON->value, $newResponse->getHeaderLine('Content-Type'));
        $this->assertEquals('2.0', $newResponse->getProtocolVersion());
        $this->assertEquals('{"created":true}', (string) $newResponse->getBody());

        // Verify original is unchanged
        $this->assertEquals(Status::OK->value, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('Content-Type'));
        $this->assertEquals('1.1', $response->getProtocolVersion());
        $this->assertEquals('', (string) $response->getBody());
    }

    public function test_status_code_and_reason_phrase_properly_preserved(): void
    {
        $response = new Response(Status::OK, [], '', '1.1', 'Custom OK');
        $newResponse = $response->withHeader('X-Test', 'value');

        $this->assertEquals(Status::OK->value, $newResponse->getStatusCode(), 'Status code should be preserved');
        $this->assertEquals('Custom OK', $newResponse->getReasonPhrase(), 'Reason phrase should be preserved');
    }
}
