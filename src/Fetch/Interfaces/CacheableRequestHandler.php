<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Cache\CacheInterface;

interface CacheableRequestHandler extends CacheableHandler
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function withCache(?CacheInterface $cache = null, array $options = []): self;

    public function withoutCache(): self;

    public function getCache(): ?CacheInterface;

    public function isCacheEnabled(): bool;
}
