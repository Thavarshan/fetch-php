<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use Fetch\Interfaces\Response as FetchResponse;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;

MockServer::fake([
    'GET https://api.example.test/v1/users' => MockResponse::json([
        ['id' => 42, 'name' => 'Ada Lovelace'],
    ]),
]);
MockServer::preventStrayRequests();

// @showcase
$client = fetch_client([
    'base_uri' => 'https://api.example.test/v1/',
    'headers' => ['Accept' => 'application/json'],
    'timeout' => 5,
]);

$response = $client->get('users');
// @endshowcase

if (! ($response instanceof FetchResponse) || ! $response->successful()) {
    throw new RuntimeException('The homepage client example failed.');
}
