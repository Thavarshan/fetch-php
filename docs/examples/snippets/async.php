<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;
use Fetch\Interfaces\Response as FetchResponse;

use function Matrix\Support\all;
use function Matrix\Support\async;
use function Matrix\Support\await;

MockServer::fake([
    'GET https://api.example.test/v1/users' => MockResponse::json([['id' => 1]]),
    'GET https://api.example.test/v1/posts' => MockResponse::json([['id' => 8]]),
]);
MockServer::preventStrayRequests();

$results = await(all([
    'users' => async(static function (): FetchResponse {
        $response = fetch('https://api.example.test/v1/users');

        if (! $response instanceof FetchResponse) {
            throw new RuntimeException('The user request did not return a response.');
        }

        return $response;
    }),
    'posts' => async(static function (): FetchResponse {
        $response = fetch('https://api.example.test/v1/posts');

        if (! $response instanceof FetchResponse) {
            throw new RuntimeException('The post request did not return a response.');
        }

        return $response;
    }),
]));

if (! is_array($results)
    || ! isset($results['users'], $results['posts'])
    || count($results['users']->json()) !== 1
    || count($results['posts']->json()) !== 1
) {
    throw new RuntimeException('The promise aggregation example failed.');
}
