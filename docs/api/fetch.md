# `fetch` Function API Reference

The `fetch()` function in FetchPHP is designed to mimic JavaScript’s `fetch()` API for making HTTP requests. It provides an easy-to-use interface for sending both synchronous and asynchronous requests, and supports flexible configuration through options.

---

## Function Signature

```php
function fetch(?string $url = null, ?array $options = []): \Fetch\Http\Response|\Fetch\Http\ClientHandler
```

---

## Parameters# fetch() Function

The `fetch()` function is the primary entry point for making HTTP requests in Fetch PHP. It provides a JavaScript-like syntax for performing both synchronous and asynchronous HTTP operations.

## Syntax

```php
function fetch(?string $url = null, ?array $options = []): ResponseInterface|ClientHandler
```

## Parameters

### `$url` (optional)

- **Type**: `string|null`
- **Default**: `null`
- **Description**: The URL to send the request to. If `null`, a new `ClientHandler` instance is returned for fluent API usage.

### `$options` (optional)

- **Type**: `array|null`
- **Default**: `[]`
- **Description**: An associative array of request options.

## Return Value

- When `$url` is provided: Returns a `ResponseInterface` instance with the response from the request
- When `$url` is `null`: Returns a `ClientHandler` instance for building requests using the fluent API

## Request Options

The `$options` array accepts the following configuration parameters:

### Core Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `method` | string | `'GET'` | HTTP method (GET, POST, PUT, PATCH, DELETE, etc.) |
| `headers` | array | `[]` | Associative array of HTTP headers |
| `body` | mixed | `null` | Request body content (arrays are automatically JSON-encoded) |
| `timeout` | int | `30` | Request timeout in seconds |
| `retries` | int | `1` | Number of retry attempts for failed requests |
| `retry_delay` | int | `100` | Delay between retries in milliseconds |
| `base_uri` | string | `null` | Base URI to prepend to the URL |
| `query` | array | `null` | Associative array of query parameters |

### Authentication Options

| Option | Type | Description |
|--------|------|-------------|
| `auth` | array | Basic authentication credentials as `[username, password]` |

### Advanced Options

| Option | Type | Description |
|--------|------|-------------|
| `proxy` | string\|array | Proxy server configuration |
| `cookies` | bool\|CookieJarInterface | Cookie handling configuration |
| `allow_redirects` | bool\|array | Control redirect behavior |
| `verify` | bool\|string | SSL certificate verification settings |
| `cert` | string\|array | SSL client certificate |
| `ssl_key` | string\|array | SSL client key |
| `stream` | bool | Stream the response instead of loading it all into memory |
| `async` | bool | Set to `true` to make the request asynchronous |
| `http_errors` | bool | Whether to throw exceptions for 4xx/5xx responses |
| `form_params` | array | For `application/x-www-form-urlencoded` requests |
| `multipart` | array | For `multipart/form-data` requests (file uploads) |
| `json` | mixed | JSON data to send as the request body |
| `client` | ClientInterface | Custom Guzzle client instance |

## Basic Examples

### Simple GET Request

```php
$response = fetch('https://api.example.com/users');
$users = $response->json();
```

### POST Request with JSON Body

```php
$response = fetch('https://api.example.com/users', [
    'method' => 'POST',
    'body' => [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]
]);

$newUser = $response->json();
```

### Setting Request Headers

```php
$response = fetch('https://api.example.com/users', [
    'headers' => [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer your-token-here'
    ]
]);
```

### Using Query Parameters

```php
$response = fetch('https://api.example.com/users', [
    'query' => [
        'page' => 1,
        'limit' => 10,
        'sort' => 'name'
    ]
]);
```

### Authentication

```php
// Basic authentication
$response = fetch('https://api.example.com/secure', [
    'auth' => ['username', 'password']
]);

// OAuth Bearer token
$response = fetch('https://api.example.com/profile', [
    'headers' => [
        'Authorization' => 'Bearer your-token-here'
    ]
]);
```

