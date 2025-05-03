<?php

declare(strict_types=1);

namespace Tests\Integration;

use Fetch\Http\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

beforeEach(function () {
    // Set up a simple mock handler and history tracker
    $this->mockHandler = new MockHandler;
    $this->history = [];

    $stack = HandlerStack::create($this->mockHandler);
    $stack->push(Middleware::history($this->history));

    $this->client = new Client(['handler' => $stack]);
});

test('makes a successful synchronous GET request', function () {
    // Add a mock response
    $this->mockHandler->append(
        new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"message":"Hello World"}')
    );

    // Make a request using the fetch function
    $response = fetch('https://example.com', [
        'client' => $this->client,
    ]);

    // Check response
    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->json())->toBe(['message' => 'Hello World']);

    // Check request was made correctly
    expect($this->history)->toHaveCount(1);
    expect($this->history[0]['request']->getMethod())->toBe('GET');
});

test('makes a successful asynchronous GET request', function () {
    // Add a mock response
    $this->mockHandler->append(
        new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"data":"async result"}')
    );

    // Create a client handler instance
    $handler = fetch(null, [
        'async' => true,
    ]);

    // Set our mock client
    $handler->setSyncClient($this->client);
    $handler->async();

    // Make the async request
    $promise = $handler->get('https://example.com');

    // Resolve the promise
    $promise->start();
    $response = $promise->getResult();

    // Verify the response
    expect($response)->toBeInstanceOf(Response::class);
    expect($response->json())->toBe(['data' => 'async result']);
});

test('sends headers with a GET request', function () {
    // Add a mock response
    $this->mockHandler->append(
        new GuzzleResponse(200)
    );

    // Make a request with custom headers
    fetch('https://example.com', [
        'client'  => $this->client,
        'headers' => [
            'X-API-Key' => 'test-key',
            'Accept'    => 'application/json',
        ],
    ]);

    // Check headers were sent
    $request = $this->history[0]['request'];
    expect($request->getHeaderLine('X-API-Key'))->toBe('test-key');
    expect($request->getHeaderLine('Accept'))->toBe('application/json');
});

test('appends query parameters to the GET request', function () {
    // Add a mock response
    $this->mockHandler->append(
        new GuzzleResponse(200)
    );

    // Make a request with query parameters
    fetch('https://example.com', [
        'client' => $this->client,
        'query'  => [
            'foo' => 'bar',
            'baz' => 'qux',
        ],
    ]);

    // Check query was appended
    $uri = $this->history[0]['request']->getUri();
    $query = urldecode($uri->getQuery());

    // Just check that both parameters are present
    expect($query)->toContain('foo=bar');
    expect($query)->toContain('baz=qux');
});

test('handles timeout for synchronous requests', function () {
    // Add a mock response
    $this->mockHandler->append(
        new GuzzleResponse(200)
    );

    // Make a request with a timeout
    fetch('https://example.com', [
        'client'  => $this->client,
        'timeout' => 5,
    ]);

    // Check timeout was set
    expect($this->history[0]['options'])->toHaveKey('timeout', 5);
});

test('makes a POST request with body data', function () {
    // Add a mock response
    $this->mockHandler->append(
        new GuzzleResponse(201)
    );

    // Data to send
    $data = ['name' => 'Test', 'email' => 'test@example.com'];

    // Make a POST request
    fetch('https://example.com', [
        'client' => $this->client,
        'method' => 'POST',
        'body'   => $data,
    ]);

    // Check request
    $request = $this->history[0]['request'];
    expect($request->getMethod())->toBe('POST');
    expect($request->getHeaderLine('Content-Type'))->toBe('application/json');

    // Verify JSON body
    $sentData = json_decode((string) $request->getBody(), true);
    expect($sentData)->toBe($data);
});
