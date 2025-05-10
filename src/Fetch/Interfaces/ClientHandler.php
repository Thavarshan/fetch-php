<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Enum\ContentType;
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
     * Handles an HTTP request with the given method, URI, and options.
     *
     * @param  array<string, mixed>  $options  Additional options for the request.
     */
    public static function handle(string $method, string $uri, array $options = []): Response|PromiseInterface;

    /**
     * Retrieves the default options for the client.
     */
    public static function getDefaultOptions(): array;

    /**
     * Sets the default options for the client.
     *
     * @param  array  $options  Default options to set.
     */
    public static function setDefaultOptions(array $options): void;

    /**
     * Creates a new instance of the client handler.
     */
    public static function create(): self;

    /**
     * Creates a new instance of the client handler with a base URI.
     *
     * @param  string  $baseUri  Base URI for the client.
     */
    public static function createWithBaseUri(string $baseUri): self;

    /**
     * Creates a new instance of the client handler with a custom client.
     *
     * @param  ClientInterface  $client  Custom HTTP client.
     */
    public static function createWithClient(ClientInterface $client): self;

    /**
     * Sends an HTTP request with the specified parameters.
     *
     * @param  string  $method  HTTP method (e.g., GET, POST).
     * @param  string  $uri  URI to send the request to.
     * @param  mixed  $body  Request body.
     * @param  string|ContentType  $contentType  Content type of the request.
     * @param  array  $options  Additional options for the request.
     */
    public function request(string $method, string $uri, mixed $body = null, string|ContentType $contentType = ContentType::JSON, array $options = []): Response|PromiseInterface;

    /**
     * Sets the authorization token for the client.
     *
     * @param  string  $token  Authorization token.
     */
    public function withToken(string $token): self;

    /**
     * Sets the basic authentication credentials for the client.
     *
     * @param  string  $username  Username for authentication.
     * @param  string  $password  Password for authentication.
     */
    public function withAuth(string $username, string $password): self;

    /**
     * Sets multiple headers for the client.
     *
     * @param  array  $headers  Headers to set.
     */
    public function withHeaders(array $headers): self;

    /**
     * Sets a single header for the client.
     *
     * @param  string  $header  Header name.
     * @param  mixed  $value  Header value.
     */
    public function withHeader(string $header, mixed $value): self;

    /**
     * Sets the request body and content type.
     *
     * @param  array|string  $body  Request body.
     * @param  string|ContentType  $contentType  Content type of the request.
     */
    public function withBody(array|string $body, string|ContentType $contentType = ContentType::JSON): self;

    /**
     * Sets the request body as JSON data.
     *
     * @param  array  $data  JSON data to set as the body.
     * @param  int  $options  JSON encoding options.
     */
    public function withJson(array $data, int $options = 0): self;

    /**
     * Sets the request body as form parameters.
     *
     * @param  array  $params  Form parameters to set as the body.
     */
    public function withFormParams(array $params): self;

    /**
     * Sets the request body as multipart data.
     *
     * @param  array  $multipart  Multipart data to set as the body.
     */
    public function withMultipart(array $multipart): self;

    /**
     * Sets query parameters for the request.
     *
     * @param  array  $queryParams  Query parameters to set.
     */
    public function withQueryParameters(array $queryParams): self;

    /**
     * Sets a single query parameter for the request.
     *
     * @param  string  $name  Query parameter name.
     * @param  mixed  $value  Query parameter value.
     */
    public function withQueryParameter(string $name, mixed $value): self;

    /**
     * Sets the timeout for the request.
     *
     * @param  int  $seconds  Timeout in seconds.
     */
    public function timeout(int $seconds): self;

    /**
     * Configures the client to retry failed requests.
     *
     * @param  int  $retries  Number of retries.
     * @param  int  $delay  Delay between retries in milliseconds.
     */
    public function retry(int $retries, int $delay = 100): self;

    /**
     * Sets the status codes that should trigger a retry.
     *
     * @param  array  $statusCodes  HTTP status codes.
     */
    public function retryStatusCodes(array $statusCodes): self;

    /**
     * Sets the exceptions that should trigger a retry.
     *
     * @param  array  $exceptions  Exception classes.
     */
    public function retryExceptions(array $exceptions): self;

    /**
     * Configures the client for asynchronous requests.
     *
     * @param  bool|null  $async  Whether to enable asynchronous mode.
     */
    public function async(?bool $async = true): mixed;

    /**
     * Checks if the client is in asynchronous mode.
     */
    public function isAsync(): bool;

    /**
     * Sets the proxy configuration for the client.
     *
     * @param  string|array  $proxy  Proxy configuration.
     */
    public function withProxy(string|array $proxy): self;

    /**
     * Configures the client to use cookies.
     *
     * @param  bool|CookieJarInterface  $cookies  Cookie configuration.
     */
    public function withCookies(bool|CookieJarInterface $cookies): self;

    /**
     * Configures the client to follow redirects.
     *
     * @param  bool|array  $redirects  Redirect configuration.
     */
    public function withRedirects(bool|array $redirects = true): self;

    /**
     * Sets the SSL certificate for the client.
     *
     * @param  string|array  $cert  SSL certificate configuration.
     */
    public function withCert(string|array $cert): self;

    /**
     * Sets the SSL key for the client.
     *
     * @param  string|array  $sslKey  SSL key configuration.
     */
    public function withSslKey(string|array $sslKey): self;

    /**
     * Configures the client to use streaming mode.
     *
     * @param  bool  $stream  Whether to enable streaming mode.
     */
    public function withStream(bool $stream): self;

    /**
     * Sets multiple options for the client.
     *
     * @param  array  $options  Options to set.
     */
    public function withOptions(array $options): self;

    /**
     * Sets a single option for the client.
     *
     * @param  string  $key  Option key.
     * @param  mixed  $value  Option value.
     */
    public function withOption(string $key, mixed $value): self;

    /**
     * Sets the base URI for the client.
     *
     * @param  string  $baseUri  Base URI to set.
     */
    public function baseUri(string $baseUri): self;

    /**
     * Resets the client configuration to its default state.
     */
    public function reset(): self;

    /**
     * Sends a HEAD request to the specified URI.
     *
     * @param  string  $uri  URI to send the request to.
     */
    public function head(string $uri): Response|PromiseInterface;

    /**
     * Sends a GET request to the specified URI with optional query parameters.
     *
     * @param  string  $uri  URI to send the request to.
     * @param  array  $queryParams  Query parameters to include in the request.
     */
    public function get(string $uri, array $queryParams = []): Response|PromiseInterface;

    /**
     * Sends a POST request to the specified URI with an optional body.
     *
     * @param  string  $uri  URI to send the request to.
     * @param  mixed  $body  Request body.
     * @param  string|ContentType  $contentType  Content type of the request.
     */
    public function post(string $uri, mixed $body = null, string|ContentType $contentType = ContentType::JSON): Response|PromiseInterface;

    /**
     * Sends a PUT request to the specified URI with an optional body.
     *
     * @param  string  $uri  URI to send the request to.
     * @param  mixed  $body  Request body.
     * @param  string|ContentType  $contentType  Content type of the request.
     */
    public function put(string $uri, mixed $body = null, string|ContentType $contentType = ContentType::JSON): Response|PromiseInterface;

    /**
     * Sends a PATCH request to the specified URI with an optional body.
     *
     * @param  string  $uri  URI to send the request to.
     * @param  mixed  $body  Request body.
     * @param  string|ContentType  $contentType  Content type of the request.
     */
    public function patch(string $uri, mixed $body = null, string|ContentType $contentType = ContentType::JSON): Response|PromiseInterface;

    /**
     * Sends a DELETE request to the specified URI with an optional body.
     *
     * @param  string  $uri  URI to send the request to.
     * @param  mixed  $body  Request body.
     * @param  string|ContentType  $contentType  Content type of the request.
     */
    public function delete(string $uri, mixed $body = null, string|ContentType $contentType = ContentType::JSON): Response|PromiseInterface;

    /**
     * Sends an OPTIONS request to the specified URI.
     *
     * @param  string  $uri  URI to send the request to.
     */
    public function options(string $uri): Response|PromiseInterface;

    /**
     * Wraps a callable in an asynchronous context.
     *
     * @param  callable  $callable  Callable to wrap.
     */
    public function wrapAsync(callable $callable): PromiseInterface;

    /**
     * Awaits the resolution of a promise with an optional timeout.
     *
     * @param  PromiseInterface  $promise  Promise to await.
     * @param  float|null  $timeout  Timeout in seconds.
     */
    public function awaitPromise(PromiseInterface $promise, ?float $timeout = null): mixed;

    /**
     * Resolves when all promises in the array are resolved.
     *
     * @param  array  $promises  Array of promises.
     */
    public function all(array $promises): PromiseInterface;

    /**
     * Resolves when the first promise in the array is resolved.
     *
     * @param  array  $promises  Array of promises.
     */
    public function race(array $promises): PromiseInterface;

    /**
     * Resolves when any promise in the array is resolved.
     *
     * @param  array  $promises  Array of promises.
     */
    public function any(array $promises): PromiseInterface;

    /**
     * Executes an array of callables in sequence.
     *
     * @param  array  $callables  Array of callables.
     */
    public function sequence(array $callables): PromiseInterface;

    /**
     * Adds a fulfillment and rejection handler to the promise.
     *
     * @param  callable  $onFulfilled  Fulfillment handler.
     * @param  callable|null  $onRejected  Rejection handler.
     */
    public function then(callable $onFulfilled, ?callable $onRejected = null): PromiseInterface;

    /**
     * Adds a rejection handler to the promise.
     *
     * @param  callable  $onRejected  Rejection handler.
     */
    public function catch(callable $onRejected): PromiseInterface;

    /**
     * Adds a finalization handler to the promise.
     *
     * @param  callable  $onFinally  Finalization handler.
     */
    public function finally(callable $onFinally): PromiseInterface;

    /**
     * Resolves a promise with the given value.
     *
     * @param  mixed  $value  Value to resolve the promise with.
     */
    public function resolve(mixed $value): PromiseInterface;

    /**
     * Rejects a promise with the given reason.
     *
     * @param  mixed  $reason  Reason to reject the promise.
     */
    public function reject(mixed $reason): PromiseInterface;

    /**
     * Maps an array of items to promises using a callback.
     *
     * @param  array  $items  Items to map.
     * @param  callable  $callback  Callback to apply to each item.
     * @param  int  $concurrency  Maximum number of concurrent promises.
     */
    public function map(array $items, callable $callback, int $concurrency = 5): PromiseInterface;

    /**
     * Retrieves the synchronous HTTP client.
     */
    public function getSyncClient(): ClientInterface;

    /**
     * Sets the synchronous HTTP client.
     *
     * @param  ClientInterface  $syncClient  Synchronous HTTP client.
     */
    public function setSyncClient(ClientInterface $syncClient): self;

    /**
     * Retrieves the current options for the client.
     */
    public function getOptions(): array;

    /**
     * Retrieves the current headers for the client.
     */
    public function getHeaders(): array;

    /**
     * Checks if a specific header is set.
     *
     * @param  string  $header  Header name.
     */
    public function hasHeader(string $header): bool;

    /**
     * Checks if a specific option is set.
     *
     * @param  string  $option  Option name.
     */
    public function hasOption(string $option): bool;

    /**
     * Retrieves debugging information for the client.
     */
    public function debug(): array;

    /**
     * Sets the logger for the client.
     *
     * @param  LoggerInterface  $logger  Logger instance.
     */
    public function setLogger(LoggerInterface $logger): self;

    /**
     * Retrieves the maximum number of retries for the client.
     */
    public function getMaxRetries(): int;

    /**
     * Retrieves the delay between retries for the client.
     */
    public function getRetryDelay(): int;

    /**
     * Retrieves the status codes that trigger retries.
     */
    public function getRetryableStatusCodes(): array;

    /**
     * Retrieves the exceptions that trigger retries.
     */
    public function getRetryableExceptions(): array;

    /**
     * Retrieves the prepared options for the client.
     */
    public function getPreparedOptions(): array;

    /**
     * Creates a new instance of the client handler with cloned options.
     *
     * @param  array  $options  Options to clone.
     */
    public function withClonedOptions(array $options): self;
}
