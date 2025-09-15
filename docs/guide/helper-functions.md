---
title: Helper Functions
description: Learn about the helper functions available in the Fetch HTTP package
---

# Helper Functions

The Fetch HTTP package provides a set of global helper functions that offer a simpler, more direct way to make HTTP requests. These functions are designed to be familiar for developers who have experience with JavaScript's fetch API or other HTTP client libraries.

## Main Functions

### `fetch()`

The primary helper function that mimics JavaScript's `fetch()` API. It provides a simple yet powerful way to make HTTP requests.

```php
function fetch(
    string|RequestInterface|null $resource = null,
    ?array $options = []
): ResponseInterface|ClientHandlerInterface|Client
```

#### Parameters

- `$resource`: A URL string, a `Request` object, or `null` to return the client for chaining
- `$options`: An array of request options, including:
  - `method`: HTTP method (string or `Method` enum)
  - `headers`: Request headers (array)
  - `body`: Request body (mixed)
  - `json`: JSON data to send as body (array, takes precedence over body)
  - `form`: Form data to send as body (array, takes precedence if no json)
  - `multipart`: Multipart form data (array, takes precedence if no json/form)
  - `query`: Query parameters (array)
  - `base_uri`: Base URI (string)
  - `timeout`: Request timeout in seconds (int)
  - `retries`: Number of retries (int)
  - `auth`: Basic auth credentials [username, password] (array)
  - `token`: Bearer token (string)
  - Plus other options like `proxy`, `cookies`, `allow_redirects`, etc.

#### Return Value

- If `$resource` is `null`: Returns the client instance for method chaining
- If `$resource` is a URL string: Returns a `Response` object
- If `$resource` is a `Request` object: Returns a `Response` object

#### Examples

Basic GET request:

```php
$response = fetch('https://api.example.com/users');
$users = $response->json();
```

POST request with JSON data:

```php
$response = fetch('https://api.example.com/users', [
    'method' => 'POST',
    'json' => ['name' => 'John Doe', 'email' => 'john@example.com']
]);
```

Setting headers:

```php
$response = fetch('https://api.example.com/users', [
    'headers' => [
        'Authorization' => 'Bearer token123',
        'Accept' => 'application/json'
    ]
]);
```

Using query parameters:

```php
$response = fetch('https://api.example.com/users', [
    'query' => ['page' => 1, 'per_page' => 20]
]);
```

Method chaining:

```php
fetch()
    ->withToken('your-token')
    ->withHeader('Accept', 'application/json')
    ->get('https://api.example.com/users');
```

### `fetch_client()`

Gets or configures the global fetch client instance.

```php
function fetch_client(
    ?array $options = null,
    bool $reset = false
): Client
```

#### Parameters

- `$options`: Global client options
- `$reset`: Whether to reset the client instance

#### Return Value

Returns the global `Client` instance.

#### Examples

Get the default client:

```php
$client = fetch_client();
```

Configure the global client:

```php
fetch_client([
    'base_uri' => 'https://api.example.com',
    'timeout' => 10,
    'headers' => [
        'User-Agent' => 'MyApp/1.0'
    ]
]);

// Now all fetch() calls will use these settings by default
$response = fetch('/users'); // Uses the base_uri
```

Reset the client:

```php
fetch_client(reset: true);
```

Add a logger:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('http');
$logger->pushHandler(new StreamHandler('logs/http.log', Logger::DEBUG));

// Set the logger on the client
$client = fetch_client();
$client->setLogger($logger);
```

## HTTP Method Helpers

The package provides shorthand functions for common HTTP methods, making your code more concise and readable.

### `get()`

```php
function get(
    string $url,
    ?array $query = null,
    ?array $options = []
): ResponseInterface
```

#### Examples

Simple GET request:

```php
$response = get('https://api.example.com/users');
```

GET with query parameters:

```php
$response = get('https://api.example.com/users', [
    'page' => 1,
    'per_page' => 20
]);
```

GET with additional options:

```php
$response = get('https://api.example.com/users', null, [
    'headers' => ['Accept' => 'application/json'],
    'timeout' => 5
]);
```

### `post()`

```php
function post(
    string $url,
    mixed $data = null,
    ?array $options = []
): ResponseInterface
```

#### Examples

POST with JSON data (arrays are automatically converted to JSON):

```php
$response = post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

POST with raw data:

```php
$response = post('https://api.example.com/webhook', 'Raw content', [
    'headers' => ['Content-Type' => 'text/plain']
]);
```

POST with form data:

```php
$response = post('https://api.example.com/login', null, [
    'form' => [
        'username' => 'johndoe',
        'password' => 'secret'
    ]
]);
```

### `put()`

```php
function put(
    string $url,
    mixed $data = null,
    ?array $options = []
): ResponseInterface
```

#### Examples

```php
$response = put('https://api.example.com/users/123', [
    'name' => 'John Smith',
    'email' => 'john.smith@example.com'
]);
```

### `patch()`

```php
function patch(
    string $url,
    mixed $data = null,
    ?array $options = []
): ResponseInterface
```

