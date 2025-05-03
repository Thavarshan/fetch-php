<?php

declare(strict_types=1);

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
     */
    protected static array $defaultOptions = [
        'method' => 'GET',
        'headers' => [],
        'timeout' => self::DEFAULT_TIMEOUT,
    ];

    /**
     * ClientHandler constructor.
     *
     * @param  ClientInterface|null  $syncClient  The synchronous HTTP client.
     * @param  array  $options  The options for the request.
     * @param  int|null  $timeout  Timeout for the request.
     * @param  int|null  $retries  Number of retries for the request.
     * @param  int|null  $retryDelay  Delay between retries.
     * @param  bool  $isAsync  Whether the request is asynchronous.
     * @return void
     */
    public function __construct(
        protected ?ClientInterface $syncClient = null,
        protected array $options = [],
        protected ?int $timeout = null,
        protected ?int $retries = null,
        protected ?int $retryDelay = null,
        protected bool $isAsync = false
    ) {}

    /**
     * Apply options and execute the request.
     */
    public static function handle(string $method, string $uri, array $options = []): mixed
    {
        $handler = new static;
        $handler->applyOptions($options);

        return $handler->finalizeRequest($method, $uri);
    }

    /**
     * Apply the options to the handler.
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
     */
    protected function mergeOptionsAndProperties(): void
    {
        $this->options['timeout'] = $this->timeout ?? self::DEFAULT_TIMEOUT;
        $this->options['retries'] = $this->retries ?? self::DEFAULT_RETRIES;
        $this->options['retry_delay'] = $this->retryDelay ?? self::DEFAULT_RETRY_DELAY;
    }

    /**
     * Send a synchronous HTTP request.
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
     */
    protected function sendAsync(): AsyncHelperInterface
    {
        return new AsyncHelper(
            promise: fn (): ResponseInterface => $this->sendSync()
        );
    }

    /**
     * Implement retry logic for the request with exponential backoff.
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

                // Only retry on server errors or network issues
                if (! $this->isRetryableError($e)) {
                    throw $e;
                }

                // Exponential backoff with jitter
                $jitter = mt_rand(0, 100) / 100; // Random value between 0 and 1
                usleep((int) (($delay * (1 + $jitter)) * 1000)); // Convert milliseconds to microseconds
                $delay *= 2; // Exponential backoff
            }
        }

        throw new RuntimeException('Request failed after all retries.');
    }

    /**
     * Determine if an error is retryable.
     */
    protected function isRetryableError(RequestException $e): bool
    {
        $statusCode = $e->getCode();

        // Retry server errors (5xx) and specific client errors
        return ($statusCode >= 500 && $statusCode < 600) ||
               in_array($statusCode, [408, 429]) ||
               $e->getPrevious() instanceof \GuzzleHttp\Exception\ConnectException;
    }

    /**
     * Get the full URI for the request.
     */
    protected function getFullUri(): string
    {
        $baseUri = $this->options['base_uri'] ?? '';
        $uri = $this->options['uri'] ?? '';

        // If the URI is an absolute URL, return it as is
        if (filter_var($uri, \FILTER_VALIDATE_URL)) {
            // If query parameters exist, append them to the URL
            if (! empty($this->options['query'])) {
                $parsedUrl = parse_url($uri);
                $separator = ! empty($parsedUrl['query']) ? '&' : '?';

                return $uri.$separator.http_build_query($this->options['query']);
            }

            return $uri;
        }

        // If base URI is empty, return the URI with leading slashes trimmed
        if (empty($baseUri)) {
            $result = ltrim($uri, '/');
        } else {
            // Ensure base URI is a valid URL
            if (! filter_var($baseUri, \FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException("Invalid base URI: $baseUri");
            }

            // Concatenate base URI and URI ensuring no double slashes
            $result = rtrim($baseUri, '/').'/'.ltrim($uri, '/');
        }

        // If query parameters exist, append them to the URL
        if (! empty($this->options['query'])) {
            $separator = strpos($result, '?') !== false ? '&' : '?';
            $result .= $separator.http_build_query($this->options['query']);
        }

        return $result;
    }

    /**
     * Reset the handler state.
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
     */
    public function getSyncClient(): ClientInterface
    {
        if (! $this->syncClient) {
            $this->syncClient = new SyncClient;
        }

        return $this->syncClient;
    }

    /**
     * Set the synchronous HTTP client.
     */
    public function setSyncClient(ClientInterface $syncClient): self
    {
        $this->syncClient = $syncClient;

        return $this;
    }

    /**
     * Get the default options for the request.
     */
    public static function getDefaultOptions(): array
    {
        return self::$defaultOptions;
    }

    /**
     * Set the base URI for the request.
     */
    public function baseUri(string $baseUri): self
    {
        $this->options['base_uri'] = $baseUri;

        return $this;
    }

    /**
     * Set multiple options for the request.
     */
    public function withOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Set a single option for the request.
     */
    public function withOption(string $key, mixed $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Set the query parameters for the request.
     */
    public function withFormParams(array $params): self
    {
        $this->options['form_params'] = $params;

        return $this;
    }

    /**
     * Set the multipart data for the request.
     */
    public function withMultipart(array $multipart): self
    {
        $this->options['multipart'] = $multipart;

        return $this;
    }

    /**
     * Set the token for the request.
     */
    public function withToken(string $token): self
    {
        $this->options['headers']['Authorization'] = 'Bearer '.$token;

        return $this;
    }

    /**
     * Set the basic auth for the request.
     */
    public function withAuth(string $username, string $password): self
    {
        $this->options['auth'] = [$username, $password];

        return $this;
    }

    /**
     * Set the headers for the request.
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
     * Set a single header for the request.
     */
    public function withHeader(string $header, mixed $value): self
    {
        $this->options['headers'][$header] = $value;

        return $this;
    }

    /**
     * Set the body for the request.
     */
    public function withBody(array|string $body, string $contentType = 'application/json'): self
    {
        if (is_array($body) && $contentType === 'application/json') {
            $this->options['body'] = json_encode($body);

            // Set JSON content type header if not already set
            if (! $this->hasHeader('Content-Type')) {
                $this->withHeader('Content-Type', 'application/json');
            }
        } elseif (is_array($body) && $contentType === 'application/x-www-form-urlencoded') {
            $this->options['form_params'] = $body;
        } elseif (is_array($body) && $contentType === 'multipart/form-data') {
            $this->options['multipart'] = $body;
        } else {
            $this->options['body'] = $body;

            if (! $this->hasHeader('Content-Type')) {
                $this->withHeader('Content-Type', $contentType);
            }
        }

        return $this;
    }

    /**
     * Set the JSON body for the request.
     */
    public function withJson(array $data): self
    {
        $this->options['json'] = $data;

        if (! $this->hasHeader('Content-Type')) {
            $this->withHeader('Content-Type', 'application/json');
        }

        return $this;
    }

    /**
     * Set the query parameters for the request.
     */
    public function withQueryParameters(array $queryParams): self
    {
        $this->options['query'] = array_merge(
            $this->options['query'] ?? [],
            $queryParams
        );

        return $this;
    }

    /**
     * Set a single query parameter for the request.
     */
    public function withQueryParameter(string $name, mixed $value): self
    {
        $this->options['query'][$name] = $value;

        return $this;
    }

    /**
     * Set the timeout for the request.
     */
    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Set the retry logic for the request.
     */
    public function retry(int $retries, int $delay = 100): self
    {
        $this->retries = $retries;
        $this->retryDelay = $delay;

        return $this;
    }

    /**
     * Set the request to be asynchronous or not.
     */
    public function async(?bool $async = true): self
    {
        $this->isAsync = $async;

        return $this;
    }

    /**
     * Set the proxy for the request.
     */
    public function withProxy(string|array $proxy): self
    {
        $this->options['proxy'] = $proxy;

        return $this;
    }

    /**
     * Set the cookies for the request.
     */
    public function withCookies(bool|CookieJarInterface $cookies): self
    {
        $this->options['cookies'] = $cookies;

        return $this;
    }

    /**
     * Set whether to follow redirects.
     */
    public function withRedirects(bool|array $redirects = true): self
    {
        $this->options['allow_redirects'] = $redirects;

        return $this;
    }

    /**
     * Set the certificate for the request.
     */
    public function withCert(string|array $cert): self
    {
        $this->options['cert'] = $cert;

        return $this;
    }

    /**
     * Set the SSL key for the request.
     */
    public function withSslKey(string|array $sslKey): self
    {
        $this->options['ssl_key'] = $sslKey;

        return $this;
    }

    /**
     * Set the stream option for the request.
     */
    public function withStream(bool $stream): self
    {
        $this->options['stream'] = $stream;

        return $this;
    }

    /**
     * Set the sink option for the request.
     */
    public function head(string $uri): mixed
    {
        return $this->finalizeRequest('HEAD', $uri);
    }

    /**
     * Finalize and send a GET request.
     */
    public function get(string $uri): mixed
    {
        return $this->finalizeRequest('GET', $uri);
    }

    /**
     * Finalize and send a POST request.
     */
    public function post(string $uri, mixed $body = null, string $contentType = 'application/json'): mixed
    {
        $this->configurePostableRequest($uri, $body, $contentType);

        return $this->finalizeRequest('POST', $uri);
    }

    /**
     * Finalize and send a PATCH request.
     */
    public function patch(string $uri, mixed $body = null, string $contentType = 'application/json'): mixed
    {
        $this->configurePostableRequest($uri, $body, $contentType);

        return $this->finalizeRequest('PATCH', $uri);
    }

    /**
     * Finalize and send a PUT request.
     */
    public function put(string $uri, mixed $body = null, string $contentType = 'application/json'): mixed
    {
        $this->configurePostableRequest($uri, $body, $contentType);

        return $this->finalizeRequest('PUT', $uri);
    }

    /**
     * Finalize and send a POST/PATCH/PUT request.
     */
    protected function configurePostableRequest(string $uri, mixed $body = null, string $contentType = 'application/json'): void
    {
        if ($body !== null) {
            if (is_array($body) && $contentType === 'application/json') {
                $this->withJson($body);
            } elseif (is_array($body) && $contentType === 'application/x-www-form-urlencoded') {
                $this->withFormParams($body);
            } else {
                $this->withBody($body, $contentType);
            }
        }
    }

    /**
     * Finalize and send a DELETE request.
     */
    public function delete(string $uri): mixed
    {
        return $this->finalizeRequest('DELETE', $uri);
    }

    /**
     * Finalize and send an OPTIONS request.
     */
    public function options(string $uri): mixed
    {
        return $this->finalizeRequest('OPTIONS', $uri);
    }

    /**
     * Indicate that the request is asynchronous.
     */
    public function isAsync(): bool
    {
        return $this->isAsync;
    }

    /**
     * Get the request options.
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get the request headers.
     */
    public function getHeaders(): array
    {
        return $this->options['headers'] ?? [];
    }

    /**
     * Determine if the request has a specific header.
     */
    public function hasHeader(string $header): bool
    {
        return isset($this->options['headers'][$header]);
    }

    /**
     * Determine if the request has a specific option.
     */
    public function hasOption(string $option): bool
    {
        return isset($this->options[$option]);
    }

    /**
     * Debug the request and return its details.
     */
    public function debug(): array
    {
        return [
            'uri' => $this->getFullUri(),
            'method' => $this->options['method'] ?? 'GET',
            'headers' => $this->getHeaders(),
            'options' => array_diff_key($this->options, ['headers' => true]),
        ];
    }
}
