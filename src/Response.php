<?php

namespace Fetch;

use GuzzleHttp\Psr7\Response as BaseResponse;
use RuntimeException;

class Response extends BaseResponse
{
    /**
     * The buffered content of the body.
     *
     * @var string
     */
    protected string $bodyContents;

    /**
     * Create new response instance.
     *
     * @param int    $status
     * @param array  $headers
     * @param string $body
     * @param string $version
     * @param string $reason
     */
    public function __construct(
        int $status = 200,
        array $headers = [],
        string $body = '',
        string $version = '1.1',
        string $reason = null
    ) {
        parent::__construct($status, $headers, $body, $version, $reason);
        $this->bodyContents = (string) $this->getBody();
    }

    /**
     * Get the body as a JSON-decoded array or object.
     *
     * @param bool $assoc Whether to return associative array (true) or object (false)
     *
     * @return mixed
     */
    public function json(bool $assoc = true, bool $throwOnError = true)
    {
        $decoded = json_decode($this->bodyContents, $assoc);
        $jsonError = json_last_error();

        if ($jsonError === \JSON_ERROR_NONE) {
            return $decoded;
        }

        if ($throwOnError) {
            throw new RuntimeException('Failed to decode JSON: ' . json_last_error_msg());
        }

        return null; // or return an empty array/object depending on your needs.
    }

    /**
     * Get the body as plain text.
     *
     * @return string
     */
    public function text(): string
    {
        return $this->bodyContents;
    }

    /**
     * Get the body as a stream (simulating a "blob" in JavaScript).
     *
     * @return resource|false
     */
    public function blob()
    {
        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            return false;
        }
        fwrite($stream, $this->bodyContents);
        rewind($stream);

        return $stream;
    }

    /**
     * Get the body as an array buffer (binary data).
     *
     * @return string
     */
    public function arrayBuffer(): string
    {
        return $this->bodyContents;
    }

    /**
     * Get the status text for the response (e.g., "OK").
     *
     * @return string
     */
    public function statusText(): string
    {
        return $this->getReasonPhrase() ?: 'No reason phrase available';
    }

    /**
     * Check if the response was successful (status code 200-299).
     *
     * @return bool
     */
    public function ok(): bool
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**
     * Check if the response status is in the informational range (100-199).
     *
     * @return bool
     */
    public function isInformational(): bool
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }

    /**
     * Check if the response status is in the redirection range (300-399).
     *
     * @return bool
     */
    public function isRedirection(): bool
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
     * Check if the response status is in the client error range (400-499).
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * Check if the response status is in the server error range (500-599).
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }
}
