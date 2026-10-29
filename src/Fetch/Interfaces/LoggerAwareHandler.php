<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Psr\Log\LoggerInterface;

/** Optional logger configuration supported by concrete request handlers. */
interface LoggerAwareHandler
{
    public function setLogger(LoggerInterface $logger): self;
}
