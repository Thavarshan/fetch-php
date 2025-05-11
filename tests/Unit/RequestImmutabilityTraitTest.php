<?php

declare(strict_types=1);

namespace Tests\Fetch\Traits;

use Fetch\Enum\Method;
use Fetch\Http\Request;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;

class RequestImmutabilityTraitTest extends TestCase
{
    public function test_immutability_when_adding_header(): void
    {
        $request = Request::get('https://api.example.com/users');
        $newRequest = $request->withAddedHeader('Accept', 'application/json');

        $this->assertNotSame($request, $newRequest, 'withAddedHeader should return a new instance');
        $this->assertInstanceOf(Request::class, $newRequest, 'New instance should be of the same type');
        $this->assertEquals('application/json', $newRequest->getHeaderLine('Accept'), 'New header should be present in new instance');
        $this->assertFalse($request->hasHeader('Accept'), 'Original instance should not be modified');
    }

    public function test_immutability_when_removing_header(): void
    {
        $request = Request::get('https://api.example.com/users', ['Accept' => 'application/json']);
        $newRequest = $request->withoutHeader('Accept');

        $this->assertNotSame($request, $newRequest, 'withoutHeader should return a new instance');
        $this->assertInstanceOf(Request::class, $newRequest, 'New instance should be of the same type');
        $this->assertFalse($newRequest->hasHeader('Accept'), 'Header should be removed in new instance');
        $this->assertTrue($request->hasHeader('Accept'), 'Original instance should not be modified');
    }

    public function test_immutability_when_replacing_header(): void
    {
        $request = Request::get('https://api.example.com/users', ['Accept' => 'application/json']);
        $newRequest = $request->withHeader('Accept', 'text/html');

        $this->assertNotSame($request, $newRequest, 'withHeader should return a new instance');
        $this->assertInstanceOf(Request::class, $newRequest, 'New instance should be of the same type');
        $this->assertEquals('text/html', $newRequest->getHeaderLine('Accept'), 'Header should be updated in new instance');
        $this->assertEquals('application/json', $request->getHeaderLine('Accept'), 'Original instance should not be modified');
    }

    public function test_immutability_when_changing_protocol_version(): void
    {
        $request = Request::get('https://api.example.com/users');
        $newRequest = $request->withProtocolVersion('2.0');

        $this->assertNotSame($request, $newRequest, 'withProtocolVersion should return a new instance');
        $this->assertInstanceOf(Request::class, $newRequest, 'New instance should be of the same type');
        $this->assertEquals('2.0', $newRequest->getProtocolVersion(), 'Protocol version should be updated in new instance');
        $this->assertEquals('1.1', $request->getProtocolVersion(), 'Original instance should not be modified');
    }

    public function test_immutability_when_changing_uri(): void
    {
        $request = Request::get('https://api.example.com/users');
        $newUri = new Uri('https://api.example.com/posts');
        $newRequest = $request->withUri($newUri);

        $this->assertNotSame($request, $newRequest, 'withUri should return a new instance');
        $this->assertInstanceOf(Request::class, $newRequest, 'New instance should be of the same type');
        $this->assertEquals('https://api.example.com/posts', (string) $newRequest->getUri(), 'URI should be updated in new instance');
        $this->assertEquals('https://api.example.com/users', (string) $request->getUri(), 'Original instance should not be modified');
    }

    public function test_immutability_when_changing_method(): void
    {
        $request = Request::get('https://api.example.com/users');
        $newRequest = $request->withMethod(Method::POST->value);

        $this->assertNotSame($request, $newRequest, 'withMethod should return a new instance');
        $this->assertInstanceOf(Request::class, $newRequest, 'New instance should be of the same type');
        $this->assertEquals(Method::POST->value, $newRequest->getMethod(), 'Method should be updated in new instance');
        $this->assertEquals(Method::GET->value, $request->getMethod(), 'Original instance should not be modified');
    }

