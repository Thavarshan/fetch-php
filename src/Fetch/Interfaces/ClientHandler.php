<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use GuzzleHttp\ClientInterface;

/**
 * Composes the configuration and execution contracts exposed by a handler.
 *
 * Implementations provide fluent request setup, sync and async execution,
 * caching, middleware, events, retries, diagnostics, and transport access.
 */
interface ClientHandler extends
    CacheableRequestHandler,
    DebuggableHandler,
    EventAware,
    HttpClientAware,
    MiddlewareAware,
    PoolAwareHandler,
    PromiseHandler,
    RequestConfigurator,
    RequestExecutor,
    RetryableHandler
{
    /**
     * Returns the request options applied by default.
     *
     * @return array<string, mixed>
     */
    public static function getDefaultOptions(): array;

    /**
     * Replaces the options applied to requests by default.
     *
     * @param  array<string, mixed>  $options
     */
    public static function setDefaultOptions(array $options): void;

    /**
     * Create a handler using the configured package defaults.
     */
    public static function create(): self;

    /**
     * Create a handler with a base URI for relative request paths.
     */
    public static function createWithBaseUri(string $baseUri): self;

    /**
     * Create a handler that uses the supplied Guzzle client.
     */
    public static function createWithClient(ClientInterface $client): self;
}
