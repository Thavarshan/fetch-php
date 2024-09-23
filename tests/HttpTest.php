<?php

use Fetch\Http;
use Fetch\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\RequestInterface;

beforeEach(function () {
    // Setup a mock Guzzle client to use in each test
    $this->mock = new MockHandler();
    $this->handler = HandlerStack::create($this->mock);
    $this->client = new Client(['handler' => $this->handler]);

    // Optional middleware for testing custom middleware functionality
    $this->handler->push(Middleware::mapRequest(function (RequestInterface $request) {
        return $request->withHeader('X-Test-Header', 'TestValue');
    }));

    // Set the client in the Http singleton instance
    Http::getInstance()->setClient($this->client);
});

afterEach(function () {
    // Reset the mock handler after each test
    $this->mock->reset();

    // Reset the Http singleton instance
    Http::resetInstance();
});

test('Http can handle a successful GET request', function () {
    // Simulate a Guzzle success response
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"message":"success"}'));

    $response = Http::getInstance()->makeRequest('/', [
        'method' => 'GET',
        'headers' => ['Accept' => 'application/json']
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->json())->toMatchArray(['message' => 'success']);
});

test('Http can handle a 404 error response', function () {
    // Ensure the mock queue has a 404 response
    $this->mock->append(new GuzzleResponse(404, [], 'Not Found'));

    $response = Http::getInstance()->makeRequest('/', [
        'method' => 'GET',
    ]);

    // Check that the response is handled as expected
    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(404);
    expect($response->text())->toBe('Not Found');
});

test('Http can handle a successful async GET request', function () {
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"message":"success"}'));

    $promise = Http::getInstance()->makeRequest('/', [
        'method' => 'GET',
        'headers' => ['Accept' => 'application/json']
    ], true);

    $response = $promise->wait();

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->json())->toMatchArray(['message' => 'success']);
});

test('Http can handle a 500 error async response', function () {
    $this->mock->append(new GuzzleResponse(500, [], 'Internal Server Error'));

    $promise = Http::getInstance()->makeRequest('/', [
        'method' => 'GET'
    ], true);
    $response = $promise->wait();

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(500);
    expect($response->text())->toBe('Internal Server Error');
});

test('Http can send multipart form data', function () {
    $this->mock->append(new GuzzleResponse(200, [], 'OK'));

    $response = Http::getInstance()->makeRequest('/upload', [
        'method' => 'POST',
        'multipart' => [
            [
                'name'     => 'file',
                'contents' => 'file content',
                'filename' => 'test.txt'
            ]
        ]
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->text())->toBe('OK');
});

test('Http can send JSON data', function () {
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"success":true}'));

    $response = Http::getInstance()->makeRequest('/api', [
        'method' => 'POST',
        'json' => [
            'key' => 'value'
        ]
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->json())->toMatchArray(['success' => true]);
});

test('Http can send requests with custom middleware', function () {
    $this->mock->append(new GuzzleResponse(200, [], 'OK'));

    $response = Http::getInstance()->makeRequest('/api', [
        'method' => 'GET'
    ]);

    // Ensure custom middleware added 'X-Test-Header'
    expect($response->getStatusCode())->toBe(200);
    expect($this->mock->getLastRequest()->getHeaderLine('X-Test-Header'))->toBe('TestValue');
});

test('Http handles invalid URL', function () {
    $this->mock->append(new RequestException('Invalid URL', new Request('GET', 'invalid-url')));

    $response = Http::getInstance()->makeRequest('invalid-url', [
        'method' => 'GET'
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(500);
    expect($response->text())->toContain('Invalid URL');
});

test('Http handles timeout scenario', function () {
    $this->mock->append(new RequestException('Request timed out', new Request('GET', '/timeout')));

    $response = Http::getInstance()->makeRequest('/timeout', [
        'method' => 'GET',
        'timeout' => 1
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(500);
    expect($response->text())->toContain('Request timed out');
});

test('Http can handle a PUT request', function () {
    $this->mock->append(new GuzzleResponse(200, [], 'PUT success'));

    $response = Http::getInstance()->makeRequest('/put', [
        'method' => 'PUT',
        'json' => ['key' => 'value']
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->text())->toBe('PUT success');
});

// Test for DELETE request
test('Http can handle a DELETE request', function () {
    $this->mock->append(new GuzzleResponse(200, [], 'DELETE success'));

    $response = Http::getInstance()->makeRequest('/delete', [
        'method' => 'DELETE'
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->text())->toBe('DELETE success');
});

test('Http can handle XML response', function () {
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'application/xml'], '<response><message>success</message></response>'));

    $response = Http::getInstance()->makeRequest('/xml', [
        'method' => 'GET',
        'headers' => ['Accept' => 'application/xml']
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->text())->toContain('<message>success</message>');
});

// Test for handling plain text response
test('Http can handle plain text response', function () {
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'text/plain'], 'Plain text response'));

    $response = Http::getInstance()->makeRequest('/text', [
        'method' => 'GET',
        'headers' => ['Accept' => 'text/plain']
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->text())->toBe('Plain text response');
});

test('Http handles request exceptions without a response', function () {
    $this->mock->append(new RequestException('Network error', new Request('GET', '/network-error')));

    $response = Http::getInstance()->makeRequest('/network-error', [
        'method' => 'GET'
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(500);
    expect($response->text())->toContain('Network error');
});

test('Http handles redirects', function () {
    $responses = [
        new GuzzleResponse(302, ['Location' => '/new-location']),
        new GuzzleResponse(200, [], 'Redirected response')
    ];

    $this->handler->setHandler(create_redirect_handler($responses));

    $response = Http::getInstance()->makeRequest('/redirect', [
        'method' => 'GET',
        'allow_redirects' => [
            'max' => 10, // Allow up to 10 redirects
            'strict' => false,
            'referer' => false,
            'track_redirects' => true,
        ],
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->text())->toBe('Redirected response');
});

test('Http uses singleton Guzzle client instance', function () {
    // Get the singleton instance of the Http class
    $http1 = Http::getInstance();
    $http2 = Http::getInstance();

    // Ensure both instances are the same
    expect($http1)->toBe($http2);

    // Create a new Guzzle client and set it in the Http instance
    $client1 = new Client();
    $http1->setClient($client1);

    // Get the singleton instance again and check if the client is the same
    $http3 = Http::getInstance();
    expect($http3->getClient())->toBe($client1);
});
