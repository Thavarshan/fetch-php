# Response Class

The `Response` class represents an HTTP response and provides methods for processing and accessing the response data. It extends Guzzle's PSR-7 Response implementation and implements `ArrayAccess` for convenient access to JSON response data.

## Class Declaration

```php
namespace Fetch\Http;

use ArrayAccess;
use Fetch\Interfaces\Response as ResponseInterface;
use GuzzleHttp\Psr7\Response as BaseResponse;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class Response extends BaseResponse implements ArrayAccess, ResponseInterface
{
    // ...
}
```

## Constructor

```php
public function __construct(
    int $status = 200,
    array $headers = [],
    string $body = '',
    string $version = '1.1',
    ?string $reason = null
)
```

### Parameters

- `$status`: HTTP status code
- `$headers`: Response headers array
- `$body`: Response body content
- `$version`: HTTP protocol version
- `$reason`: Status reason phrase

## Static Methods

### `createFromBase()`

Creates a new Response instance from a PSR-7 ResponseInterface.

```php
public static function createFromBase(PsrResponseInterface $response): self
```

## Response Body Methods

### `json()`

Parses the response body as JSON.

```php
public function json(bool $assoc = true, bool $throwOnError = true, int $depth = 512, int $options = 0)
```

### `object()`

Gets the response body as a JSON-decoded object.

```php
public function object(bool $throwOnError = true)
```

### `array()`

Gets the response body as a JSON-decoded array.

```php
public function array(bool $throwOnError = true): array
```

### `text()`

Gets the response body as plain text.

```php
public function text(): string
```

### `body()`

Gets the raw response body content.

```php
public function body(): string
```

### `blob()`

Gets the body as a stream.

```php
public function blob()
```

### `arrayBuffer()`

Gets the body as binary data.

```php
public function arrayBuffer(): string
```

### `xml()`

Parses the body as XML.

```php
public function xml(int $options = 0, bool $throwOnError = true): ?SimpleXMLElement
```

## Status Methods

### `status()`

Gets the response status code.

```php
public function status(): int
```

### `statusText()`

Gets the response status text.

```php
public function statusText(): string
```

### `ok()`

Checks if the response status is successful (2xx).

```php
public function ok(): bool
```

### `successful()`

Alias for `ok()`.

```php
public function successful(): bool
```

### `failed()`

Checks if the response status indicates an error (4xx or 5xx).

```php
public function failed(): bool
```

### `isInformational()`

Checks if the response status is informational (1xx).

```php
public function isInformational(): bool
```

### `redirect()`

Checks if the response is a redirect (3xx).

```php
public function redirect(): bool
```

### `isRedirection()`

Alias for `redirect()`.

```php
public function isRedirection(): bool
```

### `clientError()`

Checks if the response is a client error (4xx).

```php
public function clientError(): bool
```

### `isClientError()`

Alias for `clientError()`.

```php
public function isClientError(): bool
```

### `serverError()`

Checks if the response is a server error (5xx).

```php
public function serverError(): bool
```

### `isServerError()`

Alias for `serverError()`.

```php
public function isServerError(): bool
```

### Status Code Checks

```php
public function isStatus(int $status): bool         // Check specific status code
public function isOk(): bool                        // 200
public function isCreated(): bool                   // 201
public function isAccepted(): bool                  // 202
public function isNoContent(): bool                 // 204
public function isMovedPermanently(): bool          // 301
public function isFound(): bool                     // 302
public function isBadRequest(): bool                // 400
public function isUnauthorized(): bool              // 401
public function isForbidden(): bool                 // 403
public function isNotFound(): bool                  // 404
public function isConflict(): bool                  // 409
public function isUnprocessableEntity(): bool       // 422
public function isTooManyRequests(): bool           // 429
public function isInternalServerError(): bool       // 500
public function isServiceUnavailable(): bool        // 503
```

## Header Methods

### `headers()`

Gets all response headers.

```php
public function headers(): array
```

### `header()`

Gets a specific response header.

```php
public function header(string $header): ?string
```

### `hasHeader()`

Checks if a specific header exists.

