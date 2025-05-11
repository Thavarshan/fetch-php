---
title: Status Enum API Reference
description: API reference for the Status enum in the Fetch HTTP client package
---

# Status Enum

The `Status` enum represents HTTP status codes with their respective descriptions. It provides type-safe constants for HTTP status codes and helper methods to work with them.

## Namespace

```php
namespace Fetch\Enum;
```

## Definition

```php
enum Status: int
{
    // 1xx - Informational
    case CONTINUE = 100;
    case SWITCHING_PROTOCOLS = 101;
    case PROCESSING = 102;
    case EARLY_HINTS = 103;

    // 2xx - Success
    case OK = 200;
    case CREATED = 201;
    case ACCEPTED = 202;
    case NON_AUTHORITATIVE_INFORMATION = 203;
    case NO_CONTENT = 204;
    case RESET_CONTENT = 205;
    case PARTIAL_CONTENT = 206;
    case MULTI_STATUS = 207;
    case ALREADY_REPORTED = 208;
    case IM_USED = 226;

    // 3xx - Redirection
    case MULTIPLE_CHOICES = 300;
    case MOVED_PERMANENTLY = 301;
    case FOUND = 302;
    case SEE_OTHER = 303;
    case NOT_MODIFIED = 304;
    case USE_PROXY = 305;
    case TEMPORARY_REDIRECT = 307;
    case PERMANENT_REDIRECT = 308;

    // 4xx - Client Error
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case PAYMENT_REQUIRED = 402;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case NOT_ACCEPTABLE = 406;
    case PROXY_AUTHENTICATION_REQUIRED = 407;
    case REQUEST_TIMEOUT = 408;
    case CONFLICT = 409;
    case GONE = 410;
    case LENGTH_REQUIRED = 411;
    case PRECONDITION_FAILED = 412;
    case PAYLOAD_TOO_LARGE = 413;
    case URI_TOO_LONG = 414;
    case UNSUPPORTED_MEDIA_TYPE = 415;
    case RANGE_NOT_SATISFIABLE = 416;
    case EXPECTATION_FAILED = 417;
    case IM_A_TEAPOT = 418;
    case MISDIRECTED_REQUEST = 421;
    case UNPROCESSABLE_ENTITY = 422;
    case LOCKED = 423;
    case FAILED_DEPENDENCY = 424;
    case TOO_EARLY = 425;
    case UPGRADE_REQUIRED = 426;
    case PRECONDITION_REQUIRED = 428;
    case TOO_MANY_REQUESTS = 429;
    case REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    case UNAVAILABLE_FOR_LEGAL_REASONS = 451;

    // 5xx - Server Error
    case INTERNAL_SERVER_ERROR = 500;
    case NOT_IMPLEMENTED = 501;
    case BAD_GATEWAY = 502;
    case SERVICE_UNAVAILABLE = 503;
    case GATEWAY_TIMEOUT = 504;
    case HTTP_VERSION_NOT_SUPPORTED = 505;
    case VARIANT_ALSO_NEGOTIATES = 506;
    case INSUFFICIENT_STORAGE = 507;
    case LOOP_DETECTED = 508;
    case NOT_EXTENDED = 510;
    case NETWORK_AUTHENTICATION_REQUIRED = 511;

    /**
     * Get a status code from a string or integer.
     *
     * @throws \ValueError If the status code is invalid
     */
    public static function fromInt(int $statusCode): self
    {
        return self::from($statusCode);
    }

    /**
     * Try to get a status code from an integer, or return default.
     */
    public static function tryFromInt(int $statusCode, ?self $default = null): ?self
    {
        return self::tryFrom($statusCode) ?? $default;
    }

    /**
     * Get the reason phrase for a status code.
     */
    public function phrase(): string
    {
        // Implementation details
    }

    /**
     * Check if the status code is informational (1xx).
     */
    public function isInformational(): bool
    {
        return $this->value >= 100 && $this->value < 200;
    }

    /**
     * Check if the status code indicates success (2xx).
     */
    public function isSuccess(): bool
    {
        return $this->value >= 200 && $this->value < 300;
    }

    /**
     * Check if the status code indicates redirection (3xx).
     */
    public function isRedirection(): bool
    {
        return $this->value >= 300 && $this->value < 400;
    }

    /**
     * Check if the status code indicates client error (4xx).
     */
    public function isClientError(): bool
    {
        return $this->value >= 400 && $this->value < 500;
    }

    /**
     * Check if the status code indicates server error (5xx).
     */
    public function isServerError(): bool
    {
        return $this->value >= 500 && $this->value < 600;
    }

    /**
     * Check if the status code indicates an error (4xx or 5xx).
     */
    public function isError(): bool
    {
        return $this->isClientError() || $this->isServerError();
    }

    /**
     * Check if the response is cacheable according to HTTP specifications.
     */
    public function isCacheable(): bool
    {
        return match ($this) {
            self::OK, self::NON_AUTHORITATIVE_INFORMATION, self::PARTIAL_CONTENT,
            self::MULTIPLE_CHOICES, self::MOVED_PERMANENTLY, self::NOT_FOUND,
            self::METHOD_NOT_ALLOWED, self::GONE => true,
            default => false,
        };
    }

    /**
     * Check if the status code indicates that the resource was not modified.
     */
    public function isNotModified(): bool
    {
        return $this === self::NOT_MODIFIED;
    }

    /**
     * Check if the status code indicates that the resource was empty (204, 304).
     */
    public function isEmpty(): bool
    {
        return $this === self::NO_CONTENT || $this === self::NOT_MODIFIED;
    }
}
```

