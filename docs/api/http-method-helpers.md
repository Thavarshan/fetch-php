---
title: HTTP Method Helpers API Reference
description: API reference for the HTTP method helpers in the Fetch HTTP client package
---

# HTTP Method Helpers

The Fetch package provides a set of convenient global helper functions for making HTTP requests with different HTTP methods. These helper functions make your code more readable and expressive by providing simplified shortcuts for common operations.

## Function Signatures

### `get()`

Perform a GET request.

```php
/**
 * @param  string  $url  URL to fetch
 * @param  array<string, mixed>|null  $query  Query parameters
 * @param  array<string, mixed>|null  $options  Additional request options
 * @return ResponseInterface The response
 *
 * @throws ClientExceptionInterface If a client exception occurs
 */
function get(string $url, ?array $query = null, ?array $options = []): ResponseInterface
```

### `post()`

Perform a POST request.

```php
/**
 * @param  string  $url  URL to fetch
 * @param  mixed  $data  Request body or JSON data
 * @param  array<string, mixed>|null  $options  Additional request options
 * @return ResponseInterface The response
 *
 * @throws ClientExceptionInterface If a client exception occurs
 */
function post(string $url, mixed $data = null, ?array $options = []): ResponseInterface
```

### `put()`

Perform a PUT request.

```php
/**
 * @param  string  $url  URL to fetch
 * @param  mixed  $data  Request body or JSON data
 * @param  array<string, mixed>|null  $options  Additional request options
 * @return ResponseInterface The response
 *
 * @throws ClientExceptionInterface If a client exception occurs
 */
function put(string $url, mixed $data = null, ?array $options = []): ResponseInterface
```

### `patch()`

Perform a PATCH request.

```php
/**
 * @param  string  $url  URL to fetch
 * @param  mixed  $data  Request body or JSON data
 * @param  array<string, mixed>|null  $options  Additional request options
 * @return ResponseInterface The response
 *
 * @throws ClientExceptionInterface If a client exception occurs
 */
function patch(string $url, mixed $data = null, ?array $options = []): ResponseInterface
```

### `delete()`

Perform a DELETE request.

```php
/**
 * @param  string  $url  URL to fetch
 * @param  mixed  $data  Request body or JSON data
 * @param  array<string, mixed>|null  $options  Additional request options
 * @return ResponseInterface The response
 *
 * @throws ClientExceptionInterface If a client exception occurs
 */
function delete(string $url, mixed $data = null, ?array $options = []): ResponseInterface
```

## Examples

### GET Request

```php
// Simple GET request
$response = get('https://api.example.com/users');

// GET request with query parameters
$response = get('https://api.example.com/users', [
    'page' => 1,
    'limit' => 10,
    'sort' => 'name'
]);

// GET request with additional options
$response = get('https://api.example.com/users', ['page' => 1], [
    'headers' => [
        'X-API-Key' => 'your-api-key'
    ],
    'timeout' => 5
]);

// Process the response
$users = $response->json();
foreach ($users as $user) {
    echo $user['name'] . "\n";
}
```

### POST Request

```php
// POST request with JSON data
$response = post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// POST request with a string body
$response = post('https://api.example.com/raw', 'Raw request body');

// POST request with additional options
$response = post('https://api.example.com/users',
    ['name' => 'John Doe'],
    [
        'headers' => [
            'X-Custom-Header' => 'value'
        ],
        'timeout' => 10
    ]
);

// Check if the request was successful
if ($response->isSuccess()) {
    $user = $response->json();
    echo "Created user with ID: " . $user['id'];
}
```

### PUT Request

```php
// PUT request to update a resource
$response = put('https://api.example.com/users/1', [
    'name' => 'John Doe Updated',
    'email' => 'john.updated@example.com'
]);

// Check the response
if ($response->isSuccess()) {
    echo "User updated successfully";
}
```

### PATCH Request

```php
// PATCH request to partially update a resource
$response = patch('https://api.example.com/users/1', [
    'email' => 'new.email@example.com'
]);

// Check the response
if ($response->isSuccess()) {
    echo "User email updated successfully";
}
```

### DELETE Request

```php
// DELETE request to remove a resource
$response = delete('https://api.example.com/users/1');

// Delete with request body (for batch deletions)
$response = delete('https://api.example.com/users', [
    'ids' => [1, 2, 3]
]);

// Check if the resource was deleted
if ($response->isSuccess()) {
    echo "Resource deleted successfully";
}
```

## Internal Implementation

Internally, these helper functions use the `request_method()` function, which in turn calls the `fetch()` function with the appropriate HTTP method and data configuration:

```php
function request_method(
    string $method,
    string $url,
    mixed $data = null,
    ?array $options = [],
    bool $dataIsQuery = false
): ResponseInterface
{
    $options = $options ?? [];
    $options['method'] = $method;

    if ($data !== null) {
        if ($dataIsQuery) {
            $options['query'] = $data;
        } elseif (is_array($data)) {
            $options['json'] = $data; // Treat arrays as JSON by default
        } else {
            $options['body'] = $data;
        }
    }

    return fetch($url, $options);
}
```

## Notes

- These helpers provide a more concise way to make common HTTP requests compared to using `fetch()` directly
- When passing an array as the data parameter in `post()`, `put()`, `patch()`, or `delete()`, it's automatically encoded as JSON
- For GET requests, the data parameter is treated as query parameters
- You can still use the full range of request options by passing them in the `$options` parameter
- All helper functions use the global client instance from `fetch_client()` internally, so any global configuration applies

## See Also

- [fetch()](/api/fetch) - Main function for making HTTP requests
- [fetch_client()](/api/fetch-client) - Get or configure the global client instance
- [Client](/api/client) - More details on the Client class
- [Response](/api/response) - API for working with response objects
