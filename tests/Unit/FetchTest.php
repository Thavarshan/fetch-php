<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use Fetch\Http\Response;
use Mockery\MockInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

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
            ->with('GET', 'http://localhost', \Mockery::type('array'))
            ->andReturn(new Response(200, [], json_encode(['success' => true])));
    });

    $response = fetch('http://localhost', ['client' => $mockClient]);

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
            ->with('GET', 'http://localhost', \Mockery::type('array'))
            ->andReturn(new Response(200, [], json_encode(['async' => 'result'])));
    });

    async(fn () => fetch('http://localhost', ['client' => $mockClient]))
        ->then(function (Response $response) {
            expect($response->json())->toBe(['async' => 'result']);
            expect($response->getStatusCode())->toBe(200);
        })
        ->catch(function (\Throwable $e) {
            throw $e;
        });
});

test('fetch makes successful synchronous POST request using fluent API', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('POST', 'http://localhost/posts', \Mockery::type('array'))
            ->andReturn(new Response(201, [], json_encode(['success' => true])));
    });

    $response = fetch()
        ->setSyncClient($mockClient) // Set the mock client
        ->baseUri('http://localhost')
        ->withHeaders(['Content-Type' => 'application/json'])
        ->withBody(['key' => 'value'])
        ->withToken('fake-bearer-auth-token')
        ->post('/posts');

    expect($response->json())->toBe(['success' => true]);
    expect($response->getStatusCode())->toBe(201);
});

/*
 * Test for sending headers with a GET request using fetch.
 */
test('fetch sends headers with a GET request', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('GET', 'http://localhost', \Mockery::on(function ($options) {
                return $options['headers']['Authorization'] === 'Bearer token';
            }))
            ->andReturn(new Response(200, [], 'Headers checked'));
    });

    $response = fetch('http://localhost', [
        'headers' => ['Authorization' => 'Bearer token'],
        'client'  => $mockClient,
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
            ->with('GET', 'http://localhost', \Mockery::on(function ($options) {
                return $options['query'] === ['foo' => 'bar', 'baz' => 'qux'];
            }))
            ->andReturn(new Response(200, [], 'Query params checked'));
    });

    $response = fetch('http://localhost', [
        'query'  => ['foo' => 'bar', 'baz' => 'qux'],
        'client' => $mockClient,
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
            ->with('GET', 'http://localhost', \Mockery::on(function ($options) {
                return $options['timeout'] === 1;
            }))
            ->andThrow(new RequestException('Timeout', new Request('GET', 'http://localhost')));
    });

    try {
        fetch('http://localhost', ['timeout' => 1, 'client' => $mockClient]);
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
            ->with('GET', 'http://localhost', \Mockery::type('array'))
            ->andThrow(new RequestException('Failed request', new Request('GET', 'http://localhost')));
        $mock->shouldReceive('request')
            ->times(1) // Expecting 2 calls: 1 failed, 1 retry
            ->with('GET', 'http://localhost', \Mockery::type('array'))
            ->andReturn(new Response(200, [], 'Success after retry'));
    });

    $response = fetch('http://localhost', ['retries' => 2, 'client' => $mockClient]);

    expect($response->text())->toBe('Success after retry');
});

/*
 * Test for making a POST request with body data using fetch.
 */
test('fetch makes a POST request with body data', function () {
    $mockClient = mock(Client::class, function (MockInterface $mock) {
        $mock->shouldReceive('request')
            ->once()
            ->with('POST', 'http://localhost/users', \Mockery::on(function ($options) {
                return $options['body'] === json_encode(['name' => 'John']);
            }))
            ->andReturn(new Response(201, [], 'Created'));
    });

    $response = fetch('http://localhost/users', [
        'method' => 'POST',
        'body'   => json_encode(['name' => 'John']),
        'client' => $mockClient,
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
            ->with('GET', 'http://localhost', \Mockery::type('array'))
            ->andThrow(new RequestException('Failed request', new Request('GET', 'http://localhost')));
        $mock->shouldReceive('request')
            ->times(1)
            ->with('GET', 'http://localhost', \Mockery::type('array'))
            ->andReturn(new Response(200, [], 'Success after retry'));
    });

    async(fn () => fetch('http://localhost', ['retries' => 2, 'client' => $mockClient]))
        ->then(function (Response $response) {
            expect($response->text())->toBe('Success after retry');
        })
        ->catch(function (\Throwable $e) {
            throw $e; // Fail the test if an exception is caught
        });
});
