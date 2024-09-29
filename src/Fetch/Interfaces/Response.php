<?php

namespace Fetch\Interfaces;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface Response extends PsrResponseInterface
{
    /**
     * Get the body as a JSON-decoded array or object.
     *
     * @param bool $assoc        Whether to return associative array (true) or object (false)
     * @param bool $throwOnError Whether to throw an exception on JSON decode error
     *
     * @return mixed
     */
    public function json(bool $assoc = true, bool $throwOnError = true);

    /**
     * Get the body as plain text.
     *
     * @return string
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
     *
     * @return string
     */
    public function arrayBuffer(): string;

    /**
     * Get the status text for the response (e.g., "OK").
     *
     * @return string
     */
    public function statusText(): string;

    /**
     * Create a new response from a base response.
     *
     * Note: The response body will be fully read into memory.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return self
     */
    public static function createFromBase(PsrResponseInterface $response): self;

    /**
     * Check if the response status code is informational (1xx).
     *
     * @return bool
     */
    public function isInformational(): bool;

    /**
     * Check if the response status code is OK (2xx).
     *
     * @return bool
     */
    public function ok(): bool;

    /**
     * Check if the response status code is a redirection (3xx).
     *
     * @return bool
     */
    public function isRedirection(): bool;

    /**
     * Check if the response status code is a client error (4xx).
     *
     * @return bool
     */
    public function isClientError(): bool;

    /**
     * Check if the response status code is a server error (5xx).
     *
     * @return bool
     */
    public function isServerError(): bool;
}
