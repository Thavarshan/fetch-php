<?php

declare(strict_types=1);

namespace Fetch\Cache;

use RuntimeException;

/**
 * File-based cache implementation for persistent caching.
 */
class FileCache implements CacheInterface
{
    /**
     * File extension for cache files.
     */
    private const FILE_EXTENSION = '.cache';

    /**
     * The cache directory.
     */
    private string $directory;

    /**
     * Default TTL in seconds.
     */
    private int $defaultTtl;

    /**
     * Maximum cache size in bytes.
     */
    private int $maxSize;

    /**
     * Create a new file cache instance.
     */
    public function __construct(
        string $directory = '/tmp/fetch-cache',
        int $defaultTtl = 3600,
        int $maxSize = 104857600
    ) {
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
        $this->defaultTtl = $defaultTtl;
        $this->maxSize = $maxSize;

        $this->ensureDirectoryExists();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): ?CachedResponse
    {
        $path = $this->getPath($key);

        if (! file_exists($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $data = json_decode($contents, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            // Invalid cache file, delete it
            @unlink($path);

            return null;
        }

        // Reconstruct the cached response
        $response = CachedResponse::fromArray($data);
        if ($response === null) {
            @unlink($path);

            return null;
        }

        // Check if expired
        if ($response->isExpired()) {
            @unlink($path);

            return null;
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, CachedResponse $response, ?int $ttl = null): void
    {
        $this->ensureDirectoryExists();

        // Check cache size and prune if necessary
        if ($this->getCacheSize() > $this->maxSize) {
            $this->prune();
        }

        $path = $this->getPath($key);

        // If TTL is provided, update the cached response with the correct expiration
        if ($ttl !== null) {
            $response = CachedResponse::fromArray(
                array_merge($response->toArray(), [
                    'expires_at' => time() + $ttl,
                ])
            );
        } elseif ($response->getExpiresAt() === null && $this->defaultTtl > 0) {
            $response = CachedResponse::fromArray(
                array_merge($response->toArray(), [
                    'expires_at' => time() + $this->defaultTtl,
                ])
            );
        }

        // PHPStan cannot infer that $response is non-null here after the conditionals above
        // but we know $response is always a valid CachedResponse at this point
        // @phpstan-ignore-next-line
        $serialized = serialize($response->toArray());
        $result = @file_put_contents($path, $serialized, LOCK_EX);

        if ($result === false) {
            throw new RuntimeException("Failed to write cache file: {$path}");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $path = $this->getPath($key);

        if (file_exists($path)) {
            return @unlink($path);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $files = glob($this->directory.DIRECTORY_SEPARATOR.'*'.self::FILE_EXTENSION);

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            @unlink($file);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prune(): int
    {
        $count = 0;
        $files = glob($this->directory.DIRECTORY_SEPARATOR.'*'.self::FILE_EXTENSION);

        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            $contents = @file_get_contents($file);
            if ($contents === false) {
                @unlink($file);
                $count++;

                continue;
            }

            $data = @unserialize($contents);
            if ($data === false) {
                @unlink($file);
                $count++;

                continue;
            }

            $response = CachedResponse::fromArray($data);
            if ($response === null || $response->isExpired()) {
                @unlink($file);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get cache statistics.
     *
     * @return array{directory: string, items: int, size: int, max_size: int, default_ttl: int}
     */
    public function getStats(): array
    {
        $files = glob($this->directory.DIRECTORY_SEPARATOR.'*'.self::FILE_EXTENSION);

        return [
            'directory' => $this->directory,
            'items' => $files !== false ? count($files) : 0,
            'size' => $this->getCacheSize(),
            'max_size' => $this->maxSize,
            'default_ttl' => $this->defaultTtl,
        ];
    }

    /**
     * Get the cache directory.
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * Get the file path for a cache key.
     */
    private function getPath(string $key): string
    {
        // Hash the key to create a safe filename
        $filename = hash('sha256', $key).self::FILE_EXTENSION;

        return $this->directory.DIRECTORY_SEPARATOR.$filename;
    }

    /**
     * Ensure the cache directory exists.
     *
     * @throws RuntimeException If the directory cannot be created
     */
    private function ensureDirectoryExists(): void
    {
        if (! is_dir($this->directory)) {
            if (! @mkdir($this->directory, 0755, true)) {
                throw new RuntimeException("Failed to create cache directory: {$this->directory}");
            }
        }

        if (! is_writable($this->directory)) {
            throw new RuntimeException("Cache directory is not writable: {$this->directory}");
        }
    }

    /**
     * Get the current size of the cache in bytes.
     */
    private function getCacheSize(): int
    {
        $size = 0;
        $files = glob($this->directory.DIRECTORY_SEPARATOR.'*'.self::FILE_EXTENSION);

        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            $fileSize = @filesize($file);
            if ($fileSize !== false) {
                $size += $fileSize;
            }
        }

        return $size;
    }
}
