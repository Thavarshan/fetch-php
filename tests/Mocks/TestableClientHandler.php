<?php

namespace Tests\Mocks;

use Fetch\Http\ClientHandler;
use Fetch\Http\Response;
use GuzzleHttp\ClientInterface;
use React\Promise\PromiseInterface;

// Create a test subclass to help with testing static methods
/**
 * Exposes ClientHandler static factories through a controllable mock client.
 */
class TestableClientHandler extends ClientHandler
{
    /**
     * Client used by the static handler factory in tests.
     */
    protected static ?ClientInterface $mockClient = null;

    /**
     * Sets the Guzzle client used by subsequently created test handlers.
     */
    public static function setMockClient(ClientInterface $client): void
    {
        self::$mockClient = $client;
    }

    /**
     * Creates a handler configured with the shared mock client.
     */
    public static function create(): static
    {
        return new static(self::$mockClient);
    }

    /**
     * Sends a request through a handler backed by the configured mock.
     */
    public static function handle(
        string $method,
        string $uri,
        array $options = [],
    ): Response|PromiseInterface {
        // Delegate to parent's implementation which uses static::create()
        return parent::handle($method, $uri, $options);
    }
}
