<?php

use Fetch\Response;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

test('Response::json() correctly decodes JSON', function () {
    $guzzleResponse = new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"key":"value"}');
    $response = new Response($guzzleResponse);

    $json = $response->json();
    expect($json)->toMatchArray(['key' => 'value']);
});

test('Response::json() throws exception for invalid JSON', function () {
    $guzzleResponse = new GuzzleResponse(200, ['Content-Type' => 'application/json'], 'Invalid JSON');
    $response = new Response($guzzleResponse);

    expect(fn () => $response->json())->toThrow(RuntimeException::class, 'Failed to decode JSON');
});

test('Response::text() correctly retrieves plain text', function () {
    $guzzleResponse = new GuzzleResponse(200, [], 'Plain text content');
    $response = new Response($guzzleResponse);

    expect($response->text())->toBe('Plain text content');
});

test('Response::blob() correctly retrieves blob (stream)', function () {
    $guzzleResponse = new GuzzleResponse(200, [], 'Binary data');
    $response = new Response($guzzleResponse);

    $blob = $response->blob();
    expect(is_resource($blob))->toBeTrue();
    expect(stream_get_contents($blob))->toBe('Binary data');
    fclose($blob);
});

test('Response::arrayBuffer() correctly retrieves binary data as string', function () {
    $guzzleResponse = new GuzzleResponse(200, [], 'Binary data');
    $response = new Response($guzzleResponse);

    expect($response->arrayBuffer())->toBe('Binary data');
});

test('Response::statusText() correctly retrieves status text', function () {
    $guzzleResponse = new GuzzleResponse(200);
    $response = new Response($guzzleResponse);

    expect($response->statusText())->toBe('OK');
});

test('Response status helper methods work correctly', function () {
    $informationalResponse = new Response(new GuzzleResponse(100));
    $successfulResponse = new Response(new GuzzleResponse(200));
    $redirectionResponse = new Response(new GuzzleResponse(301));
    $clientErrorResponse = new Response(new GuzzleResponse(404));
    $serverErrorResponse = new Response(new GuzzleResponse(500));

    expect($informationalResponse->isInformational())->toBeTrue();
    expect($successfulResponse->ok())->toBeTrue();
    expect($redirectionResponse->isRedirection())->toBeTrue();
    expect($clientErrorResponse->isClientError())->toBeTrue();
    expect($serverErrorResponse->isServerError())->toBeTrue();
});

test('Response handles error gracefully', function () {
    $errorMessage = 'Something went wrong';
    $guzzleResponse = new GuzzleResponse(500, [], $errorMessage);
    $response = new Response($guzzleResponse);

    expect($response->getStatusCode())->toBe(500);
    expect($response->text())->toBe($errorMessage);
});

test('Response handles empty body and returns empty array when $assoc is true', function () {
    $guzzleResponse = new GuzzleResponse(200, ['Content-Type' => 'application/json'], '');
    $response = new Response($guzzleResponse);

    expect($response->text())->toBe('');
    expect($response->json(true))->toBe([]);
});

test('Response handles empty body and returns null when $assoc is false', function () {
    $guzzleResponse = new GuzzleResponse(200, ['Content-Type' => 'application/json'], '');
    $response = new Response($guzzleResponse);

    expect($response->text())->toBe('');
    expect($response->json(false))->toBeNull();
});

test('Response::header() correctly sets and retrieves headers', function () {
    $guzzleResponse = new GuzzleResponse(200);
    $response = new Response($guzzleResponse);

    $response->header('X-Custom-Header', 'HeaderValue');
    $headers = $response->headers();

    expect($headers)->toHaveKey('X-Custom-Header');
    expect($headers['X-Custom-Header'])->toBe(['HeaderValue']);
});

test('Response::status() correctly sets and retrieves status code', function () {
    $guzzleResponse = new GuzzleResponse(200);
    $response = new Response($guzzleResponse);

    $response->status(404);
    expect($response->getStatusCode())->toBe(404);
});

test('Response handles non-JSON content types', function () {
    $guzzleResponse = new GuzzleResponse(200, ['Content-Type' => 'text/plain'], 'Plain text content');
    $response = new Response($guzzleResponse);

    expect($response->text())->toBe('Plain text content');
    expect(fn () => $response->json())->toThrow(RuntimeException::class, 'Failed to decode JSON');
});

test('Response handles large bodies', function () {
    $largeContent = str_repeat('A', 1024 * 1024); // 1MB of 'A'
    $guzzleResponse = new GuzzleResponse(200, [], $largeContent);
    $response = new Response($guzzleResponse);

    expect($response->text())->toBe($largeContent);
    expect($response->arrayBuffer())->toBe($largeContent);
});

test('Response handles different status codes', function () {
    $statusCodes = [100, 200, 301, 404, 500];
    foreach ($statusCodes as $statusCode) {
        $guzzleResponse = new GuzzleResponse($statusCode);
        $response = new Response($guzzleResponse);
        expect($response->getStatusCode())->toBe($statusCode);
    }
});
