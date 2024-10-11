<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use Fetch\Http\Response;
use Mockery\MockInterface;
use GuzzleHttp\Psr7\Request;
use Fetch\Http\ClientHandler;
use GuzzleHttp\Exception\RequestException;

beforeEach(function () {
    \Mockery::close(); // Reset Mockery before each test
});

/*
 * Test for a successful synchronous GET request.
 */
test('makes a successful synchronous GET request', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('GET', 'https://example.com', \Mockery::type('array'))
            ->andReturn(new Response(200, [], json_encode(['success' => true])));
    });

    $clientHandler = new ClientHandler;
    $clientHandler->setSyncClient($mockClient);

    $response = $clientHandler->get('https://example.com');

    expect($response->json())->toBe(['success' => true]);
    expect($response->getStatusCode())->toBe(200);
});

/*
 * Test for a successful asynchronous GET request.
 */
test('makes a successful asynchronous GET request', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('GET', 'https://example.com', \Mockery::type('array'))
            ->andReturn(new Response(200, [], json_encode(['async' => 'result'])));
    });

    $clientHandler = new ClientHandler;
    $clientHandler->setSyncClient($mockClient);

    async(fn () => $clientHandler->get('https://example.com'))
        ->then(function (Response $response) {
            expect($response->json())->toBe(['async' => 'result']);
            expect($response->getStatusCode())->toBe(200);
        });
});

/*
 * Test for sending headers with a GET request.
 */
test('sends headers with a GET request', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('GET', 'https://example.com', \Mockery::on(function ($options) {
                return $options['headers']['Authorization'] === 'Bearer token';
            }))
            ->andReturn(new Response(200, [], 'Headers checked'));
    });

    $clientHandler = new ClientHandler;
    $clientHandler->setSyncClient($mockClient);

    $response = $clientHandler->withHeaders(['Authorization' => 'Bearer token'])
        ->get('https://example.com');

    expect($response->text())->toBe('Headers checked');
});

/*
 * Test for sending query parameters with a GET request.
 */
test('appends query parameters to the GET request', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('GET', 'https://example.com', \Mockery::on(function ($options) {
                return $options['query'] === ['foo' => 'bar', 'baz' => 'qux'];
            }))
            ->andReturn(new Response(200, [], 'Query params checked'));
    });

    $clientHandler = new ClientHandler;
    $clientHandler->setSyncClient($mockClient);

    $response = $clientHandler->withQueryParameters(['foo' => 'bar', 'baz' => 'qux'])
        ->get('https://example.com');

    expect($response->text())->toBe('Query params checked');
});

/*
 * Test for handling timeouts in synchronous requests.
 */
test('handles timeout for synchronous requests', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('GET', 'https://example.com', \Mockery::on(function ($options) {
                return $options['timeout'] === 1;
            }))
            ->andThrow(new RequestException('Timeout', new Request('GET', 'https://example.com')));
    });

    $clientHandler = new ClientHandler;
    $clientHandler->setSyncClient($mockClient);

    expect(fn () => $clientHandler->timeout(1)->get('https://example.com'))
        ->toThrow(RequestException::class, 'Timeout');
});

/*
 * Test for retry mechanism in synchronous requests.
 */
test('retries a failed synchronous request', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->times(1)
            ->with('GET', 'https://example.com', \Mockery::type('array'))
            ->andThrow(new RequestException('Failed request', new Request('GET', 'https://example.com')));
        $mock->shouldReceive('request')
            ->times(1)
            ->with('GET', 'https://example.com', \Mockery::type('array'))
            ->andReturn(new Response(200, [], 'Success after retry'));
    });

    $clientHandler = new ClientHandler;
    $clientHandler->setSyncClient($mockClient);

    $response = $clientHandler->retry(2, 100)->get('https://example.com');

    expect($response->text())->toBe('Success after retry');
});

/*
 * Test for making a POST request with body data.
 */
test('makes a POST request with body data', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('POST', 'https://example.com/users', \Mockery::on(function ($options) {
                return $options['body'] === json_encode(['name' => 'John']);
            }))
            ->andReturn(new Response(201, [], 'Created'));
    });

    $clientHandler = new ClientHandler;
    $clientHandler->setSyncClient($mockClient);

    $response = $clientHandler->withBody(['name' => 'John'])
        ->post('https://example.com/users');

    expect($response->getStatusCode())->toBe(201);
    expect($response->text())->toBe('Created');
});

/*
 * Test for retry mechanism in asynchronous requests.
 */
test('retries an asynchronous request on failure', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->times(1)
            ->with('GET', 'https://example.com', \Mockery::type('array'))
            ->andThrow(new RequestException('Failed request', new Request('GET', 'https://example.com')));
        $mock->shouldReceive('request')
            ->times(1)
            ->with('GET', 'https://example.com', \Mockery::type('array'))
            ->andReturn(new Response(200, [], 'Success after retry'));
    });

    $clientHandler = new ClientHandler;
    $clientHandler->setSyncClient($mockClient);

    async(fn () => $clientHandler->retry(2, 100)->get('https://example.com'))
        ->then(function (Response $response) {
            expect($response->text())->toBe('Success after retry');
        });
});
