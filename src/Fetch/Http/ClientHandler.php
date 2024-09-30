<?php

namespace Fetch\Http;

use Fetch\Interfaces\ClientHandler as ClientHandlerInterface;
use Fetch\Interfaces\Response as ResponseInterface;
use GuzzleHttp\Client as SyncClient;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;
use Matrix\AsyncHelper;
use Matrix\Interfaces\AsyncHelper as AsyncHelperInterface;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

class ClientHandler implements ClientHandlerInterface
{
    /**
     * Default timeout for requests in seconds.
     */
    private const DEFAULT_TIMEOUT = 30;

    /**
     * Default number of retries.
     */
    private const DEFAULT_RETRIES = 1;

    /**
     * Default delay between retries in milliseconds.
     */
    private const DEFAULT_RETRY_DELAY = 100;

    /**
     * Default options for the request.
     *
     * @var array
     */
    protected static array $defaultOptions = [
        'method' => 'GET',
        'headers' => [],
        'timeout' => self::DEFAULT_TIMEOUT,
    ];

    /**
     * ClientHandler constructor.
     *
     * @param ClientInterface|null $syncClient The synchronous HTTP client.
     * @param array                $options    The options for the request.
     * @param int|null             $timeout    Timeout for the request.
     * @param int|null             $retries    Number of retries for the request.
     * @param int|null             $retryDelay Delay between retries.
     * @param bool                 $isAsync    Whether the request is asynchronous.
     *
     * @return void
     */
    public function __construct(
        protected ?ClientInterface $syncClient = null,
        protected array $options = [],
        protected ?int $timeout = null,
        protected ?int $retries = null,
        protected ?int $retryDelay = null,
        protected bool $isAsync = false
    ) {
    }

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

        $this->timeout = $options['timeout'] ?? $this->timeout;
        $this->retries = $options['retries'] ?? $this->retries;
        $this->retryDelay = $options['retry_delay'] ?? $this->retryDelay;
        $this->isAsync = ! empty($options['async']);

        if (isset($options['base_uri'])) {
            $this->baseUri($options['base_uri']);
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

        $this->mergeOptionsAndProperties();

        return $this->isAsync ? $this->sendAsync() : $this->sendSync();
    }

    /**
     * Merge class properties and options into the final options array.
     *
     * @return void
     */
    protected function mergeOptionsAndProperties(): void
    {
        $this->options['timeout'] = $this->timeout ?? self::DEFAULT_TIMEOUT;
        $this->options['retries'] = $this->retries ?? self::DEFAULT_RETRIES;
        $this->options['retry_delay'] = $this->retryDelay ?? self::DEFAULT_RETRY_DELAY;
    }

    /**
     * Send a synchronous HTTP request.
     *
     * @return ResponseInterface
     */
    protected function sendSync(): ResponseInterface
    {
        return $this->retryRequest(function (): ResponseInterface {
            $psrResponse = $this->getSyncClient()->request(
                $this->options['method'],
                $this->getFullUri(),
                $this->options
            );

            return Response::createFromBase($psrResponse);
        });
    }

    /**
     * Send an asynchronous HTTP request.
     *
     * @return AsyncHelperInterface
     */
    protected function sendAsync(): AsyncHelperInterface
    {
        return new AsyncHelper(
            promise: fn (): ResponseInterface => $this->sendSync()
        );
    }

    /**
     * Implement retry logic for the request with exponential backoff.
     *
     * @param callable $request
     *
     * @return ResponseInterface
     */
    protected function retryRequest(callable $request): ResponseInterface
    {
        $attempts = $this->retries ?? self::DEFAULT_RETRIES;
        $delay = $this->retryDelay ?? self::DEFAULT_RETRY_DELAY;

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
     * Determine if an error is retryable.
     *
     * @param RequestException $e
     *
     * @return bool
     */
    protected function isRetryableError(RequestException $e): bool
    {
        return in_array($e->getCode(), [500, 502, 503, 504]);
    }

    /**
     * Get the full URI for the request.
     *
     * @return string
     */
    protected function getFullUri(): string
    {
        $baseUri = $this->options['base_uri'] ?? '';
        $uri = $this->options['uri'] ?? '';

        // If the URI is an absolute URL, return it as is
        if (filter_var($uri, \FILTER_VALIDATE_URL)) {
            return $uri;
        }

        // If base URI is empty, return the URI with leading slashes trimmed
        if (empty($baseUri)) {
            return ltrim($uri, '/');
        }

        // Ensure base URI is a valid URL
        if (! filter_var($baseUri, \FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid base URI: $baseUri");
        }

        // Concatenate base URI and URI ensuring no double slashes
        return rtrim($baseUri, '/') . '/' . ltrim($uri, '/');
    }

    /**
     * Reset the handler state.
     *
     * @return self
     */
    public function reset(): self
    {
        $this->options = [];
        $this->timeout = null;
        $this->retries = null;
        $this->retryDelay = null;
        $this->isAsync = false;

        return $this;
    }

    /**
     * Get the synchronous HTTP client.
     *
     * @return ClientInterface
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
     * @param ClientInterface $syncClient
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
}
