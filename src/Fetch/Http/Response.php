<?php

declare(strict_types=1);

namespace Fetch\Http;

use ArrayAccess;
use Fetch\Interfaces\Response as ResponseInterface;
use GuzzleHttp\Psr7\Response as BaseResponse;
use JsonException;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use RuntimeException;
use SimpleXMLElement;

class Response extends BaseResponse implements ArrayAccess, ResponseInterface
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
     * Get the body as a JSON-decoded array or object.
     *
     * @param  bool  $assoc  Whether to return associative array (true) or object (false)
     * @param  bool  $throwOnError  Whether to throw an exception on JSON decode errors
     * @param  int  $depth  Maximum nesting depth
     * @param  int  $options  JSON decode options
     * @return mixed
     *
     * @throws \JsonException When JSON cannot be decoded and $throwOnError is true
     */
    public function json(bool $assoc = true, bool $throwOnError = true, int $depth = 512, int $options = 0)
    {
        try {
            return json_decode(
                $this->bodyContents,
                $assoc,
                $depth,
                $options | JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            if ($throwOnError) {
                throw new RuntimeException('Failed to decode JSON: '.$e->getMessage(), $e->getCode(), $e);
            }

            return $assoc ? [] : (object) [];
        }
    }

    /**
     * Get the body as a JSON-decoded object.
     *
     * @param  bool  $throwOnError  Whether to throw an exception on JSON decode errors
     * @return object
     */
    public function object(bool $throwOnError = true)
    {
        return $this->json(false, $throwOnError);
    }

    /**
     * Get the body as a JSON-decoded array.
     *
     * @param  bool  $throwOnError  Whether to throw an exception on JSON decode errors
     */
    public function array(bool $throwOnError = true): array
    {
        return $this->json(true, $throwOnError) ?: [];
    }

    /**
     * Get the body as plain text.
     */
    public function text(): string
    {
        return $this->bodyContents;
    }

    /**
     * Get the raw body content.
     */
    public function body(): string
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
     * Get the status code of the response.
     */
    public function status(): int
    {
        return $this->getStatusCode();
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
     * Check if the response status code is a success (2xx).
     */
    public function successful(): bool
    {
        return $this->ok();
    }

    /**
     * Check if the response status code is a redirection (3xx).
     */
    public function isRedirection(): bool
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
     * Check if the response status code is a redirect (3xx).
     */
    public function redirect(): bool
    {
        return $this->isRedirection();
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

    /**
     * Determine if the response is a client or server error.
     */
    public function failed(): bool
    {
        return $this->isClientError() || $this->isServerError();
    }

    /**
     * Determine if the response indicates a client or server error occurred.
     */
    public function clientError(): bool
    {
        return $this->isClientError();
    }

    /**
     * Determine if the response indicates a server error occurred.
     */
    public function serverError(): bool
    {
        return $this->isServerError();
    }

    /**
     * Get the Content-Type header from the response.
     */
    public function contentType(): ?string
    {
        return $this->getHeaderLine('Content-Type');
    }

    /**
     * Get the headers from the response as an array.
     */
    public function headers(): array
    {
        return $this->getHeaders();
    }

    /**
     * Get a specific header from the response.
     */
    public function header(string $header): ?string
    {
        $header = $this->getHeaderLine($header);

        return $header === '' ? null : $header;
    }

    /**
     * Determine if the response contains a specific header.
     */
    public function hasHeader($header): bool
    {
        return parent::hasHeader($header);
    }

    /**
     * Parse the body as XML.
     *
     * @param  int  $options  SimpleXML options
     * @param  bool  $throwOnError  Whether to throw an exception on XML parse errors
     */
    public function xml(int $options = 0, bool $throwOnError = true): ?SimpleXMLElement
    {
        try {
            // Use libxml_use_internal_errors to capture errors instead of emitting warnings
            $previous = libxml_use_internal_errors(true);

            $xml = new SimpleXMLElement($this->bodyContents, $options);

            // Restore previous error handling
            libxml_use_internal_errors($previous);

            return $xml;
        } catch (\Throwable $e) {
            // Restore previous error handling
            libxml_use_internal_errors(false);

            if ($throwOnError) {
                throw new RuntimeException('Failed to parse XML: '.$e->getMessage(), $e->getCode(), $e);
            }

            return null;
        }
    }

    /**
     * Determine if the given offset exists in the JSON response.
     *
     * @param  string|int  $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->array()[$offset]);
    }

    /**
     * Get the value at the given offset from the JSON response.
     *
     * @param  string|int  $offset
     */
    public function offsetGet($offset): mixed
    {
        return $this->array()[$offset];
    }

    /**
     * Set the value at the given offset in the JSON response (unsupported).
     *
     * @param  string|int  $offset
     * @param  mixed  $value
     *
     * @throws \RuntimeException
     */
    public function offsetSet($offset, $value): void
    {
        throw new RuntimeException('Response data cannot be modified using array access.');
    }

    /**
     * Unset the value at the given offset from the JSON response (unsupported).
     *
     * @param  string|int  $offset
     *
     * @throws \RuntimeException
     */
    public function offsetUnset($offset): void
    {
        throw new RuntimeException('Response data cannot be modified using array access.');
    }

    /**
     * Check if the response has the given status code.
     */
    public function isStatus(int $status): bool
    {
        return $this->getStatusCode() === $status;
    }

    /**
     * Check if the response has a 200 status code.
     */
    public function isOk(): bool
    {
        return $this->isStatus(200);
    }

    /**
     * Check if the response has a 201 status code.
     */
    public function isCreated(): bool
    {
        return $this->isStatus(201);
    }

    /**
     * Check if the response has a 202 status code.
     */
    public function isAccepted(): bool
    {
        return $this->isStatus(202);
    }

    /**
     * Check if the response has a 204 status code.
     */
    public function isNoContent(): bool
    {
        return $this->isStatus(204);
    }

    /**
     * Check if the response has a 301 status code.
     */
    public function isMovedPermanently(): bool
    {
        return $this->isStatus(301);
    }

    /**
     * Check if the response has a 302 status code.
     */
    public function isFound(): bool
    {
        return $this->isStatus(302);
    }

    /**
     * Check if the response has a 400 status code.
     */
    public function isBadRequest(): bool
    {
        return $this->isStatus(400);
    }

    /**
     * Check if the response has a 401 status code.
     */
    public function isUnauthorized(): bool
    {
        return $this->isStatus(401);
    }

    /**
     * Check if the response has a 403 status code.
     */
    public function isForbidden(): bool
    {
        return $this->isStatus(403);
    }

    /**
     * Check if the response has a 404 status code.
     */
    public function isNotFound(): bool
    {
        return $this->isStatus(404);
    }

    /**
     * Check if the response has a 409 status code.
     */
    public function isConflict(): bool
    {
        return $this->isStatus(409);
    }

    /**
     * Check if the response has a 422 status code.
     */
    public function isUnprocessableEntity(): bool
    {
        return $this->isStatus(422);
    }

    /**
     * Check if the response has a 429 status code.
     */
    public function isTooManyRequests(): bool
    {
        return $this->isStatus(429);
    }

    /**
     * Check if the response has a 500 status code.
     */
    public function isInternalServerError(): bool
    {
        return $this->isStatus(500);
    }

    /**
     * Check if the response has a 503 status code.
     */
    public function isServiceUnavailable(): bool
    {
        return $this->isStatus(503);
    }

    /**
     * Get the value for a given key from the JSON response.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $array = $this->array(false);

        if (isset($array[$key])) {
            return $array[$key];
        }

        return $default;
    }

    /**
     * Get the body of the response.
     */
    public function __toString(): string
    {
        return $this->bodyContents;
    }
}
