<?php

declare(strict_types=1);

namespace Fetch\Pool;

/**
 * Configuration class for HTTP/2 settings.
 */
class Http2Configuration
{
    /**
     * Default maximum concurrent streams.
     */
    public const DEFAULT_MAX_CONCURRENT_STREAMS = 100;

    /**
     * Default window size (64KB).
     */
    public const DEFAULT_WINDOW_SIZE = 65535;

    /**
     * Default header table size (4KB).
     */
    public const DEFAULT_HEADER_TABLE_SIZE = 4096;

    /**
     * Create a new HTTP/2 configuration instance.
     *
     * @param  bool  $enabled  Whether HTTP/2 is enabled
     * @param  int  $maxConcurrentStreams  Maximum concurrent streams
     * @param  int  $windowSize  Window size for flow control
     * @param  int  $headerTableSize  Header compression table size
     * @param  bool  $enableServerPush  Whether server push is enabled
     * @param  bool  $streamPrioritization  Whether stream prioritization is enabled
     */
    public function __construct(
        protected bool $enabled = true,
        protected int $maxConcurrentStreams = self::DEFAULT_MAX_CONCURRENT_STREAMS,
        protected int $windowSize = self::DEFAULT_WINDOW_SIZE,
        protected int $headerTableSize = self::DEFAULT_HEADER_TABLE_SIZE,
        protected bool $enableServerPush = false,
        protected bool $streamPrioritization = false,
    ) {}

    /**
     * Create a configuration instance from an array.
     *
     * @param  array<string, mixed>  $config  Configuration array
     * @return static New configuration instance
     */
    public static function fromArray(array $config): static
    {
        return new static(
            enabled: (bool) ($config['enabled'] ?? true),
            maxConcurrentStreams: (int) ($config['max_concurrent_streams'] ?? self::DEFAULT_MAX_CONCURRENT_STREAMS),
            windowSize: (int) ($config['window_size'] ?? self::DEFAULT_WINDOW_SIZE),
            headerTableSize: (int) ($config['header_table_size'] ?? self::DEFAULT_HEADER_TABLE_SIZE),
            enableServerPush: (bool) ($config['enable_server_push'] ?? false),
            streamPrioritization: (bool) ($config['stream_prioritization'] ?? false),
        );
    }

    /**
     * Check if HTTP/2 is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get maximum concurrent streams.
     */
    public function getMaxConcurrentStreams(): int
    {
        return $this->maxConcurrentStreams;
    }

    /**
     * Get window size for flow control.
     */
    public function getWindowSize(): int
    {
        return $this->windowSize;
    }

    /**
     * Get header compression table size.
     */
    public function getHeaderTableSize(): int
    {
        return $this->headerTableSize;
    }

    /**
     * Check if server push is enabled.
     */
    public function isServerPushEnabled(): bool
    {
        return $this->enableServerPush;
    }

    /**
     * Check if stream prioritization is enabled.
     */
    public function isStreamPrioritizationEnabled(): bool
    {
        return $this->streamPrioritization;
    }

    /**
     * Get cURL options for HTTP/2.
     *
     * @return array<int, mixed>
     */
    public function getCurlOptions(): array
    {
        $options = [];

        if ($this->enabled) {
            // Use HTTP/2 with automatic fallback
            $options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2_0;
        }

        return $options;
    }

    /**
     * Get cURL multi options for HTTP/2 multiplexing.
     *
     * These options should be used with curl_multi_setopt().
     *
     * @return array<int, mixed>
     */
    public function getCurlMultiOptions(): array
    {
        $options = [];

        if ($this->enabled && defined('CURLPIPE_MULTIPLEX')) {
            $options[CURLMOPT_PIPELINING] = CURLPIPE_MULTIPLEX;
        }

        return $options;
    }

    /**
     * Convert configuration to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'max_concurrent_streams' => $this->maxConcurrentStreams,
            'window_size' => $this->windowSize,
            'header_table_size' => $this->headerTableSize,
            'enable_server_push' => $this->enableServerPush,
            'stream_prioritization' => $this->streamPrioritization,
        ];
    }
}
