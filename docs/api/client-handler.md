---
title: ClientHandler API Reference
description: API reference for the ClientHandler class in the Fetch HTTP client package
---

# ClientHandler API Reference

The complete API reference for the `ClientHandler` class in the Fetch HTTP client package.

## Class Declaration

```php
namespace Fetch\Http;

class ClientHandler implements ClientHandlerInterface
{
    use ConfiguresRequests,
        HandlesUris,
        ManagesPromises,
        ManagesRetries,
        PerformsHttpRequests;

    // ...
}
```

## Constants

```php
/**
 * Default timeout for requests in seconds.
 */
public const DEFAULT_TIMEOUT = 30;

/**
 * Default number of retries.
 */
public const DEFAULT_RETRIES = 1;

/**
 * Default delay between retries in milliseconds.
 */
public const DEFAULT_RETRY_DELAY = 100;
```

## Constructor

```php
/**
 * ClientHandler constructor.
 *
 * @param  ClientInterface|null  $httpClient  The HTTP client
 * @param  array<string, mixed>  $options  The options for the request
 * @param  int|null  $timeout  Timeout for the request in seconds
 * @param  int|null  $maxRetries  Number of retries for the request
 * @param  int|null  $retryDelay  Delay between retries in milliseconds
 * @param  bool  $isAsync  Whether the request is asynchronous
 * @param  LoggerInterface|null  $logger  Logger for request/response details
 */
public function __construct(
    protected ?ClientInterface $httpClient = null,
    protected array $options = [],
    protected ?int $timeout = null,
    ?int $maxRetries = null,
    ?int $retryDelay = null,
    bool $isAsync = false,
    ?LoggerInterface $logger = null
)
```

## Factory Methods

### `create()`

Creates a new client handler with factory defaults.

```php
public static function create(): static
```

### `createWithBaseUri()`

Creates a client handler with preconfigured base URI.

```php
public static function createWithBaseUri(string $baseUri): static
```

### `createWithClient()`

Creates a client handler with a custom HTTP client.

```php
public static function createWithClient(ClientInterface $client): static
```

### `handle()`

Creates and executes an HTTP request with a single static method call.

```php
public static function handle(
    string $method,
    string $uri,
    array $options = []
): Response|PromiseInterface
```

## HTTP Methods

### `get()`

Sends a GET request.

```php
public function get(
    string $uri,
    array $queryParams = []
): ResponseInterface|PromiseInterface
```

### `post()`

Sends a POST request.

```php
public function post(
    string $uri,
    mixed $body = null,
    ContentType|string $contentType = ContentType::JSON
): ResponseInterface|PromiseInterface
```

### `put()`

Sends a PUT request.

```php
public function put(
    string $uri,
    mixed $body = null,
    ContentType|string $contentType = ContentType::JSON
): ResponseInterface|PromiseInterface
```

### `patch()`

Sends a PATCH request.

```php
public function patch(
    string $uri,
    mixed $body = null,
    ContentType|string $contentType = ContentType::JSON
): ResponseInterface|PromiseInterface
```

### `delete()`

Sends a DELETE request.

```php
public function delete(
    string $uri,
    mixed $body = null,
    ContentType|string $contentType = ContentType::JSON
): ResponseInterface|PromiseInterface
```

### `head()`

Sends a HEAD request.

```php
public function head(string $uri): ResponseInterface|PromiseInterface
```

### `options()`

Sends an OPTIONS request.

```php
public function options(string $uri): ResponseInterface|PromiseInterface
```

### `request()`

Sends a custom HTTP request.

```php
public function request(
    string|Method $method,
    string $uri,
    mixed $body = null,
    string|ContentType $contentType = ContentType::JSON,
    array $options = []
): Response|PromiseInterface
```

### `sendRequest()`

Sends an HTTP request with the specified method and URI.

```php
public function sendRequest(
    Method|string $method,
    string $uri,
    array $options = []
): ResponseInterface|PromiseInterface
```

### `sendRequestWithBody()`

Sends an HTTP request with a body.

```php
protected function sendRequestWithBody(
    Method|string $method,
    string $uri,
    mixed $body = null,
    ContentType|string $contentType = ContentType::JSON,
    array $options = []
): ResponseInterface|PromiseInterface
```

## URI Configuration

### `baseUri()`

Sets the base URI for all requests.

```php
public function baseUri(string $baseUri): ClientHandler
```

### `getFullUri()`

Gets the full URI for the request, combining base URI, relative URI, and query parameters.

```php
protected function getFullUri(): string
```

### `buildFullUri()`

Builds and gets the full URI from a base URI and path.

```php
protected function buildFullUri(string $uri): string
```

### `normalizeUri()`

Normalizes a URI by ensuring it has the correct format.

```php
protected function normalizeUri(string $uri): string
```

### `validateUriInputs()`

Validates URI and base URI inputs.

```php
protected function validateUriInputs(string $uri, string $baseUri): void
```

### `isAbsoluteUrl()`

Checks if a URI is an absolute URL.

```php
protected function isAbsoluteUrl(string $uri): bool
```

### `joinUriPaths()`