#### Examples

```php
$response = patch('https://api.example.com/users/123', [
    'status' => 'active'
]);
```

### `delete()`

```php
function delete(
    string $url,
    mixed $data = null,
    ?array $options = []
): ResponseInterface
```

#### Examples

Simple DELETE:

```php
$response = delete('https://api.example.com/users/123');
```

DELETE with body:

```php
$response = delete('https://api.example.com/users/batch', [
    'ids' => [123, 456, 789]
]);
```

## Async/Promise Functions

The Fetch PHP package includes a set of functions for working with asynchronous requests and promises.

### `async()`

Wraps a function to run asynchronously and returns a promise.

```php
function async(callable $fn): PromiseInterface
```

#### Example

```php
$promise = async(function() {
    return fetch('https://api.example.com/users');
});
```

### `await()`

Waits for a promise to resolve and returns its value.

```php
function await(PromiseInterface $promise): mixed
```

#### Example

```php
$response = await(async(function() {
    return fetch('https://api.example.com/users');
}));

$users = $response->json();
```

### `all()`

Executes multiple promises concurrently and waits for all to complete.

```php
function all(array $promises): PromiseInterface
```

#### Example

```php
$results = await(all([
    'users' => async(fn() => fetch('https://api.example.com/users')),
    'posts' => async(fn() => fetch('https://api.example.com/posts'))
]));

$users = $results['users']->json();
$posts = $results['posts']->json();
```

### `race()`

Executes multiple promises concurrently and returns the first to complete.

```php
function race(array $promises): PromiseInterface
```

#### Example

```php
$response = await(race([
    async(fn() => fetch('https://api1.example.com/data')),
    async(fn() => fetch('https://api2.example.com/data'))
]));
```

### `any()`

Executes multiple promises concurrently and returns the first to succeed.

```php
function any(array $promises): PromiseInterface
```

#### Example

```php
$response = await(any([
    async(fn() => fetch('https://api1.example.com/data')),
    async(fn() => fetch('https://api2.example.com/data'))
]));
```

### `map()`

Maps an array of items through an async function with controlled concurrency.

```php
function map(array $items, callable $callback, int $concurrency = 5): PromiseInterface
```

#### Example

```php
$responses = await(map([1, 2, 3, 4, 5], function($id) {
    return async(function() use ($id) {
        return fetch("https://api.example.com/users/{$id}");
    });
}, 3)); // Process at most 3 items concurrently
```

### `batch()`

Processes items in batches with controlled concurrency.

```php
function batch(
    array $items,
    callable $callback,
    int $batchSize = 10,
    int $concurrency = 5
): PromiseInterface
```

#### Example

```php
$results = await(batch(
    [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    function($batch) {
        return async(function() use ($batch) {
            $results = [];
            foreach ($batch as $id) {
                $response = await(async(fn() =>
                    fetch("https://api.example.com/users/{$id}")
                ));
                $results[] = $response->json();
            }
            return $results;
        });
    },
    3, // batch size
    2  // concurrency
));
```

### `retry()`

Retries an async operation with exponential backoff.

```php
function retry(
    callable $fn,
    int $attempts = 3,
    callable|int $delay = 100
): PromiseInterface
```

#### Example

```php
$response = await(retry(
    function() {
        return async(function() {
            return fetch('https://api.example.com/unstable-endpoint');
        });
    },
    3, // max attempts
    function($attempt) {
        // Exponential backoff strategy
        return min(pow(2, $attempt) * 100, 1000);
    }
));
```

## Using Helper Functions in Real-World Examples

### Basic API Interaction

```php
// Fetch a list of users
$users = get('https://api.example.com/users')->json();

// Create a new user
$user = post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
])->json();

// Update a user
$updatedUser = put("https://api.example.com/users/{$user['id']}", [
    'name' => 'John Smith'
])->json();

// Delete a user
delete("https://api.example.com/users/{$user['id']}");
```

### Authentication

```php
// Configure client with authentication
fetch_client([
    'base_uri' => 'https://api.example.com',
    'headers' => [
        'Authorization' => 'Bearer your-oauth-token'
    ]
]);

// All requests now include authentication
$response = get('/protected-resource');

// Or using the withToken method
$response = fetch_client()
    ->withToken('your-oauth-token')
    ->get('/protected-resource');

// Basic authentication
$response = fetch_client()
    ->withAuth('username', 'password')
    ->get('/protected-resource');
```

### File Upload

```php
$response = fetch('https://api.example.com/upload', [
    'method' => 'POST',
    'multipart' => [
        [
            'name' => 'file',
            'contents' => file_get_contents('/path/to/image.jpg'),
            'filename' => 'upload.jpg',
            'headers' => ['Content-Type' => 'image/jpeg']
        ],
        [
            'name' => 'description',
            'contents' => 'Profile picture'
        ]
    ]
]);

// Using the fluent interface
$response = fetch_client()
    ->withMultipart([
        [
            'name' => 'file',
            'contents' => fopen('/path/to/image.jpg', 'r'),
            'filename' => 'upload.jpg',
        ],
        [
            'name' => 'description',
            'contents' => 'Profile picture'
        ]
    ])
    ->post('https://api.example.com/upload');

// Check if upload was successful
if ($response->successful()) {
    $fileUrl = $response->json()['url'];
    echo "File uploaded successfully: {$fileUrl}";
}
```

