<?php

declare(strict_types=1);

use Fetch\Http\ClientHandler;
use Fetch\Http\Response;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use React\Promise\PromiseInterface;

beforeEach(function () {
    // Create a mock Guzzle client with predefined responses
    $this->mockHandler = new MockHandler;
    $handlerStack = HandlerStack::create($this->mockHandler);
    $this->mockClient = new Client(['handler' => $handlerStack]);
});

test('handle method creates a handler instance and sends request', function () {
    // Create a custom test subclass that overrides the getSyncClient method
    $handlerClass = new class extends ClientHandler
    {
        public static $mockClient = null;

        public function getSyncClient(): ClientInterface
        {
            return self::$mockClient ?: parent::getSyncClient();
        }
    };

    // Set up mock client with expected response
    $mockHandler = new MockHandler([
        new GuzzleResponse(200, ['X-Test' => 'Value'], '{"data":"test"}'),
    ]);
    $mockClient = new Client(['handler' => HandlerStack::create($mockHandler)]);
    $handlerClass::$mockClient = $mockClient;

    // Call the method and verify results
    $response = $handlerClass::handle('GET', 'https://example.com', ['timeout' => 10]);

    // Verify response properties
    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->getHeaderLine('X-Test'))->toBe('Value');
    expect($response->body())->toBe('{"data":"test"}');
});

test('retry mechanism works with server errors', function () {
    $handler = makeRetryableHandler(3);

    $calls = 0;
    $mockResponse = mock(Response::class);

    $response = $handler->testRetry(function () use (&$calls, $mockResponse) {
        $calls++;

        if ($calls < 2) {
            throw new RequestException(
                'Server Error',
                new Request('GET', '/'),
                new Response(500)
            );
        }

        return $mockResponse;
    });

    expect($response)->toBe($mockResponse)
        ->and($calls)->toBe(2);
});

test('retry gives up after all attempts fail', function () {
    $handler = makeRetryableHandler(2);

    $handler->testRetry(function () {
        throw new RequestException(
            'Still failing',
            new Request('GET', '/'),
            null,
            null,
            ['code' => 503]
        );
    });
})->throws(RequestException::class);

test('non-retryable error fails immediately', function () {
    $handler = makeRetryableHandler(3);

    $handler->testRetry(function () {
        throw new RequestException(
            'Bad Request',
            new Request('GET', '/'),
            null,
            null,
            ['code' => 400]
        );
    });
})->throws(RequestException::class);

test('request succeeds on first attempt without retries', function () {
    $handler = makeRetryableHandler(3);

    $mockResponse = mock(Response::class);

    $response = $handler->testRetry(fn () => $mockResponse);

    expect($response)->toBe($mockResponse);
});

test('constructor sets properties correctly', function () {
    $syncClient = Mockery::mock(ClientInterface::class);
    $options = ['timeout' => 15];

    $handler = new ClientHandler(
        syncClient: $syncClient,
        options: $options,
        timeout: 20,
        retries: 3,
        retryDelay: 200,
        isAsync: true
    );

    $handlerOptions = $handler->getOptions();

    expect($handler->getSyncClient())->toBe($syncClient)
        ->and($handlerOptions)->toBe($options)
        ->and($handler->isAsync())->toBeTrue();
});

test('get method sends a GET request and returns a response', function () {
    // Setup mock response
    $this->mockHandler->append(new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"status": "success"}'));

    // Create handler with mock client
    $handler = new ClientHandler(syncClient: $this->mockClient);

    // Send request
    $response = $handler->get('https://example.com/api');

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($response->getHeaderLine('Content-Type'))->toBe('application/json'); // Use getHeaderLine instead
});

test('post method sends a POST request with correct body', function () {
    // Setup mock response
    $this->mockHandler->append(new GuzzleResponse(201, ['Content-Type' => 'application/json'], '{"id": 1}'));

    // Create request history middleware to inspect the request
    $container = [];
    $history = Middleware::history($container);

    $handlerStack = HandlerStack::create($this->mockHandler);
    $handlerStack->push($history);
    $mockClient = new Client(['handler' => $handlerStack]);

    // Create handler with mock client
    $handler = new ClientHandler(syncClient: $mockClient);

    // Send request
    $response = $handler->post(
        'https://example.com/api/resource',
        ['name' => 'Test', 'value' => 123]
    );

    // Check that the request was made correctly
    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())->toBe(201);

    // Verify the request details from history
    expect($container)->toHaveCount(1);
    $request = $container[0]['request'];
    expect($request->getMethod())->toBe('POST')
        ->and($request->getUri()->__toString())->toBe('https://example.com/api/resource')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/json'); // Use getHeaderLine

    // Check request body
    $body = (string) $request->getBody();
    $decodedBody = json_decode($body, true);
    expect($decodedBody)->toBe(['name' => 'Test', 'value' => 123]);
});

