---
title: Overview
description: An overview of the Fetch HTTP package, its architecture and main components
---

# Fetch HTTP Package - Overview

## Introduction

The Fetch HTTP package provides a modern, flexible HTTP client for PHP applications, bringing JavaScript's `fetch` API experience to PHP. It features a fluent interface, extensive configuration options, and robust error handling, making it ideal for consuming APIs and working with web services.

## Key Components

The package is comprised of several main components:

### Client

The `Client` class is a high-level wrapper that:

- Implements PSR-18 ClientInterface for standardized HTTP client behavior
- Implements PSR-3 LoggerAwareInterface for easy integration with logging systems
- Provides a simple fetch-style API similar to JavaScript's fetch API
- Handles error conversion to specific exception types

### ClientHandler

The `ClientHandler` class is a more powerful, configurable implementation that:

- Offers a fluent, chainable API for request building
- Provides extensive configuration options for request customization
- Supports both synchronous and asynchronous requests
- Implements retry logic with exponential backoff
- Offers promise-based operations for complex async workflows

### Response

The `Response` class extends PSR-7 ResponseInterface and provides:

- Rich methods for working with HTTP responses
- Status code helpers like `isOk()`, `isNotFound()`, etc.
- Content type inspection with `hasJsonContent()`, `hasHtmlContent()`, etc.
- Convenient data access methods like `json()`, `text()`, `array()`, `object()`
- Array access interface for working with JSON responses

### Enums

Type-safe PHP 8.2 enums for HTTP concepts:

- `Method`: HTTP methods (GET, POST, PUT, etc.)
- `ContentType`: Content types with helpers like `isJson()`, `isText()`
- `Status`: HTTP status codes with helpers like `isSuccess()`, `isClientError()`

## Feature Highlights

- **JavaScript-like API**: Familiar syntax for developers coming from JavaScript
- **PSR Compatibility**: Implements PSR-18 (HTTP Client), PSR-7 (HTTP Messages), and PSR-3 (Logger)
- **Fluent Interface**: Chain method calls for clean, readable code
- **Type-Safe Enums**: Modern PHP 8.2 enums for HTTP methods, content types, and status codes
- **Flexible Authentication**: Support for Bearer tokens, Basic auth, and more
- **Logging**: Comprehensive request/response logging with sanitization of sensitive data
- **Retries**: Configurable retry logic with exponential backoff and jitter
- **Asynchronous Requests**: Promise-based async operations with concurrency control
- **Content Type Handling**: Simplified handling of JSON, forms, multipart data, etc.
- **Testing Utilities**: Built-in mock response helpers for testing

## Architecture

```
+------------------+
|      Client      |  <-- High-level API (PSR-18 compliant)
+------------------+
          |
          v
+------------------+
|   ClientHandler  |  <-- Core implementation with advanced features
+------------------+
          |
    +-----------+
    |  Traits   |  <-- Functionality separated into focused traits
    +-----------+
    |  ConfiguresRequests
    |  HandlesUris
    |  ManagesPromises
    |  ManagesRetries
    |  PerformsHttpRequests
    +-----------+
          |
          v
+------------------+
|  Response        |  <-- Enhanced response handling
+------------------+
          |
          v
+------------------+
|  Guzzle Client   |  <-- Underlying HTTP client implementation
+------------------+
```

## Usage Patterns

### Simple Usage

```php
// Global helper functions for quick requests
$response = fetch('https://api.example.com/users');
$users = $response->json();

// HTTP method-specific helpers
$user = post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
])->json();
```

### Advanced Configuration

The `ClientHandler` class offers more control and customization options for advanced use cases:

```php
use Fetch\Http\ClientHandler;

$handler = new ClientHandler();
$response = $handler
    ->withToken('your-api-token')
    ->withHeaders(['Accept' => 'application/json'])
    ->withQueryParameters(['page' => 1, 'limit' => 10])
    ->timeout(5)
    ->retry(3, 100)
    ->get('https://api.example.com/users');
```

### Type-Safe Enums

```php
use Fetch\Enum\Method;
use Fetch\Enum\ContentType;
use Fetch\Enum\Status;

// Use enums for HTTP methods
$client = fetch_client();
$response = $client->request(Method::POST, '/users', $userData);

// Check HTTP status with enums
if ($response->statusEnum() === Status::OK) {
    // Process successful response
}

// Content type handling
$response = $client->withBody($data, ContentType::JSON)->post('/users');
```

### Asynchronous Requests

For handling multiple requests efficiently:

```php
use function async;
use function await;
use function all;

// Execute an async function
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

### Global Client Configuration

```php
// Configure once at application bootstrap
fetch_client([
    'base_uri' => 'https://api.example.com',
    'headers' => [
        'User-Agent' => 'MyApp/1.0',
        'Accept' => 'application/json',
    ],
    'timeout' => 10,
]);

// Use the configured client throughout your application
function getUserData($userId) {
    return fetch_client()->get("/users/{$userId}")->json();
}

function createUser($userData) {
    return fetch_client()->post('/users', $userData)->json();
}
```

## Enhanced Response Handling

```php
$response = fetch('https://api.example.com/users/1');

// Status code helpers
if ($response->isOk()) {
    // Handle 200 OK
} else if ($response->isNotFound()) {
    // Handle 404 Not Found
} else if ($response->isUnauthorized()) {
    // Handle 401 Unauthorized
}

// Status category helpers
if ($response->successful()) {
    // Handle any 2xx status
} else if ($response->isClientError()) {
    // Handle any 4xx status
} else if ($response->isServerError()) {
    // Handle any 5xx status
}

// Content type helpers
if ($response->hasJsonContent()) {
    $data = $response->json();
} else if ($response->hasHtmlContent()) {
    $html = $response->text();
}

// Array access for JSON responses
$user = $response['user'];
$name = $response['user']['name'];
```

## When to Use Each Class

### Use `Client` when

- You need PSR-18 compatibility
- You prefer a simpler API similar to JavaScript's fetch
- You're working within a framework that expects a PSR-18 client
- You want built-in exception handling for network and HTTP errors

### Use `ClientHandler` when

- You need advanced configuration options
- You want to use asynchronous requests and promises
- You need fine-grained control over retries and timeouts
- You're performing complex operations like concurrent requests

### Use global helpers (`fetch()`, `get()`, `post()`, etc.) when

- You're making simple, one-off requests
- You don't need extensive configuration
- You want the most concise, readable code

## Exception Handling

The package provides several exception types for different error scenarios:

- `NetworkException`: For connection and network-related errors
- `RequestException`: For HTTP request errors
- `ClientException`: For unexpected client errors
- `TimeoutException`: For request timeouts

Each exception provides context about the failed request to aid in debugging and error handling.
