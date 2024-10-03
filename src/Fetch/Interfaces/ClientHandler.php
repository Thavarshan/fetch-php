<?php

namespace Fetch\Interfaces;

use GuzzleHttp\Cookie\CookieJarInterface;
use Psr\Http\Client\ClientInterface;

interface ClientHandler
{
    /**
     * Handle the request with the given method, URI, and options.
     *
     * @param string $method
     * @param string $uri
     * @param array  $options
     *
     * @return mixed
     */
    public static function handle(string $method, string $uri, array $options = []): mixed;

    /**
     * Set the headers for the request.
     *
     * @param array $headers
     *
     * @return self
     */
    public function withHeaders(array $headers): self;

    /**
     * Set the body for the request.
     *
     * @param array $body
     *
     * @return self
     */
    public function withBody(array $body): self;

    /**
     * Set the query parameters for the request.
     *
     * @param array $queryParams
     *
     * @return self
     */
    public function withQueryParameters(array $queryParams): self;

    /**
     * Set the timeout for the request.
     *
     * @param int $seconds
     *
     * @return self
     */
    public function timeout(int $seconds): self;

    /**
     * Set the retry logic for the request.
     *
     * @param int $retries
     * @param int $delay
     *
     * @return self
     */
    public function retry(int $retries, int $delay = 100): self;

    /**
     * Set the request to be asynchronous.
     *
     * @return self
     */
    public function async(): self;

    /**
     * Set the proxy for the request.
     *
     * @param string|array $proxy
     *
     * @return self
     */
    public function withProxy(string|array $proxy): self;

    /**
     * Set the cookies for the request.
     *
     * @param bool|\GuzzleHttp\Cookie\CookieJarInterface $cookies
     *
     * @return self
     */
    public function withCookies(bool|CookieJarInterface $cookies): self;

    /**
     * Set whether to follow redirects.
     *
     * @param bool|array $redirects
     *
     * @return self
     */
    public function withRedirects(bool|array $redirects = true): self;

    /**
     * Set the certificate for the request.
     *
     * @param string|array $cert
     *
     * @return self
     */
    public function withCert(string|array $cert): self;

    /**
     * Set the SSL key for the request.
     *
     * @param string|array $sslKey
     *
     * @return self
     */
    public function withSslKey(string|array $sslKey): self;

    /**
     * Set the stream option for the request.
     *
     * @param bool $stream
     *
     * @return self
     */
    public function withStream(bool $stream): self;

    /**
     * Finalize and send a GET request.
     *
     * @param string $uri
     *
     * @return mixed
     */
    public function get(string $uri): mixed;

    /**
     * Finalize and send a POST request.
     *
     * @param string $uri
     * @param mixed  $body
     *
     * @return mixed
     */
    public function post(string $uri, mixed $body = null): mixed;

    /**
     * Finalize and send a PUT request.
     *
     * @param string $uri
     * @param mixed  $body
     *
     * @return mixed
     */
    public function put(string $uri, mixed $body = null): mixed;

    /**
     * Finalize and send a DELETE request.
     *
     * @param string $uri
     *
     * @return mixed
     */
    public function delete(string $uri): mixed;

    /**
     * Finalize and send an OPTIONS request.
     *
     * @param string $uri
     *
     * @return mixed
     */
    public function options(string $uri): mixed;

    /**
     * Get the synchronous HTTP client.
     *
     * @return \Psr\Http\Client\ClientInterface
     */
    public function getSyncClient(): ClientInterface;

    /**
     * Set the synchronous HTTP client.
     *
     * @param \Psr\Http\Client\ClientInterface $syncClient
     *
     * @return self
     */
    public function setSyncClient(ClientInterface $syncClient): self;

    /**
     * Get the default options for the request.
     *
     * @return array
     */
    public static function getDefaultOptions(): array;
}
