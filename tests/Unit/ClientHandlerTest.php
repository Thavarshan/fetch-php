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
 * Test for a successful synchronous GET request.
 */
test('makes a successful synchronous GET request', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('GET', 'https://example.com', \Mockery::type('array'))
            ->andReturn(new Response(200, [], json_encode(['success' => true])));
    });

    $clientHandler = new ClientHandler();
    $clientHandler->setSyncClient($mockClient);

    $response = $clientHandler->get('https://example.com');

    // Since we are using Fetch\Http\Response, we check the json method
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

    $clientHandler = new ClientHandler();
    $clientHandler->setSyncClient($mockClient);

    // Directly invoke the async method and interact with the AsyncHelper instance
    $clientHandler->async()->get('https://example.com')
        ->then(function (Response $response) {
            expect($response->json())->toBe(['async' => 'result']);
            expect($response->getStatusCode())->toBe(200);
        })
        ->catch(function (\Throwable $e) {
            throw $e; // Fail the test if an exception is caught
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

    $clientHandler = new ClientHandler();
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

    $clientHandler = new ClientHandler();
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

    $clientHandler = new ClientHandler();
    $clientHandler->setSyncClient($mockClient);

    try {
        $clientHandler->timeout(1)->get('https://example.com');
    } catch (RequestException $e) {
        expect($e->getMessage())->toContain('Timeout');
    }
});

/*
 * Test for retry mechanism in synchronous requests.
 */
test('retries a failed synchronous request', function () {
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

    $response = $clientHandler->retry(2)->get('https://example.com'); // Retry once on failure

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

    $clientHandler = new ClientHandler();
    $clientHandler->setSyncClient($mockClient);

    $response = $clientHandler->withBody(json_encode(['name' => 'John']))
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

    $clientHandler = new ClientHandler();
    $clientHandler->setSyncClient($mockClient);

    async(fn () => $clientHandler->retry(2)->get('https://example.com'))
        ->then(function (Response $response) {
            expect($response->text())->toBe('Success after retry');
        })
        ->catch(function (\Throwable $e) {
            throw $e; // Fail the test if an exception is caught
        });
});
