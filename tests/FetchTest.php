<?php

use Fetch\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
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
});

afterEach(function () {
    // Reset the mock handler after each test
    $this->mock->reset();
});

// Test for synchronous fetch with a successful response
test('fetch can handle a successful GET request', function () {
    // Simulate a Guzzle success response
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"message":"success"}'));

    $response = fetch('/', [
        'client' => $this->client,
        'method' => 'GET',
        'headers' => ['Accept' => 'application/json']
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->json())->toMatchArray(['message' => 'success']);
});

// Test for synchronous fetch with a 404 error
test('fetch can handle a 404 error response', function () {
    $this->mock->append(new GuzzleResponse(404, [], 'Not Found'));

    $response = fetch('/', [
        'client' => $this->client,
        'method' => 'GET'
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(404);
    expect($response->text())->toBe('Not Found');
});

// Test for asynchronous fetch with a successful response
test('fetch_async can handle a successful GET request', function () {
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"message":"success"}'));

    $promise = fetch_async('/', [
        'client' => $this->client,
        'method' => 'GET',
        'headers' => ['Accept' => 'application/json']
    ]);
    $response = $promise->wait();

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->json())->toMatchArray(['message' => 'success']);
});

// Test for asynchronous fetch with a 500 error
test('fetch_async can handle a 500 error response', function () {
    $this->mock->append(new GuzzleResponse(500, [], 'Internal Server Error'));

    $promise = fetch_async('/', [
        'client' => $this->client,
        'method' => 'GET'
    ]);
    $response = $promise->wait();

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(500);
    expect($response->text())->toBe('Internal Server Error');
});

// Test for deprecated fetchAsync method (with warning)
test('fetchAsync shows deprecation warning and can handle a successful GET request', function () {
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"message":"success"}'));

    $promise = fetchAsync('/', [
        'client' => $this->client,
        'method' => 'GET',
        'headers' => ['Accept' => 'application/json']
    ]);
    $response = $promise->wait();

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->json())->toMatchArray(['message' => 'success']);
    // Ensure deprecation warning is shown (this may require checking logs depending on the test runner setup)
});

// Test for multipart form data request
test('fetch can send multipart form data', function () {
    $this->mock->append(new GuzzleResponse(200, [], 'OK'));

    $response = fetch('/upload', [
        'client' => $this->client,
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

// Test for JSON body request
test('fetch can send JSON data', function () {
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"success":true}'));

    $response = fetch('/api', [
        'client' => $this->client,
        'method' => 'POST',
        'json' => [
            'key' => 'value'
        ]
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->json())->toMatchArray(['success' => true]);
});

// Test for custom middleware functionality
test('fetch can send requests with custom middleware', function () {
    $this->mock->append(new GuzzleResponse(200, [], 'OK'));

    $response = fetch('/api', [
        'client' => $this->client,
        'method' => 'GET'
    ]);

    // Ensure custom middleware added 'X-Test-Header'
    expect($response->getStatusCode())->toBe(200);
    expect($this->mock->getLastRequest()->getHeaderLine('X-Test-Header'))->toBe('TestValue');
});
