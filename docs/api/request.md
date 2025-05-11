---
title: Request API Reference
description: API reference for the Request class in the Fetch HTTP client package
---

# Request API Reference

The complete API reference for the `Request` class in the Fetch HTTP client package.

## Class Declaration

```php
namespace Fetch\Http;

class Request extends BaseRequest implements RequestInterface
{
    use RequestImmutabilityTrait;

    // ...
}
```

## Constructor

```php
/**
 * Create a new Request instance.
 */
public function __construct(
    string|Method $method,
    string|UriInterface $uri,
    array $headers = [],
    $body = null,
    string $version = '1.1',
    ?string $requestTarget = null
)
```

## Static Factory Methods

### HTTP Method Factories

### `get()`

Create a new GET request.

```php
public static function get(string|UriInterface $uri, array $headers = []): static
```

### `post()`

Create a new POST request.

```php
public static function post(
    string|UriInterface $uri,
    $body = null,
    array $headers = [],
    ContentType|string|null $contentType = null
): static
```

### `put()`

Create a new PUT request.

```php
public static function put(
    string|UriInterface $uri,
    $body = null,
    array $headers = [],
    ContentType|string|null $contentType = null
): static
```

### `patch()`

Create a new PATCH request.

```php
public static function patch(
    string|UriInterface $uri,
    $body = null,
    array $headers = [],
    ContentType|string|null $contentType = null
): static
```

### `delete()`

Create a new DELETE request.

```php
public static function delete(
    string|UriInterface $uri,
    $body = null,
    array $headers = [],
    ContentType|string|null $contentType = null
): static
```

### `head()`

Create a new HEAD request.

```php
public static function head(string|UriInterface $uri, array $headers = []): static
```

### `options()`

Create a new OPTIONS request.

```php
public static function options(string|UriInterface $uri, array $headers = []): static
```

### Content Type Factories

### `json()`

Create a new Request instance with a JSON body.

```php
public static function json(
    string|Method $method,
    string|UriInterface $uri,
    array $data,
    array $headers = []
): static
```

### `form()`

Create a new Request instance with form parameters.

```php
public static function form(
    string|Method $method,
    string|UriInterface $uri,
    array $formParams,
    array $headers = []
): static
```

### `multipart()`

Create a new Request instance with multipart form data.

```php
public static function multipart(
    string|Method $method,
    string|UriInterface $uri,
    array $multipart,
    array $headers = []
): static
```

## Request Target Methods

### `getRequestTarget()`

Get the request target (path for origin-form, absolute URI for absolute-form, authority for authority-form, or asterisk for asterisk-form).

```php
public function getRequestTarget(): string
```

### `withRequestTarget()`

Return an instance with the specific request target.

```php
public function withRequestTarget($requestTarget): static
```

## Request Method Information

### `getMethodEnum()`

Get the method as an enum.

```php
public function getMethodEnum(): ?Method
```

### `supportsRequestBody()`

Check if the request method supports a request body.

```php
public function supportsRequestBody(): bool
```

## Content Type Methods

### `getContentTypeEnum()`

Get the content type from the headers as an enum.

```php
public function getContentTypeEnum(): ?ContentType
```

### `hasJsonContent()`

Check if the request has JSON content.

```php
public function hasJsonContent(): bool
```

### `hasFormContent()`

Check if the request has form content.

```php
public function hasFormContent(): bool
```

### `hasMultipartContent()`

Check if the request has multipart content.

```php
public function hasMultipartContent(): bool
```

### `hasTextContent()`

Check if the request has text content.

```php
public function hasTextContent(): bool
```

## Body Methods

### `getBodyAsString()`

Get the request body as a string.

```php
public function getBodyAsString(): string
```

### `getBodyAsJson()`

Get the request body as JSON.

```php
public function getBodyAsJson(bool $assoc = true, int $depth = 512, int $options = 0): mixed
```

### `getBodyAsFormParams()`

Get the request body as form parameters.

```php
public function getBodyAsFormParams(): array
```

## Request Modification Methods

### `withBody()`

Return an instance with the specified body.

```php
public function withBody($body): static
```

### `withContentType()`

Set the content type of the request.

```php
public function withContentType(ContentType|string $contentType): static
```

### `withQueryParam()`

Set a query parameter on the request URI.

```php
public function withQueryParam(string $name, string|int|float|bool|null $value): static
```

### `withQueryParams()`

Set multiple query parameters on the request URI.

```php
public function withQueryParams(array $params): static
```

### `withBearerToken()`

Set an authorization header with a bearer token.

```php
public function withBearerToken(string $token): static
```

### `withBasicAuth()`

Set a basic authentication header.

```php
public function withBasicAuth(string $username, string $password): static
```

### `withJsonBody()`

Set a JSON body on the request.

```php
public function withJsonBody(array $data, int $options = 0): static
```

### `withFormBody()`

Set a form body on the request.

```php
public function withFormBody(array $data): static
```

## PSR-7 Methods (from RequestImmutabilityTrait)

These methods override the PSR-7 request methods to ensure immutability and proper type preservation.

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

### `withUri()`

Return an instance with the specified URI.

```php
public function withUri(UriInterface $uri, $preserveHost = false): static
```

### `withMethod()`

Return an instance with the provided HTTP method.

```php
public function withMethod($method): static
```
