<?php

namespace Fetch;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use RuntimeException;

class Response
{
    /**
     * The original PSR-7 response instance.
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected PsrResponseInterface $response;

    /**
     * The body content of the response.
     *
     * @var string
     */
    protected string $body;

    /**
     * The response headers.
     *
     * @var array
     */
    protected array $headers = [];

    /**
     * The response status code.
     *
     * @var int
     */
    protected int $statusCode;

    /**
     * Create a new response instance.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return void
     */
    public function __construct(PsrResponseInterface $response)
    {
        $this->response = $response;
        $this->body = (string) $response->getBody();
        $this->headers = $response->getHeaders();
        $this->statusCode = $response->getStatusCode();
    }

    /**
     * Get or set the response content.
     *
     * @param string|null $content
     *
     * @return string|self
     */
    public function content(string $content = null): string|self
    {
        if ($content === null) {
            return $this->body;
        }

        $this->body = $content;

        return $this;
    }

    /**
     * Get the response status code.
     *
     * @return int
     */
    public function status(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the response status code (backwards compatibility).
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->status();
    }

    /**
     * Add a header to the response.
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function header(string $key, string $value): self
    {
        $this->headers[$key] = [$value];

        return $this;
    }

    /**
     * Set multiple headers on the response.
     *
     * @param array $headers
     *
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $key => $value) {
            $this->header($key, $value);
        }

        return $this;
    }

    /**
     * Get the response headers.
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get the body content as JSON.
     *
     * @param bool $assoc
     *
     * @return mixed
     */
    public function json(bool $assoc = true): mixed
    {
        if (trim($this->body) === '') {
            return $assoc ? [] : null;
        }

        $decoded = json_decode($this->body, $assoc);

        if (json_last_error() !== \JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to decode JSON: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Get the body content as plain text.
     *
     * @return string
     */
    public function text(): string
    {
        return $this->body;
    }

    /**
     * Get the body content as a blob (stream).
     *
     * @return resource
     */
    public function blob()
    {
        $stream = fopen('php://memory', 'r+');

        if ($stream === false) {
            throw new RuntimeException('Failed to create stream for blob');
        }

        fwrite($stream, $this->body);
        rewind($stream);

        return $stream;
    }

    /**
     * Get the body content as an array buffer (binary data).
     *
     * @return string
     */
    public function arrayBuffer(): string
    {
        return $this->body;
    }

    /**
     * Get the status text for the response (e.g., "OK").
     *
     * @return string
     */
    public function statusText(): string
    {
        return $this->response->getReasonPhrase() ?: 'Unknown Status';
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
