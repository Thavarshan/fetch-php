# Synchronous Requests

Fetch PHP provides an intuitive API for making synchronous HTTP requests that should feel familiar to JavaScript developers while leveraging the power of PHP. You can choose between using the straightforward `fetch()` function or the more flexible fluent API.

## Basic Usage

### Making GET Requests

The simplest way to make a GET request is to pass a URL to the `fetch()` function:

```php
$response = fetch('https://api.example.com/users');

if ($response->ok()) {
    $data = $response->json();
    print_r($data);
} else {
    echo "Error: " . $response->statusText();
}
```

### Making POST Requests

For POST requests with a JSON body:

```php
$response = fetch('https://api.example.com/users', [
    'method' => 'POST',
    'headers' => [
        'Content-Type' => 'application/json',
    ],
    'body' => ['name' => 'John Doe', 'email' => 'john@example.com'],
]);

if ($response->ok()) {
    $newUser = $response->json();
    echo "Created user with ID: " . $newUser['id'];
}
```

Note that when you pass an array as the `body`, Fetch PHP automatically JSON-encodes it and sets the appropriate `Content-Type` header if not already specified.

### Other HTTP Methods

Fetch PHP supports all standard HTTP methods:

```php
// PUT request
$response = fetch('https://api.example.com/users/1', [
    'method' => 'PUT',
    'body' => ['name' => 'Updated Name'],
]);

// DELETE request
$response = fetch('https://api.example.com/users/1', [
    'method' => 'DELETE',
]);

// PATCH request
$response = fetch('https://api.example.com/users/1', [
    'method' => 'PATCH',
    'body' => ['status' => 'inactive'],
]);
```

## Fluent API

For more complex requests, Fetch PHP offers a fluent API that allows you to chain methods for a more readable syntax.

### Building Requests with the Fluent API

To use the fluent API, call `fetch()` without any parameters to get a `ClientHandler` instance:

```php
$response = fetch()
    ->baseUri('https://api.example.com')
    ->withHeaders(['Accept' => 'application/json'])
    ->withToken('your-access-token')
    ->get('/users');

$users = $response->json();
```

### HTTP Methods with the Fluent API

The fluent API provides dedicated methods for different HTTP methods:

```php
// GET request
$response = fetch()
    ->baseUri('https://api.example.com')
    ->get('/users');

// POST request
$response = fetch()
    ->baseUri('https://api.example.com')
    ->withJson(['name' => 'John Doe', 'email' => 'john@example.com'])
    ->post('/users');

// PUT request
$response = fetch()
    ->baseUri('https://api.example.com')
    ->withJson(['name' => 'Updated Name'])
    ->put('/users/1');

// PATCH request
$response = fetch()
    ->baseUri('https://api.example.com')
    ->withJson(['status' => 'inactive'])
    ->patch('/users/1');

// DELETE request
$response = fetch()
    ->baseUri('https://api.example.com')
    ->delete('/users/1');
```

## Request Configuration

### Adding Headers

```php
// Using options array
$response = fetch('https://api.example.com/users', [
    'headers' => [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer your-token',
        'X-Custom-Header' => 'Value',
    ],
]);

// Using fluent API
$response = fetch()
    ->withHeaders([
        'Accept' => 'application/json',
        'X-Custom-Header' => 'Value',
    ])
    ->withToken('your-token')
    ->get('https://api.example.com/users');

// Adding a single header
$response = fetch()
    ->withHeader('Accept', 'application/json')
    ->get('https://api.example.com/users');
```

### Query Parameters

```php
// Using URL
$response = fetch('https://api.example.com/users?page=1&limit=10');

// Using options array
$response = fetch('https://api.example.com/users', [
    'query' => ['page' => 1, 'limit' => 10],
]);

// Using fluent API
$response = fetch()
    ->withQueryParameters(['page' => 1, 'limit' => 10])
    ->get('https://api.example.com/users');

// Adding a single query parameter
$response = fetch()
    ->withQueryParameter('page', 1)
    ->withQueryParameter('limit', 10)
    ->get('https://api.example.com/users');
```

### Authentication

```php
// Bearer token (Authorization header)
$response = fetch()
    ->withToken('your-access-token')
    ->get('https://api.example.com/users');

// Basic authentication
$response = fetch()
    ->withAuth('username', 'password')
    ->get('https://api.example.com/users');
```

### Request Body

