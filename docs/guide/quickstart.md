---
title: Quickstart
description: Get up and running with the Fetch HTTP package quickly
---

# Quickstart

This guide will help you get started with the Fetch HTTP package quickly.

## Installation

```bash
composer require jerome/fetch-php
```

## Basic Usage

The Fetch HTTP package provides a simple, intuitive API for making HTTP requests, inspired by JavaScript's fetch API but built specifically for PHP.

### Making a Simple Request

```php
// Make a GET request using the global fetch() function
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
$data = $response->json();      // As array
$object = $response->object();  // As object
$array = $response->array();    // Explicitly as array

// Plain text response
$text = $response->text();

// Raw response body
$content = $response->body();

// Binary data
$binary = $response->arrayBuffer();
$stream = $response->blob();

// XML response
$xml = $response->xml();
```

You can also access JSON data using array syntax:

```php
$name = $response['name'];
$email = $response['email'];
$address = $response['address']['street'];
```

### Checking Response Status

```php
// Status code checks
if ($response->isOk()) {                  // 200
    // Success
} elseif ($response->isNotFound()) {      // 404
    // Not found
} elseif ($response->isUnauthorized()) {  // 401
    // Unauthorized
}

// Status categories
if ($response->successful()) {            // 2xx
    // Success
} elseif ($response->isClientError()) {   // 4xx
    // Client error
} elseif ($response->isServerError()) {   // 5xx
    // Server error
}
```

### Using Enums

```php
use Fetch\Enum\Status;
use Fetch\Enum\Method;
use Fetch\Enum\ContentType;

// Check status using enum
if ($response->statusEnum() === Status::OK) {
    // Status is 200 OK
}

// Use method enum for requests
$response = fetch_client()->request(Method::POST, '/users', $userData);

// Use content type enum
$response = fetch_client()
    ->withBody($data, ContentType::JSON)
    ->post('/users');
```

## Authentication

### Bearer Token

```php
// Using fetch options
$response = fetch('https://api.example.com/users', [
    'token' => 'your-token'
]);

// Using helper methods
$response = fetch_client()
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
$response = fetch_client()
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

// Or using the fluent interface
$response = fetch_client()
    ->withMultipart([
        [
            'name' => 'file',
            'contents' => fopen('/path/to/file.jpg', 'r'),
            'filename' => 'upload.jpg',
        ],
        [
            'name' => 'description',
            'contents' => 'File description'
        ]
    ])
    ->post('https://api.example.com/upload');
```

### Asynchronous Requests

```php
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\all;

// Modern async/await pattern
await(async(function() {
    // Process multiple requests in parallel
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

// Traditional promise pattern
$handler = fetch_client()->getHandler();
$handler->async();

$promise = $handler->get('https://api.example.com/users')
    ->then(function ($response) {
        if ($response->successful()) {
            return $response->json();
        }
        throw new \Exception("Request failed with status: " . $response->getStatusCode());
    })
    ->catch(function (\Throwable $e) {
        echo "Error: " . $e->getMessage();
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
$response = fetch_client()
    ->retry(3, 100)
    ->retryStatusCodes([408, 429, 500, 502, 503, 504]) // Customize retryable status codes
    ->retryExceptions(['GuzzleHttp\Exception\ConnectException']) // Customize retryable exceptions
    ->get('https://api.example.com/unstable');
```

### Content Type Detection

```php
$response = fetch('https://api.example.com/data');

// Detect content type
$contentType = $response->contentType();
$contentTypeEnum = $response->contentTypeEnum();

// Check specific content types
if ($response->hasJsonContent()) {
    $data = $response->json();
} elseif ($response->hasHtmlContent()) {
    $html = $response->text();
} elseif ($response->hasTextContent()) {
    $text = $response->text();
}
```

### Working with URIs

```php
$response = fetch_client()
    ->baseUri('https://api.example.com')
    ->withQueryParameter('page', 1)
    ->withQueryParameters([
        'limit' => 10,
        'sort' => 'name',
        'include' => 'posts,comments'
    ])
    ->get('/users');
```

### Logging

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a PSR-3 compatible logger
$logger = new Logger('http');
$logger->pushHandler(new StreamHandler('logs/http.log', Logger::DEBUG));

// Set it on the client
$client = fetch_client();
$client->setLogger($logger);

// Or set it on the handler
$handler = fetch_client()->getHandler();
$handler->setLogger($logger);

// Now all requests will be logged
$response = get('https://api.example.com/users');
```

### Creating Mock Responses (For Testing)

```php
use Fetch\Http\ClientHandler;

// Create a simple mock response
$mockResponse = ClientHandler::createMockResponse(
    statusCode: 200,
    headers: ['Content-Type' => 'application/json'],
    body: '{"name": "John", "email": "john@example.com"}'
);

// Create a JSON mock response
$mockJsonResponse = ClientHandler::createJsonResponse(
    data: ['name' => 'John', 'email' => 'john@example.com'],
    statusCode: 200
);
```

## Error Handling

```php
try {
    $response = fetch('https://api.example.com/users');

    if ($response->failed()) {
        throw new Exception("Request failed with status: " . $response->status());
    }

    $users = $response->json();
} catch (\Fetch\Exceptions\NetworkException $e) {
    echo "Network error: " . $e->getMessage();
} catch (\Fetch\Exceptions\RequestException $e) {
    echo "Request error: " . $e->getMessage();
} catch (\Fetch\Exceptions\ClientException $e) {
    echo "Client error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Next Steps

Now that you've gotten started with the basic functionality, check out these guides to learn more:

- [Making Requests](/guide/making-requests) - More details on making HTTP requests
- [Helper Functions](/guide/helper-functions) - Learn about all available helper functions
- [Working with Responses](/guide/working-with-responses) - Advanced response handling
- [Working with Enums](/guide/working-with-enums) - Using type-safe enums for HTTP concepts