## Available Constants

Here's a selection of the most commonly used status codes. For the complete list, refer to the enum definition.

### 1xx - Informational

| Constant | Value | Description |
|----------|-------|-------------|
| `Status::CONTINUE` | 100 | The server has received the request headers and the client should proceed to send the request body. |
| `Status::SWITCHING_PROTOCOLS` | 101 | The requester has asked the server to switch protocols. |

### 2xx - Success

| Constant | Value | Description |
|----------|-------|-------------|
| `Status::OK` | 200 | The request has succeeded. |
| `Status::CREATED` | 201 | The request has been fulfilled and a new resource has been created. |
| `Status::ACCEPTED` | 202 | The request has been accepted for processing, but the processing has not been completed. |
| `Status::NO_CONTENT` | 204 | The server successfully processed the request, but is not returning any content. |

### 3xx - Redirection

| Constant | Value | Description |
|----------|-------|-------------|
| `Status::MOVED_PERMANENTLY` | 301 | The requested resource has been assigned a new permanent URI. |
| `Status::FOUND` | 302 | The resource was found, but at a different URI. |
| `Status::NOT_MODIFIED` | 304 | The resource has not been modified since the last request. |

### 4xx - Client Error

| Constant | Value | Description |
|----------|-------|-------------|
| `Status::BAD_REQUEST` | 400 | The server cannot process the request due to a client error. |
| `Status::UNAUTHORIZED` | 401 | Authentication is required and has failed or has not been provided. |
| `Status::FORBIDDEN` | 403 | The server understood the request but refuses to authorize it. |
| `Status::NOT_FOUND` | 404 | The requested resource could not be found. |
| `Status::METHOD_NOT_ALLOWED` | 405 | The request method is not supported for the requested resource. |
| `Status::TOO_MANY_REQUESTS` | 429 | The user has sent too many requests in a given amount of time. |

### 5xx - Server Error

| Constant | Value | Description |
|----------|-------|-------------|
| `Status::INTERNAL_SERVER_ERROR` | 500 | The server encountered an unexpected condition that prevented it from fulfilling the request. |
| `Status::BAD_GATEWAY` | 502 | The server received an invalid response from an upstream server. |
| `Status::SERVICE_UNAVAILABLE` | 503 | The server is currently unable to handle the request due to temporary overloading or maintenance. |
| `Status::GATEWAY_TIMEOUT` | 504 | The server did not receive a timely response from an upstream server. |

## Methods

### fromInt()

Converts an integer status code to a Status enum value. Throws an exception if the code doesn't match any valid status.

```php
public static function fromInt(int $statusCode): self
```

**Parameters:**

- `$statusCode`: An integer representing an HTTP status code

**Returns:**

- The corresponding Status enum value

**Throws:**

- `\ValueError` if the integer doesn't represent a valid HTTP status code

**Example:**

```php
$status = Status::fromInt(200); // Returns Status::OK
```

### tryFromInt()

Attempts to convert an integer to a Status enum value. Returns a default value if the integer doesn't match any valid status.

```php
public static function tryFromInt(int $statusCode, ?self $default = null): ?self
```

**Parameters:**

- `$statusCode`: An integer representing an HTTP status code
- `$default`: The default enum value to return if the integer doesn't match (defaults to null)

**Returns:**

- The corresponding Status enum value or the default value

**Example:**

```php
$status = Status::tryFromInt(200); // Returns Status::OK
$status = Status::tryFromInt(999, Status::OK); // Returns Status::OK as fallback
```

### phrase()

Returns the standard reason phrase for the status code.

```php
public function phrase(): string
```

**Returns:**

- A string containing the standard reason phrase for the HTTP status code

**Example:**

```php
$text = Status::OK->phrase(); // Returns "OK"
$text = Status::NOT_FOUND->phrase(); // Returns "Not Found"
```

### isInformational()

Checks if the status code is in the informational range (100-199).

```php
public function isInformational(): bool
```

**Returns:**

- `true` if the status code is between 100 and 199, `false` otherwise

**Example:**

```php
if (Status::CONTINUE->isInformational()) {
    // Handle informational response
}
```

### isSuccess()

Checks if the status code is in the success range (200-299).

```php
public function isSuccess(): bool
```

**Returns:**

- `true` if the status code is between 200 and 299, `false` otherwise

**Example:**

```php
if (Status::OK->isSuccess()) {
    // Handle successful response
}
```

### isCacheable()

Checks if the status code indicates a cacheable response according to HTTP specifications.

```php
public function isCacheable(): bool
```

**Returns:**

- `true` if the status code is considered cacheable (200, 203, 206, 300, 301, 404, 405, 410), `false` otherwise

**Example:**

```php
if ($status->isCacheable()) {
    // This response can be cached
}
```

### isNotModified()

Checks if the status code indicates the resource has not been modified (304).

```php
public function isNotModified(): bool
```

**Returns:**

- `true` if the status is NOT_MODIFIED (304), `false` otherwise

**Example:**

```php
if ($status->isNotModified()) {
    // Use the cached version of the resource
}
```

### isEmpty()

Checks if the status code indicates an empty response body (204, 304).

```php
public function isEmpty(): bool
```

**Returns:**

- `true` if the status is NO_CONTENT (204) or NOT_MODIFIED (304), `false` otherwise

**Example:**

```php
if ($status->isEmpty()) {
    // Don't attempt to parse response body as it should be empty
}
