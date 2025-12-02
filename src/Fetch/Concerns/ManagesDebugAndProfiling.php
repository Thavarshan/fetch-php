<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Support\DebugConfig;
use Fetch\Support\DebugInfo;
use Fetch\Support\FetchProfiler;
use Fetch\Support\ProfilerBridge;
use Fetch\Support\ProfilerInterface;

/**
 * Trait ManagesDebugAndProfiling.
 *
 * Provides debugging and profiling capabilities for HTTP requests.
 */
trait ManagesDebugAndProfiling
{
    /**
     * Centralized debug configuration.
     */
    protected ?DebugConfig $debugConfig = null;

    /**
     * The last debug info from the most recent request.
     *
     * @deprecated This property is kept for backward compatibility only.
     *             Debug info is now stored per-response via Response::getDebugInfo().
     *             This will be removed in a future major version.
     */
    protected ?DebugInfo $lastDebugInfo = null;

    /**
     * Bridge for profiling/debug operations.
     */
    protected ?ProfilerBridge $profilerBridge = null;

    /**
     * Enable debug mode with specified options.
     *
     * @param array<string, mixed>|bool $options Debug options or true to enable all
     *
     * @return $this
     */
    public function withDebug(array|bool $options = true): static
    {
        $bridge = $this->getProfilerBridge()->enableDebug($options);
        $this->profilerBridge = $bridge;
        $this->debugConfig = $bridge->getDebugConfig();

        return $this;
    }

    /**
     * Set a profiler for performance tracking.
     *
     * @param FetchProfiler|ProfilerInterface $profiler The profiler instance
     *
     * @return $this
     */
    public function withProfiler(FetchProfiler|ProfilerInterface $profiler): static
    {
        $bridge = $this->getProfilerBridge()->withProfilerInstance($profiler);
        $this->profilerBridge = $bridge;
        $this->debugConfig = $bridge->getDebugConfig();

        return $this;
    }

    /**
     * Get the profiler instance if set.
     */
    public function getProfiler(): ?ProfilerInterface
    {
        return $this->getProfilerBridge()->getProfiler();
    }

    /**
     * Check if debug mode is enabled.
     */
    public function isDebugEnabled(): bool
    {
        return $this->getProfilerBridge()->isDebugEnabled();
    }

    /**
     * Get the debug options.
     *
     * @return array<string, mixed>
     */
    public function getDebugOptions(): array
    {
        return $this->getProfilerBridge()->getDebugConfig()->getOptions();
    }

    /**
     * Get the last debug info from the most recent request.
     *
     * @deprecated Use Response::getDebugInfo() instead for per-request debug info.
     *             This method is kept for backward compatibility but may return
     *             incorrect data in concurrent async scenarios.
     */
    public function getLastDebugInfo(): ?DebugInfo
    {
        return $this->lastDebugInfo;
    }

    /**
     * Create debug info for the current request.
     *
     * Returns a DebugInfo instance that should be attached to the response.
     * Also updates lastDebugInfo for backward compatibility.
     *
     * @param string                                   $method          HTTP method
     * @param string                                   $uri             Request URI
     * @param array<string, mixed>                     $options         Request options
     * @param \Psr\Http\Message\ResponseInterface|null $response        The response
     * @param array<string, float>                     $timings         Timing information
     * @param array<string, mixed>                     $connectionStats Connection statistics
     * @param int                                      $memoryUsage     Memory usage in bytes
     *
     * @return DebugInfo The debug info to attach to the response
     */
    protected function createDebugInfo(
        string $method,
        string $uri,
        array $options,
        ?\Psr\Http\Message\ResponseInterface $response = null,
        array $timings = [],
        array $connectionStats = [],
        int $memoryUsage = 0,
    ): DebugInfo {
        $debugInfo = $this->getProfilerBridge()->createDebugInfo(
            $method,
            $uri,
            $options,
            $response,
            $timings,
            $connectionStats,
            $memoryUsage
        );

        // Update lastDebugInfo for backward compatibility
        $this->lastDebugInfo = $debugInfo;

        return $debugInfo;
    }

    /**
     * Start profiling for a request.
     *
     * @param string $method HTTP method
     * @param string $uri    Request URI
     *
     * @return string|null The request ID if profiling is enabled
     */
    protected function startProfiling(string $method, string $uri): ?string
    {
        return $this->getProfilerBridge()->startRequest($method, $uri);
    }

    /**
     * Record a profiling event.
     *
     * @param string|null $requestId The request ID
     * @param string      $event     The event name
     */
    protected function recordProfilingEvent(?string $requestId, string $event): void
    {
        $this->getProfilerBridge()->recordEvent($requestId, $event);
    }

    /**
     * End profiling for a request.
     *
     * @param string|null $requestId  The request ID
     * @param int|null    $statusCode HTTP status code
     */
    protected function endProfiling(?string $requestId, ?int $statusCode = null): void
    {
        $this->getProfilerBridge()->endRequest($requestId, $statusCode);
    }

    /**
     * Lazily initialize debug configuration.
     */
    protected function getDebugConfig(): DebugConfig
    {
        if (null === $this->debugConfig) {
            $this->debugConfig = DebugConfig::create();
        }

        return $this->debugConfig;
    }

    /**
     * Lazily initialize profiler bridge.
     */
    protected function getProfilerBridge(): ProfilerBridge
    {
        if (null === $this->profilerBridge) {
            $this->profilerBridge = new ProfilerBridge(
                profiler: $this->debugConfig?->getProfiler(),
                debugConfig: $this->debugConfig ?? DebugConfig::create()
            );
        }

        return $this->profilerBridge;
    }

    /**
     * Capture and store debug info via the profiler bridge.
     *
     * Returns a DebugInfo instance that should be attached to the response.
     * Also updates lastDebugInfo for backward compatibility.
     *
     * @param array<string, mixed> $options
     * @param array<string, mixed> $connectionStats
     *
     * @return DebugInfo|null The debug info to attach to the response, or null if debug disabled
     */
    protected function captureDebugSnapshot(
        string $method,
        string $uri,
        array $options,
        ?\Psr\Http\Message\ResponseInterface $response,
        float $startTime,
        int $startMemory,
        array $connectionStats = [],
    ): ?DebugInfo {
        $debugInfo = $this->getProfilerBridge()->captureSnapshot(
            $method,
            $uri,
            $options,
            $response,
            $startTime,
            $startMemory,
            $connectionStats
        );

        if (null !== $debugInfo) {
            // Update lastDebugInfo for backward compatibility
            $this->lastDebugInfo = $debugInfo;
        }

        return $debugInfo;
    }
}
