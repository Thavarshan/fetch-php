---
title: HTTP Method Helpers API Reference
description: API reference for the HTTP method helpers in the Fetch HTTP client package
---

# HTTP Method Helpers

The Fetch package provides a set of convenient helper methods for making HTTP requests with different HTTP methods. These helpers make your code more readable and expressive.

## Available Methods

All these methods are available on the `Client` and `ClientHandler` classes:

| Method | Description |
|--------|-------------|
| `head()` | Sends a HEAD request |
| `get()` | Sends a GET request |
| `post()` | Sends a POST request with optional body |
| `put()` | Sends a PUT request with optional body |
| `patch()` | Sends a PATCH request with optional body |
| `delete()` | Sends a DELETE request with optional body |
| `options()` | Sends an OPTIONS request |

## Method Signatures

### HEAD Request

```php
public function head(string $uri): ResponseInterface|PromiseInterface
```

**Parameters:**

- `$uri`: The URI to request

**Returns:**

- A `Response` object or a `Promise` if in async mode

**Example:**

```php
$response = fetch_client()->head('https://api.example.com/resource');
```

### GET Request

```php
public function get(string $uri, array $queryParams = []): ResponseInterface|PromiseInterface
```

**Parameters:**

- `$uri`: The URI to request
- `$queryParams`: Optional array of query parameters

**Returns:**

- A `Response` object or a `Promise` if in async mode

**Example:**

```php
$response = fetch_client()->get('https://api.example.com/users', [
    'page' => 1,
    'limit' => 10
]);
```

### POST Request

```php
public function post(
    string $uri,
    mixed $body = null,
    ContentType|string $contentType = 'application/json'
): ResponseInterface|PromiseInterface
```

**Parameters:**

- `$uri`: The URI to request
- `$body`: The request body (can be array, string, or null)
- `$contentType`: The content type of the request (defaults to JSON)

**Returns:**

- A `Response` object or a `Promise` if in async mode

**Example:**

```php
$response = fetch_client()->post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

### PUT Request

```php
public function put(
    string $uri,
    mixed $body = null,
    ContentType|string $contentType = 'application/json'
): ResponseInterface|PromiseInterface
```

**Parameters:**

- `$uri`: The URI to request
- `$body`: The request body (can be array, string, or null)
- `$contentType`: The content type of the request (defaults to JSON)

**Returns:**

- A `Response` object or a `Promise` if in async mode

**Example:**

```php
$response = fetch_client()->put('https://api.example.com/users/1', [
    'name' => 'John Doe Updated',
    'email' => 'john.updated@example.com'
]);
```

### PATCH Request

```php
public function patch(
    string $uri,
    mixed $body = null,
    ContentType|string $contentType = 'application/json'
): ResponseInterface|PromiseInterface
```

**Parameters:**

- `$uri`: The URI to request
- `$body`: The request body (can be array, string, or null)
- `$contentType`: The content type of the request (defaults to JSON)

**Returns:**

- A `Response` object or a `Promise` if in async mode

**Example:**

```php
$response = fetch_client()->patch('https://api.example.com/users/1', [
    'email' => 'john.new@example.com'
]);
```

### DELETE Request

```php
public function delete(
    string $uri,
    mixed $body = null,
    ContentType|string $contentType = 'application/json'
): ResponseInterface|PromiseInterface
```

**Parameters:**

- `$uri`: The URI to request
- `$body`: Optional request body (can be array, string, or null)
- `$contentType`: The content type of the request (defaults to JSON)

**Returns:**

- A `Response` object or a `Promise` if in async mode

**Example:**

```php
$response = fetch_client()->delete('https://api.example.com/users/1');

// With request body
$response = fetch_client()->delete('https://api.example.com/batch-delete', [
    'ids' => [1, 2, 3]
]);
```

### OPTIONS Request

```php
public function options(string $uri): ResponseInterface|PromiseInterface
```

**Parameters:**

- `$uri`: The URI to request

**Returns:**

- A `Response` object or a `Promise` if in async mode

**Example:**

```php
$response = fetch_client()->options('https://api.example.com/users');
```

## Body Content Types

When sending requests with a body, you can specify the content type:

```php
use Fetch\Enum\ContentType;

// Using enum
$response = fetch_client()->post('https://api.example.com/users', $data, ContentType::JSON);

// Using string
$response = fetch_client()->post('https://api.example.com/users', $data, 'application/json');
```

Available content types in the `ContentType` enum:

- `ContentType::JSON` - For JSON requests
- `ContentType::FORM_URLENCODED` - For form submissions
- `ContentType::MULTIPART` - For multipart form data (file uploads)
- `ContentType::XML` - For XML requests
- `ContentType::TEXT` - For plain text requests

## Internal Behavior

Internally, these methods leverage the `finalizeRequest()` method which configures and sends the request with the appropriate HTTP method. For methods that can include a request body (`POST`, `PUT`, `PATCH`, `DELETE`), the body is processed using the `configurePostableRequest()` method to properly set up the request payload based on the specified content type.
