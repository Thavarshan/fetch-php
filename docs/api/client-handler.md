# ClientHandler Class

The `ClientHandler` class provides a fluent, chainable API for building and sending HTTP requests. It offers extensive configuration options, including headers, authentication, request body, and more.

## Class Declaration

```php
namespace Fetch\Http;

class ClientHandler implements ClientHandlerInterface
{
    // ...
}
```

## Constructor

```php
public function __construct(
    protected ?ClientInterface $syncClient = null,
    protected array $options = [],
    protected ?int $timeout = null,
    protected ?int $retries = null,
    protected ?int $retryDelay = null,
    protected bool $isAsync = false
)
```

### Parameters

- `$syncClient` (optional): A custom Guzzle HTTP client
- `$options` (optional): Request options array
- `$timeout` (optional): Request timeout in seconds
- `$retries` (optional): Number of retry attempts
- `$retryDelay` (optional): Delay between retry attempts in milliseconds
- `$isAsync` (optional): Whether requests should be asynchronous

## Static Methods

### `handle()`

Creates and executes an HTTP request.

```php
public static function handle(string $method, string $uri, array $options = []): ResponseInterface|PromiseInterface
```

### `getDefaultOptions()`

Returns the default request options.

```php
public static function getDefaultOptions(): array
```

## HTTP Methods

### `get()`

Sends a GET request.

```php
public function get(string $uri): ResponseInterface|PromiseInterface
```

### `post()`

Sends a POST request.

```php
public function post(string $uri, mixed $body = null, ContentType|string $contentType = 'application/json'): ResponseInterface|PromiseInterface
```

### `put()`

Sends a PUT request.

```php
public function put(string $uri, mixed $body = null, ContentType|string $contentType = 'application/json'): ResponseInterface|PromiseInterface
```

### `patch()`

Sends a PATCH request.

```php
public function patch(string $uri, mixed $body = null, ContentType|string $contentType = 'application/json'): ResponseInterface|PromiseInterface
```

### `delete()`

Sends a DELETE request.

```php
public function delete(string $uri): ResponseInterface|PromiseInterface
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

## Request Configuration Methods

### `baseUri()`

Sets the base URI for requests.

```php
public function baseUri(string $baseUri): self
```

### `withHeaders()`

Sets multiple request headers.

```php
public function withHeaders(array $headers): self
```

### `withHeader()`

Sets a single request header.

```php
public function withHeader(string $header, mixed $value): self
```

### `withBody()`

Sets the request body.

```php
public function withBody(array|string $body, ContentType|string $contentType = 'application/json'): self
```

### `withJson()`

Sets a JSON request body.

```php
public function withJson(array $data): self
```

### `withFormParams()`

Sets form parameters for URL-encoded forms.

```php
public function withFormParams(array $params): self
```

### `withMultipart()`

Sets multipart form data (for file uploads).

```php
public function withMultipart(array $multipart): self
```

### `withQueryParameters()`

Sets query parameters.

```php
public function withQueryParameters(array $queryParams): self
```

### `withQueryParameter()`

Sets a single query parameter.

```php
public function withQueryParameter(string $name, mixed $value): self
```

### `withToken()`

Sets a Bearer token for authentication.

```php
public function withToken(string $token): self
```

### `withAuth()`

Sets Basic authentication credentials.

```php
public function withAuth(string $username, string $password): self
```

### `withProxy()`

Sets a proxy for the request.

```php
public function withProxy(string|array $proxy): self
```

### `withCookies()`

Sets cookies for the request.

```php
public function withCookies(bool|CookieJarInterface $cookies): self
```

### `withRedirects()`

Configures redirect behavior.

```php
public function withRedirects(bool|array $redirects = true): self
```

### `withCert()`

Sets an SSL client certificate.

```php
public function withCert(string|array $cert): self
```

### `withSslKey()`

Sets an SSL client key.

```php
public function withSslKey(string|array $sslKey): self
```

### `withStream()`

Enables response streaming.

```php
public function withStream(bool $stream): self
```

### `withOption()`

Sets a single request option.

```php
public function withOption(string $key, mixed $value): self
```

### `withOptions()`

Sets multiple request options.

```php
public function withOptions(array $options): self
```

### `timeout()`

Sets the request timeout.

```php
public function timeout(int $seconds): self
```

### `retry()`

Sets retry configuration.

```php
public function retry(int $retries, int $delay = 100): self
```

### `async()`

Sets asynchronous mode.

```php
public function async(?bool $async = true): self
```

## Utility Methods

### `reset()`

Resets the handler state.

```php
public function reset(): self
```

### `getSyncClient()`

Gets the underlying Guzzle client.

```php
public function getSyncClient(): ClientInterface
```

### `setSyncClient()`

Sets the underlying Guzzle client.

```php
public function setSyncClient(ClientInterface $syncClient): self
```

### `isAsync()`

Checks if async mode is enabled.

```php
public function isAsync(): bool
```

### `getOptions()`

Gets the current request options.

```php
public function getOptions(): array
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

