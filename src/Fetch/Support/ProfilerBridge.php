<?php

declare(strict_types=1);

namespace Fetch\Support;

use Psr\Http\Message\ResponseInterface;

/**
 * Bridge between request execution and profiling.
 *
 * This service encapsulates profiling operations and provides a clean
 * interface for recording request metrics without coupling the HTTP
 * execution logic to specific profiler implementations.
 *
 * **Design Decisions:**
 *
 * 1. **Null-safe operations**: All methods handle the case where profiling
 *    is disabled or no profiler is configured, avoiding null checks in calling code.
 *
 * 2. **Request ID based**: Each request gets a unique ID for tracking through
 *    its lifecycle (start → events → end).
 *
 * 3. **Stateless per-instance**: The bridge itself is stateless; all state
 *    is maintained in the underlying profiler or via request IDs.
 */
final class ProfilerBridge
{
    /**
     * The profiler instance if configured.
     */
    private ?ProfilerInterface $profiler;

    /**
     * Debug configuration.
     */
    private DebugConfig $debugConfig;

    /**
     * Create a new profiler bridge.
     */
    public function __construct(
        ?ProfilerInterface $profiler = null,
        ?DebugConfig $debugConfig = null,
    ) {
        $this->profiler = $profiler;
        $this->debugConfig = $debugConfig ?? DebugConfig::create();
    }

    /**
     * Create a bridge with a profiler.
     */
    public static function withProfiler(ProfilerInterface $profiler): self
    {
        return new self($profiler);
    }

    /**
     * Create a bridge with debug configuration.
     */
    public static function withDebug(DebugConfig $config): self
    {
        return new self($config->getProfiler(), $config);
    }

    /**
     * Create a disabled bridge (no profiling).
     */
    public static function disabled(): self
    {
        return new self;
    }

    /**
     * Check if profiling is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->profiler !== null && $this->profiler->isEnabled();
    }

    /**
     * Check if debug mode is enabled.
     */
    public function isDebugEnabled(): bool
    {
        return $this->debugConfig->isEnabled();
    }

    /**
     * Get the profiler instance if configured.
     */
    public function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }

    /**
     * Get the debug configuration.
     */
    public function getDebugConfig(): DebugConfig
    {
        return $this->debugConfig;
    }

    /**
     * Start profiling for a request.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     * @return string|null Request ID for tracking, or null if profiling is disabled
     */
    public function startRequest(string $method, string $uri): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $requestId = FetchProfiler::generateRequestId($method, $uri);
        $this->profiler->startProfile($requestId);

        return $requestId;
    }

    /**
     * Record an event during request execution.
     *
     * @param  string|null  $requestId  Request ID from startRequest()
     * @param  string  $event  Event name (e.g., 'request_sent', 'response_start')
     */
    public function recordEvent(?string $requestId, string $event): void
    {
        if ($requestId === null || ! $this->isEnabled()) {
            return;
        }

        $this->profiler->recordEvent($requestId, $event);
    }

    /**
     * End profiling for a request.
     *
     * @param  string|null  $requestId  Request ID from startRequest()
     * @param  int|null  $statusCode  HTTP status code (null for errors)
     */
    public function endRequest(?string $requestId, ?int $statusCode = null): void
    {
        if ($requestId === null || ! $this->isEnabled()) {
            return;
        }

        $this->profiler->endProfile($requestId, $statusCode);
    }

    /**
     * Create debug info for a completed request.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     * @param  array<string, mixed>  $options  Request options
     * @param  ResponseInterface|null  $response  Response if successful
     * @param  array<string, float>  $timings  Timing information
     * @param  array<string, mixed>  $connectionStats  Connection statistics
     * @param  int  $memoryUsage  Memory usage in bytes
     */
    public function createDebugInfo(
        string $method,
        string $uri,
        array $options,
        ?ResponseInterface $response = null,
        array $timings = [],
        array $connectionStats = [],
        int $memoryUsage = 0,
    ): ?DebugInfo {
        if (! $this->isDebugEnabled()) {
            return null;
        }

        return DebugInfo::create(
            $method,
            $uri,
            $options,
            $response,
            $timings,
            $connectionStats,
            $memoryUsage
        );
    }

    /**
     * Capture a debug snapshot if debugging is enabled.
     *
     * Convenience method that calculates timings and creates debug info.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     * @param  array<string, mixed>  $options  Request options
     * @param  ResponseInterface|null  $response  Response if successful
     * @param  float  $startTime  Request start time (microtime)
     * @param  int  $startMemory  Starting memory usage
     * @param  array<string, mixed>  $connectionStats  Connection statistics
     */
    public function captureSnapshot(
        string $method,
        string $uri,
        array $options,
        ?ResponseInterface $response,
        float $startTime,
        int $startMemory,
        array $connectionStats = [],
    ): ?DebugInfo {
        if (! $this->isDebugEnabled()) {
            return null;
        }

        $duration = microtime(true) - $startTime;
        $memoryUsage = memory_get_usage(true) - $startMemory;

        $timings = [
            'total_time' => round($duration * 1000, 3),
            'start_time' => $startTime,
            'end_time' => microtime(true),
        ];

        return $this->createDebugInfo(
            $method,
            $uri,
            $options,
            $response,
            $timings,
            $connectionStats,
            $memoryUsage
        );
    }

    /**
     * Create a copy with a different profiler.
     */
    public function withProfilerInstance(ProfilerInterface $profiler): self
    {
        return new self($profiler, $this->debugConfig);
    }

    /**
     * Create a copy with debug configuration.
     */
    public function withDebugConfig(DebugConfig $config): self
    {
        $profiler = $config->getProfiler() ?? $this->profiler;

        return new self($profiler, $config);
    }

    /**
     * Enable debug mode with options.
     *
     * @param  array<string, mixed>|bool  $options
     */
    public function enableDebug(array|bool $options = true): self
    {
        $config = $this->debugConfig->withOptions($options);

        return new self($this->profiler, $config);
    }

    /**
     * Disable debug mode.
     */
    public function disableDebug(): self
    {
        $config = $this->debugConfig->withOptions(false);

        return new self($this->profiler, $config);
    }
}
