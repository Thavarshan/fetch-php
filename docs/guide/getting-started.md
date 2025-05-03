# Guide

## Installation

```bash
composer require jerome/fetch-php
```

::: info Requirements
- PHP 8.1 or higher
- The `sockets` PHP extension enabled
:::

## Basic Concepts

Fetch PHP provides a JavaScript-like `fetch()` function for making HTTP requests. The library supports both synchronous and asynchronous operations:

```php
// Synchronous request
$response = fetch('https://api.example.com/users');

// Asynchronous request
use function async;
use function await;

$response = await(async(fn () => fetch('https://api.example.com/users')));
```

### The fetch() Function

The core of the library is the `fetch()` function, which works in two modes:

1. **URL Mode**: When provided with a URL, it immediately sends a request
   ```php
   $response = fetch('https://api.example.com/users');
   ```

2. **Client Mode**: When called without a URL, it returns a `ClientHandler` instance for fluent API usage
   ```php
   $client = fetch();
   $response = $client->get('https://api.example.com/users');
   ```

### Request Options

The `fetch()` function accepts an options array as its second parameter:

```php
$response = fetch('https://api.example.com/users', [
    'method' => 'POST',
    'headers' => ['Content-Type' => 'application/json'],
    'body' => ['name' => 'John Doe', 'email' => 'john@example.com'],
    'timeout' => 30,
    'retries' => 3
]);
```

Common options include:

- `method`: HTTP method (GET, POST, PUT, etc.)
- `headers`: Request headers as an associative array
- `body`: Request body (arrays are automatically JSON-encoded)
- `timeout`: Request timeout in seconds
- `retries`: Number of retry attempts for failed requests
- `retry_delay`: Delay between retries in milliseconds

### Response Handling

The `fetch()` function returns a `Response` object with methods for inspecting and processing the response:

```php
$response = fetch('https://api.example.com/users');

// Check if request was successful
if ($response->ok()) {
    // Get response body as JSON
    $users = $response->json();

    // Get response status code
    $statusCode = $response->status();

    // Get response headers
    $contentType = $response->header('Content-Type');
}
```

## Making Requests

### GET Requests

```php
// Simple GET request
$response = fetch('https://api.example.com/users');

// With query parameters
$response = fetch('https://api.example.com/users?page=1&limit=10');

// Using fluent API
$response = fetch()
    ->withQueryParameters(['page' => 1, 'limit' => 10])
    ->get('https://api.example.com/users');
```

### POST Requests

```php
// POST with JSON body
$response = fetch('https://api.example.com/users', [
    'method' => 'POST',
    'headers' => ['Content-Type' => 'application/json'],
    'body' => ['name' => 'John Doe', 'email' => 'john@example.com'],
]);

// Using fluent API
$response = fetch()
    ->withJson(['name' => 'John Doe', 'email' => 'john@example.com'])
    ->post('https://api.example.com/users');
```

### Other HTTP Methods

```php
// PUT request
$response = fetch()
    ->withJson(['name' => 'John Doe', 'email' => 'john@example.com'])
    ->put('https://api.example.com/users/1');

// PATCH request
$response = fetch()
    ->withJson(['name' => 'John Doe'])
    ->patch('https://api.example.com/users/1');

// DELETE request
$response = fetch()
    ->delete('https://api.example.com/users/1');
```

## Authentication

### Bearer Token

```php
$response = fetch('https://api.example.com/profile', [
    'headers' => ['Authorization' => 'Bearer your-token-here']
]);

// Using fluent API
$response = fetch()
    ->withToken('your-token-here')
    ->get('https://api.example.com/profile');
```

### Basic Authentication

```php
$response = fetch('https://api.example.com/secure', [
    'auth' => ['username', 'password']
]);

// Using fluent API
$response = fetch()
    ->withAuth('username', 'password')
    ->get('https://api.example.com/secure');
```

## Error Handling

### Synchronous Error Handling

```php
try {
    $response = fetch('https://api.example.com/users/999');

    if ($response->ok()) {
        $user = $response->json();
    } else {
        echo "Request failed with status: " . $response->status();
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
```

### Request Retries

Fetch PHP automatically retries failed requests based on your configuration:

