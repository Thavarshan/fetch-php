<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use Fetch\Enum\Method;
use Fetch\Http\ClientHandler;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

$mock = new MockHandler([
    new GuzzleResponse(
        200,
        ['Content-Type' => 'text/plain'],
        "first line\nsecond line\n",
    ),
]);
$client = new GuzzleClient(['handler' => HandlerStack::create($mock)]);
$handler = ClientHandler::createWithClient($client);
$stream = $handler->stream(Method::GET, 'https://api.example.test/log');
$lines = iterator_to_array($stream->lines(), false);

if ($lines !== ['first line', 'second line']) {
    throw new RuntimeException(
        'The streamed response example returned unexpected lines.',
    );
}
