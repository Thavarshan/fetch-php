<?php

declare(strict_types=1);

namespace Fetch\Support;

/**
 * Contract for collecting per-request lifecycle events and summaries.
 *
 * Implementations should keep profiles isolated by request ID and tolerate
 * clearing an ID that has not been started.
 */
interface ProfilerInterface
{
    /**
     * Report whether profiling is currently active.
     */
    public function isEnabled(): bool;

    /**
     * Enable collection for subsequently recorded request events.
     */
    public function enable(): self;

    /**
     * Disable collection without discarding recorded profiles.
     */
    public function disable(): self;

    /**
     * Begin a profile for a logical request.
     */
    public function startProfile(string $requestId): void;

    /**
     * Record a named lifecycle event, using the current time when omitted.
     */
    public function recordEvent(
        string $requestId,
        string $event,
        ?float $timestamp = null,
    ): void;

    /**
     * Finish a profile and optionally attach the response status code.
     */
    public function endProfile(
        string $requestId,
        ?int $statusCode = null,
    ): void;

    /**
     * Returns one named request profile, or null when it is not recorded.
     *
     * @return array<string, mixed>|null
     */
    public function getProfile(string $requestId): ?array;

    /**
     * Returns all collected request profiles keyed by request identifier.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllProfiles(): array;

    /**
     * Remove one recorded profile by request ID.
     */
    public function clearProfile(string $requestId): void;

    /**
     * Remove all recorded profiles.
     */
    public function clearAll(): void;

    /**
     * Returns aggregate profiling metrics for the recorded requests.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array;
}
