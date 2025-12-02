<?php

declare(strict_types=1);

namespace Fetch\Http;

use ArrayAccess;
use Fetch\Enum\ContentType;
use Fetch\Enum\Status;
use Fetch\Interfaces\Response as ResponseInterface;
use Fetch\Support\DebugInfo;
use Fetch\Traits\ResponseImmutabilityTrait;
use GuzzleHttp\Psr7\Response as BaseResponse;
use JsonException;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use RuntimeException;
use SimpleXMLElement;

/**
 * @implements ArrayAccess<string, mixed>
 */
class Response extends BaseResponse implements ArrayAccess, ResponseInterface
{
    use ResponseImmutabilityTrait;

    /**
     * The buffered content of the body.
     */
    protected string $bodyContents;

    /**
     * Debug information for this specific request/response.
     *
     * This is stored per-response to avoid race conditions in concurrent usage.
     */
    protected ?DebugInfo $debugInfo = null;

    /**
     * Create new response instance.
     */
    public function __construct(
        int|Status $status = Status::OK,
        array $headers = [],
        string $body = '',
        string $version = '1.1',
        ?string $reason = null
    ) {
        // Convert Status enum to its value if provided
        $statusCode = $status instanceof Status ? $status->value : $status;

        parent::__construct($statusCode, $headers, $body, $version, $reason);

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
     * Create a response with JSON content.
     */
    public static function withJson(
        mixed $data,
        int|Status $status = Status::OK,
        array $headers = [],
        int $options = 0
    ): self {
        $json = json_encode($data, $options | JSON_THROW_ON_ERROR);

        // Set JSON content type if not already set
        if (! isset($headers['Content-Type'])) {
            $headers['Content-Type'] = ContentType::JSON->value;
        }

        return new self($status, $headers, $json);
    }

    /**
     * Create a response with no content.
     */
    public static function noContent(array $headers = []): self
    {
        return new self(Status::NO_CONTENT, $headers);
    }

    /**
     * Create a response for a created resource.
     */
    public static function created(
        string $location,
        mixed $data = null,
        array $headers = []
    ): self {
        $headers['Location'] = $location;

        if ($data !== null) {
            return static::withJson($data, Status::CREATED, $headers);
        }

        return new self(Status::CREATED, $headers);
    }

    /**
     * Create a redirect response.
     */
    public static function withRedirect(
        string $location,
        int|Status $status = Status::FOUND,
        array $headers = []
    ): self {
        $headers['Location'] = $location;

        return new self($status, $headers);
    }

    /**
     * Get the body as a JSON-decoded array or object.
     *
     * @throws \RuntimeException When JSON cannot be decoded and $throwOnError is true
     */
    public function json(bool $assoc = true, bool $throwOnError = true, int $depth = 512, int $options = 0): mixed
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
     */
    public function object(bool $throwOnError = true): object
    {
        return $this->json(false, $throwOnError);
    }

    /**
     * Get the body as a JSON-decoded array.
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
     * Get the status as an enum.
     */
    public function statusEnum(): ?Status
    {
        return Status::tryFrom($this->getStatusCode());
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
        $header = $this->getHeaderLine('Content-Type') ?: null;
        if ($header === null) {
            return null;
        }

        // Strip parameters like charset
        if (($pos = strpos($header, ';')) !== false) {
            return trim(substr($header, 0, $pos));
        }

        return $header;
    }

    /**
     * Get the Content-Type as an enum.
     */
    public function contentTypeEnum(): ?ContentType
    {
        $contentType = $this->contentType();

        return $contentType ? ContentType::tryFromString($contentType) : null;
    }

    /**
     * Check if the response has JSON content.
     */
    public function hasJsonContent(): bool
    {
        return $this->contentTypeEnum() === ContentType::JSON;
    }

    /**
     * Check if the response has HTML content.
     */
    public function hasHtmlContent(): bool
    {
        return $this->contentTypeEnum() === ContentType::HTML;
    }

    /**
     * Check if the response has text content.
     */
    public function hasTextContent(): bool
    {
        $contentType = $this->contentTypeEnum();

        return $contentType !== null && $contentType->isText();
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
     * @throws \RuntimeException When XML cannot be parsed and $throwOnError is true
     */
    public function xml(int $options = 0, bool $throwOnError = true): ?SimpleXMLElement
    {
        $previous = false;

        try {
            // Use libxml_use_internal_errors to capture errors instead of emitting warnings
            $previous = libxml_use_internal_errors(true);

            $xml = new SimpleXMLElement($this->bodyContents, $options);

            // Restore previous error handling
            libxml_use_internal_errors($previous);

            return $xml;
        } catch (\Throwable $e) {
            // Restore previous error handling
            libxml_use_internal_errors($previous);

            if ($throwOnError) {
                throw new RuntimeException('Failed to parse XML: '.$e->getMessage(), $e->getCode(), $e);
            }

            return null;
        }
    }

    /**
     * Determine if the given offset exists in the JSON response.
     */
    public function offsetExists($offset): bool
    {
        return isset($this->array(false)[$offset]);
    }

    /**
     * Get the value at the given offset from the JSON response.
     */
    public function offsetGet($offset): mixed
    {
        return $this->array(false)[$offset] ?? null;
    }

    /**
     * Set the value at the given offset in the JSON response (unsupported).
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
     * @throws \RuntimeException
     */
    public function offsetUnset($offset): void
    {
        throw new RuntimeException('Response data cannot be modified using array access.');
    }

    /**
     * Check if the response has the given status code.
     */
    public function isStatus(int|Status $status): bool
    {
        $statusCode = $status instanceof Status ? $status->value : $status;

        return $this->getStatusCode() === $statusCode;
    }

    /**
     * Check if the response has a 200 status code.
     */
    public function isOk(): bool
    {
        return $this->isStatus(Status::OK);
    }

    /**
     * Check if the response has a 201 status code.
     */
    public function isCreated(): bool
    {
        return $this->isStatus(Status::CREATED);
    }

    /**
     * Check if the response has a 202 status code.
     */
    public function isAccepted(): bool
    {
        return $this->isStatus(Status::ACCEPTED);
    }

    /**
     * Check if the response has a 204 status code.
     */
    public function isNoContent(): bool
    {
        return $this->isStatus(Status::NO_CONTENT);
    }

    /**
     * Check if the response has a 301 status code.
     */
    public function isMovedPermanently(): bool
    {
        return $this->isStatus(Status::MOVED_PERMANENTLY);
    }

    /**
     * Check if the response has a 302 status code.
     */
    public function isFound(): bool
    {
        return $this->isStatus(Status::FOUND);
    }

    /**
     * Check if the response has a 400 status code.
     */
    public function isBadRequest(): bool
    {
        return $this->isStatus(Status::BAD_REQUEST);
    }

    /**
     * Check if the response has a 401 status code.
     */
    public function isUnauthorized(): bool
    {
        return $this->isStatus(Status::UNAUTHORIZED);
    }

    /**
     * Check if the response has a 403 status code.
     */
    public function isForbidden(): bool
    {
        return $this->isStatus(Status::FORBIDDEN);
    }

    /**
     * Check if the response has a 404 status code.
     */
    public function isNotFound(): bool
    {
        return $this->isStatus(Status::NOT_FOUND);
    }

    /**
     * Check if the response has a 409 status code.
     */
    public function isConflict(): bool
    {
        return $this->isStatus(Status::CONFLICT);
    }

    /**
     * Check if the response has a 422 status code.
     */
    public function isUnprocessableEntity(): bool
    {
        return $this->isStatus(Status::UNPROCESSABLE_ENTITY);
    }

    /**
     * Check if the response has a 429 status code.
     */
    public function isTooManyRequests(): bool
    {
        return $this->isStatus(Status::TOO_MANY_REQUESTS);
    }

    /**
     * Check if the response has a 500 status code.
     */
    public function isInternalServerError(): bool
    {
        return $this->isStatus(Status::INTERNAL_SERVER_ERROR);
    }

    /**
     * Check if the response has a 503 status code.
     */
    public function isServiceUnavailable(): bool
    {
        return $this->isStatus(Status::SERVICE_UNAVAILABLE);
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
     * Attach debug information to this response.
     *
     * This method is called internally to store per-request debug info.
     *
     * @internal
     *
     * @return $this
     */
    public function withDebugInfo(DebugInfo $debugInfo): static
    {
        $this->debugInfo = $debugInfo;

        return $this;
    }

    /**
     * Get the debug information for this specific request/response.
     *
     * Returns null if debug mode was not enabled for this request.
     */
    public function getDebugInfo(): ?DebugInfo
    {
        return $this->debugInfo;
    }

    /**
     * Check if this response has debug information attached.
     */
    public function hasDebugInfo(): bool
    {
        return $this->debugInfo !== null;
    }

    /**
     * Get the body of the response.
     */
    public function __toString(): string
    {
        return $this->bodyContents;
    }
}
