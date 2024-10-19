<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use GuzzleHttp\Cookie\CookieJarInterface;
use Psr\Http\Client\ClientInterface;

interface ClientHandler
{
    /**
     * Handle the request with the given method, URI, and options.
     */
    public static function handle(string $method, string $uri, array $options = []): mixed;

    /**
     * Set the headers for the request.
     */
    public function withHeaders(array $headers): self;

    /**
     * Set the body for the request.
     */
    public function withBody(array $body): self;

    /**
     * Set the query parameters for the request.
     */
    public function withQueryParameters(array $queryParams): self;

    /**
     * Set the timeout for the request.
     */
    public function timeout(int $seconds): self;

    /**
     * Set the retry logic for the request.
     */
    public function retry(int $retries, int $delay = 100): self;

    /**
     * Set the request to be asynchronous.
     */
    public function async(): self;

    /**
     * Set the proxy for the request.
     */
    public function withProxy(string|array $proxy): self;

    /**
     * Set the cookies for the request.
     */
    public function withCookies(bool|CookieJarInterface $cookies): self;

    /**
     * Set whether to follow redirects.
     */
    public function withRedirects(bool|array $redirects = true): self;

    /**
     * Set the certificate for the request.
     */
    public function withCert(string|array $cert): self;

    /**
     * Set the SSL key for the request.
     */
    public function withSslKey(string|array $sslKey): self;

    /**
     * Set the stream option for the request.
     */
    public function withStream(bool $stream): self;

    /**
     * Finalize and send a GET request.
     */
    public function get(string $uri): mixed;

    /**
     * Finalize and send a POST request.
     */
    public function post(string $uri, mixed $body = null): mixed;

    /**
     * Finalize and send a PUT request.
     */
    public function put(string $uri, mixed $body = null): mixed;

    /**
     * Finalize and send a DELETE request.
     */
    public function delete(string $uri): mixed;

    /**
     * Finalize and send an OPTIONS request.
     */
    public function options(string $uri): mixed;

    /**
     * Get the synchronous HTTP client.
     */
    public function getSyncClient(): ClientInterface;

    /**
     * Set the synchronous HTTP client.
     */
    public function setSyncClient(ClientInterface $syncClient): self;

    /**
     * Get the default options for the request.
     */
    public static function getDefaultOptions(): array;
}