### File Uploads

```php
$response = fetch('https://api.example.com/upload', [
    'method' => 'POST',
    'multipart' => [
        [
            'name' => 'file',
            'contents' => fopen('/path/to/file.jpg', 'r'),
            'filename' => 'upload.jpg',
        ],
        [
            'name' => 'description',
            'contents' => 'File description',
        ]
    ]
]);
```

### Error Handling

```php
try {
    $response = fetch('https://api.example.com/users/999');

    if ($response->ok()) {
        $user = $response->json();
    } else {
        echo "HTTP Error: " . $response->status() . " " . $response->statusText();
    }
} catch (\Throwable $e) {
    echo "Request failed: " . $e->getMessage();
}
```

## Advanced Examples

### Using Base URI

```php
$response = fetch('users', [
    'base_uri' => 'https://api.example.com/'
]);
```

### Setting Timeout and Retries

```php
$response = fetch('https://api.example.com/unstable', [
    'timeout' => 5,           // 5 second timeout
    'retries' => 3,           // Retry up to 3 times
    'retry_delay' => 100      // Start with 100ms delay (doubles each retry)
]);
```

### Using a Custom Guzzle Client

```php
use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://api.example.com/',
    'timeout' => 5,
    'headers' => [
        'User-Agent' => 'My-App/1.0'
    ]
]);

$response = fetch('users', [
    'client' => $client
]);
```

### Disabling HTTP Error Exceptions

```php
$response = fetch('https://api.example.com/users/999', [
    'http_errors' => false  // Don't throw exceptions for 4xx/5xx responses
]);

if ($response->ok()) {
    // Process successful response
} else {
    // Handle error response
    echo "Error: " . $response->status() . " " . $response->statusText();
}
```

## Using the Fluent API

The `fetch()` function can be called without a URL to return a `ClientHandler` instance for fluent API usage:

```php
$response = fetch()
    ->baseUri('https://api.example.com')
    ->withHeaders([
        'Accept' => 'application/json',
        'X-API-Key' => 'your-api-key'
    ])
    ->withToken('your-oauth-token')
    ->withQueryParameters([
        'page' => 1,
        'limit' => 10
    ])
    ->get('/users');

$users = $response->json();
```

See the [ClientHandler API](./client-handler.md) for more details on the fluent API.

## Asynchronous Requests

To make asynchronous requests, you can use `fetch()` with the Matrix package:

```php
use function async;
use function await;

// Promise-based approach
$promise = async(fn () => fetch('https://api.example.com/users'));

$promise
    ->then(fn ($response) => $response->json())
    ->then(fn ($users) => print_r($users))
    ->catch(fn ($error) => echo "Error: " . $error->getMessage());

// Async/await approach
try {
    $response = await(async(fn () => fetch('https://api.example.com/users')));
    $users = $response->json();
    print_r($users);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
```

See the [Asynchronous API](./asynchronous.md) for more details on asynchronous requests.

### **`$url`** (string|null)

- The URL to which the HTTP request will be sent.
- If `null`, the function returns a `ClientHandler` instance, allowing for fluent chaining of methods before sending the request.

### **`$options`** (array|null)

- An associative array of options to customize the request.
- These options include HTTP method, headers, body, and other configurations.
- If not provided, default options are merged with any specified values.

---

## Return Type

- **`Response`**: If a URL is provided, the function sends an HTTP request and returns a `Fetch\Http\Response` object that contains the response data.
- **`ClientHandler`**: If no URL is provided, the function returns a `Fetch\Http\ClientHandler` object to allow for further configuration of the request before sending it.

---

## Behavior

The `fetch()` function sends an HTTP request and handles several common use cases, including:

1. **Method Specification**:
   - The HTTP method (e.g., `GET`, `POST`, `PUT`, `DELETE`) is specified in the `$options` array using the `'method'` key.
   - If no method is specified, `GET` is used by default.
   - The method is automatically uppercased for consistency.