### `hasOption()`

Checks if an option is set.

```php
public function hasOption(string $option): bool
```

### `debug()`

Returns debug information about the request.

```php
public function debug(): array
```

## Asynchronous Methods

### `wrapAsync()`

Wraps a callable to run asynchronously.

```php
public function wrapAsync(callable $callable): PromiseInterface
```

### `awaitPromise()`

Waits for a promise to resolve.

```php
public function awaitPromise(PromiseInterface $promise): mixed
```

### `all()`

Waits for all promises to resolve.

```php
public function all(array $promises): PromiseInterface
```

### `race()`

Returns the first promise to resolve.

```php
public function race(array $promises): PromiseInterface
```

### `any()`

Returns the first promise to succeed.

```php
public function any(array $promises): PromiseInterface
```

### `then()`

Adds a fulfillment handler.

```php
public function then(callable $onFulfilled, ?callable $onRejected = null): PromiseInterface
```

### `catch()`

Adds a rejection handler.

```php
public function catch(callable $onRejected): PromiseInterface
```

### `finally()`

Adds a handler for promise settlement.

```php
public function finally(callable $onFinally): PromiseInterface
```

## Examples

### Basic GET Request

```php
$client = new ClientHandler();
$response = $client->get('https://api.example.com/users');
$users = $response->json();
```

### Configuring Headers and Authentication

```php
$client = new ClientHandler();
$response = $client
    ->withHeaders([
        'Accept' => 'application/json',
        'X-API-Key' => 'your-api-key'
    ])
    ->withToken('your-oauth-token')
    ->get('https://api.example.com/users');
```

### POST Request with JSON Body

```php
$client = new ClientHandler();
$response = $client
    ->baseUri('https://api.example.com')
    ->withJson([
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ])
    ->post('/users');
```

### File Upload

```php
$client = new ClientHandler();
$response = $client
    ->withMultipart([
        [
            'name' => 'file',
            'contents' => fopen('/path/to/file.jpg', 'r'),
            'filename' => 'upload.jpg',
        ],
        [
            'name' => 'description',
            'contents' => 'File description',
        ]
    ])
    ->post('https://api.example.com/upload');
```

### Setting Timeout and Retries

```php
$client = new ClientHandler();
$response = $client
    ->timeout(5)           // 5 second timeout
    ->retry(3, 100)        // Retry up to 3 times with 100ms initial delay
    ->get('https://api.example.com/unstable');
```

### Asynchronous Request with Promise Chain

```php
$client = new ClientHandler();
$client
    ->withToken('your-oauth-token')
    ->async()                // Enable async mode
    ->get('https://api.example.com/users')
    ->then(function ($response) {
        $users = $response->json();
        echo "Fetched " . count($users) . " users";
        return $users;
    })
    ->catch(function ($error) {
        echo "Error: " . $error->getMessage();
    });
```

### Multiple Concurrent Requests

```php
$client = new ClientHandler();

// Create promises for multiple requests
$usersPromise = $client->async()->get('https://api.example.com/users');
$postsPromise = $client->async()->get('https://api.example.com/posts');

// Wait for all to complete
$client->all([
    'users' => $usersPromise,
    'posts' => $postsPromise
])->then(function ($results) {
    $users = $results['users']->json();
    $posts = $results['posts']->json();

    echo "Fetched " . count($users) . " users and " . count($posts) . " posts";
});
```

### Get the First to Complete

```php
$client = new ClientHandler();

// Create promises for multiple endpoints
$promises = [
    $client->async()->get('https://api1.example.com/data'),
    $client->async()->get('https://api2.example.com/data'),
    $client->async()->get('https://api3.example.com/data')
];

// Get the result from whichever completes first
$client->race($promises)
    ->then(function ($response) {
        $data = $response->json();
        echo "Got data from the fastest source";
    });
```
