<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use Fetch\Http\ClientHandler;
use Fetch\Interfaces\Response as FetchResponse;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;

MockServer::fake([
    'GET https://api.example.test/user' => MockResponse::json(['id' => 42]),
]);
MockServer::preventStrayRequests();
$handler = ClientHandler::create()->withResponseSchema([
    'type' => 'object',
    'required' => ['id'],
    'properties' => ['id' => ['type' => 'integer']],
]);
$response = $handler->get('https://api.example.test/user');

if (! ($response instanceof FetchResponse) || $response->json()['id'] !== 42) {
    throw new RuntimeException('The validated response did not match.');
}
