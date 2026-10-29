<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;
use Fetch\Interfaces\Response as FetchResponse;

MockServer::fake([
    'GET https://api.example.test/v1/users*' => MockResponse::json([
        ['id' => 1, 'name' => 'Ada'],
    ]),
    'POST https://api.example.test/v1/users' => MockResponse::created([
        'id' => 2,
        'name' => 'Grace',
    ]),
]);
MockServer::preventStrayRequests();

$usersResponse = get('https://api.example.test/v1/users', ['active' => true]);

if (! $usersResponse instanceof FetchResponse) {
    throw new RuntimeException('The user request did not return a response.');
}

$users = $usersResponse->json();

$created = fetch('https://api.example.test/v1/users', [
    'method' => 'POST',
    'headers' => ['Accept' => 'application/json'],
    'json' => ['name' => 'Grace'],
    'timeout' => 5,
]);

if (! $created instanceof FetchResponse || count($users) !== 1 || ! $created->isCreated()) {
    throw new RuntimeException('The request examples returned unexpected responses.');
}
