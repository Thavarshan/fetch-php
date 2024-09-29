<?php

namespace Fetch\Http;

use Fetch\Interfaces\ClientHandler as ClientHandlerInterface;
use Fetch\Interfaces\Response as ResponseInterface;
use GuzzleHttp\Client as SyncClient;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\RequestException;
use Matrix\AsyncHelper;
use Matrix\Interfaces\AsyncHelper as AsyncHelperInterface;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

class ClientHandler implements ClientHandlerInterface
{
    /**
     * Default options for the request.
     *
     * @var array
     */
    protected static array $defaultOptions = [
        'method' => 'GET',
        'headers' => [],
        'timeout' => 30, // Default timeout
    ];

    /**
     * The synchronous HTTP client.
     *
     * @var \Psr\Http\Client\ClientInterface|null
     */
    protected ?ClientInterface $syncClient = null;

    /**
     * The options for the request.
     *
     * @var array
     */
    protected array $options = [];

    /**
     * Timeout for the request.
     *
     * @var int|null
     */
    protected ?int $timeout = null;

    /**
     * Number of retries for the request.
     *
     * @var int|null
     */
    protected ?int $retries = null;

    /**
     * Delay between retries.
     *
     * @var int|null
     */
    protected ?int $retryDelay = null;

    /**
     * Whether the request is asynchronous.
     *
     * @var bool
     */
    protected bool $isAsync = false;

    /**
     * Apply options and execute the request.
     *
     * @param string $method
     * @param string $uri
     * @param array  $options
     *
     * @return mixed
     */
    public static function handle(string $method, string $uri, array $options = []): mixed
    {
        $handler = new static();
        $handler->applyOptions($options);

        return $handler->finalizeRequest($method, $uri);
    }

    /**
     * Apply the options to the handler.
     *
     * @param array $options
     *
     * @return void
     */
    protected function applyOptions(array $options): void
    {
        if (isset($options['client'])) {
            $this->setSyncClient($options['client']);
        }

        $this->options = array_merge($this->options, $options);

        if (isset($options['timeout'])) {
            $this->timeout($options['timeout']);
        }

        if (isset($options['retries'])) {
            $this->retry($options['retries'], $options['retry_delay'] ?? 100);
        }

        if (! empty($options['async'])) {
            $this->isAsync = true;
        }
    }

    /**
     * Finalize the request and send it.
     *
     * @param string $method
     * @param string $uri
     *
     * @return mixed
     */
    protected function finalizeRequest(string $method, string $uri): mixed
    {
        $this->options['method'] = $method;
        $this->options['uri'] = $uri;

        // Merge timeout and retry properties into the options array
        $this->mergePropertiesIntoOptions();

        return $this->isAsync ? $this->sendAsync() : $this->sendSync();
    }

    /**
     * Merge class properties (timeout, retries, etc.) into the final options array.
     *
     * @return void
     */
    protected function mergePropertiesIntoOptions(): void
    {
        if ($this->timeout !== null) {
            $this->options['timeout'] = $this->timeout;
        }
    }

    /**
     * Send a synchronous HTTP request.
     *
     * @return \Fetch\Interfaces\Response
     */
    protected function sendSync(): ResponseInterface
    {
        return $this->retryRequest(function (): ResponseInterface {
            $psrResponse = $this->getSyncClient()->request(
                $this->options['method'],
                $this->options['uri'],
                $this->options
            );

            return Response::createFromBase($psrResponse);
        });
    }

    /**
     * Send an asynchronous HTTP request.
     *
     * @return \Matrix\Interfaces\AsyncHelper
     */
    protected function sendAsync(): AsyncHelperInterface
    {
        return new AsyncHelper(function (): ResponseInterface {
            return $this->sendSync();
        });
    }

    /**
     * Implement retry logic for the request.
     *
     * @param callable $request
     *
     * @return \Fetch\Interfaces\Response
     */
    protected function retryRequest(callable $request): ResponseInterface
    {
        $attempts = $this->retries ?? 1;
        $delay = $this->retryDelay ?? 100; // Default retry delay is 100ms

        for ($i = 0; $i < $attempts; $i++) {
            try {
                return $request();
            } catch (RequestException $e) {
                if ($i === $attempts - 1) {
                    throw $e; // Rethrow if all retries failed
                }
                usleep($delay * 1000); // Convert milliseconds to microseconds
            }
        }

        throw new RuntimeException('Request failed after all retries.');
    }

    /**
     * Set the base URI for the request.
     *
     * @param string $baseUri
     *
     * @return self
     */
    public function baseUri(string $baseUri): self
    {
        $this->options['base_uri'] = $baseUri;

        return $this;
    }

    /**
     * Set the token for the request.
     *
     * @param string $token
     *
     * @return self
     */
    public function withToken(string $token): self
    {
        $this->options['headers']['Authorization'] = 'Bearer ' . $token;

        return $this;
    }

