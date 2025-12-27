---
title: API Reference for Fetch PHP
description: API reference for the Fetch HTTP client package
---

# API Reference

Welcome to the API reference for the Fetch HTTP client package. This section provides detailed documentation for all the components, functions, classes, and interfaces available in the package.

## Core Components

The Fetch package is built around several key components:

### Functions

- [`fetch()`](./fetch.md) - The primary function for making HTTP requests
- [`fetch_client()`](./fetch-client.md) - Function to create a configured HTTP client
- [HTTP Method Helpers](./http-method-helpers.md) - Helper functions like `get()`, `post()`, etc.

### Classes

- [`Client`](./client.md) - Main HTTP client class
- [`ClientHandler`](./client-handler.md) - Low-level HTTP client implementation
- [`Response`](./response.md) - HTTP response representation

### Enums

- [`Method`](./method-enum.md) - HTTP request methods (GET, POST, PUT, etc.)
- [`ContentType`](./content-type-enum.md) - Content type (MIME type) constants
- [`Status`](./status-enum.md) - HTTP status codes

## Architectural Overview

The Fetch package is designed with a layered architecture:

1. **User-facing API**: The `fetch()`, `fetch_client()`, and HTTP method helpers (`get()`, `post()`, etc.) provide a simple, expressive API for common HTTP operations.

2. **Client Layer**: The `Client` class provides a higher-level, feature-rich API with method chaining and fluent interface.

3. **Handler Layer**: The `ClientHandler` class provides the core HTTP functionality, handling the low-level details of making HTTP requests.

4. **HTTP Message Layer**: The `Response` class represents HTTP responses and provides methods for working with them.

5. **Utilities and Constants**: Enums (`Method`, `ContentType`, `Status`) and other utilities provide standardized constants and helper functions.

## Usage Patterns

The API is designed to be used in several ways, depending on your needs:

### One-line Requests

```php
// Quick GET request
$response = fetch('https://api.example.com/users');

// Quick POST request with JSON data
$response = fetch('https://api.example.com/users', [
    'method' => 'POST',
    'json' => [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]
]);

// Using HTTP method helpers
$response = get('https://api.example.com/users');
$response = post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

### Fluent Interface with Client

```php
// Create a client with a base URI
$client = fetch_client([
    'base_uri' => 'https://api.example.com'
]);

// Chain method calls for a more complex request
$response = $client
    ->getHandler()
    ->withHeader('X-API-Key', 'your-api-key')
    ->withQueryParameter('page', 1)
    ->timeout(5)
    ->get('/users');
```

### Asynchronous Requests

```php
// Make an asynchronous request
$promise = fetch_client()
    ->getHandler()
    ->async()
    ->get('https://api.example.com/users');

// Add callbacks
$promise->then(
    function ($response) {
        // Handle successful response
        $users = $response->json();
        foreach ($users as $user) {
            echo $user['name'] . "\n";
        }
    },
    function ($exception) {
        // Handle error
        echo "Error: " . $exception->getMessage();
    }
);
```

### Request Batching and Concurrency

```php
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\all;
use function Matrix\Support\map;

// Execute multiple requests in parallel
$results = await(all([
    'users' => async(fn() => fetch('https://api.example.com/users')),
    'posts' => async(fn() => fetch('https://api.example.com/posts')),
    'comments' => async(fn() => fetch('https://api.example.com/comments'))
]));

// Process multiple items with controlled concurrency
$userIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
$results = await(map($userIds, function($id) {
    return async(function() use ($id) {
        return fetch("https://api.example.com/users/{$id}");
    });
}, 3)); // Process 3 at a time
```

### Working with Enums

```php
use Fetch\Enum\Method;
use Fetch\Enum\ContentType;
use Fetch\Enum\Status;

// Make a request with enum values
$response = fetch_client()
    ->getHandler()
    ->withBody($data, ContentType::JSON)
    ->sendRequest(Method::POST, 'https://api.example.com/users');

// Check response status using enums
if ($response->statusEnum() === Status::CREATED) {
    echo "User created successfully";
} elseif (($status = $response->statusEnum()) && $status->isClientError()) {
    echo "Client error: " . $status->phrase();
}
```

## Extending the Package

The package is designed to be extensible. You can:

- Create custom client handlers
- Extend the base client with additional functionality
- Add middleware for request/response processing
- Create specialized clients for specific APIs

See the [Custom Clients](../guide/custom-clients.md) guide for more information on extending the package.

## Performance Considerations

- Use the global client instance via `fetch_client()` for best performance, as it reuses connections
- Consider using asynchronous requests for I/O-bound operations
- Use the `map()` function with controlled concurrency for processing multiple items
- For large responses, consider streaming the response with the `stream` option

## Compatibility Notes

- Requires PHP 8.3 or higher
- Built on top of Guzzle HTTP, a widely-used PHP HTTP client
- Follows PSR-7 (HTTP Message Interface) and PSR-18 (HTTP Client) standards
- Supports both synchronous and asynchronous operations
