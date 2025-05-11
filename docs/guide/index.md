---
title: Overview
description: An overview of the Fetch HTTP package, its architecture and main components
---

# Fetch HTTP Package - Overview

## Introduction

The Fetch HTTP package provides a modern, flexible HTTP client for PHP applications. It features a fluent interface, extensive configuration options, and robust error handling, making it ideal for consuming APIs and working with web services.

## Key Components

The package is comprised of two main classes:

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

## Feature Highlights

- **PSR Compatibility**: Implements PSR-18 (HTTP Client) and PSR-3 (Logger)
- **Fluent Interface**: Chain method calls for clean, readable code
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
          |
          v
+------------------+
|  Guzzle Client   |  <-- Underlying HTTP client implementation
+------------------+
```

## Usage Patterns

### Simple Usage

The `Client` class provides a straightforward API for common HTTP operations and implements the PSR-18 interface for maximum compatibility with other PHP libraries and frameworks.

```php
use Fetch\Http\Client;

$client = new Client();
$response = $client->get('https://api.example.com/users');
$users = $response->json();
```

### Advanced Configuration

The `ClientHandler` class offers more control and customization options for advanced use cases:

```php
use Fetch\Http\ClientHandler;

$handler = new ClientHandler();
$response = $handler
    ->withToken('your-api-token')
    ->withHeaders(['Accept' => 'application/json'])
    ->timeout(5)
    ->retry(3, 100)
    ->get('https://api.example.com/users');
```

### Asynchronous Requests

For handling multiple requests efficiently:

```php
$handler = new ClientHandler();

// Create promises for multiple requests
$usersPromise = $handler->async()->get('https://api.example.com/users');
$postsPromise = $handler->async()->get('https://api.example.com/posts');

// Wait for all to complete
$handler->all([
    'users' => $usersPromise,
    'posts' => $postsPromise
])->then(function ($results) {
    // Process results
});
```

## When to Use Each Class

### Use `Client` when:
- You need PSR-18 compatibility
- You prefer a simpler API similar to JavaScript's fetch
- You're working within a framework that expects a PSR-18 client
- You want built-in exception handling for network and HTTP errors

### Use `ClientHandler` when:
- You need advanced configuration options
- You want to use asynchronous requests and promises
- You need fine-grained control over retries and timeouts
- You're performing complex operations like concurrent requests

## Exception Handling

The package provides several exception types for different error scenarios:

- `NetworkException`: For connection and network-related errors
- `RequestException`: For HTTP request errors
- `ClientException`: For unexpected client errors
- `TimeoutException`: For request timeouts

Each exception provides context about the failed request to aid in debugging and error handling.
