<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface Response extends PsrResponseInterface
{
    /**
     * Get the body as a JSON-decoded array or object.
     *
     * @param  bool  $assoc  Whether to return associative array (true) or object (false)
     * @param  bool  $throwOnError  Whether to throw an exception on JSON decode error
     * @return mixed
     */
    public function json(bool $assoc = true, bool $throwOnError = true);

    /**
     * Get the body as plain text.
     */
    public function text(): string;

    /**
     * Get the body as a stream (simulating a "blob" in JavaScript).
     *
     * @return resource|false
     */
    public function blob();

    /**
     * Get the body as an array buffer (binary data).
     */
    public function arrayBuffer(): string;

    /**
     * Get the status text for the response (e.g., "OK").
     */
    public function statusText(): string;

    /**
     * Create a new response from a base response.
     *
     * Note: The response body will be fully read into memory.
     */
    public static function createFromBase(PsrResponseInterface $response): self;

    /**
     * Check if the response status code is informational (1xx).
     */
    public function isInformational(): bool;

    /**
     * Check if the response status code is OK (2xx).
     */
    public function ok(): bool;

    /**
     * Check if the response status code is a redirection (3xx).
     */
    public function isRedirection(): bool;

    /**
     * Check if the response status code is a client error (4xx).
     */
    public function isClientError(): bool;

    /**
     * Check if the response status code is a server error (5xx).
     */
    public function isServerError(): bool;
}
