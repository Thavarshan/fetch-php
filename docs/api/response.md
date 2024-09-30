# `Response` Class API Reference

The `Response` class in FetchPHP handles the responses from HTTP requests. It extends Guzzle's `Response` class and implements `Fetch\Interfaces\Response`. This class provides methods for interacting with the response data, including handling JSON, plain text, binary data, and streams, as well as methods for checking status codes.

## Class Definition

```php
namespace Fetch\Http;

class Response extends \GuzzleHttp\Psr7\Response implements ResponseInterface
```

The `Response` class handles the response body and status, providing utility methods to parse the response and check status codes.

## Constructor

```php
public function __construct(
    int $status = 200,
    array $headers = [],
    string $body = '',
    string $version = '1.1',
    string $reason = null
)
```

### Parameters

- **`$status`** (int): The HTTP status code (e.g., 200, 404).
- **`$headers`** (array): An associative array of headers.
- **`$body`** (string): The response body as a string.
- **`$version`** (string): The HTTP protocol version (e.g., '1.1').
- **`$reason`** (string|null): The reason phrase for the status code (optional).

## Available Methods

### **`json()`**

```php
public function json(bool $assoc = true, bool $throwOnError = true): mixed
```

Parses the response body as JSON.

- **`$assoc`** (bool): If `true`, returns the JSON data as an associative array. If `false`, returns it as an object.
- **`$throwOnError`** (bool): If `true`, throws an exception if the body is not valid JSON.

**Returns**: The parsed JSON as an array or object, or `null` if invalid and `$throwOnError` is `false`.

### Example

```php
$data = $response->json();
```

### **`text()`**

```php
public function text(): string
```

Returns the raw response body as a plain text string.

**Returns**: The response body as a string.

### Example

```php
$content = $response->text();
```

### **`blob()`**

```php
public function blob(): resource|false
```

Returns the response body as a stream (similar to a "blob" in JavaScript).

**Returns**: A stream resource or `false` on failure.

### Example

```php
$stream = $response->blob();
```

### **`arrayBuffer()`**

```php
public function arrayBuffer(): string
```

Returns the response body as a binary string (array buffer).

**Returns**: The raw binary data as a string.

### Example

```php
$binaryData = $response->arrayBuffer();
```

### **`statusText()`**

```php
public function statusText(): string
```

Returns the reason phrase associated with the status code (e.g., "OK" for a 200 status).

**Returns**: The reason phrase or a default message if none is available.

### Example

```php
$statusMessage = $response->statusText();
```

### **`getStatusCode()`**

```php
public function getStatusCode(): int
```

Returns the HTTP status code of the response.

**Returns**: The status code as an integer.

### Example

```php
$statusCode = $response->getStatusCode();
```

### **`createFromBase()`**

```php
public static function createFromBase(PsrResponseInterface $response): self
```

Creates a new `Response` instance from a base PSR-7 response.

- **`$response`**: A PSR-7 response object from which to create the new `Response`.

**Returns**: A new `Response` instance.

### Example

```php
$response = Response::createFromBase($psrResponse);
```

## Status Code Checking

The `Response` class provides several methods to check the status code category of the response.

### **`isInformational()`**

```php
public function isInformational(): bool
```

Checks if the status code is informational (1xx).

**Returns**: `true` if the status code is in the 100-199 range.

### **`ok()`**

```php
public function ok(): bool
```

Checks if the status code is successful (2xx).

**Returns**: `true` if the status code is in the 200-299 range.

### Example

```php
if ($response->ok()) {
    // Handle successful response
}
```

### **`isRedirection()`**

```php
public function isRedirection(): bool
```

Checks if the status code is a redirection (3xx).

**Returns**: `true` if the status code is in the 300-399 range.

### **`isClientError()`**

```php
public function isClientError(): bool
```

Checks if the status code indicates a client error (4xx).

**Returns**: `true` if the status code is in the 400-499 range.

### **`isServerError()`**

```php
public function isServerError(): bool
```

Checks if the status code indicates a server error (5xx).

**Returns**: `true` if the status code is in the 500-599 range.
