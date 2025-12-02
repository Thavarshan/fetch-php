<?php

declare(strict_types=1);

namespace Fetch\Support;

/**
 * Central configuration for debugging and profiling.
 */
class DebugConfig
{
    protected bool $enabled;

    /**
     * @var array<string, mixed>
     */
    protected array $options;

    protected ?ProfilerInterface $profiler;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(bool $enabled = false, array $options = [], ?ProfilerInterface $profiler = null)
    {
        $this->enabled = $enabled;
        $this->options = $options === [] ? DebugInfo::getDefaultOptions() : array_merge(DebugInfo::getDefaultOptions(), $options);
        $this->profiler = $profiler;
    }

    public static function create(): self
    {
        return new self;
    }

    /**
     * @param  array<string, mixed>|bool  $options
     */
    public function withOptions(array|bool $options): self
    {
        if ($options === false) {
            return new self(false, $this->options, $this->profiler);
        }

        $mergedOptions = is_array($options)
            ? array_merge(DebugInfo::getDefaultOptions(), $options)
            : $this->options;

        return new self(true, $mergedOptions, $this->profiler);
    }

    public function withProfiler(?ProfilerInterface $profiler): self
    {
        return new self($this->enabled, $this->options, $profiler);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }
}
