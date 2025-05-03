<?php

declare(strict_types=1);

use Fetch\Http\ClientHandler;
use Fetch\Http\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

beforeEach(function () {
    $this->mockHandler = new MockHandler;
    $handlerStack = HandlerStack::create($this->mockHandler);

    $this->historyContainer = [];
    $history = Middleware::history($this->historyContainer);
    $handlerStack->push($history);

    $this->mockClient = new Client(['handler' => $handlerStack]);
});

test('fetch returns client handler when no url is provided', function () {
    $result = fetch(null, ['timeout' => 60]);

    expect($result)
        ->toBeInstanceOf(ClientHandler::class)
        ->and($result->getOptions())
        ->toHaveKey('timeout', 60);
});

test('fetch merges default options', function () {
    $mockHandler = new MockHandler([new GuzzleResponse(200)]);
    $handlerStack = HandlerStack::create($mockHandler);
    $mockClient = new Client(['handler' => $handlerStack]);

    $directHandler = new ClientHandler;
    $defaultOptions = ClientHandler::getDefaultOptions();

    fetch('https://example.com', [
        'client' => $mockClient,
    ]);

    expect($defaultOptions)->toHaveKey('method');
    expect($defaultOptions)->toHaveKey('headers');
    expect($defaultOptions)->toHaveKey('timeout');
});

test('fetch capitalizes method', function () {
    $this->mockHandler->append(new GuzzleResponse(200));

    fetch('https://example.com', [
        'client' => $this->mockClient,
    ]);

    expect($this->historyContainer)->toHaveCount(1);
    $request = $this->historyContainer[0]['request'];
    expect($request->getMethod())->toBe('GET');
});

test('fetch sets json content type when body is array', function () {
    $this->mockHandler->append(new GuzzleResponse(200));

    $body = ['foo' => 'bar'];

    fetch('https://example.com', [
        'method' => 'POST',
        'body' => $body,
        'client' => $this->mockClient,
    ]);

    expect($this->historyContainer)->toHaveCount(1);
    $request = $this->historyContainer[0]['request'];

    expect($request->getHeaderLine('Content-Type'))->toBe('application/json');
    expect((string) $request->getBody())->toBe(json_encode($body));
});

test('fetch handles base_uri correctly', function () {
    $this->mockHandler->append(new GuzzleResponse(200));

    fetch('endpoint', [
        'base_uri' => 'https://api.example.com/',
        'client' => $this->mockClient,
    ]);

    expect($this->historyContainer)->toHaveCount(1);
    $request = $this->historyContainer[0]['request'];

    expect((string) $request->getUri())->toBe('https://api.example.com/endpoint');
});

test('fetch handles base_uri trailing slash correctly', function () {
    $this->mockHandler->append(new GuzzleResponse(200));

    fetch('/endpoint', [
        'base_uri' => 'https://api.example.com',
        'client' => $this->mockClient,
    ]);

    expect($this->historyContainer)->toHaveCount(1);
    $request = $this->historyContainer[0]['request'];

    expect((string) $request->getUri())->toBe('https://api.example.com/endpoint');
});

test('fetch handles request exception with response', function () {
    $response = new GuzzleResponse(404, ['X-Foo' => 'Bar'], 'Not Found');
    $request = new Request('GET', 'https://example.com');
    $exception = new RequestException('Error Communicating with Server', $request, $response);

    $this->mockHandler->append($exception);

    $result = fetch('https://example.com', [
        'client' => $this->mockClient,
    ]);

    expect($result)
        ->toBeInstanceOf(Response::class)
        ->and($result->getStatusCode())
        ->toBe(404)
        ->and($result->getBody()->__toString())
        ->toBe('Not Found');
});

test('fetch rethrows exceptions without responses', function () {
    $request = new Request('GET', 'https://example.com');
    $exception = new RequestException('Connection Error', $request);

    $this->mockHandler->append($exception);

    expect(fn () => fetch('https://example.com', [
        'client' => $this->mockClient,
    ]))->toThrow(RuntimeException::class);
});

test('fetch returns response object for successful requests', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, ['X-Foo' => 'Bar'], '{"status":"success"}')
    );

    $result = fetch('https://example.com', [
        'client' => $this->mockClient,
    ]);

    expect($result)
        ->toBeInstanceOf(Response::class)
        ->and($result->getStatusCode())
        ->toBe(200)
        ->and($result->getHeaderLine('X-Foo'))
        ->toBe('Bar')
        ->and($result->getBody()->__toString())
        ->toBe('{"status":"success"}');
});

