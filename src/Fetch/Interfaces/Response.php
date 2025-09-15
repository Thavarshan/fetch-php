<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use ArrayAccess;
use Fetch\Enum\ContentType;
use Fetch\Enum\Status;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use SimpleXMLElement;

/**
 * @extends ArrayAccess<string, mixed>
 */
interface Response extends ArrayAccess, PsrResponseInterface
{
    /**
     * Create a new response from a base response.
     */
    public static function createFromBase(PsrResponseInterface $response): self;

    /**
     * Create a response with JSON content.
     *
     * @param  array<string, array<int, string>|string>  $headers
     */
    public static function withJson(
        mixed $data,
        int|Status $status = Status::OK,
        array $headers = [],
        int $options = 0
    ): self;

    /**
     * Create a redirect response.
     *
     * @param  array<string, array<int, string>|string>  $headers
     */
    public static function withRedirect(
        string $location,
        int|Status $status = Status::FOUND,
        array $headers = []
    ): self;

    /**
     * Create a response with no content.
     */
    /**
     * @param  array<string, array<int, string>|string>  $headers
     */
    public static function noContent(array $headers = []): self;

    /**
     * Create a response for a created resource.
     *
     * @param  array<string, array<int, string>|string>  $headers
     */
    public static function created(
        string $location,
        mixed $data = null,
        array $headers = []
    ): self;

    /**
     * Get the body as a JSON-decoded array or object.
     */
    public function json(bool $assoc = true, bool $throwOnError = true, int $depth = 512, int $options = 0): mixed;

    /**
     * Check if the response status code is a redirect (3xx).
     */
    public function redirect(): bool;

    /**
     * Get the body as a JSON-decoded object.
     */
    public function object(bool $throwOnError = true): object;

    /**
     * Get the body as a JSON-decoded array.
     */
    /**
     * @return array<mixed>
     */
    public function array(bool $throwOnError = true): array;

    /**
     * Get the body as plain text.
     */
    public function text(): string;

    /**
     * Get the raw body content.
     */
    public function body(): string;

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
     * Get the status text for the response.
     */
    public function statusText(): string;

    /**
     * Get the status code of the response.
     */
    public function status(): int;

    /**
     * Get the status as an enum.
     */
    public function statusEnum(): ?Status;

    /**
     * Check if the response status code is informational (1xx).
     */
    public function isInformational(): bool;

    /**
     * Check if the response status code is OK (2xx).
     */
    public function ok(): bool;

    /**
     * Check if the response status code is a success (2xx).
     */
    public function successful(): bool;

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

    /**
     * Determine if the response is a client or server error.
     */
    public function failed(): bool;

    /**
     * Determine if the response indicates a client error occurred.
     */
    public function clientError(): bool;

    /**
     * Determine if the response indicates a server error occurred.
     */
    public function serverError(): bool;

    /**
     * Get the Content-Type header from the response.
     */
    public function contentType(): ?string;

    /**
     * Get the Content-Type as an enum.
     */
    public function contentTypeEnum(): ?ContentType;

    /**
     * Check if the response has JSON content.
     */
    public function hasJsonContent(): bool;

    /**
     * Check if the response has HTML content.
     */
    public function hasHtmlContent(): bool;

    /**
     * Check if the response has text content.
     */
    public function hasTextContent(): bool;

    /**
     * Get the headers from the response as an array.
     */
    /**
     * @return array<string, array<int, string>>
     */
    public function headers(): array;

    /**
     * Get a specific header from the response.
     */
    public function header(string $header): ?string;

    /**
     * Parse the body as XML.
     */
    public function xml(int $options = 0, bool $throwOnError = true): ?SimpleXMLElement;

    /**
     * Check if the response has the given status code.
     */
    public function isStatus(int|Status $status): bool;

    /**
     * Check if the response has a 200 status code.
     */
    public function isOk(): bool;

    /**
     * Check if the response has a 201 status code.
     */
    public function isCreated(): bool;

    /**
     * Check if the response has a 202 status code.
     */
    public function isAccepted(): bool;

    /**
     * Check if the response has a 204 status code.
     */
    public function isNoContent(): bool;

    /**
     * Check if the response has a 301 status code.
     */
    public function isMovedPermanently(): bool;

    /**
     * Check if the response has a 302 status code.
     */
    public function isFound(): bool;

    /**
     * Check if the response has a 400 status code.
     */
    public function isBadRequest(): bool;

    /**
     * Check if the response has a 401 status code.
     */
    public function isUnauthorized(): bool;

    /**
     * Check if the response has a 403 status code.
     */
    public function isForbidden(): bool;

    /**
     * Check if the response has a 404 status code.
     */
    public function isNotFound(): bool;

    /**
     * Check if the response has a 409 status code.
     */
    public function isConflict(): bool;

    /**
     * Check if the response has a 422 status code.
     */
    public function isUnprocessableEntity(): bool;

    /**
     * Check if the response has a 429 status code.
     */
    public function isTooManyRequests(): bool;

    /**
     * Check if the response has a 500 status code.
     */
    public function isInternalServerError(): bool;

    /**
     * Check if the response has a 503 status code.
     */
    public function isServiceUnavailable(): bool;

    /**
     * Get the value for a given key from the JSON response.
     */
    public function get(string $key, mixed $default = null): mixed;
}
