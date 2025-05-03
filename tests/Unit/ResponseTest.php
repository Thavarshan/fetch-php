<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Http\Response;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SimpleXMLElement;
use stdClass;

class ResponseTest extends TestCase
{
    public function test_constructor_and_basic_accessors()
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"name":"Test","value":123}'
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['Content-Type' => ['application/json']], $response->getHeaders());
        $this->assertEquals('{"name":"Test","value":123}', $response->body());
    }

    public function test_create_from_base()
    {
        $baseResponse = new GuzzleResponse(
            204,
            ['X-Test-Header' => 'Test Value'],
            'Test Body'
        );

        $response = Response::createFromBase($baseResponse);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(['X-Test-Header' => ['Test Value']], $response->getHeaders());
        $this->assertEquals('Test Body', $response->body());
    }

    public function test_json_decoding()
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"name":"Test","value":123,"items":[1,2,3]}'
        );

        // Test array decoding
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertEquals('Test', $data['name']);
        $this->assertEquals(123, $data['value']);
        $this->assertEquals([1, 2, 3], $data['items']);

        // Test object decoding
        $obj = $response->json(false);
        $this->assertIsObject($obj);
        $this->assertEquals('Test', $obj->name);
        $this->assertEquals(123, $obj->value);
        $this->assertEquals([1, 2, 3], $obj->items);

        // Test array() shorthand
        $array = $response->array();
        $this->assertIsArray($array);
        $this->assertEquals('Test', $array['name']);

        // Test object() shorthand
        $object = $response->object();
        $this->assertIsObject($object);
        $this->assertEquals('Test', $object->name);
    }

    public function test_json_decoding_exception()
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"invalid json'
        );

        $this->expectException(RuntimeException::class);
        $response->json();
    }

    public function test_json_decoding_exception_suppression()
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"invalid json'
        );

        $result = $response->json(true, false);
        $this->assertEquals([], $result);

        $objResult = $response->object(false);
        $this->assertEquals(new stdClass, $objResult);
    }

    public function test_xml_decoding()
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/xml'],
            '<?xml version="1.0"?><root><name>Test</name><value>123</value></root>'
        );

        $xml = $response->xml();
        $this->assertInstanceOf(SimpleXMLElement::class, $xml);
        $this->assertEquals('Test', (string) $xml->name);
        $this->assertEquals('123', (string) $xml->value);
    }

    public function test_xml_decoding_exception()
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/xml'],
            '<?xml version="1.0"?><invalid>'
        );

        $this->expectException(RuntimeException::class);
        $response->xml();
    }

    public function test_xml_decoding_exception_suppression()
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/xml'],
            '<?xml version="1.0"?><invalid>'
        );

        $result = $response->xml(0, false);
        $this->assertNull($result);
    }

    public function test_status_code_checkers()
    {
        // 1xx - Informational
        $response = new Response(100);
        $this->assertTrue($response->isInformational());
        $this->assertFalse($response->ok());
        $this->assertFalse($response->isRedirection());
        $this->assertFalse($response->isClientError());
        $this->assertFalse($response->isServerError());

        // 2xx - Success
        $response = new Response(200);
        $this->assertFalse($response->isInformational());
        $this->assertTrue($response->ok());
        $this->assertTrue($response->successful());
        $this->assertFalse($response->isRedirection());
        $this->assertFalse($response->isClientError());
        $this->assertFalse($response->isServerError());
        $this->assertFalse($response->failed());

        // 3xx - Redirection
        $response = new Response(301);
        $this->assertFalse($response->isInformational());
        $this->assertFalse($response->ok());
        $this->assertTrue($response->isRedirection());
        $this->assertTrue($response->redirect());
        $this->assertFalse($response->isClientError());
        $this->assertFalse($response->isServerError());

        // 4xx - Client Error
        $response = new Response(404);
        $this->assertFalse($response->isInformational());
        $this->assertFalse($response->ok());
        $this->assertFalse($response->isRedirection());
        $this->assertTrue($response->isClientError());
        $this->assertTrue($response->clientError());
        $this->assertFalse($response->isServerError());
        $this->assertTrue($response->failed());

        // 5xx - Server Error
        $response = new Response(500);
        $this->assertFalse($response->isInformational());
        $this->assertFalse($response->ok());
        $this->assertFalse($response->isRedirection());
        $this->assertFalse($response->isClientError());
        $this->assertTrue($response->isServerError());
        $this->assertTrue($response->serverError());
        $this->assertTrue($response->failed());
    }

    public function test_specific_status_code_checkers()
    {
        $this->assertTrue((new Response(200))->isOk());
        $this->assertTrue((new Response(201))->isCreated());
        $this->assertTrue((new Response(202))->isAccepted());
        $this->assertTrue((new Response(204))->isNoContent());
        $this->assertTrue((new Response(301))->isMovedPermanently());
        $this->assertTrue((new Response(302))->isFound());
        $this->assertTrue((new Response(400))->isBadRequest());
        $this->assertTrue((new Response(401))->isUnauthorized());
        $this->assertTrue((new Response(403))->isForbidden());
        $this->assertTrue((new Response(404))->isNotFound());
        $this->assertTrue((new Response(409))->isConflict());
        $this->assertTrue((new Response(422))->isUnprocessableEntity());
        $this->assertTrue((new Response(429))->isTooManyRequests());
        $this->assertTrue((new Response(500))->isInternalServerError());
        $this->assertTrue((new Response(503))->isServiceUnavailable());
    }

    public function test_to_string()
    {
        $response = new Response(200, [], 'Test Body');
        $this->assertEquals('Test Body', (string) $response);
    }

    public function test_header_accessors()
    {
        $response = new Response(
            200,
            [
                'Content-Type' => 'application/json',
                'X-Test-Header' => 'Test Value',
            ]
        );

        $this->assertEquals('application/json', $response->contentType());
        $this->assertEquals('Test Value', $response->header('X-Test-Header'));
        $this->assertNull($response->header('Non-Existent'));
        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertFalse($response->hasHeader('X-Missing'));

        $headers = $response->headers();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('X-Test-Header', $headers);
    }

    public function test_array_access()
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"name":"Test","value":123,"nested":{"prop":"value"}}'
        );

        $this->assertTrue(isset($response['name']));
        $this->assertEquals('Test', $response['name']);
        $this->assertEquals(123, $response['value']);
        $this->assertEquals(['prop' => 'value'], $response['nested']);

        $this->expectException(RuntimeException::class);
        $response['name'] = 'Modified';
    }

    public function test_array_unset()
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"name":"Test"}'
        );

        $this->expectException(RuntimeException::class);
        unset($response['name']);
    }

    public function test_blob()
    {
        $response = new Response(200, [], 'Binary Data');
        $blob = $response->blob();

        $this->assertIsResource($blob);
        $contents = stream_get_contents($blob);
        $this->assertEquals('Binary Data', $contents);
    }

    public function test_array_buffer()
    {
        $response = new Response(200, [], 'Binary Data');
        $buffer = $response->arrayBuffer();

        $this->assertIsString($buffer);
        $this->assertEquals('Binary Data', $buffer);
    }

    public function test_get_method()
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"name":"Test","value":123}'
        );

        $this->assertEquals('Test', $response->get('name'));
        $this->assertEquals(123, $response->get('value'));
        $this->assertEquals('default', $response->get('missing', 'default'));
        $this->assertNull($response->get('nonexistent'));
    }
}
