<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use Fetch\Interfaces\Response as FetchResponse;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;

MockServer::fake([
    'GET https://api.example.test/v1/users' => MockResponse::json([
        ['id' => 1, 'name' => 'Ada'],
    ]),
]);
MockServer::preventStrayRequests();

$client = fetch_client(
    [
        'base_uri' => 'https://api.example.test/v1/',
        'headers' => ['Accept' => 'application/json'],
        'timeout' => 5,
    ],
    reset: true,
);

$handler = $client->getHandler();
if ($handler->getOptions()['timeout'] !== 5) {
    throw new RuntimeException('The client options were not applied.');
}

$handler->withHeader('X-Client', 'example');
$response = $handler->get('users');

if (! ($response instanceof FetchResponse) || ! $response->successful()) {
    throw new RuntimeException('The configured client request failed.');
}
