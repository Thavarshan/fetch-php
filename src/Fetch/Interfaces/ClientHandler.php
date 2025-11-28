<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJarInterface;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;

/**
 * Interface ClientHandler
 *
 * Provides methods for handling HTTP client requests and configurations.
 */
interface ClientHandler
{
    /**
     * Retrieves the default options for the client.
     *
     * @return array<string, mixed> Default options
     */
    public static function getDefaultOptions(): array;

    /**
     * Sets the default options for the client.
     *
     * @param  array<string, mixed>  $options  Default options to set
     */
    public static function setDefaultOptions(array $options): void;

    /**
     * Creates a new instance of the client handler.
     *
     * @return static New client handler instance
     */
    public static function create(): self;

    /**
     * Creates a new instance of the client handler with a base URI.
     *
     * @param  string  $baseUri  Base URI for the client
     * @return static New client handler instance
     */
    public static function createWithBaseUri(string $baseUri): self;

    /**
     * Creates a new instance of the client handler with a custom client.
     *
     * @param  ClientInterface  $client  Custom HTTP client
     * @return static New client handler instance
     */
    public static function createWithClient(ClientInterface $client): self;

    /**
     * Sends an HTTP request with the specified parameters.
     *
     * @param  string|Method  $method  HTTP method (e.g., GET, POST)
     * @param  string  $uri  URI to send the request to
     * @param  mixed  $body  Request body
     * @param  string|ContentType  $contentType  Content type of the request
     * @param  array<string, mixed>  $options  Additional request options
     * @return Response|PromiseInterface<Response> Response or promise
     */
    public function request(
        string|Method $method,
        string $uri,
        mixed $body = null,
        string|ContentType $contentType = ContentType::JSON,
        array $options = []
    ): Response|PromiseInterface;

    /**
     * Sends an HTTP request.
     *
     * @param  Method|string  $method  The HTTP method
     * @param  string  $uri  The URI to request
     * @param  array<string, mixed>  $options  Additional options
     * @return Response|PromiseInterface<Response> Response or promise
     */
    public function sendRequest(
        Method|string $method,
        string $uri,
        array $options = []
    ): Response|PromiseInterface;

    /**
     * Sets the authorization token for the client.
     *
     * @param  string  $token  Authorization token
     * @return $this
     */
    public function withToken(string $token): self;

    /**
     * Sets the basic authentication credentials for the client.
     *
     * @param  string  $username  Username for authentication
     * @param  string  $password  Password for authentication
     * @return $this
     */
    public function withAuth(string $username, string $password): self;

    /**
     * Sets multiple headers for the client.
     *
     * @param  array<string, mixed>  $headers  Headers to set
     * @return $this
     */
    public function withHeaders(array $headers): self;

    /**
     * Sets a single header for the client.
     *
     * @param  string  $header  Header name
     * @param  mixed  $value  Header value
     * @return $this
     */
    public function withHeader(string $header, mixed $value): self;

    /**
     * Sets the request body and content type.
     *
     * @param  array<string, mixed>|string  $body  Request body
     * @param  string|ContentType  $contentType  Content type of the request
     * @return $this
     */
    public function withBody(array|string $body, string|ContentType $contentType = ContentType::JSON): self;

    /**
     * Sets the request body as JSON data.
     *
     * @param  array<string, mixed>  $data  JSON data to set as the body
     * @param  int  $options  JSON encoding options
     * @return $this
     */
    public function withJson(array $data, int $options = 0): self;

    /**
     * Sets the request body as form parameters.
     *
     * @param  array<string, mixed>  $params  Form parameters to set as the body
     * @return $this
     */
    public function withFormParams(array $params): self;

    /**
     * Sets the request body as multipart data.
     *
     * @param  array<int, array{name: string, contents: mixed, headers?: array<string, string>}>  $multipart  Multipart data to set as the body
     * @return $this
     */
    public function withMultipart(array $multipart): self;

    /**
     * Sets query parameters for the request.
     *
     * @param  array<string, mixed>  $queryParams  Query parameters to set
     * @return $this
     */
    public function withQueryParameters(array $queryParams): self;

    /**
     * Sets a single query parameter for the request.
     *
     * @param  string  $name  Query parameter name
     * @param  mixed  $value  Query parameter value
     * @return $this
     */
    public function withQueryParameter(string $name, mixed $value): self;

