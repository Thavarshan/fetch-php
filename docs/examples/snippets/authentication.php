<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use Fetch\Interfaces\Response as FetchResponse;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;

MockServer::fake([
    'GET https://api.example.test/v1/profile' => MockResponse::json([
        'id' => 7,
    ]),
]);
MockServer::preventStrayRequests();

$handler = fetch_client(reset: true)->getHandler();
$handler->withToken('example-token');
$response = $handler->get('https://api.example.test/v1/profile');

if (! ($response instanceof FetchResponse) || $response->json()['id'] !== 7) {
    throw new RuntimeException('The authenticated request example failed.');
}

MockServer::assertSent(
    static fn ($request): bool => $request->hasHeader('Authorization') &&
        $request->getHeaderLine('Authorization') === 'Bearer example-token',
);