Joins base URI with a path properly.

```php
protected function joinUriPaths(string $baseUri, string $path): string
```

### `appendQueryParameters()`

Appends query parameters to a URI.

```php
protected function appendQueryParameters(string $uri, array $queryParams): string
```

### `splitUriFragment()`

Splits a URI into its base and fragment parts.

```php
protected function splitUriFragment(string $uri): array
```

### `getQuerySeparator()`

Determines the appropriate query string separator for a URI.

```php
protected function getQuerySeparator(string $uri): string
```

## Headers Configuration

### `withHeaders()`

Sets multiple request headers.

```php
public function withHeaders(array $headers): ClientHandler
```

### `withHeader()`

Sets a single request header.

```php
public function withHeader(string $header, mixed $value): ClientHandler
```

### `getHeaders()`

Gets the current request headers.

```php
public function getHeaders(): array
```

### `hasHeader()`

Checks if a header is set.

```php
public function hasHeader(string $header): bool
```

## Request Body Configuration

### `withBody()`

Sets the request body with content type.

```php
public function withBody(array|string $body, string|ContentType $contentType = ContentType::JSON): ClientHandler
```

### `withJson()`

Sets a JSON request body.

```php
public function withJson(array $data, int $options = 0): ClientHandler
```

### `withFormParams()`

Sets form parameters for URL-encoded forms.

```php
public function withFormParams(array $params): ClientHandler
```

### `withMultipart()`

Sets multipart form data (for file uploads).

```php
public function withMultipart(array $multipart): ClientHandler
```

### `configureRequestBody()`

Configures the request body for POST/PUT/PATCH/DELETE requests.

```php
protected function configureRequestBody(mixed $body = null, string|ContentType $contentType = ContentType::JSON): void
```

## Query Parameters

### `withQueryParameters()`

Sets multiple query parameters.

```php
public function withQueryParameters(array $queryParams): ClientHandler
```

### `withQueryParameter()`

Sets a single query parameter.

```php
public function withQueryParameter(string $name, mixed $value): ClientHandler
```

## Authentication

### `withToken()`

Sets a Bearer token for authentication.

```php
public function withToken(string $token): ClientHandler
```

### `withAuth()`

Sets Basic authentication credentials.

```php
public function withAuth(string $username, string $password): ClientHandler
```

## Request Configuration

### `timeout()`

Sets the request timeout in seconds.

```php
public function timeout(int $seconds): ClientHandler
```

### `getEffectiveTimeout()`

Gets the effective timeout for the request.

```php
public function getEffectiveTimeout(): int
```

### `prepareGuzzleOptions()`

Prepares options for Guzzle.

```php
protected function prepareGuzzleOptions(): array
```

### `withProxy()`

Sets a proxy for the request.

```php
public function withProxy(string|array $proxy): ClientHandler
```

### `withCookies()`

Sets cookies for the request.

```php
public function withCookies(bool|CookieJarInterface $cookies): ClientHandler
```

### `withRedirects()`

Configures redirect behavior.

```php
public function withRedirects(bool|array $redirects = true): ClientHandler
```

### `withCert()`

Sets an SSL client certificate.

```php
public function withCert(string|array $cert): ClientHandler
```

### `withSslKey()`

Sets an SSL client key.

```php
public function withSslKey(string|array $sslKey): ClientHandler
```

### `withStream()`

Enables response streaming.

```php
public function withStream(bool $stream): ClientHandler
```

### `withOption()`

Sets a single request option.

```php
public function withOption(string $key, mixed $value): ClientHandler
```

### `withOptions()`

Sets multiple request options.

```php
public function withOptions(array $options): ClientHandler
```

### `getOptions()`

Gets the current request options.

```php
public function getOptions(): array
```

### `hasOption()`

Checks if an option is set.

```php
public function hasOption(string $option): bool
```

### `withClonedOptions()`

Clones the client handler with new options.

```php
public function withClonedOptions(array $options): static
```

## Retry Configuration

### `retry()`

Configures retry behavior for failed requests.

```php
public function retry(int $retries, int $delay = 100): ClientHandler
```

### `retryStatusCodes()`

Sets which HTTP status codes should trigger a retry.

```php
public function retryStatusCodes(array $statusCodes): ClientHandler
```

### `retryExceptions()`

Sets which exception types should trigger a retry.

```php
public function retryExceptions(array $exceptions): ClientHandler
```

### `getMaxRetries()`

Gets the current maximum retry count.

```php
public function getMaxRetries(): int
```

### `getRetryDelay()`

Gets the current retry delay in milliseconds.

```php
public function getRetryDelay(): int
```

### `getRetryableStatusCodes()`

Gets the list of status codes that trigger retries.

```php
public function getRetryableStatusCodes(): array
```

### `getRetryableExceptions()`

Gets the list of exception types that trigger retries.

```php
public function getRetryableExceptions(): array
```

### `retryRequest()`

Implements retry logic for the request with exponential backoff.

```php
protected function retryRequest(callable $request): ResponseInterface
```

### `calculateBackoffDelay()`

Calculates backoff delay with exponential growth and jitter.

