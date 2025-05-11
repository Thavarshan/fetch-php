---
title: Quickstart
description: Get up and running with the Fetch HTTP package quickly
---

# Quickstart

This guide will help you get started with the Fetch HTTP package quickly.

## Installation

```bash
composer require fetch/http-client
```

## Basic Usage

The Fetch HTTP package provides a simple, intuitive API for making HTTP requests, inspired by JavaScript's fetch API but built specifically for PHP.

### Making a Simple Request

```php
use function Fetch\Http\fetch;

// Make a GET request
$response = fetch('https://api.example.com/users');

// Parse the JSON response
$users = $response->json();

// Check for success
if ($response->successful()) {
    foreach ($users as $user) {
        echo $user['name'] . PHP_EOL;
    }
} else {
    echo "Request failed with status: " . $response->status();
}
```

### HTTP Methods

The package provides helper functions for common HTTP methods:

```php
// GET request
$users = get('https://api.example.com/users')->json();

// POST request with JSON body
$user = post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
])->json();

// PUT request to update a resource
$updatedUser = put('https://api.example.com/users/123', [
    'name' => 'John Smith'
])->json();

// PATCH request for partial updates
$user = patch('https://api.example.com/users/123', [
    'status' => 'active'
])->json();

// DELETE request
$result = delete('https://api.example.com/users/123')->json();
```

### Request Options

The `fetch()` function accepts various options to customize your request:

```php
$response = fetch('https://api.example.com/users', [
    'method' => 'POST',
    'headers' => [
        'Authorization' => 'Bearer your-token',
        'Accept' => 'application/json',
        'X-Custom-Header' => 'value'
    ],
    'json' => [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ],
    'query' => [
        'include' => 'posts,comments',
        'sort' => 'created_at'
    ],
    'timeout' => 10,
    'retries' => 3
]);
```

### Working with Responses

The `Response` object provides methods for different content types:

```php
// JSON response
$data = $response->json();  // As array
$object = $response->object();  // As object

// Plain text response
$text = $response->text();

// Raw response body
$content = $response->body();

// XML response
$xml = $response->xml();
```

You can also access JSON data using array syntax:

```php
$name = $response['name'];
$email = $response['email'];
```

### Checking Response Status

```php
// Status code checks
if ($response->isOk()) {  // 200
    // Success
} elseif ($response->isNotFound()) {  // 404
    // Not found
} elseif ($response->isUnauthorized()) {  // 401
    // Unauthorized
}

// Status categories
if ($response->successful()) {  // 2xx
    // Success
} elseif ($response->clientError()) {  // 4xx
    // Client error
} elseif ($response->serverError()) {  // 5xx
    // Server error
}
```

## Authentication

### Bearer Token

```php
// Using fetch options
$response = fetch('https://api.example.com/users', [
    'token' => 'your-token'
]);

// Using helper methods
$response = fetch()
    ->withToken('your-token')
    ->get('https://api.example.com/users');
```

### Basic Authentication

```php
// Using fetch options
$response = fetch('https://api.example.com/users', [
    'auth' => ['username', 'password']
]);

// Using helper methods
$response = fetch()
    ->withAuth('username', 'password')
    ->get('https://api.example.com/users');
```

## Advanced Features

### Global Configuration

Set up global configuration for all requests:

```php
fetch_client([
    'base_uri' => 'https://api.example.com',
    'headers' => [
        'User-Agent' => 'MyApp/1.0',
        'Accept' => 'application/json'
    ],
    'timeout' => 5
]);

// Now all requests use this configuration
$users = get('/users')->json();  // Uses base_uri
$user = get("/users/{$id}")->json();
```

### File Uploads

```php
$response = fetch('https://api.example.com/upload', [
    'method' => 'POST',
    'multipart' => [
        [
            'name' => 'file',
            'contents' => file_get_contents('/path/to/file.jpg'),
            'filename' => 'upload.jpg',
        ],
        [
            'name' => 'description',
            'contents' => 'File description'
        ]
    ]
]);
```

### Asynchronous Requests

```php
// Get the client for async operations
$client = fetch_client();

// Create promises for parallel requests
$users = $client->async()->get('https://api.example.com/users');
$posts = $client->async()->get('https://api.example.com/posts');

// Wait for both to complete
$client->all(['users' => $users, 'posts' => $posts])
    ->then(function ($results) {
        $userData = $results['users']->json();
        $postsData = $results['posts']->json();

        // Process results
    });
```

### Retry Handling

```php
// Retry failed requests automatically
$response = fetch('https://api.example.com/unstable', [
    'retries' => 3,  // Retry up to 3 times
    'retry_delay' => 100  // Start with 100ms delay (uses exponential backoff)
]);

// Or using the fluent interface
$response = fetch()
    ->retry(3, 100)
    ->get('https://api.example.com/unstable');
```

### Logging

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a PSR-3 compatible logger
$logger = new Logger('http');
$logger->pushHandler(new StreamHandler('logs/http.log', Logger::DEBUG));

// Set it globally
fetch_client(logger: $logger);

// Now all requests will be logged
$response = get('https://api.example.com/users');
```

## Error Handling

```php
try {
    $response = fetch('https://api.example.com/users');

    if ($response->failed()) {
        throw new Exception("Request failed with status: " . $response->status());
    }

    $users = $response->json();
} catch (ClientExceptionInterface $e) {
    echo "HTTP client error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Next Steps

Now that you've gotten started with the basic functionality, check out these guides to learn more:

- [Making Requests](/guide/making-requests) - More details on making HTTP requests
- [Helper Functions](/guide/helper-functions) - Learn about all available helper functions
- [Working with Responses](/guide/working-with-responses) - Advanced response handling
