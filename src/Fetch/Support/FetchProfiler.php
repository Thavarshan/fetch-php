<?php

declare(strict_types=1);

namespace Fetch\Support;

/**
 * FetchProfiler provides performance profiling capabilities for HTTP requests.
 *
 * This class tracks timing information across multiple phases of an HTTP request,
 * including DNS resolution, connection establishment, SSL handshake, and data transfer.
 */
class FetchProfiler
{
    /**
     * Storage for request profiles.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $profiles = [];

    /**
     * Whether the profiler is enabled.
     */
    protected bool $enabled = true;

    /**
     * Generate a unique request ID.
     *
     * @param  string  $method  HTTP method
     * @param  string  $uri  Request URI
     */
    public static function generateRequestId(string $method, string $uri): string
    {
        return sprintf('%s_%s_%s', strtoupper($method), md5($uri), uniqid());
    }

    /**
     * Start a new profile for a request.
     *
     * @param  string  $requestId  Unique identifier for the request
     */
    public function startProfile(string $requestId): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->profiles[$requestId] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'events' => [],
            'completed' => false,
        ];
    }

    /**
     * Record a timing event for a request.
     *
     * @param  string  $requestId  The request identifier
     * @param  string  $event  The event name (e.g., 'dns_start', 'connect_start', 'ssl_start', 'transfer_start')
     * @param  float|null  $timestamp  The timestamp (defaults to current time)
     */
    public function recordEvent(string $requestId, string $event, ?float $timestamp = null): void
    {
        if (! $this->enabled || ! isset($this->profiles[$requestId])) {
            return;
        }

        $this->profiles[$requestId]['events'][$event] = $timestamp ?? microtime(true);
    }

    /**
     * End a profile for a request.
     *
     * @param  string  $requestId  The request identifier
     * @param  int|null  $statusCode  HTTP status code of the response
     */
    public function endProfile(string $requestId, ?int $statusCode = null): void
    {
        if (! $this->enabled || ! isset($this->profiles[$requestId])) {
            return;
        }

        $this->profiles[$requestId]['end_time'] = microtime(true);
        $this->profiles[$requestId]['end_memory'] = memory_get_usage(true);
        $this->profiles[$requestId]['status_code'] = $statusCode;
        $this->profiles[$requestId]['completed'] = true;
    }

    /**
     * Get the profile for a specific request.
     *
     * @param  string  $requestId  The request identifier
     * @return array<string, mixed>|null The profile data or null if not found
     */
    public function getProfile(string $requestId): ?array
    {
        if (! isset($this->profiles[$requestId])) {
            return null;
        }

        return $this->calculateMetrics($requestId);
    }

    /**
     * Get all profiles.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllProfiles(): array
    {
        $calculated = [];
        foreach (array_keys($this->profiles) as $requestId) {
            $calculated[$requestId] = $this->calculateMetrics($requestId);
        }

        return $calculated;
    }

    /**
     * Clear a specific profile.
     *
     * @param  string  $requestId  The request identifier
     */
    public function clearProfile(string $requestId): void
    {
        unset($this->profiles[$requestId]);
    }

    /**
     * Clear all profiles.
     */
    public function clearAll(): void
    {
        $this->profiles = [];
    }

    /**
     * Enable the profiler.
     *
     * @return $this
     */
    public function enable(): static
    {
        $this->enabled = true;

        return $this;
    }

    /**
     * Disable the profiler.
     *
     * @return $this
     */
    public function disable(): static
    {
        $this->enabled = false;

        return $this;
    }

    /**
     * Check if the profiler is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get summary statistics across all profiles.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        $allProfiles = $this->getAllProfiles();

        if (empty($allProfiles)) {
            return [
                'total_requests' => 0,
                'completed_requests' => 0,
                'failed_requests' => 0,
                'total_time' => 0,
                'average_time' => 0,
                'min_time' => 0,
                'max_time' => 0,
                'total_memory' => 0,
            ];
        }

        $times = [];
        $completed = 0;
        $failed = 0;
        $totalMemory = 0;

        foreach ($allProfiles as $profile) {
            if ($profile['completed']) {
                $times[] = $profile['total_time'];
                $completed++;

                if (isset($profile['status_code']) && $profile['status_code'] >= 400) {
                    $failed++;
                }
            }

            $totalMemory += abs($profile['memory_delta']);
        }

        $totalTime = array_sum($times);

        return [
            'total_requests' => count($allProfiles),
            'completed_requests' => $completed,
            'failed_requests' => $failed,
            'total_time' => round($totalTime, 3),
            'average_time' => $completed > 0 ? round($totalTime / $completed, 3) : 0,
            'min_time' => ! empty($times) ? round(min($times), 3) : 0,
            'max_time' => ! empty($times) ? round(max($times), 3) : 0,
            'total_memory' => $totalMemory,
        ];
    }

    /**
     * Calculate metrics for a profile.
     *
     * @param  string  $requestId  The request identifier
     * @return array<string, mixed>
     */
    protected function calculateMetrics(string $requestId): array
    {
        $profile = $this->profiles[$requestId];
        $startTime = $profile['start_time'];
        $endTime = $profile['end_time'] ?? microtime(true);
        $events = $profile['events'];

        $metrics = [
            'request_id' => $requestId,
            'total_time' => round(($endTime - $startTime) * 1000, 3), // in ms
            'memory_start' => $profile['start_memory'],
            'memory_end' => $profile['end_memory'] ?? memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'status_code' => $profile['status_code'] ?? null,
            'completed' => $profile['completed'],
        ];

        // Calculate memory delta
        $metrics['memory_delta'] = $metrics['memory_end'] - $metrics['memory_start'];

        // Calculate timing phases if events are recorded
        if (isset($events['dns_start'], $events['dns_end'])) {
            $metrics['dns_time'] = round(($events['dns_end'] - $events['dns_start']) * 1000, 3);
        }

        if (isset($events['connect_start'], $events['connect_end'])) {
            $metrics['connect_time'] = round(($events['connect_end'] - $events['connect_start']) * 1000, 3);
        }

        if (isset($events['ssl_start'], $events['ssl_end'])) {
            $metrics['ssl_time'] = round(($events['ssl_end'] - $events['ssl_start']) * 1000, 3);
        }

        if (isset($events['transfer_start'], $events['transfer_end'])) {
            $metrics['transfer_time'] = round(($events['transfer_end'] - $events['transfer_start']) * 1000, 3);
        }

        if (isset($events['request_sent'])) {
            $metrics['time_to_first_byte'] = round(($events['response_start'] ?? $endTime - $events['request_sent']) * 1000, 3);
        }

        // Include raw events for detailed analysis
        $metrics['events'] = $events;

        return $metrics;
    }
}