```php
protected function calculateBackoffDelay(int $baseDelay, int $attempt): int
```

### `isRetryableError()`

Determines if an error is retryable.

```php
protected function isRetryableError(RequestException $e): bool
```

## Request Execution

### `executeSyncRequest()`

Executes a synchronous HTTP request.

```php
protected function executeSyncRequest(
    string $method,
    string $uri,
    array $options,
    float $startTime
): ResponseInterface
```

### `executeAsyncRequest()`

Executes an asynchronous HTTP request.

```php
protected function executeAsyncRequest(
    string $method,
    string $uri,
    array $options
): PromiseInterface
```

## Asynchronous Request Handling

### `async()`

Sets the request to be asynchronous.

```php
public function async(?bool $async = true): ClientHandler
```

### `isAsync()`

Checks if the request will be executed asynchronously.

```php
public function isAsync(): bool
```

### `wrapAsync()`

Wraps a callable to run asynchronously.

```php
public function wrapAsync(callable $callable): PromiseInterface
```

### `awaitPromise()`

Waits for a promise to resolve and return its value.

```php
public function awaitPromise(PromiseInterface $promise, ?float $timeout = null): mixed
```

### `all()`

Executes multiple promises concurrently and waits for all to complete.

```php
public function all(array $promises): PromiseInterface
```

### `race()`

Executes multiple promises concurrently and returns the first to complete.

```php
public function race(array $promises): PromiseInterface
```

### `any()`

Executes multiple promises concurrently and returns the first to succeed.

```php
public function any(array $promises): PromiseInterface
```

### `sequence()`

Executes multiple promises in sequence.

```php
public function sequence(array $callables): PromiseInterface
```

### `map()`

Maps an array of items through an async callback.

```php
public function map(array $items, callable $callback, int $concurrency = 5): PromiseInterface
```

### `mapBatched()`

Processes items in batches with controlled concurrency.

```php
protected function mapBatched(array $items, callable $callback, int $concurrency): PromiseInterface
```

### `then()`

Adds a callback to be executed when the promise resolves.

```php
public function then(callable $onFulfilled, ?callable $onRejected = null): PromiseInterface
```

### `catch()`

Adds a callback to be executed when the promise is rejected.

```php
public function catch(callable $onRejected): PromiseInterface
```

### `finally()`

Adds a callback to be executed when the promise settles.

```php
public function finally(callable $onFinally): PromiseInterface
```

### `resolve()`

Creates a resolved promise with the given value.

```php
public function resolve(mixed $value): PromiseInterface
```

### `reject()`

Creates a rejected promise with the given reason.

```php
public function reject(mixed $reason): PromiseInterface
```

### `executeSequence()`

Executes promises in sequence recursively.

```php
protected function executeSequence(array $callables, array $results): PromiseInterface
```

### `validatePromises()`

Validates that all items in the array are promises.

```php
protected function validatePromises(array $promises): void
```

### `awaitWithTimeout()`

Waits for a promise with a timeout.

```php
protected function awaitWithTimeout(PromiseInterface $promise, float $timeout): mixed
```

### `createTimeoutPromise()`

Creates a promise that will reject after a timeout.

```php
protected function createTimeoutPromise(float $timeout): PromiseInterface
```

## Client Management

### `getHttpClient()`

Gets the underlying HTTP client.

```php
public function getHttpClient(): ClientInterface
```

### `setHttpClient()`

Sets the underlying HTTP client.

```php
public function setHttpClient(ClientInterface $client): self
```

## Logging

### `setLogger()`

Sets the PSR-3 logger instance.

```php
public function setLogger(LoggerInterface $logger): self
```

### `logRetry()`

Logs a retry attempt.

```php
protected function logRetry(int $attempt, int $maxAttempts, \Throwable $exception): void
```

### `logRequest()`

Logs a request.

```php
protected function logRequest(string $method, string $uri, array $options): void
```

### `logResponse()`

Logs a response.

```php
protected function logResponse(Response $response, float $duration): void
```

### `getResponseContentLength()`

Gets the content length of a response.

```php
protected function getResponseContentLength(Response $response): int|string
```

### `sanitizeOptions()`

Sanitizes options for logging.

```php
protected function sanitizeOptions(array $options): array
```

## Utility Methods

### `reset()`

Resets the handler state.

```php
public function reset(): ClientHandler
```

### `debug()`

Returns debug information about the request.

```php
public function debug(): array
```

## Testing Utilities

### `createMockResponse()`

Creates a new mock response for testing.

```php
public static function createMockResponse(
    int $statusCode = 200,
    array $headers = [],
    ?string $body = null,
    string $version = '1.1',
    ?string $reason = null
): Response
```

### `createJsonResponse()`

Creates a JSON response for testing.

```php
public static function createJsonResponse(
    array|object $data,
    int $statusCode = 200,
    array $headers = []
): Response
```

## Static Options

### `getDefaultOptions()`

Gets the default options for all requests.

```php
public static function getDefaultOptions(): array
```

### `setDefaultOptions()`

Sets the default options for all client instances.

```php
public static function setDefaultOptions(array $options): void
```