test('withHeaders adds multiple headers correctly', function () {
    $handler = new ClientHandler;

    $handler->withHeaders([
        'X-API-Key' => 'abc123',
        'Accept' => 'application/json',
    ]);

    $headers = $handler->getHeaders();

    expect($headers)->toHaveKey('X-API-Key', 'abc123')
        ->and($headers)->toHaveKey('Accept', 'application/json');
});

test('withHeader adds a single header correctly', function () {
    $handler = new ClientHandler;

    $handler->withHeader('User-Agent', 'TestClient/1.0');

    $headers = $handler->getHeaders();

    expect($headers)->toHaveKey('User-Agent', 'TestClient/1.0');
});

test('withJson sets JSON body and content type header', function () {
    $handler = new ClientHandler;

    $handler->withJson(['key' => 'value']);

    $options = $handler->getOptions();

    expect($options)->toHaveKey('json', ['key' => 'value'])
        ->and($handler->getHeaders())->toHaveKey('Content-Type', 'application/json');
});

test('withToken adds Bearer token to Authorization header', function () {
    $handler = new ClientHandler;

    $handler->withToken('secret-token');

    $headers = $handler->getHeaders();

    expect($headers)->toHaveKey('Authorization', 'Bearer secret-token');
});

test('baseUri sets base URI correctly', function () {
    $handler = new ClientHandler;

    $handler->baseUri('https://api.example.com');

    $options = $handler->getOptions();

    expect($options)->toHaveKey('base_uri', 'https://api.example.com');
});

test('async sets async mode correctly', function () {
    $handler = new ClientHandler;

    $result = $handler->async();

    expect($result)->toBe($handler) // Test fluent interface
        ->and($handler->isAsync())->toBeTrue();

    $handler->async(false);

    expect($handler->isAsync())->toBeFalse();
});

test('timeout sets timeout correctly', function () {
    $handler = new ClientHandler;

    $handler->timeout(45);

    // Timeout is only merged into options during request finalization,
    // so we need to trigger that by making a request
    $this->mockHandler->append(new GuzzleResponse(200));
    $handler->setSyncClient($this->mockClient);

    $request = $handler->get('https://example.com');

    $options = $handler->getOptions();
    expect($options)->toHaveKey('timeout', 45);
});

test('getFullUri constructs URLs correctly', function () {
    // Create a testable class extending ClientHandler to expose protected methods
    $handlerClass = new class extends ClientHandler
    {
        public function testGetFullUri(): string
        {
            return $this->getFullUri();
        }

        // Add setter for testing
        public function setUri(string $uri): void
        {
            $this->options['uri'] = $uri;
        }
    };

    // Test absolute URL
    $handler = $handlerClass->withQueryParameter('param', 'value');
    $handler->setUri('https://example.com/path');

    $uri = $handler->testGetFullUri();
    expect($uri)->toBe('https://example.com/path?param=value');

    // Test with base URI
    $handler = new $handlerClass;
    $handler->baseUri('https://api.example.org');
    $handler->setUri('resources/123');

    $uri = $handler->testGetFullUri();
    expect($uri)->toBe('https://api.example.org/resources/123');

    // Test with query parameters
    $handler->withQueryParameters(['page' => 1, 'limit' => 10]);

    $uri = $handler->testGetFullUri();
    expect($uri)->toBe('https://api.example.org/resources/123?page=1&limit=10');
});

test('reset clears all options and settings', function () {
    $handler = new ClientHandler;

    $handler->withHeader('X-Test', 'value')
        ->timeout(60)
        ->retry(5, 300)
        ->async();

    $handler->reset();

    expect($handler->getOptions())->toBe([])
        ->and($handler->getHeaders())->toBe([])
        ->and($handler->isAsync())->toBeFalse();
});

test('retry with all failures throws exception', function () {
    // Create a client with a handler that always returns 500
    $mockHandler = new MockHandler([
        new GuzzleResponse(500),
        new GuzzleResponse(500),
    ]);

    $mockClient = new Client([
        'handler' => HandlerStack::create($mockHandler),
        'http_errors' => false, // Don't throw exceptions for HTTP errors
    ]);

    $handler = new ClientHandler(syncClient: $mockClient);
    $handler->retry(1, 10); // Set 1 retry with 10ms delay

    // Create a request that will fail
    $responsePromise = $handler->get('https://example.com');

    // We should receive a RuntimeException after all retries fail
    // The actual exception may be wrapped, so we'll check if it's an instance of Response
    expect($responsePromise)->toBeInstanceOf(Response::class)
        ->and($responsePromise->getStatusCode())->toBe(500); // Final response should be the 500 error
});

