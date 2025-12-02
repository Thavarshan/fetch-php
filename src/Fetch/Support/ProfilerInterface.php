<?php

declare(strict_types=1);

namespace Fetch\Support;

/**
 * Contract for request profilers.
 */
interface ProfilerInterface
{
    public function isEnabled(): bool;

    public function enable(): self;

    public function disable(): self;

    public function startProfile(string $requestId): void;

    public function recordEvent(string $requestId, string $event, ?float $timestamp = null): void;

    public function endProfile(string $requestId, ?int $statusCode = null): void;

    /**
     * @return array<string, mixed>|null
     */
    public function getProfile(string $requestId): ?array;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getAllProfiles(): array;

    public function clearProfile(string $requestId): void;

    public function clearAll(): void;

    /**
     * @return array<string, mixed>
     */
    public function getSummary(): array;
}
