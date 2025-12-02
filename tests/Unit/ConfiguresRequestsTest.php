<?php

namespace Tests\Unit;

use Fetch\Enum\ContentType;
use Fetch\Http\ClientHandler;
use PHPUnit\Framework\TestCase;

class ConfiguresRequestsTest extends TestCase
{
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new ClientHandler;
    }

    public function test_with_options(): void
    {
        $options = ['timeout' => 60, 'verify' => false];
        $this->handler->withOptions($options);

        $this->assertEquals(60, $this->handler->getOptions()['timeout']);
        $this->assertFalse($this->handler->getOptions()['verify']);
    }

    public function test_with_headers(): void
    {
        $headers = ['Accept' => 'application/json', 'X-Custom' => 'value'];
        $this->handler->withHeaders($headers);

        $this->assertEquals($headers, $this->handler->getHeaders());
    }

    public function test_with_header(): void
    {
        $this->handler->withHeader('X-Custom', 'value');
        $this->assertEquals('value', $this->handler->getHeaders()['X-Custom']);
    }

    public function test_with_token(): void
    {
        $token = 'my-oauth-token';
        $this->handler->withToken($token);

        $this->assertEquals('Bearer '.$token, $this->handler->getHeaders()['Authorization']);
    }

    public function test_with_query_parameters(): void
    {
        $params = ['page' => 1, 'limit' => 10];
        $this->handler->withQueryParameters($params);

        $this->assertEquals($params, $this->handler->getOptions()['query']);
    }

    public function test_with_json_body(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $this->handler->withJson($data);

        $this->assertEquals($data, $this->handler->getOptions()['json']);
        $this->assertEquals(ContentType::JSON->value, $this->handler->getHeaders()['Content-Type']);
    }

    public function test_with_options_normalizes_conflicting_body(): void
    {
        $options = [
            'json' => ['foo' => 'bar'],
            'form' => ['should' => 'be removed'],
            'body' => 'raw',
            'headers' => [],
        ];

        $this->handler->withOptions($options);
        $result = $this->handler->getOptions();

        $this->assertArrayHasKey('json', $result);
        $this->assertArrayNotHasKey('form', $result);
        $this->assertArrayNotHasKey('body', $result);
        $this->assertEquals(ContentType::JSON->value, $this->handler->getHeaders()['Content-Type']);
    }

    public function test_with_form_params(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $this->handler->withFormParams($data);

        $this->assertEquals($data, $this->handler->getOptions()['form_params']);
        $this->assertEquals(ContentType::FORM_URLENCODED->value, $this->handler->getHeaders()['Content-Type']);
    }

    public function test_with_timeout(): void
    {
        $this->handler->timeout(60);

        $this->assertEquals(60, $this->handler->getOptions()['timeout']);
    }

    public function test_with_auth(): void
    {
        $username = 'user';
        $password = 'pass';
        $this->handler->withAuth($username, $password);

        $this->assertEquals([$username, $password], $this->handler->getOptions()['auth']);
    }

    public function test_with_body_json(): void
    {
        $data = ['name' => 'John Doe'];
        $this->handler->withBody($data, ContentType::JSON);

        $this->assertEquals($data, $this->handler->getOptions()['json']);
        $this->assertEquals(ContentType::JSON->value, $this->handler->getHeaders()['Content-Type']);
    }

    public function test_with_body_form(): void
    {
        $data = ['name' => 'John Doe'];
        $this->handler->withBody($data, ContentType::FORM_URLENCODED);

        $this->assertEquals($data, $this->handler->getOptions()['form_params']);
        $this->assertEquals(ContentType::FORM_URLENCODED->value, $this->handler->getHeaders()['Content-Type']);
    }

    public function test_with_body_string(): void
    {
        $body = 'raw content';
        $this->handler->withBody($body, ContentType::TEXT);

        $this->assertEquals($body, $this->handler->getOptions()['body']);
        $this->assertEquals(ContentType::TEXT->value, $this->handler->getHeaders()['Content-Type']);
    }

    public function test_with_body_array_sets_json_option_and_removes_body_option(): void
    {
        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
            'nested' => ['nested_key' => 'nested_value'],
        ];

        $this->handler->withBody($data, ContentType::JSON);
        $options = $this->handler->getOptions();

        // Assert that json option is set
        $this->assertArrayHasKey('json', $options);
        $this->assertEquals($data, $options['json']);

        // Assert that body option is NOT set (critical for Guzzle compatibility)
        $this->assertArrayNotHasKey('body', $options);
    }

    public function test_with_body_string_sets_body_option_and_removes_json_option(): void
    {
        $jsonString = json_encode(['test' => 'data']);

        $this->handler->withBody($jsonString, ContentType::JSON);
        $options = $this->handler->getOptions();

        // Assert that body option is set
        $this->assertArrayHasKey('body', $options);
        $this->assertEquals($jsonString, $options['body']);

        // Assert that json option is NOT set
        $this->assertArrayNotHasKey('json', $options);
    }

    public function test_with_body_form_params_removes_conflicting_options(): void
    {
        $data = ['param1' => 'value1', 'param2' => 'value2'];

        $this->handler->withBody($data, ContentType::FORM_URLENCODED);
        $options = $this->handler->getOptions();

        // Assert that form_params is set
        $this->assertArrayHasKey('form_params', $options);
        $this->assertEquals($data, $options['form_params']);

        // Assert that conflicting options are not set
        $this->assertArrayNotHasKey('body', $options);
        $this->assertArrayNotHasKey('json', $options);
    }

    public function test_post_request_with_array_body_works(): void
    {
        $data = [
            'inputParameters' => [
                'Application' => [
                    'Instance' => 'TEST_INSTANCE',
                    'Name' => 'TEST_NAME',
                    'User' => 'test@example.com',
                ],
                'Part' => [
                    'Namespace' => 'Default',
                    'Name' => 'TEST_PART',
                ],
                'Mode' => 0,
                'Profile' => 'default',
            ],
        ];

        // This should not throw any exceptions
        $this->handler->withBody($data, ContentType::JSON);

        // Verify the configuration
        $options = $this->handler->getOptions();
        $this->assertArrayHasKey('json', $options);
        $this->assertArrayNotHasKey('body', $options);
        $this->assertEquals($data, $options['json']);
    }
}
