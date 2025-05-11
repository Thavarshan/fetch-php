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
): ResponseInterface|ClientHandler|Client
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
    ?LoggerInterface $logger = null,
    bool $reset = false
): Client
```

#### Parameters

- `$options`: Global client options
- `$logger`: PSR-3 compatible logger
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

fetch_client(logger: $logger);
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
    'token' => 'your-oauth-token' // Sets Bearer token
]);

// All requests now include authentication
$response = get('/protected-resource');

// Or for a specific request only
$response = get('https://api.example.com/users', null, [
    'headers' => ['Authorization' => 'Bearer different-token']
]);

// Basic authentication
$response = get('https://api.example.com/protected', null, [
    'auth' => ['username', 'password']
]);
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
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Parallel Requests

```php
// Get the client for advanced operations
$client = fetch_client();

// Create promises for multiple requests
$usersPromise = $client->async()->get('https://api.example.com/users');
$postsPromise = $client->async()->get('https://api.example.com/posts');
$commentsPromise = $client->async()->get('https://api.example.com/comments');

// Wait for all to complete
$client->all([
    'users' => $usersPromise,
    'posts' => $postsPromise,
    'comments' => $commentsPromise
])->then(function ($results) {
    $users = $results['users']->json();
    $posts = $results['posts']->json();
    $comments = $results['comments']->json();

    echo "Fetched " . count($users) . " users, " .
         count($posts) . " posts, and " .
         count($comments) . " comments";
});
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

2. **Use the Right Helper for the Job**: Choose the appropriate helper function based on the HTTP method you need.

3. **Handling JSON**: Arrays passed to `post()`, `put()`, `patch()`, and `delete()` are automatically treated as JSON data.

4. **Leverage Method Chaining**: When you need more control, use the fluent interface:

   ```php
   fetch()
       ->baseUri('https://api.example.com')
       ->withToken('your-token')
       ->timeout(5)
       ->retry(3, 100)
       ->get('/users');
   ```

5. **Use the Response Methods**: Take advantage of the response helper methods for cleaner code:

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

6. **Resource Conservation**: When working with many requests, consider using async mode to improve performance.

## Next Steps

- Learn more about [Working with Responses](/guide/working-with-responses)
- Explore [Asynchronous Requests](/guide/async-requests) for parallel HTTP operations
- Discover [Retry Handling](/guide/retry-handling) for dealing with unreliable APIs $url,
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
    'token' => 'your-oauth-token' // Sets Bearer token
]);

// All requests now include authentication
$response = get('/protected-resource');

// Or for a specific request only
$response = get('https://api.example.com/users', null, [
    'headers' => ['Authorization' => 'Bearer different-token']
]);

// Basic authentication
$response = get('https://api.example.com/protected', null, [
    'auth' => ['username', 'password']
]);
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
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Parallel Requests

```php
// Get the client for advanced operations
$client = fetch_client();

// Create promises for multiple requests
$usersPromise = $client->async()->get('https://api.example.com/users');
$postsPromise = $client->async()->get('https://api.example.com/posts');
$commentsPromise = $client->async()->get('https://api.example.com/comments');

// Wait for all to complete
$client->all([
    'users' => $usersPromise,
    'posts' => $postsPromise,
    'comments' => $commentsPromise
])->then(function ($results) {
    $users = $results['users']->json();
    $posts = $results['posts']->json();
    $comments = $results['comments']->json();

    echo "Fetched " . count($users) . " users, " .
         count($posts) . " posts, and " .
         count($comments) . " comments";
});
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

2. **Use the Right Helper for the Job**: Choose the appropriate helper function based on the HTTP method you need.

3. **Handling JSON**: Arrays passed to `post()`, `put()`, `patch()`, and `delete()` are automatically treated as JSON data.

4. **Leverage Method Chaining**: When you need more control, use the fluent interface:

   ```php
   fetch()
       ->baseUri('https://api.example.com')
       ->withToken('your-token')
       ->timeout(5)
       ->retry(3, 100)
       ->get('/users');
   ```

5. **Use the Response Methods**: Take advantage of the response helper methods for cleaner code:

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

6. **Resource Conservation**: When working with many requests, consider using async mode to improve performance.

## Next Steps

- Learn more about [Working with Responses](/guide/working-with-responses)
- Explore [Asynchronous Requests](/guide/async-requests) for parallel HTTP operations
- Discover [Retry Handling](/guide/retry-handling) for dealing with unreliable APIs
