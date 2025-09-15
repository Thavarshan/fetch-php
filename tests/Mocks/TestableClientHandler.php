<?php

namespace Tests\Mocks;

use Fetch\Http\ClientHandler;
use Fetch\Http\Response;
use GuzzleHttp\ClientInterface;
use React\Promise\PromiseInterface;

// Create a test subclass to help with testing static methods
class TestableClientHandler extends ClientHandler
{
    protected static ?ClientInterface $mockClient = null;

    public static function setMockClient(ClientInterface $client): void
    {
        self::$mockClient = $client;
    }

    public static function create(): static
    {
        return new static(self::$mockClient);
    }

    public static function handle(
        string $method,
        string $uri,
        array $options = []
    ): Response|PromiseInterface {
        // Delegate to parent's implementation which uses static::create()
        return parent::handle($method, $uri, $options);
    }
}
