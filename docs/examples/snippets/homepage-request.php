<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use Fetch\Interfaces\Response as FetchResponse;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;

$json = file_get_contents(__DIR__.'/homepage-response.json');

if ($json === false) {
    throw new RuntimeException('The homepage response fixture is missing.');
}

/**
 * Defines the expected user payload for the homepage response fixture.
 *
 * @var array{id: int, name: string, role: string} $expected
 */
$expected = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

MockServer::fake([
    'GET https://api.example.test/v1/users/42' => MockResponse::json($expected),
]);
MockServer::preventStrayRequests();

/**
 * Holds the response returned by the documented FetchPHP request.
 *
 * @var FetchResponse $response
 */
// @showcase
$response = fetch(
    'https://api.example.test/v1/users/42',
);

if ($response->successful()) {
    $user = $response->json();
}
// @endshowcase

if (! isset($user) || $user !== $expected || ! $response->isStatus(200)) {
    throw new RuntimeException('The homepage request example failed.');
}
