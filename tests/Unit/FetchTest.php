<?php

use Fetch\Http\ClientHandler;
use Fetch\Http\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Mockery\MockInterface;

beforeEach(function () {
    \Mockery::close(); // Reset Mockery before each test
});

/*
 * Test for a successful synchronous GET request using fetch.
 */
test('fetch makes a successful synchronous GET request', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('GET', 'https://example.com', \Mockery::type('array'))
            ->andReturn(new Response(200, [], json_encode(['success' => true])));
    });

    $clientHandler = new ClientHandler();
    $clientHandler->setSyncClient($mockClient);

    $response = fetch('https://example.com');

    expect($response->json())->toBe(['success' => true]);
    expect($response->getStatusCode())->toBe(200);
});

/*
 * Test for a successful asynchronous GET request using fetch.
 */
test('fetch makes a successful asynchronous GET request', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('GET', 'https://example.com', \Mockery::type('array'))
            ->andReturn(new Response(200, [], json_encode(['async' => 'result'])));
    });

    $clientHandler = new ClientHandler();
    $clientHandler->setSyncClient($mockClient);

    async(fn () => fetch('https://example.com', ['async' => true]))
        ->then(function (Response $response) {
            expect($response->json())->toBe(['async' => 'result']);
            expect($response->getStatusCode())->toBe(200);
        })
        ->catch(function (\Throwable $e) {
            throw $e;
        });
});

/*
 * Test for sending headers with a GET request using fetch.
 */
test('fetch sends headers with a GET request', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('GET', 'https://example.com', \Mockery::on(function ($options) {
                return $options['headers']['Authorization'] === 'Bearer token';
            }))
            ->andReturn(new Response(200, [], 'Headers checked'));
    });

    $clientHandler = new ClientHandler();
    $clientHandler->setSyncClient($mockClient);

    $response = fetch('https://example.com', [
        'headers' => ['Authorization' => 'Bearer token']
    ]);

    expect($response->text())->toBe('Headers checked');
});

/*
 * Test for sending query parameters with a GET request using fetch.
 */
test('fetch appends query parameters to the GET request', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('GET', 'https://example.com', \Mockery::on(function ($options) {
                return $options['query'] === ['foo' => 'bar', 'baz' => 'qux'];
            }))
            ->andReturn(new Response(200, [], 'Query params checked'));
    });

    $clientHandler = new ClientHandler();
    $clientHandler->setSyncClient($mockClient);

    $response = fetch('https://example.com', [
        'query' => ['foo' => 'bar', 'baz' => 'qux']
    ]);

    expect($response->text())->toBe('Query params checked');
});

/*
 * Test for handling timeouts in synchronous requests using fetch.
 */
test('fetch handles timeout for synchronous requests', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('GET', 'https://example.com', \Mockery::on(function ($options) {
                return $options['timeout'] === 1;
            }))
            ->andThrow(new RequestException('Timeout', new Request('GET', 'https://example.com')));
    });

    $clientHandler = new ClientHandler();
    $clientHandler->setSyncClient($mockClient);

    try {
        fetch('https://example.com', ['timeout' => 1]);
    } catch (RequestException $e) {
        expect($e->getMessage())->toContain('Timeout');
    }
});

/*
 * Test for retry mechanism in fetch requests.
 */
test('fetch retries a failed synchronous request', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->times(1) // Expecting 2 calls: 1 failed, 1 retry
            ->with('GET', 'https://example.com', \Mockery::type('array'))
            ->andThrow(new RequestException('Failed request', new Request('GET', 'https://example.com')));
        $mock->shouldReceive('request')
            ->times(1) // Expecting 2 calls: 1 failed, 1 retry
            ->with('GET', 'https://example.com', \Mockery::type('array'))
            ->andReturn(new Response(200, [], 'Success after retry'));
    });

    $clientHandler = new ClientHandler();
    $clientHandler->setSyncClient($mockClient);

    $response = fetch('https://example.com', ['retries' => 2]);

    expect($response->text())->toBe('Success after retry');
});

/*
 * Test for making a POST request with body data using fetch.
 */
test('fetch makes a POST request with body data', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('POST', 'https://example.com/users', \Mockery::on(function ($options) {
                return $options['body'] === json_encode(['name' => 'John']);
            }))
            ->andReturn(new Response(201, [], 'Created'));
    });

    $clientHandler = new ClientHandler();
    $clientHandler->setSyncClient($mockClient);

    $response = fetch('https://example.com/users', [
        'method' => 'POST',
        'body' => json_encode(['name' => 'John'])
    ]);

    expect($response->getStatusCode())->toBe(201);
    expect($response->text())->toBe('Created');
});

/*
 * Test for retry mechanism in asynchronous requests using fetch.
 */
test('fetch retries an asynchronous request on failure', function () {
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

    $clientHandler = new ClientHandler();
    $clientHandler->setSyncClient($mockClient);

    async(fn () => fetch('https://example.com', ['retries' => 2, 'async' => true]))
        ->then(function (Response $response) {
            expect($response->text())->toBe('Success after retry');
        })
        ->catch(function (\Throwable $e) {
            throw $e; // Fail the test if an exception is caught
        });
});
