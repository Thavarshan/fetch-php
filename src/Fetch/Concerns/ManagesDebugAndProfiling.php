<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Support\DebugInfo;
use Fetch\Support\FetchProfiler;

/**
 * Trait ManagesDebugAndProfiling
 *
 * Provides debugging and profiling capabilities for HTTP requests.
 */
trait ManagesDebugAndProfiling
{
    /**
     * Whether debug mode is enabled.
     */
    protected bool $debugEnabled = false;

    /**
     * Debug options configuration.
     *
     * @var array<string, mixed>
     */
    protected array $debugOptions = [];

    /**
     * The profiler instance for performance tracking.
     */
    protected ?FetchProfiler $profiler = null;

    /**
     * The last debug info from the most recent request.
     */
    protected ?DebugInfo $lastDebugInfo = null;

    /**
     * Enable debug mode with specified options.
     *
     * @param  array<string, mixed>|bool  $options  Debug options or true to enable all
     * @return $this
     */
    public function withDebug(array|bool $options = true): static
    {
        $this->debugEnabled = $options !== false;
        $this->debugOptions = array_merge(DebugInfo::getDefaultOptions(), is_array($options) ? $options : []);

        return $this;
    }

    /**
     * Set a profiler for performance tracking.
     *
     * @param  FetchProfiler  $profiler  The profiler instance
     * @return $this
     */
    public function withProfiler(FetchProfiler $profiler): static
    {
        $this->profiler = $profiler;

        return $this;
    }

    /**
     * Get the profiler instance if set.
     */
    public function getProfiler(): ?FetchProfiler
    {
        return $this->profiler;
    }

    /**
     * Check if debug mode is enabled.
     */
    public function isDebugEnabled(): bool
    {
        return $this->debugEnabled;
    }

    /**
     * Get the debug options.
     *
     * @return array<string, mixed>
     */
    public function getDebugOptions(): array
    {
        return $this->debugOptions;
    }

    /**
     * Get the last debug info from the most recent request.
     */
    public function getLastDebugInfo(): ?DebugInfo
    {
        return $this->lastDebugInfo;
    }

    /**
     * Create debug info for the current request.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     * @param  array<string, mixed>  $options  Request options
     * @param  \Psr\Http\Message\ResponseInterface|null  $response  The response
     * @param  array<string, float>  $timings  Timing information
     * @param  int  $memoryUsage  Memory usage in bytes
     */
    protected function createDebugInfo(
        string $method,
        string $uri,
        array $options,
        ?\Psr\Http\Message\ResponseInterface $response = null,
        array $timings = [],
        int $memoryUsage = 0
    ): DebugInfo {
        $this->lastDebugInfo = DebugInfo::create(
            $method,
            $uri,
            $options,
            $response,
            $timings,
            $memoryUsage
        );

        return $this->lastDebugInfo;
    }

    /**
     * Start profiling for a request.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     * @return string|null The request ID if profiling is enabled
     */
    protected function startProfiling(string $method, string $uri): ?string
    {
        if ($this->profiler === null || ! $this->profiler->isEnabled()) {
            return null;
        }

        $requestId = FetchProfiler::generateRequestId($method, $uri);
        $this->profiler->startProfile($requestId);

        return $requestId;
    }

    /**
     * Record a profiling event.
     *
     * @param  string|null  $requestId  The request ID
     * @param  string  $event  The event name
     */
    protected function recordProfilingEvent(?string $requestId, string $event): void
    {
        if ($requestId === null || $this->profiler === null) {
            return;
        }

        $this->profiler->recordEvent($requestId, $event);
    }

    /**
     * End profiling for a request.
     *
     * @param  string|null  $requestId  The request ID
     * @param  int|null  $statusCode  HTTP status code
     */
    protected function endProfiling(?string $requestId, ?int $statusCode = null): void
    {
        if ($requestId === null || $this->profiler === null) {
            return;
        }

        $this->profiler->endProfile($requestId, $statusCode);
    }
}
