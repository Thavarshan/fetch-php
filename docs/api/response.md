---
title: Response API Reference
description: API reference for the Response class in the Fetch HTTP client package
---

# Response API Reference

The complete API reference for the `Response` class in the Fetch HTTP client package.

## Class Declaration

```php
namespace Fetch\Http;

class Response extends BaseResponse implements ArrayAccess, ResponseInterface
{
    use ResponseImmutabilityTrait;

    // ...
}
```

## Constructor

```php
/**
 * Create new response instance.
 */
public function __construct(
    int|Status $status = Status::OK,
    array $headers = [],
    string $body = '',
    string $version = '1.1',
    ?string $reason = null
)
```

## Static Factory Methods

### `createFromBase()`

Create a response from a PSR-7 response instance.

```php
public static function createFromBase(PsrResponseInterface $response): self
```

### `withJson()`

Create a response with JSON content.

```php
public static function withJson(
    mixed $data,
    int|Status $status = Status::OK,
    array $headers = [],
    int $options = 0
): self
```

### `noContent()`

Create a 204 No Content response.

```php
public static function noContent(array $headers = []): self
```

### `created()`

Create a 201 Created response with optional JSON body.

```php
public static function created(
    string $location,
    mixed $data = null,
    array $headers = []
): self
```

### `withRedirect()`

Create a redirect response.

```php
public static function withRedirect(
    string $location,
    int|Status $status = Status::FOUND,
    array $headers = []
): self
```

## Response Body Methods

### `json()`

Get the body as a JSON-decoded array or object.

```php
public function json(bool $assoc = true, bool $throwOnError = true, int $depth = 512, int $options = 0): mixed
```

### `object()`

Get the body as a JSON-decoded object.

```php
public function object(bool $throwOnError = true): object
```

### `array()`

Get the body as a JSON-decoded array.

```php
public function array(bool $throwOnError = true): array
```

### `text()`

Get the body as plain text.

```php
public function text(): string
```

### `body()`

Get the raw body content.

```php
public function body(): string
```

### `blob()`

Get the body as a stream (simulating a "blob" in JavaScript).

```php
public function blob()
```

### `arrayBuffer()`

Get the body as an array buffer (binary data).

```php
public function arrayBuffer(): string
```

### `xml()`

Parse the body as XML.

```php
public function xml(int $options = 0, bool $throwOnError = true): ?SimpleXMLElement
```

## Status Code Methods

### `status()`

Get the status code of the response.

```php
public function status(): int
```

### `statusText()`

Get the status text for the response (e.g., "OK").

```php
public function statusText(): string
```

### `statusEnum()`

Get the status as an enum.

```php
public function statusEnum(): ?Status
```

### `isStatus()`

Check if the response has the given status code.

```php
public function isStatus(int|Status $status): bool
```

## Status Category Methods

### `isInformational()`

Check if the response status code is informational (1xx).

```php
public function isInformational(): bool
```

### `ok()` / `successful()`

Check if the response status code is a success (2xx).

```php
public function ok(): bool
public function successful(): bool
```

### `isRedirection()` / `redirect()`

Check if the response status code is a redirection (3xx).

```php
public function isRedirection(): bool
public function redirect(): bool
```

### `isClientError()` / `clientError()`

Check if the response status code is a client error (4xx).

```php
public function isClientError(): bool
public function clientError(): bool
```

### `isServerError()` / `serverError()`

Check if the response status code is a server error (5xx).

```php
public function isServerError(): bool
public function serverError(): bool
```

### `failed()`

Determine if the response is a client or server error.

```php
public function failed(): bool
```

## Specific Status Code Methods

### `isOk()`

Check if the response has a 200 status code.

```php
public function isOk(): bool
```

### `isCreated()`

Check if the response has a 201 status code.

```php
public function isCreated(): bool
```

### `isAccepted()`

Check if the response has a 202 status code.

```php
public function isAccepted(): bool
```

### `isNoContent()`

Check if the response has a 204 status code.

```php
public function isNoContent(): bool
```

### `isMovedPermanently()`

Check if the response has a 301 status code.

```php
public function isMovedPermanently(): bool
```

### `isFound()`

