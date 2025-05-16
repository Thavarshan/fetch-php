<?php

namespace Tests\Mocks;

use Fetch\Http\ClientHandler;
use Fetch\Http\Response;
use React\Promise\PromiseInterface;

// Create a test subclass to help with testing static methods
class TestableClientHandler extends ClientHandler
{
    public static function handle(
        string $method,
        string $uri,
        array $options = []
    ): Response|PromiseInterface {
        $handler = new static;
        // Instead of applyOptions, use withOptions which exists
        $handler->withOptions($options);

        return $handler->sendRequest($method, $uri);
    }
}
