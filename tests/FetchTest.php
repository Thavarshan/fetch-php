<?php

use Fetch\Http;
use Fetch\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

beforeEach(function () {
    // Setup a mock Guzzle client to use in each test
    $this->mock = new MockHandler();
    $this->handler = HandlerStack::create($this->mock);
    $this->client = new Client(['handler' => $this->handler]);

    // Set the client in the Http singleton instance
    Http::getInstance()->setClient($this->client);
});

afterEach(function () {
    // Reset the mock handler after each test
    $this->mock->reset();

    // Reset the Http singleton instance
    Http::resetInstance();
});

test('fetch can handle a successful GET request', function () {
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"message":"success"}'));

    $response = fetch('/', [
        'method' => 'GET',
        'headers' => ['Accept' => 'application/json']
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->json())->toMatchArray(['message' => 'success']);
});

test('fetch can handle a 404 error response', function () {
    $this->mock->append(new GuzzleResponse(404, [], 'Not Found'));

    $response = fetch('/', [
        'method' => 'GET',
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(404);
    expect($response->text())->toBe('Not Found');
});

test('fetch_async can handle a successful async GET request', function () {
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"message":"success"}'));

    $promise = fetch_async('/', [
        'method' => 'GET',
        'headers' => ['Accept' => 'application/json']
    ]);

    $response = $promise->wait();

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->json())->toMatchArray(['message' => 'success']);
});

test('fetch_async can handle a 500 error async response', function () {
    $this->mock->append(new GuzzleResponse(500, [], 'Internal Server Error'));

    $promise = fetch_async('/', [
        'method' => 'GET'
    ]);
    $response = $promise->wait();

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(500);
    expect($response->text())->toBe('Internal Server Error');
});

test('fetch can send multipart form data', function () {
    $this->mock->append(new GuzzleResponse(200, [], 'OK'));

    $response = fetch('/upload', [
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

test('fetch can send JSON data', function () {
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"success":true}'));

    $response = fetch('/api', [
        'method' => 'POST',
        'json' => [
            'key' => 'value'
        ]
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->json())->toMatchArray(['success' => true]);
});

test('fetch handles invalid URL', function () {
    $this->mock->append(new RequestException('Invalid URL', new Request('GET', 'invalid-url')));

    $response = fetch('invalid-url', [
        'method' => 'GET'
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(500);
    expect($response->text())->toContain('Invalid URL');
});

test('fetch handles timeout scenario', function () {
    $this->mock->append(new RequestException('Request timed out', new Request('GET', '/timeout')));

    $response = fetch('/timeout', [
        'method' => 'GET',
        'timeout' => 1
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(500);
    expect($response->text())->toContain('Request timed out');
});

test('fetch can handle a PUT request', function () {
    $this->mock->append(new GuzzleResponse(200, [], 'PUT success'));

    $response = fetch('/put', [
        'method' => 'PUT',
        'json' => ['key' => 'value']
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->text())->toBe('PUT success');
});

test('fetch can handle a DELETE request', function () {
    $this->mock->append(new GuzzleResponse(200, [], 'DELETE success'));

    $response = fetch('/delete', [
        'method' => 'DELETE'
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->text())->toBe('DELETE success');
});

test('fetch can handle XML response', function () {
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'application/xml'], '<response><message>success</message></response>'));

    $response = fetch('/xml', [
        'method' => 'GET',
        'headers' => ['Accept' => 'application/xml']
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->text())->toContain('<message>success</message>');
});

test('fetch can handle plain text response', function () {
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'text/plain'], 'Plain text response'));

    $response = fetch('/text', [
        'method' => 'GET',
        'headers' => ['Accept' => 'text/plain']
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->text())->toBe('Plain text response');
});

test('fetch handles request exceptions without a response', function () {
    $this->mock->append(new RequestException('Network error', new Request('GET', '/network-error')));

    $response = fetch('/network-error', [
        'method' => 'GET'
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(500);
    expect($response->text())->toContain('Network error');
});

test('fetch handles redirects', function () {
    $responses = [
        new GuzzleResponse(302, ['Location' => '/new-location']),
        new GuzzleResponse(200, [], 'Redirected response')
    ];

    $this->handler->setHandler(create_redirect_handler($responses));

    $response = fetch('/redirect', [
        'method' => 'GET',
        'allow_redirects' => true
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->text())->toBe('Redirected response');
});
