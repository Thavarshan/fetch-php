---
title: fetch()
description: API reference for the fetch() helper function
---

# fetch()

The `fetch()` function is the primary way to make HTTP requests. It's designed to mimic JavaScript's `fetch()` API while providing PHP-specific enhancements.

## Signature

```php
function fetch(
    string|RequestInterface|null $resource = null,
    ?array $options = []
): ResponseInterface|ClientHandlerInterface|Client
```

## Parameters

### `$resource`

- Type: `string|RequestInterface|null`
- Default: `null`

This parameter can be:

- A URL string to fetch, e.g., `'https://api.example.com/users'`
- A pre-configured `Request` object
- `null` to return the client for method chaining

### `$options`

- Type: `array|null`
- Default: `[]`

An associative array of request options:

| Option | Type | Description |
| ------ | ---- | ----------- |
| `method` | `string\|Method` | HTTP method (GET, POST, etc.) |
| `headers` | `array` | Request headers |
| `body` | `mixed` | Request body (raw) |
| `json` | `array` | JSON data to send as body (takes precedence over body) |
| `form` | `array` | Form data to send as body (takes precedence if no json) |
| `multipart` | `array` | Multipart form data (takes precedence if no json/form) |
| `query` | `array` | Query parameters |
| `base_uri` | `string` | Base URI for the request |
| `timeout` | `int` | Request timeout in seconds |
| `connect_timeout` | `int` | Connection timeout in seconds (defaults to `timeout` when not set) |
| `retries` | `int` | Number of retries |
| `retry_delay` | `int` | Initial delay between retries in milliseconds |
| `auth` | `array` | Basic auth credentials [username, password] |
| `token` | `string` | Bearer token |
| `content_type` | `string\|ContentType` | Explicit `Content-Type` header when sending a raw `body` |
| `cache` | `bool\|array` | Enable/disable caching or supply per-request cache options (sync-only) |
| `debug` | `bool\|array` | Enable debug snapshots; array merges with `DebugInfo::getDefaultOptions()` |
| `proxy` | `string\|array` | Proxy configuration |
| `cookies` | `bool\|CookieJarInterface` | Cookies configuration |
| `allow_redirects` | `bool\|array` | Redirect handling configuration |
| `cert` | `string\|array` | SSL certificate |
| `ssl_key` | `string\|array` | SSL key |
| `stream` | `bool` | Whether to stream the response |
| `progress` | `callable` | Progress callback signature compatible with Guzzle |
| `async` | `bool` | Return a promise instead of waiting for the response |

`cache` accepts either a boolean or the same array structure described in [HTTP Caching](/guide/http-caching) (`ttl`, `respect_headers`, `force_refresh`, etc.). Remember that caching only applies to synchronous requests; when `async` is `true` the cache layer is bypassed.

`debug` can be set to `true` (use defaults), `false`, or an array overriding keys such as `response_body`, `timing`, `memory`, and so on. See [Debugging & Profiling](/guide/debugging-and-profiling) for the full option list.

## Return Value

The return value depends on the `$resource` parameter:

- If `$resource` is `null`: Returns the client instance (`ClientHandlerInterface` or `Client`) for method chaining
- If `$resource` is a URL string: Returns a `ResponseInterface` object
- If `$resource` is a `Request` object: Returns a `ResponseInterface` object
- If the `async` option is `true`: the above values are wrapped in a `React\Promise\PromiseInterface`

## Throws

- `ClientExceptionInterface` - If a client exception occurs during the request

## Examples

### Basic GET Request

```php
use function fetch;

// Make a simple GET request
$response = fetch('https://api.example.com/users');

// Check if the request was successful
if ($response->successful()) {
    // Parse the JSON response
    $users = $response->json();

    foreach ($users as $user) {
        echo $user['name'] . "\n";
    }
} else {
    echo "Error: " . $response->status() . " " . $response->statusText();
}
```

### POST Request with JSON Data