    /**
     * Sets the timeout for the request.
     *
     * @param  int  $seconds  Timeout in seconds
     * @return $this
     */
    public function timeout(int $seconds): self;

    /**
     * Configures the client to retry failed requests.
     *
     * @param  int  $retries  Number of retries
     * @param  int  $delay  Delay between retries in milliseconds
     * @return $this
     */
    public function retry(int $retries, int $delay = 100): self;

    /**
     * Sets the status codes that should trigger a retry.
     *
     * @param  array<int>  $statusCodes  HTTP status codes
     * @return $this
     */
    public function retryStatusCodes(array $statusCodes): self;

    /**
     * Sets the exceptions that should trigger a retry.
     *
     * @param  array<class-string<\Throwable>>  $exceptions  Exception classes
     * @return $this
     */
    public function retryExceptions(array $exceptions): self;

    /**
     * Configures the client for asynchronous requests.
     *
     * @param  bool|null  $async  Whether to enable asynchronous mode
     * @return $this
     */
    public function async(?bool $async = true): self;

    /**
     * Checks if the client is in asynchronous mode.
     *
     * @return bool Whether the client is in asynchronous mode
     */
    public function isAsync(): bool;

    /**
     * Sets the proxy configuration for the client.
     *
     * @param  string|array<string, mixed>  $proxy  Proxy configuration
     * @return $this
     */
    public function withProxy(string|array $proxy): self;

    /**
     * Configures the client to use cookies.
     *
     * @param  bool|CookieJarInterface  $cookies  Cookie configuration
     * @return $this
     */
    public function withCookies(bool|CookieJarInterface $cookies): self;

    /**
     * Configures the client to follow redirects.
     *
     * @param  bool|array<string, mixed>  $redirects  Redirect configuration
     * @return $this
     */
    public function withRedirects(bool|array $redirects = true): self;

    /**
     * Sets the SSL certificate for the client.
     *
     * @param  string|array<string, mixed>  $cert  SSL certificate configuration
     * @return $this
     */
    public function withCert(string|array $cert): self;

    /**
     * Sets the SSL key for the client.
     *
     * @param  string|array<string, mixed>  $sslKey  SSL key configuration
     * @return $this
     */
    public function withSslKey(string|array $sslKey): self;

    /**
     * Configures the client to use streaming mode.
     *
     * @param  bool  $stream  Whether to enable streaming mode
     * @return $this
     */
    public function withStream(bool $stream): self;

    /**
     * Sets multiple options for the client.
     *
     * @param  array<string, mixed>  $options  Options to set
     * @return $this
     */
    public function withOptions(array $options): self;

    /**
     * Sets a single option for the client.
     *
     * @param  string  $key  Option key
     * @param  mixed  $value  Option value
     * @return $this
     */
    public function withOption(string $key, mixed $value): self;

    /**
     * Sets the base URI for the client.
     *
     * @param  string  $baseUri  Base URI to set
     * @return $this
     */
    public function baseUri(string $baseUri): self;

    /**
     * Resets the client configuration to its default state.
     *
     * @return $this
     */
    public function reset(): self;

    /**
     * Sends a HEAD request to the specified URI.
     *
     * @param  string  $uri  URI to send the request to
     * @return Response|PromiseInterface<Response> Response or promise
     */
    public function head(string $uri): Response|PromiseInterface;

    /**
     * Sends a GET request to the specified URI with optional query parameters.
     *
     * @param  string  $uri  URI to send the request to
     * @param  array<string, mixed>  $queryParams  Query parameters to include in the request
     * @return Response|PromiseInterface<Response> Response or promise
     */
    public function get(string $uri, array $queryParams = []): Response|PromiseInterface;

    /**
     * Sends a POST request to the specified URI with an optional body.
     *
     * @param  string  $uri  URI to send the request to
     * @param  mixed  $body  Request body
     * @param  string|ContentType  $contentType  Content type of the request
     * @return Response|PromiseInterface<Response> Response or promise
     */
    public function post(string $uri, mixed $body = null, string|ContentType $contentType = ContentType::JSON): Response|PromiseInterface;

    /**
     * Sends a PUT request to the specified URI with an optional body.
     *
     * @param  string  $uri  URI to send the request to
     * @param  mixed  $body  Request body
     * @param  string|ContentType  $contentType  Content type of the request
     * @return Response|PromiseInterface<Response> Response or promise
     */
    public function put(string $uri, mixed $body = null, string|ContentType $contentType = ContentType::JSON): Response|PromiseInterface;

