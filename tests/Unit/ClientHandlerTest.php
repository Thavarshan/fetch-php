<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Http\ClientHandler;
use Fetch\Http\Response;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ClientHandlerTest extends TestCase
{
    public function test_constructor_with_defaults(): void
    {
        // Add a URI to avoid the InvalidArgumentException
        $handler = new ClientHandler(options: ['uri' => 'test']);

        $this->assertEquals('test', $handler->debug()['uri']);
        $this->assertEquals(Method::GET->value, $handler->debug()['method']);
        $this->assertEquals([], $handler->debug()['headers']);
        $this->assertEquals(ClientHandler::DEFAULT_TIMEOUT, $handler->debug()['timeout']);
        $this->assertEquals(ClientHandler::DEFAULT_RETRIES, $handler->debug()['retries']);
        $this->assertEquals(ClientHandler::DEFAULT_RETRY_DELAY, $handler->debug()['retry_delay']);
        $this->assertFalse($handler->debug()['is_async']);
    }

    public function test_constructor_with_custom_parameters(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $options = [
            'headers' => ['X-Test' => 'test-value'],
            'method' => Method::POST->value,
            'uri' => '/endpoint', // Add URI to avoid the exception
            'base_uri' => 'https://api.example.com',
        ];

        $handler = new ClientHandler(
            syncClient: $client,
            options: $options,
            timeout: 60,
            maxRetries: 3,
            retryDelay: 200,
            isAsync: true,
            logger: $logger
        );

        $this->assertEquals('https://api.example.com/endpoint', $handler->debug()['uri']);
        $this->assertEquals(Method::POST->value, $handler->debug()['method']);
        $this->assertEquals(['X-Test' => 'test-value'], $handler->debug()['headers']);
        $this->assertEquals(60, $handler->debug()['timeout']);
        $this->assertEquals(3, $handler->debug()['retries']);
        $this->assertEquals(200, $handler->debug()['retry_delay']);
        $this->assertTrue($handler->debug()['is_async']);
    }

    public function test_create(): void
    {
        // We need to set up the URI after creation to avoid the exception
        $handler = ClientHandler::create()->withOptions(['uri' => 'test']);

        $this->assertInstanceOf(ClientHandler::class, $handler);
        $this->assertEquals('test', $handler->debug()['uri']);
    }

    public function test_create_with_base_uri(): void
    {
        $baseUri = 'https://api.example.com';
        $handler = ClientHandler::createWithBaseUri($baseUri);

        $this->assertInstanceOf(ClientHandler::class, $handler);
        // Account for the trailing slash that's automatically added
        $this->assertEquals($baseUri.'/', $handler->debug()['uri']);
    }

    public function test_create_with_client(): void
    {
        $client = $this->createMock(ClientInterface::class);
        // Add URI to avoid the exception
        $handler = ClientHandler::createWithClient($client)->withOptions(['uri' => 'test']);

        $this->assertInstanceOf(ClientHandler::class, $handler);
        $this->assertEquals('test', $handler->debug()['uri']);
    }

    public function test_default_options(): void
    {
        $defaultOptions = ClientHandler::getDefaultOptions();
        $this->assertIsArray($defaultOptions);
        $this->assertArrayHasKey('method', $defaultOptions);
        $this->assertArrayHasKey('timeout', $defaultOptions);

        $newOptions = ['headers' => ['X-Default' => 'value']];
        ClientHandler::setDefaultOptions($newOptions);

        $updatedOptions = ClientHandler::getDefaultOptions();
        $this->assertArrayHasKey('headers', $updatedOptions);
        $this->assertEquals('value', $updatedOptions['headers']['X-Default']);
    }

    public function test_get_options(): void
    {
        $options = [
            'headers' => ['X-Test' => 'test-value'],
            'uri' => 'test', // Add URI to avoid the exception
        ];
        $handler = new ClientHandler(options: $options);

        $this->assertEquals(
            array_merge(ClientHandler::getDefaultOptions(), $options),
            $handler->getOptions()
        );
    }

    public function test_get_headers(): void
    {
        $headers = ['X-Test' => 'test-value', 'Authorization' => 'Bearer token'];
        $handler = new ClientHandler(options: [
            'headers' => $headers,
            'uri' => 'test', // Add URI to avoid the exception
        ]);

        $this->assertEquals($headers, $handler->getHeaders());
    }

    public function test_has_header(): void
    {
        $headers = ['X-Test' => 'test-value', 'Authorization' => 'Bearer token'];
        $handler = new ClientHandler(options: [
            'headers' => $headers,
            'uri' => 'test', // Add URI to avoid the exception
        ]);

        $this->assertTrue($handler->hasHeader('X-Test'));
        $this->assertTrue($handler->hasHeader('Authorization'));
        $this->assertFalse($handler->hasHeader('Content-Type'));
    }

    public function test_has_option(): void
    {
        $options = ['verify' => false, 'timeout' => 30, 'uri' => 'test'];
        $handler = new ClientHandler(options: $options);

        $this->assertTrue($handler->hasOption('verify'));
        $this->assertTrue($handler->hasOption('timeout'));
        $this->assertTrue($handler->hasOption('uri'));
        $this->assertFalse($handler->hasOption('proxy'));
    }

    public function test_debug(): void
    {
        $handler = new ClientHandler(
            options: [
                'headers' => ['X-Test' => 'value'],
                'verify' => false,
                'uri' => 'test', // Add URI to avoid the exception
            ],
            timeout: 45,
            maxRetries: 2,
            retryDelay: 150
        );

        $debug = $handler->debug();

        $this->assertIsArray($debug);
        $this->assertArrayHasKey('uri', $debug);
        $this->assertArrayHasKey('method', $debug);
        $this->assertArrayHasKey('headers', $debug);
        $this->assertArrayHasKey('options', $debug);
        $this->assertArrayHasKey('is_async', $debug);
        $this->assertArrayHasKey('timeout', $debug);
        $this->assertArrayHasKey('retries', $debug);
        $this->assertArrayHasKey('retry_delay', $debug);

        $this->assertEquals('test', $debug['uri']);
        $this->assertEquals(['X-Test' => 'value'], $debug['headers']);
        $this->assertEquals(45, $debug['timeout']);
        $this->assertEquals(2, $debug['retries']);
        $this->assertEquals(150, $debug['retry_delay']);
    }

    public function test_set_logger(): void
    {
        $handler = new ClientHandler(options: ['uri' => 'test']);
        $logger = $this->createMock(LoggerInterface::class);

        $result = $handler->setLogger($logger);

        $this->assertSame($handler, $result);
    }

    public function test_with_cloned_options(): void
    {
        $handler = new ClientHandler(
            options: ['headers' => ['X-Original' => 'original'], 'uri' => 'test']
        );

        $newOptions = ['headers' => ['X-New' => 'new-value']];
        $cloned = $handler->withClonedOptions($newOptions);

        $this->assertNotSame($handler, $cloned);
        $this->assertTrue($cloned->hasHeader('X-New'));
        $this->assertFalse($handler->hasHeader('X-New'));
    }

    public function test_create_mock_response(): void
    {
        $response = ClientHandler::createMockResponse(
            statusCode: 201,
            headers: ['X-Test' => 'test-header'],
            body: 'Test body content',
            version: '2.0',
            reason: 'Created'
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(['X-Test' => ['test-header']], $response->getHeaders());
        $this->assertEquals('Test body content', $response->getBody()->getContents());
        $this->assertEquals('2.0', $response->getProtocolVersion());
        $this->assertEquals('Created', $response->getReasonPhrase());
    }

    public function test_create_json_response(): void
    {
        $data = ['name' => 'test', 'value' => 123];
        $response = ClientHandler::createJsonResponse(
            data: $data,
            statusCode: 200,
            headers: ['X-Custom' => 'value']
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertEquals(ContentType::JSON->value, $response->getHeaderLine('Content-Type'));
        $this->assertEquals('value', $response->getHeaderLine('X-Custom'));

        $responseData = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals($data, $responseData);
    }

    public function test_timeout_handling_in_constructor(): void
    {
        // Test with explicit timeout parameter
        $handler1 = new ClientHandler(
            timeout: 45,
            options: ['uri' => 'test'] // Add URI to avoid the exception
        );
        $this->assertEquals(45, $handler1->debug()['timeout']);

        // Test with timeout in options
        $handler2 = new ClientHandler(
            options: ['timeout' => 60, 'uri' => 'test']
        );
        $this->assertEquals(60, $handler2->debug()['timeout']);

        // Test parameter taking precedence over options
        $handler3 = new ClientHandler(
            options: ['timeout' => 30, 'uri' => 'test'],
            timeout: 90
        );
        $this->assertEquals(90, $handler3->debug()['timeout']);
    }

    public function test_exception_when_no_uri_provided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URI cannot be empty');

        $handler = new ClientHandler;
        $handler->debug(); // This will trigger the getFullUri() method
    }
}