test('fetch handles different HTTP methods correctly', function () {
    // Add 5 mock responses to the queue, one for each request
    $this->mockHandler->append(
        new GuzzleResponse(200), // For first GET
        new GuzzleResponse(200), // For second GET
        new GuzzleResponse(201), // For POST
        new GuzzleResponse(204), // For DELETE
        new GuzzleResponse(200), // For PUT
        new GuzzleResponse(200)  // For PATCH
    );

    // The rest of your test stays the same...
    fetch('https://example.com', [
        'method' => 'GET',
        'client' => $this->mockClient,
    ]);

    fetch('https://example.com', [
        'method' => 'GET',
        'client' => $this->mockClient,
    ]);

    fetch('https://example.com', [
        'method' => 'POST',
        'body' => ['data' => 'value'],
        'client' => $this->mockClient,
    ]);

    fetch('https://example.com', [
        'method' => 'DELETE',
        'client' => $this->mockClient,
    ]);

    fetch('https://example.com', [
        'method' => 'PUT',
        'body' => ['data' => 'updated'],
        'client' => $this->mockClient,
    ]);

    fetch('https://example.com', [
        'method' => 'PATCH',
        'body' => ['data' => 'patched'],
        'client' => $this->mockClient,
    ]);

    expect($this->historyContainer)->toHaveCount(6);

    expect($this->historyContainer[0]['request']->getMethod())->toBe('GET');
    expect($this->historyContainer[1]['request']->getMethod())->toBe('GET');
    expect($this->historyContainer[2]['request']->getMethod())->toBe('POST');
    expect($this->historyContainer[3]['request']->getMethod())->toBe('DELETE');
    expect($this->historyContainer[4]['request']->getMethod())->toBe('PUT');
    expect($this->historyContainer[5]['request']->getMethod())->toBe('PATCH');

    $postBody = (string) $this->historyContainer[2]['request']->getBody();
    expect(json_decode($postBody, true))->toBe(['data' => 'value']);
});

test('fetch handles form parameters correctly', function () {
    $this->mockHandler->append(new GuzzleResponse(200));

    fetch('https://example.com', [
        'method' => 'POST',
        'form_params' => ['username' => 'test', 'password' => 'secret'],
        'client' => $this->mockClient,
    ]);

    expect($this->historyContainer)->toHaveCount(1);
    $request = $this->historyContainer[0]['request'];

    expect($request->getHeaderLine('Content-Type'))
        ->toBe('application/x-www-form-urlencoded');

    $body = (string) $request->getBody();
    expect($body)->toBe('username=test&password=secret');
});

test('fetch handles query parameters correctly', function () {
    $this->mockHandler->append(new GuzzleResponse(200));

    fetch('https://example.com', [
        'query' => ['page' => 1, 'limit' => 10],
        'client' => $this->mockClient,
    ]);

    expect($this->historyContainer)->toHaveCount(1);
    $request = $this->historyContainer[0]['request'];

    $uri = $request->getUri();

    $uriString = (string) $uri;
    expect($uriString)->toContain('page=1');
    expect($uriString)->toContain('limit=10');
});

test('fetch adds custom headers correctly', function () {
    $this->mockHandler->append(new GuzzleResponse(200));

    fetch('https://example.com', [
        'headers' => [
            'X-API-Key' => 'abc123',
            'Accept' => 'application/json',
        ],
        'client' => $this->mockClient,
    ]);

    expect($this->historyContainer)->toHaveCount(1);
    $request = $this->historyContainer[0]['request'];

    expect($request->getHeaderLine('X-API-Key'))->toBe('abc123');
    expect($request->getHeaderLine('Accept'))->toBe('application/json');
});

test('fetch applies timeout correctly', function () {
    $this->mockHandler->append(new GuzzleResponse(200));

    fetch('https://example.com', [
        'timeout' => 15,
        'client' => $this->mockClient,
    ]);

    expect($this->historyContainer)->toHaveCount(1);
    $options = $this->historyContainer[0]['options'];

    expect($options)->toHaveKey('timeout', 15);
});

test('fetch handles multiple requests correctly', function () {
    $this->mockHandler->append(
        new GuzzleResponse(200, [], 'First response'),
        new GuzzleResponse(200, [], 'Second response')
    );

    $firstResponse = fetch('https://example.com/first', [
        'client' => $this->mockClient,
    ]);

    $secondResponse = fetch('https://example.com/second', [
        'client' => $this->mockClient,
    ]);

    expect($this->historyContainer)->toHaveCount(2);

    expect($firstResponse->getBody()->__toString())
        ->toBe('First response');

    expect($secondResponse->getBody()->__toString())
        ->toBe('Second response');

    expect((string) $this->historyContainer[0]['request']->getUri())
        ->toBe('https://example.com/first');

    expect((string) $this->historyContainer[1]['request']->getUri())
        ->toBe('https://example.com/second');
});

test('fetch handles URLs with non-standard ports', function () {
    $this->mockHandler->append(new GuzzleResponse(200));

    fetch('https://example.com:8443/api', [
        'client' => $this->mockClient,
    ]);

    expect($this->historyContainer)->toHaveCount(1);
    $request = $this->historyContainer[0]['request'];

    $uri = $request->getUri();
    expect($uri->getPort())->toBe(8443);
    expect((string) $uri)->toBe('https://example.com:8443/api');
});

test('fetch correctly merges options from different sources', function () {
    $this->mockHandler->append(new GuzzleResponse(200));

    fetch('https://example.com', [
        'method' => 'POST',
        'body' => ['name' => 'test'],
        'headers' => ['X-Custom' => 'value'],
        'timeout' => 30,
        'query' => ['debug' => 1],
        'client' => $this->mockClient,
    ]);

    expect($this->historyContainer)->toHaveCount(1);
    $request = $this->historyContainer[0]['request'];
    $options = $this->historyContainer[0]['options'];

    expect($request->getMethod())->toBe('POST');
    expect($request->getHeaderLine('Content-Type'))->toBe('application/json');
    expect($request->getHeaderLine('X-Custom'))->toBe('value');
    expect((string) $request->getUri())->toBe('https://example.com?debug=1');
    expect($options)->toHaveKey('timeout', 30);

    $body = json_decode((string) $request->getBody(), true);
    expect($body)->toBe(['name' => 'test']);
});