2. **JSON Handling**:
   - If the `body` in the options array is an associative array, the function automatically converts it to a JSON string and sets the `Content-Type` header to `application/json`.

3. **Base URI Handling**:
   - If a `base_uri` is provided in the options, the function will append the URL to this base URI. The `base_uri` is removed from the options after concatenation.

4. **Exception Handling**:
   - The function catches any exceptions thrown during the request.
   - If the exception is a `RequestException` (from Guzzle) and a response is available, the function returns the response.
   - Otherwise, it rethrows the exception for further handling.

---

## Usage Examples

### **Basic GET Request**

```php
$response = fetch('https://example.com/api/resource');

if ($response->ok()) {
    $data = $response->json();
    print_r($data);
} else {
    echo "Error: " . $response->statusText();
}
```

### **POST Request with JSON Body**

```php
$response = fetch('https://example.com/api/resource', [
    'method' => 'POST',
    'headers' => [
        'Content-Type' => 'application/json',
    ],
    'body' => ['key' => 'value'],  // Automatically converted to JSON
]);

$data = $response->json();
echo $data['key'];
```

### **Using `ClientHandler` for Fluent API**

```php
$response = fetch()
    ->baseUri('https://example.com')
    ->withHeaders('Content-Type', 'application/json')
    ->withBody(['key' => 'value'])
    ->withToken('fake-bearer-auth-token')
    ->post('/posts');

$data = $response->json();
```

### **Error Handling**

```php
try {
    $response = fetch('https://example.com/nonexistent');

    if ($response->ok()) {
        $data = $response->json();
    } else {
        echo "Error: " . $response->statusText();
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
```

---

## Available Options

The `$options` array supports the following keys:

### **`method`** (string)

- The HTTP method to be used for the request (e.g., `GET`, `POST`, `PUT`, `DELETE`).
- Default: `GET`.

### **`headers`** (array)

- An associative array of headers to include in the request.
- Example: `['Authorization' => 'Bearer token']`.

### **`body`** (mixed)

- The body of the request. If this is an associative array, it will be converted to JSON and the `Content-Type` header will be set automatically.

### **`timeout`** (int)

- Timeout for the request in seconds.
- Default: 30 seconds.

### **`auth`** (array)

- An array for HTTP Basic or Digest authentication.
- Example: `['username', 'password']`.

### **`proxy`** (string|array)

- A proxy server URL or an associative array of proxy configurations.
- Example: `'tcp://localhost:8080'`.

### **`base_uri`** (string)

- A base URI to prepend to the URL for the request.
- Example: `'https://api.example.com'`.

### **`http_errors`** (bool)

- Set to `false` to disable throwing exceptions on HTTP error responses (4xx, 5xx).
- Default: `true`.

---

## Handling Responses

FetchPHP provides several methods for handling the response:

- **`json()`**: Parses the response body as JSON.
- **`text()`**: Returns the raw response body as plain text.
- **`statusText()`**: Returns the status text of the response (e.g., "OK" for 200 responses).
- **`ok()`**: Returns `true` if the response status code is 2xx.
- **`status()`**: Retrieves the HTTP status code of the response (e.g., 200, 404).
- **`headers()`**: Retrieves the response headers as an associative array.

---

## Error Handling

### **HTTP Errors**

FetchPHP throws exceptions for HTTP errors by default (4xx and 5xx status codes). This behavior can be disabled by setting the `http_errors` option to `false`.

```php
$response = fetch('https://example.com/not-found', [
    'http_errors' => false
]);

if (!$response->ok()) {
    echo "Error: " . $response->statusText();
}
```

### **Exception Handling**

Exceptions thrown by FetchPHP, such as network issues or invalid responses, can be caught using a `try/catch` block.

```php
try {
    $response = fetch('https://example.com/api');
    echo $response->text();
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
```
