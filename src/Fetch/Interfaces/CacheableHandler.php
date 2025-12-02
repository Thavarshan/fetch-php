<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Cache\CacheManager;

/**
 * Contract for handlers that expose a cache manager.
 */
interface CacheableHandler
{
    public function getCacheManager(): ?CacheManager;
}
