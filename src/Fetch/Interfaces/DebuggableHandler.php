<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Support\DebugInfo;
use Fetch\Support\FetchProfiler;
use Fetch\Support\ProfilerInterface;
use Psr\Log\LoggerInterface;

/**
 * Configures request logging, debugging snapshots, and profiling.
 */
interface DebuggableHandler
{
    /**
     * Set the PSR-3 level used for request diagnostics.
     */
    public function withLogLevel(string $level): self;

    /**
     * Returns diagnostic data collected for the most recent request.
     *
     * @return array<string, mixed>
     */
    public function debug(): array;

    /**
     * Replace the logger used by this handler.
     */
    public function setLogger(LoggerInterface $logger): self;

    /**
     * Attach a profiler that receives request lifecycle measurements.
     */
    public function withProfiler(
        FetchProfiler|ProfilerInterface $profiler,
    ): self;

    /**
     * Return the configured profiler, when one is attached.
     */
    public function getProfiler(): ?ProfilerInterface;

    /**
     * Configures request debugging and returns the current handler.
     *
     * @param  array<string, mixed>|bool  $options
     */
    public function withDebug(array|bool $options = true): self;

    /**
     * Report whether detailed debug capture is enabled.
     */
    public function isDebugEnabled(): bool;

    /**
     * Returns the debugging options currently configured.
     *
     * @return array<string, mixed>
     */
    public function getDebugOptions(): array;

    /**
     * Return the most recent request snapshot, when one is available.
     */
    public function getLastDebugInfo(): ?DebugInfo;
}
