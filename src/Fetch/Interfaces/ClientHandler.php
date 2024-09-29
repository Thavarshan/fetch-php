<?php

namespace Fetch\Interfaces;

use GuzzleHttp\Client as SyncClient;

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
    public static function handle(string $method, string $uri, array $options = []);

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
     * @param mixed $body
     *
     * @return self
     */
    public function withBody($body): self;

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
    public function withProxy($proxy): self;

    /**
     * Set the cookies for the request.
     *
     * @param bool|\GuzzleHttp\Cookie\CookieJarInterface $cookies
     *
     * @return self
     */
    public function withCookies($cookies): self;

    /**
     * Set whether to follow redirects.
     *
     * @param bool|array $redirects
     *
     * @return self
     */
    public function withRedirects($redirects = true): self;

    /**
     * Set the certificate for the request.
     *
     * @param string|array $cert
     *
     * @return self
     */
    public function withCert($cert): self;

    /**
     * Set the SSL key for the request.
     *
     * @param string|array $sslKey
     *
     * @return self
     */
    public function withSslKey($sslKey): self;

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
    public function get(string $uri);

    /**
     * Finalize and send a POST request.
     *
     * @param string $uri
     * @param mixed  $body
     *
     * @return mixed
     */
    public function post(string $uri, $body = null);

    /**
     * Finalize and send a PUT request.
     *
     * @param string $uri
     * @param mixed  $body
     *
     * @return mixed
     */
    public function put(string $uri, $body = null);

    /**
     * Finalize and send a DELETE request.
     *
     * @param string $uri
     *
     * @return mixed
     */
    public function delete(string $uri);

    /**
     * Finalize and send an OPTIONS request.
     *
     * @param string $uri
     *
     * @return mixed
     */
    public function options(string $uri);

    /**
     * Get the synchronous HTTP client.
     *
     * @return SyncClient
     */
    public function getSyncClient(): SyncClient;

    /**
     * Set the synchronous HTTP client.
     *
     * @param SyncClient $syncClient
     *
     * @return self
     */
    public function setSyncClient(SyncClient $syncClient): self;

    /**
     * Get the default options for the request.
     *
     * @return array
     */
    public static function getDefaultOptions(): array;
}