### Error Handling

```php
try {
    $response = get('https://api.example.com/users/999');

    if ($response->isNotFound()) {
        echo "User not found!";
    } elseif ($response->isUnauthorized()) {
        echo "Authentication required!";
    } elseif ($response->failed()) {
        echo "Request failed with status: " . $response->status();
    } else {
        $user = $response->json();
        echo "Found user: " . $user['name'];
    }
} catch (\Fetch\Exceptions\NetworkException $e) {
    echo "Network error: " . $e->getMessage();
} catch (\Fetch\Exceptions\RequestException $e) {
    echo "Request error: " . $e->getMessage();
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Parallel Requests with Modern Async/Await

```php
use function async;
use function await;
use function all;

await(async(function() {
    // Create multiple requests
    $results = await(all([
        'users' => async(fn() => fetch('https://api.example.com/users')),
        'posts' => async(fn() => fetch('https://api.example.com/posts')),
        'comments' => async(fn() => fetch('https://api.example.com/comments'))
    ]));

    // Process the results
    $users = $results['users']->json();
    $posts = $results['posts']->json();
    $comments = $results['comments']->json();

    echo "Fetched " . count($users) . " users, " .
         count($posts) . " posts, and " .
         count($comments) . " comments";
}));
```

### Working with Content Types

```php
use Fetch\Enum\ContentType;

// Using string content types
$response = fetch_client()
    ->withBody($data, 'application/json')
    ->post('https://api.example.com/users');

// Using enum content types
$response = fetch_client()
    ->withBody($data, ContentType::JSON)
    ->post('https://api.example.com/users');

// Checking content type
if ($response->hasJsonContent()) {
    $data = $response->json();
} elseif ($response->hasHtmlContent()) {
    $html = $response->text();
}
```

### Working with Enums

```php
use Fetch\Enum\Method;
use Fetch\Enum\ContentType;
use Fetch\Enum\Status;

// Use method enum
$response = fetch_client()->request(Method::POST, '/users', $userData);

// Check status with enum
if ($response->statusEnum() === Status::OK) {
    // Process successful response
}

// Content type handling
$response = fetch_client()
    ->withBody($data, ContentType::JSON)
    ->post('/users');
```

## Tips and Best Practices

1. **Configure Once, Use Everywhere**: Use `fetch_client()` to set global options and defaults that will apply to all requests.

   ```php
   // Set up once at the beginning of your application
   fetch_client([
       'base_uri' => 'https://api.example.com',
       'timeout' => 10,
       'headers' => [
           'User-Agent' => 'MyApp/1.0',
           'Accept' => 'application/json'
       ]
   ]);

   // Now use simplified calls throughout your code
   $users = get('/users')->json();
   $user = get("/users/{$id}")->json();
   ```

2. **Use Type-Safe Enums**: Take advantage of PHP 8.2 enums for better type safety and code readability.

   ```php
   use Fetch\Enum\Method;
   use Fetch\Enum\ContentType;
   use Fetch\Enum\Status;

   $response = fetch_client()->request(Method::POST, '/users', $userData);

   if ($response->statusEnum() === Status::CREATED) {
       // User was created successfully
   }
   ```

3. **Use the Right Helper for the Job**: Choose the appropriate helper function based on the HTTP method you need.

4. **Handling JSON**: Arrays passed to `post()`, `put()`, `patch()`, and `delete()` are automatically treated as JSON data.

5. **Leverage Method Chaining**: When you need more control, use the fluent interface:

   ```php
   fetch()
       ->baseUri('https://api.example.com')
       ->withToken('your-token')
       ->timeout(5)
       ->retry(3, 100)
       ->get('/users');
   ```

6. **Use the Response Methods**: Take advantage of the response helper methods for cleaner code:

   ```php
   // Instead of:
   if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
       // ...
   }

   // Use:
   if ($response->successful()) {
       // ...
   }
   ```

7. **Modern Async/Await**: Use `async()` and `await()` for cleaner asynchronous code:

   ```php
   await(async(function() {
       $response = await(async(fn() => fetch('https://api.example.com/users')));
       return $response->json();
   }));
   ```

8. **Resource Conservation**: When working with many requests, use controlled concurrency with `map()` or `batch()`:

   ```php
   $results = await(map($items, function($item) {
       return async(fn() => fetch("https://api.example.com/{$item}"));
   }, 5)); // No more than 5 concurrent requests
   ```

## Next Steps

- Learn more about [Working with Responses](/guide/working-with-responses)
- Explore [Asynchronous Requests](/guide/async-requests) for parallel HTTP operations
- Discover [Retry Handling](/guide/retry-handling) for dealing with unreliable APIs
- Learn about [Working with Enums](/guide/working-with-enums) for type-safe HTTP operations
