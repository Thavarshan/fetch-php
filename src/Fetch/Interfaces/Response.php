<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use ArrayAccess;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use SimpleXMLElement;

interface Response extends ArrayAccess, PsrResponseInterface
{
    /**
     * Create a new response from a base response.
     */
    public static function createFromBase(PsrResponseInterface $response): self;

    /**
     * Get the body as a JSON-decoded array or object.
     *
     * @param  bool  $assoc  Whether to return associative array (true) or object (false)
     * @param  bool  $throwOnError  Whether to throw an exception on JSON decode errors
     * @param  int  $depth  Maximum nesting depth
     * @param  int  $options  JSON decode options
     * @return mixed
     */
    public function json(bool $assoc = true, bool $throwOnError = true, int $depth = 512, int $options = 0);

    /**
     * Get the body as a JSON-decoded object.
     *
     * @param  bool  $throwOnError  Whether to throw an exception on JSON decode errors
     * @return object
     */
    public function object(bool $throwOnError = true);

    /**
     * Get the body as a JSON-decoded array.
     *
     * @param  bool  $throwOnError  Whether to throw an exception on JSON decode errors
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
     * Check if the response status code is a redirect (3xx).
     */
    public function redirect(): bool;

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
     * Get the headers from the response as an array.
     */
    public function headers(): array;

    /**
     * Get a specific header from the response.
     */
    public function header(string $header): ?string;

    /**
     * Parse the body as XML.
     *
     * @param  int  $options  SimpleXML options
     * @param  bool  $throwOnError  Whether to throw an exception on XML parse errors
     */
    public function xml(int $options = 0, bool $throwOnError = true): ?SimpleXMLElement;

    /**
     * Check if the response has the given status code.
     */
    public function isStatus(int $status): bool;

    /**
     * Get the value for a given key from the JSON response.
     */
    public function get(string $key, mixed $default = null): mixed;
}