```php
public function hasHeader($header): bool
```

### `contentType()`

Gets the Content-Type header from the response.

```php
public function contentType(): ?string
```

## Data Access Methods

### `get()`

Gets a value from the JSON response by key.

```php
public function get(string $key, mixed $default = null): mixed
```

### Array Access Implementation

The `Response` class implements `ArrayAccess`, allowing you to access JSON response data as an array:

```php
public function offsetExists($offset): bool
public function offsetGet($offset): mixed
public function offsetSet($offset, $value): void    // Throws RuntimeException
public function offsetUnset($offset): void          // Throws RuntimeException
```

### `__toString()`

Converts the response to a string, returning the raw body content.

```php
public function __toString(): string
```

## Examples

### Processing JSON Responses

```php
$response = fetch('https://api.example.com/users/1');

if ($response->ok()) {
    // Parse as associative array
    $user = $response->json();
    echo "User ID: " . $user['id'] . ", Name: " . $user['name'];

    // Or parse as object
    $userObj = $response->object();
    echo "User ID: " . $userObj->id . ", Name: " . $userObj->name;

    // Array access syntax
    echo "User email: " . $response['email'];

    // Get method with default value
    $role = $response->get('role', 'user');
}
```

### Checking Response Status

```php
$response = fetch('https://api.example.com/users/1');

// Check general status categories
if ($response->successful()) {
    echo "Request was successful (2xx)";
} elseif ($response->clientError()) {
    echo "Client error occurred (4xx)";
} elseif ($response->serverError()) {
    echo "Server error occurred (5xx)";
}

// Check specific status codes
if ($response->isOk()) {
    echo "Status 200 OK";
} elseif ($response->isNotFound()) {
    echo "Status 404 Not Found";
} elseif ($response->isUnauthorized()) {
    echo "Status 401 Unauthorized";
}
```

### Working with Headers

```php
$response = fetch('https://api.example.com/users/1');

// Get a specific header
$contentType = $response->header('Content-Type');
echo "Content-Type: " . $contentType;

// Check if a header exists
if ($response->hasHeader('X-Rate-Limit')) {
    $rateLimit = $response->header('X-Rate-Limit');
    echo "Rate limit: " . $rateLimit;
}

// Get all headers
$headers = $response->headers();
foreach ($headers as $name => $values) {
    echo $name . ": " . implode(", ", $values) . "\n";
}
```

### Working with Different Response Types

```php
// JSON response
$response = fetch('https://api.example.com/users');
$users = $response->json();

// XML response
$response = fetch('https://api.example.com/feed.xml');
$xml = $response->xml();
echo "Title: " . $xml->title;

// Plain text response
$response = fetch('https://api.example.com/robots.txt');
$text = $response->text();
echo $text;

// Binary response (e.g., image or file download)
$response = fetch('https://api.example.com/image.jpg');
file_put_contents('downloaded-image.jpg', $response->body());

// Or using a stream
$response = fetch('https://api.example.com/large-file.zip');
$stream = $response->blob();
$localFile = fopen('large-file.zip', 'w');
stream_copy_to_stream($stream, $localFile);
fclose($localFile);
```

### Error Handling

```php
$response = fetch('https://api.example.com/users/999');

if ($response->failed()) {
    echo "Request failed with status: " . $response->status();

    // For API errors that return JSON error details
    if ($response->contentType() === 'application/json') {
        $error = $response->json();
        echo "Error message: " . ($error['message'] ?? 'Unknown error');
        echo "Error code: " . ($error['code'] ?? 'No code');
    }
}

// Specific error handling based on status code
if ($response->isNotFound()) {
    echo "Resource not found";
} elseif ($response->isUnauthorized()) {
    echo "Authentication required";
} elseif ($response->isForbidden()) {
    echo "Access denied";
} elseif ($response->isUnprocessableEntity()) {
    echo "Validation error";

    // Process validation errors
    $errors = $response->json()['errors'] ?? [];
    foreach ($errors as $field => $messages) {
        echo $field . ": " . implode(", ", $messages) . "\n";
    }
}
```
