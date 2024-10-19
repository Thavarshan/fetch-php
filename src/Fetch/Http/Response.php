<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Interfaces\Response as ResponseInterface;
use GuzzleHttp\Psr7\Response as BaseResponse;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use RuntimeException;

class Response extends BaseResponse implements ResponseInterface
{
    /**
     * The buffered content of the body.
     */
    protected string $bodyContents;

    /**
     * Create new response instance.
     */
    public function __construct(
        int $status = 200,
        array $headers = [],
        string $body = '',
        string $version = '1.1',
        ?string $reason = null
    ) {
        parent::__construct($status, $headers, $body, $version, $reason);

        // Buffer the body contents to handle it appropriately.
        $this->bodyContents = (string) $body;
    }

    /**
     * Get the body as a JSON-decoded array or object.
     *
     * @param  bool  $assoc  Whether to return associative array (true) or object (false)
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
     */
    public function arrayBuffer(): string
    {
        return $this->bodyContents;
    }

    /**
     * Get the status text for the response (e.g., "OK").
     */
    public function statusText(): string
    {
        return $this->getReasonPhrase() ?: 'No reason phrase available';
    }

    /**
     * Create a new response from a base response.
     *
     * Note: The response body will be fully read into memory.
     */
    public static function createFromBase(PsrResponseInterface $response): self
    {
        return new self(
            $response->getStatusCode(),
            $response->getHeaders(),
            (string) $response->getBody(),
            $response->getProtocolVersion(),
            $response->getReasonPhrase()
        );
    }

    /**
     * Check if the response status code is informational (1xx).
     */
    public function isInformational(): bool
    {
        return $this->getStatusCode() >= 100 && $this->getStatusCode() < 200;
    }

    /**
     * Check if the response status code is OK (2xx).
     */
    public function ok(): bool
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**
     * Check if the response status code is a redirection (3xx).
     */
    public function isRedirection(): bool
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
     * Check if the response status code is a client error (4xx).
     */
    public function isClientError(): bool
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * Check if the response status code is a server error (5xx).
     */
    public function isServerError(): bool
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }
}
