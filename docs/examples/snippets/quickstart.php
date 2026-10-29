<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;
use Fetch\Interfaces\Response as FetchResponse;

MockServer::fake([
    'GET https://api.example.test/v1/users/42' => MockResponse::json([
        'id' => 42,
        'name' => 'Ada Lovelace',
    ]),
]);
MockServer::preventStrayRequests();

$response = fetch('https://api.example.test/v1/users/42');

if (! $response instanceof FetchResponse
    || ! $response->isStatus(200)
    || $response->json()['name'] !== 'Ada Lovelace'
) {
    throw new RuntimeException('The quickstart request returned an unexpected response.');
}
