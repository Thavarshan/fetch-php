---
title: Method Enum API Reference
description: API reference for the Method enum in the Fetch HTTP client package
---

# Method Enum

The `Method` enum represents HTTP request methods supported by the Fetch package. It provides type-safe constants for HTTP methods and helper methods to work with them.

## Namespace

```php
namespace Fetch\Enum;
```

## Definition

```php
enum Method: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';

    /**
     * Get the method from a string.
     */
    public static function fromString(string $method): self
    {
        return self::from(strtoupper($method));
    }

    /**
     * Try to get the method from a string, or return default.
     */
    public static function tryFromString(string $method, ?self $default = null): ?self
    {
        return self::tryFrom(strtoupper($method)) ?? $default;
    }

    /**
     * Determine if the method supports a request body.
     */
    public function supportsRequestBody(): bool
    {
        return in_array($this, [self::POST, self::PUT, self::PATCH, self::DELETE]);
    }
}
```

## Available Constants

 a GET request, but without the response body. |
| `Method::OPTIONS` | `"OPTIONS"` | The OPTIONS method describes the communication options for the target resource. |
| `Method::TRACE` | `"TRACE"` | The TRACE method performs a message loop-back test along the path to the target resource. |
| `Method::CONNECT` | `"CONNECT"` | The CONNECT method establishes a tunnel to the server identified by the target resource. |

## Methods

### fromString()

Converts a string to a Method enum value. Throws an exception if the string doesn't match any valid method.

```php
public static function fromString(string $method): self
```

**Parameters:**

- `$method`: A string representing an HTTP method (case-insensitive)

**Returns:**

- The corresponding Method enum value

**Throws:**

- `\ValueError` if the string doesn't represent a valid HTTP method

**Example:**

```php
$method = Method::fromString('post'); // Returns Method::POST
```

### tryFromString()

Attempts to convert a string to a Method enum value. Returns a default value if the string doesn't match any valid method.

```php
public static function tryFromString(string $method, ?self $default = null): ?self
```

**Parameters:**

- `$method`: A string representing an HTTP method (case-insensitive)
- `$default`: The default enum value to return if the string doesn't match (defaults to null)

**Returns:**

- The corresponding Method enum value or the default value

**Example:**

```php
$method = Method::tryFromString('post'); // Returns Method::POST
$method = Method::tryFromString('INVALID', Method::GET); // Returns Method::GET
```

### supportsRequestBody()

Determines whether the HTTP method supports a request body.

```php
public function supportsRequestBody(): bool
```

**Returns:**

- `true` for POST, PUT, PATCH, and DELETE methods
- `false` for GET, HEAD, and OPTIONS methods

**Example:**

```php
if (Method::POST->supportsRequestBody()) {
    // Configure request body
}
```

## Usage Examples

### With ClientHandler

```php
use Fetch\Enum\Method;
use Fetch\Http\ClientHandler;

// Using enum directly
$response = ClientHandler::handle(Method::GET->value, 'https://api.example.com/users');

// Checking for request body support
$method = Method::POST;
if ($method->supportsRequestBody()) {
    // Configure the request body
}
```

### Converting from String

```php
use Fetch\Enum\Method;

// From request input (safely handling potential errors)
$methodString = $_POST['method'] ?? 'GET';
$method = Method::tryFromString($methodString, Method::GET);

// Converting when you expect the method to be valid
try {
    $method = Method::fromString('PATCH');
} catch (\ValueError $e) {
    // Handle invalid method
}
```

### In Method Selection Logic

```php
use Fetch\Enum\Method;

function processRequest(string $methodString, string $uri, ?array $body): Response
{
    $method = Method::tryFromString($methodString, Method::GET);

    return match($method) {
        Method::GET => fetch_client()->get($uri),
        Method::POST => fetch_client()->post($uri, $body),
        Method::PUT => fetch_client()->put($uri, $body),
        Method::PATCH => fetch_client()->patch($uri, $body),
        Method::DELETE => fetch_client()->delete($uri, $body),
        default => throw new InvalidArgumentException("Unsupported method: {$methodString}")
    };
}
```