```php
// JSON body (automatically encoded)
$response = fetch()
    ->withJson(['name' => 'John Doe', 'email' => 'john@example.com'])
    ->post('https://api.example.com/users');

// Form parameters (application/x-www-form-urlencoded)
$response = fetch()
    ->withFormParams(['name' => 'John Doe', 'email' => 'john@example.com'])
    ->post('https://api.example.com/users');

// Multipart form data (multipart/form-data)
$response = fetch()
    ->withMultipart([
        [
            'name' => 'file',
            'contents' => fopen('/path/to/file.jpg', 'r'),
            'filename' => 'upload.jpg',
        ],
        [
            'name' => 'name',
            'contents' => 'John Doe',
        ],
    ])
    ->post('https://api.example.com/upload');

// Raw body with custom content type
$response = fetch()
    ->withBody('<user><name>John Doe</name></user>', 'application/xml')
    ->post('https://api.example.com/users');
```

### Timeouts and Retries

```php
// Set request timeout
$response = fetch()
    ->timeout(5) // 5 seconds
    ->get('https://api.example.com/users');

// Configure retry behavior
$response = fetch()
    ->retry(3, 100) // 3 retries with 100ms initial delay
    ->get('https://api.example.com/users');
```

### Proxies and Redirects

```php
// Use a proxy
$response = fetch()
    ->withProxy('http://proxy.example.com:8080')
    ->get('https://api.example.com/users');

// Configure redirects
$response = fetch()
    ->withRedirects(true) // Default: follow redirects
    ->get('https://api.example.com/redirecting-url');

$response = fetch()
    ->withRedirects(false) // Don't follow redirects
    ->get('https://api.example.com/redirecting-url');
```

## Handling Responses

Fetch PHP provides several methods to inspect and process response data:

```php
$response = fetch('https://api.example.com/users/1');

// Check if the request was successful
if ($response->ok()) {
    // HTTP status code (e.g., 200, 201)
    $statusCode = $response->status();

    // Status text (e.g., "OK", "Created")
    $statusText = $response->statusText();

    // Response body as JSON (parsed into array or object)
    $data = $response->json();

    // Response body as string
    $body = $response->body();

    // Get a specific header value
    $contentType = $response->header('Content-Type');

    // Get all headers
    $headers = $response->headers();
}

// Check for specific status categories
if ($response->successful()) {
    // 2xx status code
    echo "Request was successful";
} elseif ($response->clientError()) {
    // 4xx status code
    echo "Client error: " . $response->status();
} elseif ($response->serverError()) {
    // 5xx status code
    echo "Server error: " . $response->status();
}
```

## Error Handling

```php
try {
    $response = fetch('https://api.example.com/nonexistent');

    if ($response->failed()) {
        // Handle HTTP error responses (4xx, 5xx)
        echo "Request failed with status: " . $response->status();

        // You can still access the response body even for error responses
        $errorData = $response->json();
        echo "Error message: " . $errorData['message'] ?? 'Unknown error';
    }
} catch (\Throwable $e) {
    // Handle exceptions (connection errors, timeouts, etc.)
    echo "Error: " . $e->getMessage();
}
```

## Available Fluent API Methods

Here's a complete list of methods available in the fluent API:

### HTTP Methods

- `get(string $uri)`
- `post(string $uri, mixed $body = null, string $contentType = 'application/json')`
- `put(string $uri, mixed $body = null, string $contentType = 'application/json')`
- `patch(string $uri, mixed $body = null, string $contentType = 'application/json')`
- `delete(string $uri)`
- `head(string $uri)`
- `options(string $uri)`

### Request Configuration

- `baseUri(string $baseUri)`
- `withHeaders(array $headers)`
- `withHeader(string $header, mixed $value)`
- `withQueryParameters(array $queryParams)`
- `withQueryParameter(string $name, mixed $value)`
- `withBody(array|string $body, string $contentType = 'application/json')`
- `withJson(array $data)`
- `withFormParams(array $params)`
- `withMultipart(array $multipart)`
- `withToken(string $token)`
- `withAuth(string $username, string $password)`
- `timeout(int $seconds)`
- `retry(int $retries, int $delay = 100)`
- `withProxy(string|array $proxy)`
- `withCookies(bool|CookieJarInterface $cookies)`
- `withRedirects(bool|array $redirects = true)`
- `withCert(string|array $cert)`
- `withSslKey(string|array $sslKey)`
- `withStream(bool $stream)`
- `withOption(string $key, mixed $value)`
- `withOptions(array $options)`

### Utility Methods

- `reset()`
- `debug()`
- `getSyncClient()`
- `setSyncClient(ClientInterface $syncClient)`
- `isAsync()`
- `getOptions()`
- `getHeaders()`
- `hasHeader(string $header)`
- `hasOption(string $option)`
