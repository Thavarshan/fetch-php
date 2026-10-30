<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Cache\CacheInterface;

/**
 * Adds request-cache configuration and inspection to a handler.
 */
interface CacheableRequestHandler extends CacheableHandler
{
    /**
     * Enables response caching with the supplied cache options.
     *
     * @param  array<string, mixed>  $options
     */
    public function withCache(
        ?CacheInterface $cache = null,
        array $options = [],
    ): self;

    /**
     * Disable response caching for subsequent requests.
     */
    public function withoutCache(): self;

    /**
     * Return the configured cache store, or null when none is set.
     */
    public function getCache(): ?CacheInterface;

    /**
     * Report whether response caching is enabled for this handler.
     */
    public function isCacheEnabled(): bool;
}
