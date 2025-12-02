<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use GuzzleHttp\ClientInterface;

interface ClientHandler extends CacheableRequestHandler, DebuggableHandler, HttpClientAware, PoolAwareHandler, PromiseHandler, RequestConfigurator, RequestExecutor, RetryableHandler
{
    /**
     * @return array<string, mixed>
     */
    public static function getDefaultOptions(): array;

    /**
     * @param  array<string, mixed>  $options
     */
    public static function setDefaultOptions(array $options): void;

    public static function create(): self;

    public static function createWithBaseUri(string $baseUri): self;

    public static function createWithClient(ClientInterface $client): self;
}