Check if the response has a 302 status code.

```php
public function isFound(): bool
```

### `isBadRequest()`

Check if the response has a 400 status code.

```php
public function isBadRequest(): bool
```

### `isUnauthorized()`

Check if the response has a 401 status code.

```php
public function isUnauthorized(): bool
```

### `isForbidden()`

Check if the response has a 403 status code.

```php
public function isForbidden(): bool
```

### `isNotFound()`

Check if the response has a 404 status code.

```php
public function isNotFound(): bool
```

### `isConflict()`

Check if the response has a 409 status code.

```php
public function isConflict(): bool
```

### `isUnprocessableEntity()`

Check if the response has a 422 status code.

```php
public function isUnprocessableEntity(): bool
```

### `isTooManyRequests()`

Check if the response has a 429 status code.

```php
public function isTooManyRequests(): bool
```

### `isInternalServerError()`

Check if the response has a 500 status code.

```php
public function isInternalServerError(): bool
```

### `isServiceUnavailable()`

Check if the response has a 503 status code.

```php
public function isServiceUnavailable(): bool
```

## Header Methods

### `headers()`

Get the headers from the response as an array.

```php
public function headers(): array
```

### `header()`

Get a specific header from the response.

```php
public function header(string $header): ?string
```

### `hasHeader()`

Determine if the response contains a specific header.

```php
public function hasHeader($header): bool
```

## Content Type Methods

### `contentType()`

Get the Content-Type header from the response.

```php
public function contentType(): ?string
```

### `contentTypeEnum()`

Get the Content-Type as an enum.

```php
public function contentTypeEnum(): ?ContentType
```

### `hasJsonContent()`

Check if the response has JSON content.

```php
public function hasJsonContent(): bool
```

### `hasHtmlContent()`

Check if the response has HTML content.

```php
public function hasHtmlContent(): bool
```

### `hasTextContent()`

Check if the response has text content.

```php
public function hasTextContent(): bool
```

## Debug Information

When `withDebug()` is enabled on the handler, every response stores a per-request snapshot.

### `withDebugInfo()`

Attach a `DebugInfo` instance (used internally by the handler).

```php
public function withDebugInfo(\Fetch\Support\DebugInfo $debugInfo): static
```

### `getDebugInfo()`

Retrieve the `DebugInfo` for this response, or `null` when debug mode was disabled.

```php
public function getDebugInfo(): ?\Fetch\Support\DebugInfo
```

### `hasDebugInfo()`

Convenience method to check whether debug data is present.

```php
public function hasDebugInfo(): bool
```

## ArrayAccess Implementation

### `offsetExists()`

Determine if the given offset exists in the JSON response.

```php
public function offsetExists($offset): bool
```

### `offsetGet()`

Get the value at the given offset from the JSON response.

```php
public function offsetGet($offset): mixed
```

### `offsetSet()`

Set the value at the given offset in the JSON response (unsupported).

```php
public function offsetSet($offset, $value): void
```

### `offsetUnset()`

Unset the value at the given offset from the JSON response (unsupported).

```php
public function offsetUnset($offset): void
```

## Utility Methods

### `get()`

Get the value for a given key from the JSON response.

```php
public function get(string $key, mixed $default = null): mixed
```

### `__toString()`

Get the body of the response when converting to string.

```php
public function __toString(): string
```

## PSR-7 Methods (from ResponseImmutabilityTrait)

These methods override the PSR-7 response methods to ensure immutability and proper type preservation.

### `withStatus()`

Return an instance with the specified status code and reason phrase.

```php
public function withStatus($code, $reasonPhrase = ''): static
```

### `withAddedHeader()`

Return an instance with the specified header appended with the given value.

```php
public function withAddedHeader($name, $value): static
```

### `withoutHeader()`

Return an instance without the specified header.

```php
public function withoutHeader($name): static
```

### `withHeader()`

Return an instance with the provided value replacing the specified header.

```php
public function withHeader($name, $value): static
```

### `withProtocolVersion()`

Return an instance with the specified protocol version.

```php
public function withProtocolVersion($version): static
```

### `withBody()`

Return an instance with the specified body.

```php
public function withBody(StreamInterface $body): static
```