```php
// Send JSON data
$response = fetch('https://api.example.com/users', [
    'method' => 'POST',
    'json' => [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]
]);

// Or use the 'body' option with array (auto-converted to JSON)
$response = fetch('https://api.example.com/users', [
    'method' => 'POST',
    'body' => ['name' => 'John Doe', 'email' => 'john@example.com']
]);
```

### Setting Headers

```php
$response = fetch('https://api.example.com/users', [
    'headers' => [
        'Accept' => 'application/json',
        'X-API-Key' => 'your-api-key',
        'User-Agent' => 'MyApp/1.0'
    ]
]);
```

### Using Query Parameters

```php
// Add query parameters
$response = fetch('https://api.example.com/users', [
    'query' => [
        'page' => 1,
        'per_page' => 20,
        'sort' => 'created_at',
        'order' => 'desc'
    ]
]);
```

### Form Submission

```php
// Send form data
$response = fetch('https://api.example.com/login', [
    'method' => 'POST',
    'form' => [
        'username' => 'johndoe',
        'password' => 'secret',
        'remember' => true
    ]
]);
```

### File Upload

```php
// Upload a file using multipart form data
$response = fetch('https://api.example.com/upload', [
    'method' => 'POST',
    'multipart' => [
        [
            'name' => 'file',
            'contents' => file_get_contents('/path/to/file.jpg'),
            'filename' => 'upload.jpg',
            'headers' => ['Content-Type' => 'image/jpeg']
        ],
        [
            'name' => 'description',
            'contents' => 'Profile picture'
        ]
    ]
]);
```

### Authentication

```php
// Bearer token authentication
$response = fetch('https://api.example.com/profile', [
    'token' => 'your-oauth-token'
]);

// Basic authentication
$response = fetch('https://api.example.com/protected', [
    'auth' => ['username', 'password']
]);
```

### Timeouts and Retries

```php
// Set timeout and retry options
$response = fetch('https://api.example.com/slow-resource', [
    'timeout' => 30,            // 30 second total timeout
    'connect_timeout' => 5,     // 5 second connection timeout (optional)
    'retries' => 3,             // Retry up to 3 times
    'retry_delay' => 100        // Start with 100ms delay (exponential backoff + jitter)
]);
```

### Method Chaining

```php
// Return the client for method chaining
$users = fetch()
    ->withToken('your-oauth-token')
    ->withHeader('Accept', 'application/json')
    ->get('https://api.example.com/users')
    ->json();
```

### Using a Request Object

```php
use Fetch\Http\Request;

// Create a custom request
$request = Request::post('https://api.example.com/users')
    ->withJsonBody(['name' => 'John Doe', 'email' => 'john@example.com'])
    ->withHeader('X-API-Key', 'your-api-key');

// Send the request
$response = fetch($request);
```

## Internal Implementation

The `fetch()` function works by:

1. Processing the provided options with `process_request_options()`
2. Handling base URI configuration if provided with `handle_request_with_base_uri()`
3. Using the global client instance from `fetch_client()` to execute the request
4. Returning appropriate responses based on the input parameters

## Notes

- The `fetch()` function is not a direct implementation of the Web Fetch API; it's inspired by it but adapted for PHP
- When you pass an array as the request body, it is only coerced into JSON if you use the `json` option or leave the `content_type` unset/JSON—otherwise the array is preserved (e.g., multipart form data)
- For more complex request scenarios, use method chaining with `fetch()` or the `ClientHandler` class
- The function automatically handles conversion between different data formats based on content type
- When used without arguments, `fetch()` returns the global client instance for method chaining
- Retry behavior: transient network errors (e.g., connection timeouts) and certain HTTP statuses (e.g., 408, 429, 5xx) are retried when configured. HTTP error responses are returned (not thrown) and may be retried internally.
- HTTP caching (`cache` option/`withCache()`) is only applied to synchronous requests. When `async` is enabled the cache layer is bypassed.

## See Also

- [fetch_client()](/api/fetch-client) - Get or configure the global client instance
- [HTTP Method Helpers](/api/http-method-helpers) - Specialized helper functions for different HTTP methods
- [ClientHandler](/api/client-handler) - More details on the underlying client implementation
- [Response](/api/response) - API for working with response objects
