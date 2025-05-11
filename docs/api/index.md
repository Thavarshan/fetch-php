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

### Classes

- [`Client`](./client.md) - Main HTTP client class
- [`ClientHandler`](./client-handler.md) - Low-level HTTP client implementation
- [`Request`](./request.md) - HTTP request representation
- [`Response`](./response.md) - HTTP response representation

### Enums

- [`Method`](./method-enum.md) - HTTP request methods
- [`ContentType`](./content-type-enum.md) - Content type (MIME type) constants
- [`Status`](./status-enum.md) - HTTP status codes

## Architectural Overview

The Fetch package is designed with a layered architecture:

1. **User-facing API**: The `fetch()` and `fetch_client()` functions provide a simple, expressive API for common HTTP operations.

2. **Client Layer**: The `Client` class provides a higher-level, feature-rich API with method chaining and fluent interface.

3. **Handler Layer**: The `ClientHandler` class provides the core HTTP functionality, handling the low-level details of making HTTP requests.

4. **HTTP Message Layer**: The `Request` and `Response` classes represent HTTP messages and provide methods for working with them.

5. **Utilities and Constants**: Enums and other utilities provide standardized constants and helper functions.

## Usage Patterns

The API is designed to be used in several ways, depending on your needs:

### One-line Requests

```php
// Quick GET request
$response = fetch('https://api.example.com/users');

// Quick POST request with JSON data
$response = fetch('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
], 'POST');
```

### Fluent Interface with Client

```php
// Create a client with a base URI
$client = fetch_client('https://api.example.com');

// Chain method calls for a more complex request
$response = $client
    ->withHeader('X-API-Key', 'your-api-key')
    ->withQueryParameter('page', 1)
    ->timeout(5)
    ->get('/users');
```

### Asynchronous Requests

```php
// Make an asynchronous request
$promise = fetch_client()
    ->async()
    ->get('https://api.example.com/users');

// Add callbacks
$promise->then(
    function ($response) {
        // Handle successful response
    },
    function ($exception) {
        // Handle error
    }
);
```

### Request Batching

```php
// Create a client for reuse
$client = fetch_client('https://api.example.com');

// Make multiple requests in parallel
$responses = $client->map([1, 2, 3], function ($id) use ($client) {
    return $client->get("/users/{$id}");
});
```

## Extending the Package

The package is designed to be extensible. You can:

- Create custom client handlers
- Extend the base client with additional functionality
- Add middleware for request/response processing
- Create specialized clients for specific APIs

See the [Custom Clients](../guide/custom-clients.md) guide for more information on extending the package.
