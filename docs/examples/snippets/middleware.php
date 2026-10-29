<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use Fetch\Interfaces\Response as FetchResponse;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;

MockServer::fake([
    'GET https://api.example.test/v1/profile' => MockResponse::json([
        'id' => 1,
    ]),
]);
MockServer::preventStrayRequests();

$handler = fetch_client(reset: true)->getHandler();
$handler->addMiddleware(static function (
    RequestInterface $request,
    callable $next,
): ResponseInterface|PromiseInterface {
    return $next($request->withHeader('X-Request-Id', 'demo-123'));
});

$response = $handler->get('https://api.example.test/v1/profile');

if (! ($response instanceof FetchResponse) || ! $response->successful()) {
    throw new RuntimeException('The middleware example failed.');
}
