---
title: Fetch Client Helper Function API Reference
description: API reference for the fetch_client() helper function in the Fetch HTTP client package
---

# fetch_client() Function

The `fetch_client()` function creates and returns a configured HTTP client instance for making HTTP requests. It's a convenient wrapper around the Client class that allows for quick access to HTTP client functionality.

## Signature

```php
function fetch_client(
    string $baseUri = null,
    array $options = [],
    int $timeout = null,
    int $retries = null
): Client
```

## Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$baseUri` | `string\|null` | `null` | Base URI for all requests made with this client. |
| `$options` | `array` | `[]` | Request options to configure the client. |
| `$timeout` | `int\|null` | `null` | Timeout in seconds for all requests (defaults to 30 if not specified). |
| `$retries` | `int\|null` | `null` | Number of times to retry failed requests (defaults to 1 if not specified). |

## Return Value

Returns an instance of the `Fetch\Http\Client` class configured with the provided options.

## Examples

### Basic Usage

```php
// Create a simple client
$client = fetch_client();

// Send a GET request
$response = $client->get('https://api.example.com/users');
```

### With Base URI

```php
// Create a client with a base URI
$client = fetch_client('https://api.example.com');

// Requests will be made relative to the base URI
$response = $client->get('/users'); // https://api.example.com/users
```

### With Options

```php
// Create a client with custom options
$client = fetch_client(
    'https://api.example.com',
    [
        'headers' => [
            'X-API-Key' => 'your-api-key',
            'Accept' => 'application/json'
        ],
        'verify' => false, // Disable SSL verification (not recommended for production)
    ],
    10, // 10 second timeout
    2   // Retry up to 2 times
);

// Send a request using the configured client
$response = $client->get('/users');
```

### One-liner Requests

```php
// Make a GET request directly
$response = fetch_client('https://api.example.com')->get('/users');

// POST request with JSON data
$response = fetch_client('https://api.example.com')->post('/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

### Chaining Methods

```php
// Chain configuration methods with requests
$response = fetch_client()
    ->baseUri('https://api.example.com')
    ->withHeaders([
        'X-API-Key' => 'your-api-key',
        'User-Agent' => 'MyApp/1.0'
    ])
    ->timeout(5)
    ->retry(2)
    ->get('/users');
```

## Internal Implementation

Internally, the `fetch_client()` function:

1. Creates a new instance of the `Fetch\Http\Client` class
2. Applies the specified base URI if provided
3. Configures the client with the provided options
4. Sets the timeout and retry settings if specified
5. Returns the configured client ready for use

This function provides a more concise way to create and configure a client compared to directly instantiating the `Client` class.
