<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Http\Request;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function test_create_with_json_body(): void
    {
        $data = ['name' => 'John', 'email' => 'john@example.com'];
        $request = Request::json(Method::POST, 'https://api.example.com/users', $data);

        $this->assertEquals(Method::POST->value, $request->getMethod());
        $this->assertEquals('https://api.example.com/users', (string) $request->getUri());
        $this->assertEquals(ContentType::JSON->value, $request->getHeaderLine('Content-Type'));

        $expectedJson = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expectedJson, $request->getBodyAsString());
        $this->assertEquals($data, $request->getBodyAsJson());
    }

    public function test_json_encoding_failure(): void
    {
        // Create an object with a recursive reference that can't be JSON encoded
        $recursiveData = ['name' => 'John'];
        $recursiveData['self'] = &$recursiveData;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to encode data to JSON');

        Request::json(Method::POST, 'https://api.example.com/users', $recursiveData);
    }

    public function test_create_with_form_params(): void
    {
        $formParams = ['username' => 'john', 'password' => 'secret'];
        $request = Request::form(Method::POST, 'https://api.example.com/login', $formParams);

        $this->assertEquals(Method::POST->value, $request->getMethod());
        $this->assertEquals('https://api.example.com/login', (string) $request->getUri());
        $this->assertEquals(ContentType::FORM_URLENCODED->value, $request->getHeaderLine('Content-Type'));

        $expectedBody = http_build_query($formParams);
        $this->assertEquals($expectedBody, $request->getBodyAsString());
        $this->assertEquals($formParams, $request->getBodyAsFormParams());
    }

    public function test_create_with_multipart_data(): void
    {
        $multipart = [
            [
                'name' => 'field1',
                'contents' => 'value1',
            ],
            [
                'name' => 'file',
                'filename' => 'test.txt',
                'contents' => 'file contents',
                'headers' => [
                    'Content-Type' => 'text/plain',
                ],
            ],
        ];

        $request = Request::multipart(Method::POST, 'https://api.example.com/upload', $multipart);

        $this->assertEquals(Method::POST->value, $request->getMethod());
        $this->assertEquals('https://api.example.com/upload', (string) $request->getUri());
        $this->assertStringStartsWith(ContentType::MULTIPART->value, $request->getHeaderLine('Content-Type'));
        $this->assertMatchesRegularExpression('/^multipart\/form-data; boundary=.+$/', $request->getHeaderLine('Content-Type'));

        // Check that the body contains the parts
        $body = $request->getBodyAsString();
        $this->assertStringContainsString('name="field1"', $body);
        $this->assertStringContainsString('value1', $body);
        $this->assertStringContainsString('name="file"', $body);
        $this->assertStringContainsString('filename="test.txt"', $body);
        $this->assertStringContainsString('Content-Type: text/plain', $body);
        $this->assertStringContainsString('file contents', $body);
    }

    public function test_create_get_request(): void
    {
        $request = Request::get('https://api.example.com/users');

        $this->assertEquals(Method::GET->value, $request->getMethod());
        $this->assertEquals('https://api.example.com/users', (string) $request->getUri());
        $this->assertEmpty($request->getBodyAsString());
    }

    public function test_create_post_request(): void
    {
        $body = json_encode(['name' => 'John']);
        $request = Request::post('https://api.example.com/users', $body, [
            'Accept' => 'application/json',
        ], ContentType::JSON);

        $this->assertEquals(Method::POST->value, $request->getMethod());
        $this->assertEquals('https://api.example.com/users', (string) $request->getUri());
        $this->assertEquals(ContentType::JSON->value, $request->getHeaderLine('Content-Type'));
        $this->assertEquals('application/json', $request->getHeaderLine('Accept'));
        $this->assertEquals($body, $request->getBodyAsString());
    }

    public function test_create_put_request(): void
    {
        $body = json_encode(['name' => 'Updated Name']);
        $request = Request::put('https://api.example.com/users/1', $body, [], ContentType::JSON);

        $this->assertEquals(Method::PUT->value, $request->getMethod());
        $this->assertEquals('https://api.example.com/users/1', (string) $request->getUri());
        $this->assertEquals(ContentType::JSON->value, $request->getHeaderLine('Content-Type'));
        $this->assertEquals($body, $request->getBodyAsString());
    }

    public function test_create_patch_request(): void
    {
        $body = json_encode(['name' => 'Patched Name']);
        $request = Request::patch('https://api.example.com/users/1', $body, [], ContentType::JSON);

        $this->assertEquals(Method::PATCH->value, $request->getMethod());
        $this->assertEquals('https://api.example.com/users/1', (string) $request->getUri());
        $this->assertEquals(ContentType::JSON->value, $request->getHeaderLine('Content-Type'));
        $this->assertEquals($body, $request->getBodyAsString());
    }

    public function test_create_delete_request(): void
    {
        $request = Request::delete('https://api.example.com/users/1');

        $this->assertEquals(Method::DELETE->value, $request->getMethod());
        $this->assertEquals('https://api.example.com/users/1', (string) $request->getUri());
    }

    public function test_create_delete_request_with_body(): void
    {
        $body = json_encode(['reason' => 'User requested deletion']);
        $request = Request::delete('https://api.example.com/users/1', $body, [], ContentType::JSON);

        $this->assertEquals(Method::DELETE->value, $request->getMethod());
        $this->assertEquals('https://api.example.com/users/1', (string) $request->getUri());
        $this->assertEquals(ContentType::JSON->value, $request->getHeaderLine('Content-Type'));
        $this->assertEquals($body, $request->getBodyAsString());
    }

    public function test_create_head_request(): void
    {
        $request = Request::head('https://api.example.com/users');

        $this->assertEquals(Method::HEAD->value, $request->getMethod());
        $this->assertEquals('https://api.example.com/users', (string) $request->getUri());
        $this->assertEmpty($request->getBodyAsString());
    }

    public function test_create_options_request(): void
    {
        $request = Request::options('https://api.example.com/users');

        $this->assertEquals(Method::OPTIONS->value, $request->getMethod());
        $this->assertEquals('https://api.example.com/users', (string) $request->getUri());
    }

    public function test_method_supports_request_body(): void
    {
        $getRequest = Request::get('https://api.example.com/users');
        $postRequest = Request::post('https://api.example.com/users');
        $putRequest = Request::put('https://api.example.com/users');
        $deleteRequest = Request::delete('https://api.example.com/users');
        $headRequest = Request::head('https://api.example.com/users');

        $this->assertFalse($getRequest->supportsRequestBody());
        $this->assertTrue($postRequest->supportsRequestBody());
        $this->assertTrue($putRequest->supportsRequestBody());
        $this->assertTrue($deleteRequest->supportsRequestBody());
        $this->assertFalse($headRequest->supportsRequestBody());
    }

    public function test_unknown_method_body_support(): void
    {
        $request = new Request('CUSTOM', 'https://api.example.com/users');

        // Unknown methods should default to assuming they support a body
        $this->assertTrue($request->supportsRequestBody());
    }

    public function test_get_method_as_enum(): void
    {
        $request = Request::get('https://api.example.com/users');

        $this->assertEquals(Method::GET, $request->getMethodEnum());
    }

    public function test_null_for_unknown_method_enum(): void
    {
        $request = new Request('CUSTOM', 'https://api.example.com/users');

        $this->assertNull($request->getMethodEnum());
    }

    public function test_get_content_type_as_enum(): void
    {
        $request = Request::post('https://api.example.com/users', null, [], ContentType::JSON);

        $this->assertEquals(ContentType::JSON, $request->getContentTypeEnum());
    }

    public function test_null_for_missing_content_type(): void
    {
        $request = Request::get('https://api.example.com/users');

        $this->assertNull($request->getContentTypeEnum());
    }

    public function test_strip_parameters_from_content_type(): void
    {
        $request = new Request(
            'POST',
            'https://api.example.com/users',
            ['Content-Type' => 'application/json; charset=utf-8']
        );

        $this->assertEquals(ContentType::JSON, $request->getContentTypeEnum());
    }

    public function test_content_type_helpers(): void
    {
        $jsonRequest = Request::post('https://api.example.com/users', null, [], ContentType::JSON);
        $formRequest = Request::post('https://api.example.com/users', null, [], ContentType::FORM_URLENCODED);
        $multipartRequest = Request::post('https://api.example.com/users', null, [], ContentType::MULTIPART);
        $textRequest = Request::post('https://api.example.com/users', null, [], ContentType::TEXT);
        $noTypeRequest = Request::get('https://api.example.com/users');

        // JSON checks
        $this->assertTrue($jsonRequest->hasJsonContent());
        $this->assertFalse($formRequest->hasJsonContent());
        $this->assertFalse($multipartRequest->hasJsonContent());
        $this->assertFalse($textRequest->hasJsonContent());
        $this->assertFalse($noTypeRequest->hasJsonContent());

        // Form checks
        $this->assertFalse($jsonRequest->hasFormContent());
        $this->assertTrue($formRequest->hasFormContent());
        $this->assertFalse($multipartRequest->hasFormContent());
        $this->assertFalse($textRequest->hasFormContent());
        $this->assertFalse($noTypeRequest->hasFormContent());

        // Multipart checks
        $this->assertFalse($jsonRequest->hasMultipartContent());
        $this->assertFalse($formRequest->hasMultipartContent());
        $this->assertTrue($multipartRequest->hasMultipartContent());
        $this->assertFalse($textRequest->hasMultipartContent());
        $this->assertFalse($noTypeRequest->hasMultipartContent());

        // Text checks
        $this->assertTrue($jsonRequest->hasTextContent());
        $this->assertTrue($formRequest->hasTextContent());
        $this->assertFalse($multipartRequest->hasTextContent());
        $this->assertTrue($textRequest->hasTextContent());
        $this->assertFalse($noTypeRequest->hasTextContent());
    }

    public function test_get_body_as_string(): void
    {
        $body = 'test body';
        $request = Request::post('https://api.example.com/users', $body);

        $this->assertEquals($body, $request->getBodyAsString());
    }

    public function test_get_body_as_json(): void
    {
        $data = ['name' => 'John', 'email' => 'john@example.com'];
        $jsonBody = json_encode($data);
        $request = Request::post('https://api.example.com/users', $jsonBody, [], ContentType::JSON);

        $this->assertEquals($data, $request->getBodyAsJson());
    }

    public function test_get_body_as_json_object(): void
    {
        $data = ['name' => 'John', 'email' => 'john@example.com'];
        $jsonBody = json_encode($data);
        $request = Request::post('https://api.example.com/users', $jsonBody, [], ContentType::JSON);

        $result = $request->getBodyAsJson(false);
        $this->assertIsObject($result);
        $this->assertEquals('John', $result->name);
        $this->assertEquals('john@example.com', $result->email);
    }

    public function test_exception_for_invalid_json_body(): void
    {
        $invalidJson = '{name: "John",'; // Missing quote and incomplete
        $request = Request::post('https://api.example.com/users', $invalidJson, [], ContentType::JSON);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON');

        $request->getBodyAsJson();
    }

    public function test_empty_array_for_empty_json_body(): void
    {
        $request = Request::post('https://api.example.com/users', '', [], ContentType::JSON);

        $this->assertEquals([], $request->getBodyAsJson());
    }

    public function test_empty_object_for_empty_json_body(): void
    {
        $request = Request::post('https://api.example.com/users', '', [], ContentType::JSON);

        $result = $request->getBodyAsJson(false);
        $this->assertIsObject($result);
        $this->assertEquals(new \stdClass, $result);
    }

    public function test_get_body_as_form_params(): void
    {
        $formParams = ['username' => 'john', 'password' => 'secret'];
        $formBody = http_build_query($formParams);
        $request = Request::post('https://api.example.com/login', $formBody, [], ContentType::FORM_URLENCODED);

        $this->assertEquals($formParams, $request->getBodyAsFormParams());
    }

    public function test_empty_array_for_empty_form_body(): void
    {
        $request = Request::post('https://api.example.com/login', '', [], ContentType::FORM_URLENCODED);

        $this->assertEquals([], $request->getBodyAsFormParams());
    }

    public function test_set_request_body(): void
    {
        $request = Request::get('https://api.example.com/users');
        $newRequest = $request->withBody('new body');

        // Original should be unchanged
        $this->assertEmpty($request->getBodyAsString());

        // New request should have the new body
        $this->assertEquals('new body', $newRequest->getBodyAsString());
    }

    public function test_set_content_type(): void
    {
        $request = Request::get('https://api.example.com/users');
        $newRequest = $request->withContentType(ContentType::JSON);

        // Original should be unchanged
        $this->assertFalse($request->hasHeader('Content-Type'));

        // New request should have the content type
        $this->assertEquals(ContentType::JSON->value, $newRequest->getHeaderLine('Content-Type'));
    }

    public function test_set_query_parameter(): void
    {
        $request = Request::get('https://api.example.com/users');
        $newRequest = $request->withQueryParam('page', '2');

        // Original should be unchanged
        $this->assertEquals('https://api.example.com/users', (string) $request->getUri());

        // New request should have the query parameter
        $this->assertEquals('https://api.example.com/users?page=2', (string) $newRequest->getUri());
    }

    public function test_update_existing_query_parameter(): void
    {
        $request = Request::get('https://api.example.com/users?page=1&limit=10');
        $newRequest = $request->withQueryParam('page', '2');

        // Original should be unchanged
        $this->assertEquals('https://api.example.com/users?page=1&limit=10', (string) $request->getUri());

        // New request should have the updated query parameter
        $this->assertEquals('https://api.example.com/users?page=2&limit=10', (string) $newRequest->getUri());
    }

    public function test_set_multiple_query_parameters(): void
    {
        $request = Request::get('https://api.example.com/users');
        $newRequest = $request->withQueryParams(['page' => '2', 'limit' => '20']);

        // Original should be unchanged
        $this->assertEquals('https://api.example.com/users', (string) $request->getUri());

        // New request should have the query parameters
        $this->assertEquals('https://api.example.com/users?page=2&limit=20', (string) $newRequest->getUri());
    }

    public function test_merge_query_parameters(): void
    {
        $request = Request::get('https://api.example.com/users?page=1&sort=name');
        $newRequest = $request->withQueryParams(['limit' => '20', 'page' => '2']);

        // Original should be unchanged
        $this->assertEquals('https://api.example.com/users?page=1&sort=name', (string) $request->getUri());

        // New request should have merged query parameters with new values taking precedence
        $this->assertEquals('https://api.example.com/users?page=2&sort=name&limit=20', (string) $newRequest->getUri());
    }

    public function test_set_bearer_token(): void
    {
        $request = Request::get('https://api.example.com/users');
        $newRequest = $request->withBearerToken('abc123');

        // Original should be unchanged
        $this->assertFalse($request->hasHeader('Authorization'));

        // New request should have the authorization header
        $this->assertEquals('Bearer abc123', $newRequest->getHeaderLine('Authorization'));
    }

    public function test_set_basic_auth(): void
    {
        $request = Request::get('https://api.example.com/users');
        $newRequest = $request->withBasicAuth('username', 'password');

        // Original should be unchanged
        $this->assertFalse($request->hasHeader('Authorization'));

        // New request should have the authorization header
        $expectedValue = 'Basic '.base64_encode('username:password');
        $this->assertEquals($expectedValue, $newRequest->getHeaderLine('Authorization'));
    }

    public function test_set_json_body(): void
    {
        $request = Request::get('https://api.example.com/users');
        $data = ['name' => 'John', 'email' => 'john@example.com'];
        $newRequest = $request->withJsonBody($data);

        // Original should be unchanged
        $this->assertEmpty($request->getBodyAsString());
        $this->assertFalse($request->hasHeader('Content-Type'));

        // New request should have the JSON body and content type
        $this->assertEquals(ContentType::JSON->value, $newRequest->getHeaderLine('Content-Type'));
        $this->assertEquals($data, $newRequest->getBodyAsJson());
    }

    public function test_exception_when_setting_invalid_json_body(): void
    {
        $request = Request::get('https://api.example.com/users');

        // Create an object with a recursive reference that can't be JSON encoded
        $recursiveData = ['name' => 'John'];
        $recursiveData['self'] = &$recursiveData;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to encode data to JSON');

        $request->withJsonBody($recursiveData);
    }

    public function test_set_form_body(): void
    {
        $request = Request::get('https://api.example.com/users');
        $formParams = ['username' => 'john', 'password' => 'secret'];
        $newRequest = $request->withFormBody($formParams);

        // Original should be unchanged
        $this->assertEmpty($request->getBodyAsString());
        $this->assertFalse($request->hasHeader('Content-Type'));

        // New request should have the form body and content type
        $this->assertEquals(ContentType::FORM_URLENCODED->value, $newRequest->getHeaderLine('Content-Type'));
        $this->assertEquals($formParams, $newRequest->getBodyAsFormParams());
    }
}