```php
// Retry up to 3 times with exponential backoff
$response = fetch('https://api.example.com/unstable', [
    'retries' => 3,
    'retry_delay' => 100 // 100ms initial delay, doubles each retry
]);

// Using fluent API
$response = fetch()
    ->retry(3, 100)
    ->get('https://api.example.com/unstable');
```

## Asynchronous Requests

Fetch PHP provides true asynchronous HTTP requests using PHP Fibers through the Matrix package.

### Promise-based Approach

```php
use function async;
use Fetch\Interfaces\Response as ResponseInterface;

// Create an async task that returns a promise
$promise = async(fn () => fetch('https://api.example.com/users'));

// Handle the promise resolution
$promise->then(function (ResponseInterface $response) {
    $users = $response->json();
    echo "Fetched " . count($users) . " users";
})->catch(function (\Throwable $e) {
    echo "Error: " . $e->getMessage();
});
```

### Async/Await Approach

```php
use function async;
use function await;

try {
    // Await the promise resolution
    $response = await(async(fn () => fetch('https://api.example.com/users')));
    $users = $response->json();
    echo "Fetched " . count($users) . " users";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
```

### Fluent API with Async

```php
use function async;
use function await;

// Create an async task with fluent API
$response = await(async(fn () => fetch()
    ->withToken('your-token-here')
    ->get('https://api.example.com/users')));

$users = $response->json();
```

## Concurrent Requests

Fetch PHP allows you to run multiple requests concurrently and wait for their results.

### Wait for All Requests

```php
use function async;
use function await;
use function all;

// Create multiple promises
$usersPromise = async(fn () => fetch('https://api.example.com/users'));
$postsPromise = async(fn () => fetch('https://api.example.com/posts'));
$commentsPromise = async(fn () => fetch('https://api.example.com/comments'));

// Wait for all promises to resolve
$results = await(all([
    'users' => $usersPromise,
    'posts' => $postsPromise,
    'comments' => $commentsPromise
]));

// Access the results
$users = $results['users']->json();
$posts = $results['posts']->json();
$comments = $results['comments']->json();
```

### Race Requests

```php
use function async;
use function await;
use function race;

// Create multiple promises
$usersPromise = async(fn () => fetch('https://api.example.com/users'));
$postsPromise = async(fn () => fetch('https://api.example.com/posts'));

// Get the first promise to resolve
$firstResponse = await(race([$usersPromise, $postsPromise]));
$data = $firstResponse->json();
```

### First Successful Request

```php
use function async;
use function await;
use function any;

// Create multiple promises
$promises = [
    async(fn () => fetch('https://api.example.com/endpoint1')),
    async(fn () => fetch('https://api.example.com/endpoint2')),
    async(fn () => fetch('https://api.example.com/endpoint3'))
];

// Get the first promise to succeed (ignoring failures)
$firstSuccess = await(any($promises));
$data = $firstSuccess->json();
```

## Advanced Features

### File Uploads

```php
$response = fetch()
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

### Proxy Support

```php
$response = fetch('https://api.example.com', [
    'proxy' => 'http://proxy.example.com:8080'
]);

// Using fluent API
$response = fetch()
    ->withProxy('http://proxy.example.com:8080')
    ->get('https://api.example.com');
```

### Handling Redirects

```php
// Allow redirects (default)
$response = fetch('https://api.example.com/redirecting-url');

// Disable redirects
$response = fetch('https://api.example.com/redirecting-url', [
    'allow_redirects' => false
]);

// Custom redirect behavior
$response = fetch('https://api.example.com/redirecting-url', [
    'allow_redirects' => [
        'max' => 5,       // Maximum number of redirects
        'strict' => true, // Strict RFC compliant redirects
        'referer' => true // Add a Referer header
    ]
]);
```

### Streaming Responses

```php
$response = fetch()
    ->withStream(true)
    ->get('https://api.example.com/large-file');

$stream = $response->getBody();
while (!$stream->eof()) {
    echo $stream->read(1024);
}
```

### Debugging Requests

```php
$handler = fetch();
$debugInfo = $handler->debug();
print_r($debugInfo);

/* Output:
[
    'uri' => 'https://api.example.com/users',
    'method' => 'GET',
    'headers' => ['Accept' => 'application/json'],
    'options' => [...]
]
*/
```