    public function test_immutability_when_changing_request_target(): void
    {
        $request = Request::get('https://api.example.com/users');
        $newRequest = $request->withRequestTarget('/posts');

        $this->assertNotSame($request, $newRequest, 'withRequestTarget should return a new instance');
        $this->assertInstanceOf(Request::class, $newRequest, 'New instance should be of the same type');
        $this->assertEquals('/posts', $newRequest->getRequestTarget(), 'Request target should be updated in new instance');
        $this->assertEquals('/users', $request->getRequestTarget(), 'Original instance should not be modified');
    }

    public function test_request_target_preserved_when_changing_uri(): void
    {
        $request = Request::get('https://api.example.com/users')->withRequestTarget('/custom-target');
        $newUri = new Uri('https://api.example.com/posts');
        $newRequest = $request->withUri($newUri);

        $this->assertEquals('/custom-target', $newRequest->getRequestTarget(), 'Custom request target should be preserved when changing URI');
    }

    public function test_protocol_version_preserved_when_changing_uri(): void
    {
        $request = Request::get('https://api.example.com/users')->withProtocolVersion('2.0');
        $newUri = new Uri('https://api.example.com/posts');
        $newRequest = $request->withUri($newUri);

        $this->assertEquals('2.0', $newRequest->getProtocolVersion(), 'Protocol version should be preserved when changing URI');
    }

    public function test_chained_method_calls_preserve_immutability(): void
    {
        $request = Request::get('https://api.example.com/users');

        $newRequest = $request
            ->withHeader('Accept', 'application/json')
            ->withMethod(Method::POST->value)
            ->withProtocolVersion('2.0')
            ->withRequestTarget('/custom-target');

        $this->assertNotSame($request, $newRequest, 'Chained calls should return a new instance');
        $this->assertInstanceOf(Request::class, $newRequest, 'New instance should be of the same type');

        // Verify all properties were updated
        $this->assertEquals('application/json', $newRequest->getHeaderLine('Accept'));
        $this->assertEquals(Method::POST->value, $newRequest->getMethod());
        $this->assertEquals('2.0', $newRequest->getProtocolVersion());
        $this->assertEquals('/custom-target', $newRequest->getRequestTarget());

        // Verify original is unchanged
        $this->assertFalse($request->hasHeader('Accept'));
        $this->assertEquals(Method::GET->value, $request->getMethod());
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals('/users', $request->getRequestTarget());
    }

    public function test_body_preserved_during_immutable_operations(): void
    {
        $body = 'test body content';
        $request = Request::post('https://api.example.com/users', $body);

        // Change something unrelated to body
        $newRequest = $request->withHeader('X-Test', 'value');

        $this->assertEquals($body, $newRequest->getBodyAsString(), 'Body content should be preserved');
    }

    public function test_headers_preserved_during_immutable_operations(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Api-Key' => 'abcd1234',
        ];

        $request = Request::get('https://api.example.com/users', $headers);

        // Change something unrelated to these headers
        $newRequest = $request->withProtocolVersion('2.0');

        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $newRequest->getHeaderLine($name), "Header '$name' should be preserved");
        }
    }

    public function test_adding_same_header_twice(): void
    {
        $request = Request::get('https://api.example.com/users')
            ->withAddedHeader('Accept', 'application/json')
            ->withAddedHeader('Accept', 'text/html');

        $expected = ['application/json', 'text/html'];
        $this->assertEquals($expected, $request->getHeader('Accept'), 'Both headers should be preserved');
    }

    public function test_request_target_derived_from_uri(): void
    {
        $request = Request::get('https://api.example.com/users/123?query=value#fragment');

        $this->assertEquals('/users/123?query=value', $request->getRequestTarget(), 'Request target should be derived from URI path and query');
    }

    public function test_immutability_with_query_parameters(): void
    {
        $request = Request::get('https://api.example.com/users');
        $newRequest = $request->withQueryParam('page', '2');

        $this->assertNotSame($request, $newRequest, 'withQueryParam should return a new instance');
        $this->assertEquals('https://api.example.com/users?page=2', (string) $newRequest->getUri(), 'Query param should be added');
        $this->assertEquals('https://api.example.com/users', (string) $request->getUri(), 'Original instance should not be modified');
    }
}