    /**
     * Sends a PATCH request to the specified URI with an optional body.
     *
     * @param  string  $uri  URI to send the request to
     * @param  mixed  $body  Request body
     * @param  string|ContentType  $contentType  Content type of the request
     * @return Response|PromiseInterface<Response> Response or promise
     */
    public function patch(string $uri, mixed $body = null, string|ContentType $contentType = ContentType::JSON): Response|PromiseInterface;

    /**
     * Sends a DELETE request to the specified URI with an optional body.
     *
     * @param  string  $uri  URI to send the request to
     * @param  mixed  $body  Request body
     * @param  string|ContentType  $contentType  Content type of the request
     * @return Response|PromiseInterface<Response> Response or promise
     */
    public function delete(string $uri, mixed $body = null, string|ContentType $contentType = ContentType::JSON): Response|PromiseInterface;

    /**
     * Sends an OPTIONS request to the specified URI.
     *
     * @param  string  $uri  URI to send the request to
     * @return Response|PromiseInterface<Response> Response or promise
     */
    public function options(string $uri): Response|PromiseInterface;

    /**
     * Wraps a callable in an asynchronous context.
     *
     * @param  callable  $callable  Callable to wrap
     * @return PromiseInterface<mixed> Promise
     */
    public function wrapAsync(callable $callable): PromiseInterface;

    /**
     * Awaits the resolution of a promise with an optional timeout.
     *
     * @param  PromiseInterface<mixed>  $promise  Promise to await
     * @param  float|null  $timeout  Timeout in seconds
     * @return mixed The resolved value
     */
    public function awaitPromise(PromiseInterface $promise, ?float $timeout = null): mixed;

    /**
     * Resolves when all promises in the array are resolved.
     *
     * @param  array<PromiseInterface<mixed>>  $promises  Array of promises
     * @return PromiseInterface<array<mixed>> Promise that resolves with array of results
     */
    public function all(array $promises): PromiseInterface;

    /**
     * Resolves when the first promise in the array is resolved.
     *
     * @param  array<PromiseInterface<mixed>>  $promises  Array of promises
     * @return PromiseInterface<mixed> Promise that resolves with the first result
     */
    public function race(array $promises): PromiseInterface;

    /**
     * Resolves when any promise in the array is resolved.
     *
     * @param  array<PromiseInterface<mixed>>  $promises  Array of promises
     * @return PromiseInterface<mixed> Promise that resolves with the first successful result
     */
    public function any(array $promises): PromiseInterface;

    /**
     * Executes an array of callables in sequence.
     *
     * @param  array<callable(): PromiseInterface<mixed>>  $callables  Array of callables
     * @return PromiseInterface<array<mixed>> Promise that resolves with array of results
     */
    public function sequence(array $callables): PromiseInterface;

    /**
     * Creates a resolved promise with the given value.
     *
     * @param  mixed  $value  Value to resolve the promise with
     * @return PromiseInterface Resolved promise
     */
    public function resolve(mixed $value): PromiseInterface;

    /**
     * Creates a rejected promise with the given reason.
     *
     * @param  mixed  $reason  Reason to reject the promise
     * @return PromiseInterface Rejected promise
     */
    public function reject(mixed $reason): PromiseInterface;

    /**
     * Maps an array of items to promises using a callback.
     *
     * @param  array<mixed>  $items  Items to map
     * @param  callable  $callback  Callback to apply to each item
     * @param  int  $concurrency  Maximum number of concurrent promises
     * @return PromiseInterface Promise that resolves with array of results
     */
    public function map(array $items, callable $callback, int $concurrency = 5): PromiseInterface;

    /**
     * Retrieves the HTTP client.
     *
     * @return ClientInterface The HTTP client
     */
    public function getHttpClient(): ClientInterface;

    /**
     * Sets the HTTP client.
     *
     * @param  ClientInterface  $client  HTTP client
     * @return $this
     */
    public function setHttpClient(ClientInterface $client): self;

    /**
     * Retrieves the current options for the client.
     *
     * @return array<string, mixed> Current options
     */
    public function getOptions(): array;

    /**
     * Retrieves the current headers for the client.
     *
     * @return array<string, mixed> Current headers
     */
    public function getHeaders(): array;

    /**
     * Checks if a specific header is set.
     *
     * @param  string  $header  Header name
     * @return bool Whether the header is set
     */
    public function hasHeader(string $header): bool;

