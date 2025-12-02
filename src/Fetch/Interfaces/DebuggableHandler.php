<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Support\FetchProfiler;
use Fetch\Support\ProfilerInterface;
use Psr\Log\LoggerInterface;

interface DebuggableHandler
{
    public function withLogLevel(string $level): self;

    /**
     * @return array<string, mixed>
     */
    public function debug(): array;

    public function setLogger(LoggerInterface $logger): self;

    public function withProfiler(FetchProfiler|ProfilerInterface $profiler): self;

    public function getProfiler(): ?ProfilerInterface;

    /**
     * @param  array<string, mixed>|bool  $options
     */
    public function withDebug(array|bool $options = true): self;

    public function isDebugEnabled(): bool;

    /**
     * @return array<string, mixed>
     */
    public function getDebugOptions(): array;

    public function getLastDebugInfo(): ?\Fetch\Support\DebugInfo;
}
