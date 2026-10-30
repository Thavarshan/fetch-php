<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use Fetch\Http\ClientHandler;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;

use function Matrix\Support\all;
use function Matrix\Support\await;

MockServer::fake([
    'GET https://api.example.test/v1/users' => MockResponse::json([
        ['id' => 1],
    ]),
    'GET https://api.example.test/v1/posts' => MockResponse::json([
        ['id' => 8],
    ]),
]);
MockServer::preventStrayRequests();

$handler = ClientHandler::create()->async();
$results = await(all([
    'users' => $handler->get('https://api.example.test/v1/users'),
    'posts' => $handler->get('https://api.example.test/v1/posts'),
]));

if (
    ! is_array($results) ||
    ! isset($results['users'], $results['posts']) ||
    count($results['users']->json()) !== 1 ||
    count($results['posts']->json()) !== 1
) {
    throw new RuntimeException('The promise aggregation example failed.');
}
