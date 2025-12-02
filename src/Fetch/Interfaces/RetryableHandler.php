<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

interface RetryableHandler
{
    public function retry(int $retries, int $delay = 100): self;

    /**
     * @param  array<int>  $statusCodes
     */
    public function retryStatusCodes(array $statusCodes): self;

    /**
     * @param  array<class-string<\Throwable>>  $exceptions
     */
    public function retryExceptions(array $exceptions): self;

    public function getMaxRetries(): int;

    public function getRetryDelay(): int;

    /**
     * @return array<int>
     */
    public function getRetryableStatusCodes(): array;

    /**
     * @return array<class-string<\Throwable>>
     */
    public function getRetryableExceptions(): array;
}
