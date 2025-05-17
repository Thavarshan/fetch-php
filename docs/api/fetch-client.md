---
title: Fetch Client Helper Function API Reference
description: API reference for the fetch_client() helper function in the Fetch HTTP client package
---

# fetch_client() Function

The `fetch_client()` function creates, configures, and returns a global HTTP client instance for making HTTP requests. It provides a singleton-like pattern to maintain a consistent client instance throughout your application.

## Signature

```php
function fetch_client(
    ?array $options = null,
    bool $reset = false
): Client
```

## Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$options` | `array\|null` | `null` | Global client options to configure the client. |
| `$reset` | `bool` | `false` | Whether to reset the client instance, creating a new one. |

## Return Value

Returns an instance of the `Fetch\Http\Client` class configured with the provided options.

## Throws

- `RuntimeException` - If client creation or configuration fails

## Examples

### Basic Usage

```php
// Get the global client instance
$client = fetch_client();

// Send a GET request
$response = $client->get('https://api.example.com/users');
```

### With Configuration Options

```php
// Configure the global client with custom options
$client = fetch_client([
    'base_uri' => 'https://api.example.com',
    'headers' => [
        'X-API-Key' => 'your-api-key',
        'Accept' => 'application/json'
    ],
    'timeout' => 10
]);

// Any subsequent call to fetch_client() will use the same configured instance
$sameClient = fetch_client();

// Send a request using the configured client
$response = $client->get('/users');
```

### Resetting the Client

```php
// Get the default client
$client1 = fetch_client();

// Configure with specific options
$client2 = fetch_client([
    'headers' => ['X-Custom' => 'value']
]);

// Create a completely new instance, discarding the previous one
$freshClient = fetch_client(null, true);
```

### One-liner Requests

```php
// Configure and make a request in one line
$response = fetch_client([
    'base_uri' => 'https://api.example.com',
    'headers' => ['Authorization' => 'Bearer token']
])->get('/users');

// POST request with JSON data
$response = fetch_client([
    'base_uri' => 'https://api.example.com'
])->post('/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

### Chaining Methods

```php
// Chain configuration methods with requests
$response = fetch_client()
    ->getHandler()
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

1. Maintains a static instance of the `Fetch\Http\Client` class
2. Creates a new instance when first called or when `$reset` is `true`
3. Applies the specified options to the client if provided
4. Creates a new client with the modified handler when options are provided
5. Returns the configured client ready for use

The function uses a singleton-like pattern to ensure the same client instance is reused throughout the application, which helps maintain consistent configuration and can improve performance by reusing connections.

## Notes

- The first call to `fetch_client()` creates the singleton instance
- Subsequent calls return the same instance unless `$reset` is `true`
- When providing options to an existing instance, a new instance is created with those options
- The client maintains a separate handler instance that does the actual HTTP work

## See Also

- [fetch()](/api/fetch) - Main function for making HTTP requests
- [HTTP Method Helpers](/api/http-method-helpers) - Specialized helper functions for different HTTP methods
- [Client](/api/client) - More details on the Client class
- [ClientHandler](/api/client-handler) - More details on the underlying client handler implementation
