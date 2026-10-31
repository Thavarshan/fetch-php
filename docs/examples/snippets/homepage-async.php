<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

// @showcase
use Fetch\Http\ClientHandler;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;

// @endshowcase

use function Matrix\Support\all;
use function Matrix\Support\await;

MockServer::fake([
    'GET https://api.example.test/users' => MockResponse::json([['id' => 42]]),
    'GET https://api.example.test/posts' => MockResponse::json([['id' => 8]]),
]);
MockServer::preventStrayRequests();

// @showcase
$client = ClientHandler::create()->async();

$responses = await(all([
    'users' => $client->get('https://api.example.test/users'),
    'posts' => $client->get('https://api.example.test/posts'),
]));
// @endshowcase

if (
    ! is_array($responses) ||
    ! isset($responses['users'], $responses['posts']) ||
    count($responses['users']->json()) !== 1 ||
    count($responses['posts']->json()) !== 1
) {
    throw new RuntimeException('The homepage async example failed.');
}
