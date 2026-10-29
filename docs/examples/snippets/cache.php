<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use Fetch\Cache\MemoryCache;
use Fetch\Enum\Method;
use Fetch\Interfaces\Response as FetchResponse;
use Fetch\Http\ClientHandler;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

$mock = new MockHandler([
    new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"version":1}'),
]);
$client = new GuzzleClient(['handler' => HandlerStack::create($mock)]);
$handler = ClientHandler::createWithClient($client)
    ->withCache(new MemoryCache, ['default_ttl' => 60]);

$first = $handler->sendRequest(Method::GET, 'https://api.example.test/v1/catalog');
$second = $handler->sendRequest(Method::GET, 'https://api.example.test/v1/catalog');

if (! $first instanceof FetchResponse || ! $second instanceof FetchResponse) {
    throw new RuntimeException('The cache requests did not return responses.');
}

if (! $second->hasHeader('X-Cache-Status') || $second->getHeaderLine('X-Cache-Status') !== 'HIT') {
    throw new RuntimeException('The cache did not serve the second response.');
}
