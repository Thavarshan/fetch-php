---
title: Client API Reference
description: API reference for the Client class in the Fetch HTTP client package
---

# Client API Reference

The complete API reference for the `Client` class in the Fetch HTTP client package.

## Class Declaration

```php
namespace Fetch\Http;

class Client implements ClientInterface, LoggerAwareInterface
{
    // ...
}
```

## Constructor

```php
/**
 * Client constructor.
 *
 * @param  ClientHandlerInterface|null  $handler  The client handler
 * @param  array<string, mixed>  $options  Default request options
 * @param  LoggerInterface|null  $logger  PSR-3 logger
 */
public function __construct(
    ?ClientHandlerInterface $handler = null,
    array $options = [],
    ?LoggerInterface $logger = null
)
```

## Factory Methods

### `createWithBaseUri()`

Creates a new client with a base URI.

```php
public static function createWithBaseUri(string $baseUri, array $options = []): static
```

## PSR-7 Implementation

### `sendRequest()`

Sends a PSR-7 request and returns a PSR-7 response. Implements the PSR-18 ClientInterface.

```php
public function sendRequest(RequestInterface $request): PsrResponseInterface
```

#### Throws

- `NetworkException` - If there's a network error
- `RequestException` - If there's an error with the request
- `ClientException` - For unexpected errors

## Fetch API

### `fetch()`

Creates and sends an HTTP request. Returns the handler for method chaining if no URL is provided.

```php
public function fetch(?string $url = null, ?array $options = []): ResponseInterface|ClientHandlerInterface
```

## HTTP Methods

### `get()`

Makes a GET request.

```php
public function get(string $url, ?array $queryParams = null, ?array $options = []): ResponseInterface
```

### `post()`

Makes a POST request.

```php
public function post(
    string $url,
    mixed $body = null,
    string|ContentType $contentType = ContentType::JSON,
    ?array $options = []
): ResponseInterface
```

### `put()`

Makes a PUT request.

```php
public function put(
    string $url,
    mixed $body = null,
    string|ContentType $contentType = ContentType::JSON,
    ?array $options = []
): ResponseInterface
```

### `patch()`

Makes a PATCH request.

```php
public function patch(
    string $url,
    mixed $body = null,
    string|ContentType $contentType = ContentType::JSON,
    ?array $options = []
): ResponseInterface
```

### `delete()`

Makes a DELETE request.

```php
public function delete(
    string $url,
    mixed $body = null,
    string|ContentType $contentType = ContentType::JSON,
    ?array $options = []
): ResponseInterface
```

### `head()`

Makes a HEAD request.

```php
public function head(string $url, ?array $options = []): ResponseInterface
```

### `options()`

Makes an OPTIONS request.

```php
public function options(string $url, ?array $options = []): ResponseInterface
```

### `methodRequest()`

Makes a request with a specific HTTP method (protected method used internally).

```php
protected function methodRequest(
    Method $method,
    string $url,
    mixed $body = null,
    string|ContentType $contentType = ContentType::JSON,
    ?array $options = []
): ResponseInterface
```

## Client Handling

### `getHandler()`

Gets the underlying client handler.

```php
public function getHandler(): ClientHandlerInterface
```

### `getHttpClient()`

Gets the PSR-7 HTTP client.

```php
public function getHttpClient(): ClientInterface
```

## Logger Integration

### `setLogger()`

Sets a PSR-3 logger. Implements PSR-3 LoggerAwareInterface.

```php
public function setLogger(LoggerInterface $logger): void
```

## Protected Methods

### `extractOptionsFromRequest()`

Extracts options from a PSR-7 request.

```php
protected function extractOptionsFromRequest(RequestInterface $request): array
```
