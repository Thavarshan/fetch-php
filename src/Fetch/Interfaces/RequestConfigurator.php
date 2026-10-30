<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Enum\ContentType;
use GuzzleHttp\Cookie\CookieJarInterface;

/**
 * Defines fluent configuration for request options held by a handler.
 */
interface RequestConfigurator
{
    /**
     * Configure bearer-token authentication.
     */
    public function withToken(string $token): self;

    /**
     * Configure HTTP Basic authentication credentials.
     */
    public function withAuth(string $username, string $password): self;

    /**
     * Merges the supplied headers into the request configuration.
     *
     * @param  array<string, mixed>  $headers
     */
    public function withHeaders(array $headers): self;

    /**
     * Add or replace one request header.
     */
    public function withHeader(string $header, mixed $value): self;

    /**
     * Sets the request body from a string or structured body options.
     *
     * @param  array<string, mixed>|string  $body
     */
    public function withBody(
        array|string $body,
        string|ContentType $contentType = ContentType::JSON,
    ): self;

    /**
     * Sets a JSON request body and its corresponding content type.
     *
     * @param  array<string, mixed>  $data
     */
    public function withJson(array $data, int $options = 0): self;

    /**
     * Sets form parameters for the outgoing request body.
     *
     * @param  array<string, mixed>  $params
     */
    public function withFormParams(array $params): self;

    /**
     * Sets multipart fields and files for the outgoing request body.
     *
     * @param  array<int, array{
     *     name: string,
     *     contents: mixed,
     *     headers?: array<string, string>
     * }>  $multipart
     */
    public function withMultipart(array $multipart): self;

    /**
     * Merges query parameters into the request URI options.
     *
     * @param  array<string, mixed>  $queryParams
     */
    public function withQueryParameters(array $queryParams): self;

    /**
     * Add or replace one query parameter.
     */
    public function withQueryParameter(string $name, mixed $value): self;

    /**
     * Set the request timeout in seconds.
     */
    public function timeout(int $seconds): self;

    /**
     * Configures the proxy used by the underlying HTTP transport.
     *
     * @param  string|array<string, mixed>  $proxy
     */
    public function withProxy(string|array $proxy): self;

    /**
     * Enable a cookie jar or attach a caller-provided jar.
     */
    public function withCookies(bool|CookieJarInterface $cookies): self;

    /**
     * Configures whether and how the HTTP transport follows redirects.
     *
     * @param  bool|array<string, mixed>  $redirects
     */
    public function withRedirects(bool|array $redirects = true): self;

    /**
     * Configures the client certificate used for TLS requests.
     *
     * @param  string|array<string, mixed>  $cert
     */
    public function withCert(string|array $cert): self;

    /**
     * Configures the private key used with the client certificate.
     *
     * @param  string|array<string, mixed>  $sslKey
     */
    public function withSslKey(string|array $sslKey): self;

    /**
     * Configure whether Guzzle streams the response body.
     */
    public function withStream(bool $stream): self;

    /**
     * Merges options into the existing request configuration.
     *
     * @param  array<string, mixed>  $options
     */
    public function withOptions(array $options): ClientHandler;

    /**
     * Set one Guzzle request option without replacing other options.
     */
    public function withOption(string $key, mixed $value): self;

    /**
     * Set the base URI used to resolve relative request paths.
     */
    public function baseUri(string $baseUri): self;

    /**
     * Replaces request options with a cloned options array.
     *
     * @param  array<string, mixed>  $options
     */
    public function withClonedOptions(array $options): self;

    /**
     * Restore this handler's request options to configured defaults.
     */
    public function reset(): self;

    /**
     * Returns the options stored in this configuration object.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array;

    /**
     * Returns the request headers stored in this configuration object.
     *
     * @return array<string, mixed>
     */
    public function getHeaders(): array;

    /**
     * Check whether a header exists in the current handler options.
     */
    public function hasHeader(string $header): bool;

    /**
     * Check whether an option exists in the current handler options.
     */
    public function hasOption(string $option): bool;
}
