<?php

declare(strict_types=1);

namespace Fetch\Support;

/**
 * Central configuration for debugging and profiling.
 */
class DebugConfig
{
    /**
     * Whether debug snapshots and request tracing are enabled.
     */
    protected bool $enabled;

    /**
     * Stores the normalized options that control request diagnostics.
     *
     * @var array<string, mixed>
     */
    protected array $options;

    /**
     * Profiler receiving lifecycle data when one is configured.
     */
    protected ?ProfilerInterface $profiler;

    /**
     * Creates an immutable debug configuration from the supplied options.
     *
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        bool $enabled = false,
        array $options = [],
        ?ProfilerInterface $profiler = null,
    ) {
        $this->enabled = $enabled;
        $this->options =
            $options === []
                ? DebugInfo::getDefaultOptions()
                : array_merge(DebugInfo::getDefaultOptions(), $options);
        $this->profiler = $profiler;
    }

    /**
     * Create disabled debug configuration with the standard option set.
     */
    public static function create(): self
    {
        return new self;
    }

    /**
     * Merges options into the existing request configuration.
     *
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

    /**
     * Return a copy using the supplied profiler.
     */
    public function withProfiler(?ProfilerInterface $profiler): self
    {
        return new self($this->enabled, $this->options, $profiler);
    }

    /**
     * Report whether debug capture is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Returns the options stored in this configuration object.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Return the profiler attached to this configuration, if any.
     */
    public function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }
}
