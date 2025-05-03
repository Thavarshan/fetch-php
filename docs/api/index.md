# API Reference

This API reference provides detailed documentation for all components of the Fetch PHP library. Use this reference to understand the available functions, classes, and methods for making HTTP requests in PHP with a JavaScript-like interface.

## Core Components

### [`fetch()` Function](./fetch.md)

The primary entry point for making HTTP requests, inspired by JavaScript's fetch API:

```php
// Basic GET request
$response = fetch('https://api.example.com/users');

// POST request with JSON body
$response = fetch('https://api.example.com/users', [
    'method' => 'POST',
    'body' => ['name' => 'John Doe', 'email' => 'john@example.com'],
]);

// Return a ClientHandler for fluent API usage
$client = fetch();
$response = $client->get('https://api.example.com/users');
```

### [ClientHandler Class](./client-handler.md)

A powerful class for building and sending HTTP requests with a fluent, chainable API:

```php
$response = fetch()
    ->baseUri('https://api.example.com')
    ->withHeaders(['Accept' => 'application/json'])
    ->withToken('your-access-token')
    ->withJson(['name' => 'John Doe', 'email' => 'john@example.com'])
    ->post('/users');
```

### [Response Class](./response.md)

Comprehensive methods for working with HTTP responses:

```php
$response = fetch('https://api.example.com/users/1');

// Check response status
if ($response->ok()) {
    // Parse JSON response
    $user = $response->json();

    // Access response properties
    $id = $user['id'];
    $name = $user['name'];

    // Check specific status codes
    if ($response->isCreated()) {
        echo "New resource created!";
    }
}
```

### [Asynchronous API](./async.md)

Methods for non-blocking HTTP requests powered by PHP Fibers:

```php
use function Matrix\async;
use function Matrix\await;

// Promise-based pattern
async(fn () => fetch('https://api.example.com/users'))
    ->then(fn ($response) => $response->json())
    ->catch(fn ($error) => handleError($error));

// Async/await pattern
try {
    $response = await(async(fn () => fetch('https://api.example.com/users')));
    $users = $response->json();
} catch (\Throwable $e) {
    handleError($e);
}
```

### [Promise Operations](./promises.md)

Utilities for working with multiple promises:

```php
use function Matrix\async;
use function Matrix\await;
use function Matrix\all;

// Run multiple requests in parallel
$userPromise = async(fn () => fetch('https://api.example.com/users'));
$postsPromise = async(fn () => fetch('https://api.example.com/posts'));

// Wait for all to complete
$results = await(all([
    'users' => $userPromise,
    'posts' => $postsPromise,
]));

// Access results
$users = $results['users']->json();
$posts = $results['posts']->json();
```

## Types and Interfaces

Fetch PHP provides several interfaces and types that define the structure of its components:

- `Fetch\Interfaces\ClientHandler` - Interface for the client handler
- `Fetch\Interfaces\Response` - Interface for HTTP responses
- `React\Promise\PromiseInterface` - Interface for promises (provided by React\Promise)

## Error Handling

Fetch PHP provides robust error handling mechanisms:

```php
try {
    $response = fetch('https://api.example.com/nonexistent');

    if ($response->failed()) {
        // Handle HTTP error responses (4xx, 5xx)
        echo "Request failed with status: " . $response->status();
    }
} catch (\Throwable $e) {
    // Handle exceptions (connection errors, timeouts, etc.)
    echo "Error: " . $e->getMessage();
}
```

## Common Patterns

### Making Authenticated Requests

```php
// Using Bearer token
$response = fetch()
    ->withToken('your-access-token')
    ->get('https://api.example.com/profile');

// Using Basic Auth
$response = fetch()
    ->withAuth('username', 'password')
    ->get('https://api.example.com/secure');
```

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

### Handling Large Datasets

```php
// Process a large collection in batches
$page = 1;
$pageSize = 100;
$allItems = [];

do {
    $response = fetch('https://api.example.com/items', [
        'query' => ['page' => $page, 'limit' => $pageSize]
    ]);

    $data = $response->json();
    $items = $data['items'];
    $allItems = array_merge($allItems, $items);

    $hasMorePages = count($items) === $pageSize;
    $page++;
} while ($hasMorePages);
```

### Retrying Failed Requests

```php
$response = fetch()
    ->retry(3, 100)  // 3 retries with 100ms initial delay
    ->get('https://api.example.com/unstable');
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

For more detailed information on each component, follow the links to the specific API documentation pages.