    /**
     * Set the basic auth for the request.
     *
     * @param string $username
     * @param string $password
     *
     * @return self
     */
    public function withAuth(string $username, string $password): self
    {
        $this->options['auth'] = [$username, $password];

        return $this;
    }

    /**
     * Set the headers for the request.
     *
     * @param array $headers
     *
     * @return self
     */
    public function withHeaders(array $headers): self
    {
        $this->options['headers'] = array_merge(
            $this->options['headers'] ?? [],
            $headers
        );

        return $this;
    }

    /**
     * Set the body for the request.
     *
     * @param mixed $body
     *
     * @return self
     */
    public function withBody(mixed $body): self
    {
        $this->options['body'] = $body;

        return $this;
    }

    /**
     * Set the query parameters for the request.
     *
     * @param array $queryParams
     *
     * @return self
     */
    public function withQueryParameters(array $queryParams): self
    {
        $this->options['query'] = $queryParams;

        return $this;
    }

    /**
     * Set the timeout for the request.
     *
     * @param int $seconds
     *
     * @return self
     */
    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Set the retry logic for the request.
     *
     * @param int $retries
     * @param int $delay
     *
     * @return self
     */
    public function retry(int $retries, int $delay = 100): self
    {
        $this->retries = $retries;
        $this->retryDelay = $delay;

        return $this;
    }

    /**
     * Set the request to be asynchronous.
     *
     * @return self
     */
    public function async(): self
    {
        $this->isAsync = true;

        return $this;
    }

    /**
     * Set the proxy for the request.
     *
     * @param string|array $proxy
     *
     * @return self
     */
    public function withProxy(string|array $proxy): self
    {
        $this->options['proxy'] = $proxy;

        return $this;
    }

    /**
     * Set the cookies for the request.
     *
     * @param bool|\GuzzleHttp\Cookie\CookieJarInterface $cookies
     *
     * @return self
     */
    public function withCookies(bool|CookieJarInterface $cookies): self
    {
        $this->options['cookies'] = $cookies;

        return $this;
    }

    /**
     * Set whether to follow redirects.
     *
     * @param bool|array $redirects
     *
     * @return self
     */
    public function withRedirects(bool|array $redirects = true): self
    {
        $this->options['allow_redirects'] = $redirects;

        return $this;
    }

    /**
     * Set the certificate for the request.
     *
     * @param string|array $cert
     *
     * @return self
     */
    public function withCert(string|array $cert): self
    {
        $this->options['cert'] = $cert;

        return $this;
    }

    /**
     * Set the SSL key for the request.
     *
     * @param string|array $sslKey
     *
     * @return self
     */
    public function withSslKey(string|array $sslKey): self
    {
        $this->options['ssl_key'] = $sslKey;

        return $this;
    }

    /**
     * Set the stream option for the request.
     *
     * @param bool $stream
     *
     * @return self
     */
    public function withStream(bool $stream): self
    {
        $this->options['stream'] = $stream;

        return $this;
    }

    /**
     * Finalize and send a GET request.
     *
     * @param string $uri
     *
     * @return mixed
     */
    public function get(string $uri): mixed
    {
        return $this->finalizeRequest('GET', $uri);
    }

    /**
     * Finalize and send a POST request.
     *
     * @param string $uri
     * @param mixed  $body
     *
     * @return mixed
     */
    public function post(string $uri, mixed $body = null): mixed
    {
        if ($body !== null) {
            $this->withBody($body);
        }

        return $this->finalizeRequest('POST', $uri);
    }

    /**
     * Finalize and send a PUT request.
     *
     * @param string $uri
     * @param mixed  $body
     *
     * @return mixed
     */
    public function put(string $uri, mixed $body = null): mixed
    {
        if ($body !== null) {
            $this->withBody($body);
        }

        return $this->finalizeRequest('PUT', $uri);
    }

    /**
     * Finalize and send a DELETE request.
     *
     * @param string $uri
     *
     * @return mixed
     */
    public function delete(string $uri): mixed
    {
        return $this->finalizeRequest('DELETE', $uri);
    }

    /**
     * Finalize and send an OPTIONS request.
     *
     * @param string $uri
     *
     * @return mixed
     */
    public function options(string $uri): mixed
    {
        return $this->finalizeRequest('OPTIONS', $uri);
    }

    /**
     * Get the synchronous HTTP client.
     *
     * @return \Psr\Http\Client\ClientInterface
     */
    public function getSyncClient(): ClientInterface
    {
        if (! $this->syncClient) {
            $this->syncClient = new SyncClient();
        }

        return $this->syncClient;
    }

    /**
     * Set the synchronous HTTP client.
     *
     * @param \Psr\Http\Client\ClientInterface $syncClient
     *
     * @return self
     */
    public function setSyncClient(ClientInterface $syncClient): self
    {
        $this->syncClient = $syncClient;

        return $this;
    }

    /**
     * Get the default options for the request.
     *
     * @return array
     */
    public static function getDefaultOptions(): array
    {
        return self::$defaultOptions;
    }
}
