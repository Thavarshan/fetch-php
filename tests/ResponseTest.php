<?php

use Fetch\Response;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

test('Response::json() correctly decodes JSON', function () {
    $guzzleResponse = new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"key":"value"}');
    $response = new Response(
        $guzzleResponse->getStatusCode(),
        $guzzleResponse->getHeaders(),
        (string) $guzzleResponse->getBody(),
        $guzzleResponse->getProtocolVersion(),
        $guzzleResponse->getReasonPhrase()
    );

    $json = $response->json();
    expect($json)->toMatchArray(['key' => 'value']);
});

test('Response::text() correctly retrieves plain text', function () {
    $guzzleResponse = new GuzzleResponse(200, [], 'Plain text content');
    $response = new Response(
        $guzzleResponse->getStatusCode(),
        $guzzleResponse->getHeaders(),
        (string) $guzzleResponse->getBody(),
        $guzzleResponse->getProtocolVersion(),
        $guzzleResponse->getReasonPhrase()
    );

    expect($response->text())->toBe('Plain text content');
});

test('Response::blob() correctly retrieves blob (stream)', function () {
    $guzzleResponse = new GuzzleResponse(200, [], 'Binary data');
    $response = new Response(
        $guzzleResponse->getStatusCode(),
        $guzzleResponse->getHeaders(),
        (string) $guzzleResponse->getBody(),
        $guzzleResponse->getProtocolVersion(),
        $guzzleResponse->getReasonPhrase()
    );

    $blob = $response->blob();
    expect(is_resource($blob))->toBeTrue();
});

test('Response::arrayBuffer() correctly retrieves binary data as string', function () {
    $guzzleResponse = new GuzzleResponse(200, [], 'Binary data');
    $response = new Response(
        $guzzleResponse->getStatusCode(),
        $guzzleResponse->getHeaders(),
        (string) $guzzleResponse->getBody(),
        $guzzleResponse->getProtocolVersion(),
        $guzzleResponse->getReasonPhrase()
    );

    expect($response->arrayBuffer())->toBe('Binary data');
});

test('Response::statusText() correctly retrieves status text', function () {
    $guzzleResponse = new GuzzleResponse(200);
    $response = new Response(
        $guzzleResponse->getStatusCode(),
        $guzzleResponse->getHeaders(),
        (string) $guzzleResponse->getBody(),
        $guzzleResponse->getProtocolVersion(),
        $guzzleResponse->getReasonPhrase()
    );

    expect($response->statusText())->toBe('OK');
});

test('Response status helper methods work correctly', function () {
    $informationalResponse = new Response(100);
    $successfulResponse = new Response(200);
    $redirectionResponse = new Response(301);
    $clientErrorResponse = new Response(404);
    $serverErrorResponse = new Response(500);

    expect($informationalResponse->isInformational())->toBeTrue();
    expect($successfulResponse->ok())->toBeTrue();
    expect($redirectionResponse->isRedirection())->toBeTrue();
    expect($clientErrorResponse->isClientError())->toBeTrue();
    expect($serverErrorResponse->isServerError())->toBeTrue();
});

test('Response handles error gracefully', function () {
    $errorMessage = 'Something went wrong';
    $guzzleResponse = new GuzzleResponse(500, [], $errorMessage);
    $response = new Response(
        $guzzleResponse->getStatusCode(),
        $guzzleResponse->getHeaders(),
        (string) $guzzleResponse->getBody(),
        $guzzleResponse->getProtocolVersion(),
        $guzzleResponse->getReasonPhrase()
    );

    expect($response->getStatusCode())->toBe(500);
    expect($response->text())->toBe($errorMessage);
});
