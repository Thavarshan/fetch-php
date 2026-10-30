<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use React\Promise\PromiseInterface;

interface AsyncTransport
{
    /** @param array<string, mixed> $options */
    public function requestAsync(
        string $method,
        string $uri,
        array $options,
    ): PromiseInterface;
}