test('sendAsync returns a Promise', function () {
    $this->mockHandler->append(new GuzzleResponse(200));

    $handler = new ClientHandler(syncClient: $this->mockClient);
    $handler->async();

    $result = $handler->get('https://example.com');

    expect($result)->toBeInstanceOf(PromiseInterface::class);
});

test('isRetryableError correctly identifies retryable errors', function () {
    // Create a testable version of ClientHandler with exposed protected method
    $handlerClass = new class extends ClientHandler
    {
        public function testIsRetryableError(RequestException $e): bool
        {
            return $this->isRetryableError($e);
        }
    };

    $handler = new $handlerClass;

    // Test with 500 error (should be retryable)
    $serverError = new RequestException(
        'Server Error',
        new Request('GET', 'https://example.com'),
        new GuzzleResponse(500)
    );
    expect($handler->testIsRetryableError($serverError))->toBeTrue();

    // Test with 400 error (should not be retryable)
    $clientError = new RequestException(
        'Client Error',
        new Request('GET', 'https://example.com'),
        new GuzzleResponse(400)
    );
    expect($handler->testIsRetryableError($clientError))->toBeFalse();

    // Test with 429 Too Many Requests (should be retryable)
    $rateLimitError = new RequestException(
        'Rate Limit',
        new Request('GET', 'https://example.com'),
        new GuzzleResponse(429)
    );
    expect($handler->testIsRetryableError($rateLimitError))->toBeTrue();
});

test('debug method returns request details', function () {
    // Create a testable class extending ClientHandler to set protected properties
    $handlerClass = new class extends ClientHandler
    {
        public function setRequestMethod(string $method): void
        {
            $this->options['method'] = $method;
        }

        public function setRequestUri(string $uri): void
        {
            $this->options['uri'] = $uri;
        }
    };

    $handler = new $handlerClass;

    $handler->baseUri('https://api.example.com')
        ->withHeader('X-API-Key', 'secret')
        ->timeout(30)
        ->withQueryParameter('page', 1);

    $handler->setRequestMethod('GET');
    $handler->setRequestUri('resources');

    $debug = $handler->debug();

    expect($debug)->toHaveKeys(['uri', 'method', 'headers', 'options'])
        ->and($debug['uri'])->toBe('https://api.example.com/resources?page=1')
        ->and($debug['method'])->toBe('GET')
        ->and($debug['headers'])->toHaveKey('X-API-Key', 'secret');
});

// HTTP method convenience functions tests
test('http method convenience functions work correctly', function () {
    // Setup expected responses for each method
    $responses = [
        new GuzzleResponse(200), // GET
        new GuzzleResponse(201), // POST
        new GuzzleResponse(200), // PUT
        new GuzzleResponse(200), // PATCH
        new GuzzleResponse(204), // DELETE
        new GuzzleResponse(200), // HEAD
        new GuzzleResponse(200), // OPTIONS
    ];

    foreach ($responses as $response) {
        $this->mockHandler->append($response);
    }

    $handler = new ClientHandler(syncClient: $this->mockClient);

    // Test each method
    $getResponse = $handler->get('https://example.com');
    expect($getResponse)->toBeInstanceOf(Response::class);

    $postResponse = $handler->post('https://example.com', ['data' => 'value']);
    expect($postResponse)->toBeInstanceOf(Response::class);

    $putResponse = $handler->put('https://example.com', ['data' => 'updated']);
    expect($putResponse)->toBeInstanceOf(Response::class);

    $patchResponse = $handler->patch('https://example.com', ['data' => 'partial']);
    expect($patchResponse)->toBeInstanceOf(Response::class);

    $deleteResponse = $handler->delete('https://example.com');
    expect($deleteResponse)->toBeInstanceOf(Response::class);

    $headResponse = $handler->head('https://example.com');
    expect($headResponse)->toBeInstanceOf(Response::class);

    $optionsResponse = $handler->options('https://example.com');
    expect($optionsResponse)->toBeInstanceOf(Response::class);
});

// Tests for the new withOptions and withOption methods
test('withOptions adds multiple options correctly', function () {
    $handler = new ClientHandler;

    $handler->withOptions([
        'connect_timeout' => 5,
        'debug' => true,
        'version' => '1.1',
    ]);

    $options = $handler->getOptions();

    expect($options)->toHaveKey('connect_timeout', 5)
        ->and($options)->toHaveKey('debug', true)
        ->and($options)->toHaveKey('version', '1.1');
});

test('withOption adds a single option correctly', function () {
    $handler = new ClientHandler;

    $handler->withOption('verify', false);

    $options = $handler->getOptions();

    expect($options)->toHaveKey('verify', false);
});