    /**
     * Checks if a specific option is set.
     *
     * @param  string  $option  Option name
     * @return bool Whether the option is set
     */
    public function hasOption(string $option): bool;

    /**
     * Retrieves debugging information for the client.
     *
     * @return array<string, mixed> Debug information
     */
    public function debug(): array;

    /**
     * Sets the logger for the client.
     *
     * @param  LoggerInterface  $logger  Logger instance
     * @return $this
     */
    public function setLogger(LoggerInterface $logger): self;

    /**
     * Retrieves the maximum number of retries for the client.
     *
     * @return int Maximum retries
     */
    public function getMaxRetries(): int;

    /**
     * Retrieves the delay between retries for the client.
     *
     * @return int Retry delay in milliseconds
     */
    public function getRetryDelay(): int;

    /**
     * Retrieves the status codes that trigger retries.
     *
     * @return array<int> Retryable status codes
     */
    public function getRetryableStatusCodes(): array;

    /**
     * Retrieves the exceptions that trigger retries.
     *
     * @return array<class-string<\Throwable>> Retryable exceptions
     */
    public function getRetryableExceptions(): array;

    /**
     * Creates a new instance of the client handler with cloned options.
     *
     * @param  array<string, mixed>  $options  Options to clone
     * @return static New client handler instance
     */
    public function withClonedOptions(array $options): self;

    /**
     * Enable debug mode with specified options.
     *
     * @param  array<string, mixed>|bool  $options  Debug options or true to enable all
     * @return $this
     */
    public function withDebug(array|bool $options = true): self;

    /**
     * Set a profiler for performance tracking.
     *
     * @param  \Fetch\Support\FetchProfiler  $profiler  The profiler instance
     * @return $this
     */
    public function withProfiler(\Fetch\Support\FetchProfiler $profiler): self;

    /**
     * Get the profiler instance if set.
     */
    public function getProfiler(): ?\Fetch\Support\FetchProfiler;

    /**
     * Check if debug mode is enabled.
     */
    public function isDebugEnabled(): bool;

    /**
     * Get the debug options.
     *
     * @return array<string, mixed>
     */
    public function getDebugOptions(): array;

    /**
     * Get the last debug info from the most recent request.
     */
    public function getLastDebugInfo(): ?\Fetch\Support\DebugInfo;

    /**
     * Configure connection pooling for this handler.
     *
     * @param  array<string, mixed>|bool  $config  Pool configuration or boolean to enable/disable
     * @return $this
     */
    public function withConnectionPool(array|bool $config = true): self;

    /**
     * Configure HTTP/2 for this handler.
     *
     * @param  array<string, mixed>|bool  $config  HTTP/2 configuration or boolean to enable/disable
     * @return $this
     */
    public function withHttp2(array|bool $config = true): self;

    /**
     * Get the connection pool instance.
     *
     * @return \Fetch\Pool\ConnectionPool|null The pool instance or null if not configured
     */
    public function getConnectionPool(): ?\Fetch\Pool\ConnectionPool;

    /**
     * Get the DNS cache instance.
     *
     * @return \Fetch\Pool\DnsCache|null The DNS cache or null if not configured
     */
    public function getDnsCache(): ?\Fetch\Pool\DnsCache;

    /**
     * Get the HTTP/2 configuration.
     *
     * @return \Fetch\Pool\Http2Configuration|null The HTTP/2 config or null if not configured
     */
    public function getHttp2Config(): ?\Fetch\Pool\Http2Configuration;

    /**
     * Check if connection pooling is enabled.
     */
    public function isPoolingEnabled(): bool;

    /**
     * Check if HTTP/2 is enabled.
     */
    public function isHttp2Enabled(): bool;

    /**
     * Get connection pool statistics.
     *
     * @return array<string, mixed>
     */
    public function getPoolStats(): array;

    /**
     * Get DNS cache statistics.
     *
     * @return array<string, mixed>
     */
    public function getDnsCacheStats(): array;

    /**
     * Clear the DNS cache.
     *
     * @param  string|null  $hostname  Specific hostname to clear, or null for all
     * @return $this
     */
    public function clearDnsCache(?string $hostname = null): self;

    /**
     * Close all pooled connections.
     *
     * @return $this
     */
    public function closeAllConnections(): self;

    /**
     * Reset the global connection pool and DNS cache.
     *
     * @return $this
     */
    public function resetPool(): self;
}
