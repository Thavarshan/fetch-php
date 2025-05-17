---
title: Working with Enums
description: Guide on using enums in the Fetch PHP client package
---

# Working with Enums

Fetch PHP makes extensive use of PHP 8.1's enum feature to provide type safety and better developer experience. This guide explains how to effectively work with the three main enums in the package: `Method`, `ContentType`, and `Status`.

## Overview of Enums in Fetch PHP

Fetch PHP includes three key enums that represent common HTTP concepts:

1. **Method** - HTTP methods like GET, POST, PUT, etc.
2. **ContentType** - MIME types for HTTP request and response bodies
3. **Status** - HTTP status codes and their meanings

These enums provide several benefits:

- Type safety and autocompletion in your IDE
- Helper methods for common operations
- Documentation and standardization
- Improved code readability

## Using the Method Enum

The `Method` enum represents HTTP methods and helps ensure you're using valid methods in your requests.

```php
use Fetch\Enum\Method;

// Using enum values directly
$response = fetch('https://api.example.com/users', [
    'method' => Method::GET
]);

// Using string values (automatically converted)
$response = fetch('https://api.example.com/users', [
    'method' => 'POST'
]);

// Using with the ClientHandler
$response = fetch_client()
    ->request(Method::PUT, 'https://api.example.com/users/1', $data);
```

### Method Enum Features

#### Available Values

```php
Method::GET    // "GET"
Method::POST   // "POST"
Method::PUT    // "PUT"
Method::PATCH  // "PATCH"
Method::DELETE // "DELETE"
Method::HEAD   // "HEAD"
Method::OPTIONS // "OPTIONS"
```

#### Converting Strings to Method Enum

```php
// Convert a string to a Method enum (throws ValueError if invalid)
$method = Method::fromString('get');  // Returns Method::GET
$method = Method::fromString('post'); // Returns Method::POST

// Safely try to convert a string to a Method enum
$method = Method::tryFromString('get');          // Returns Method::GET
$method = Method::tryFromString('invalid', Method::GET); // Returns Method::GET (default)
```

#### Checking Method Properties

```php
// Check if a method supports a request body
if (Method::POST->supportsRequestBody()) {
    // Add body to the request
}

// Only methods that support request bodies will return true
Method::POST->supportsRequestBody();   // true
Method::PUT->supportsRequestBody();    // true
Method::PATCH->supportsRequestBody();  // true
Method::DELETE->supportsRequestBody(); // true
Method::GET->supportsRequestBody();    // false
Method::HEAD->supportsRequestBody();   // false
```

## Using the ContentType Enum

The `ContentType` enum represents MIME types for HTTP content and provides utilities for working with different content formats.

```php
use Fetch\Enum\ContentType;

// Using enum in request methods
$response = fetch_client()->post(
    'https://api.example.com/users',
    ['name' => 'John Doe'],
    ContentType::JSON
);

// Setting headers with enum values
$response = fetch_client()
    ->withHeader('Content-Type', ContentType::FORM_URLENCODED->value)
    ->post('https://api.example.com/login', $formData);
```

### ContentType Enum Features

#### Available Values

```php
ContentType::JSON            // "application/json"
ContentType::FORM_URLENCODED // "application/x-www-form-urlencoded"
ContentType::MULTIPART       // "multipart/form-data"
ContentType::TEXT            // "text/plain"
ContentType::HTML            // "text/html"
ContentType::XML             // "application/xml"
ContentType::XML_TEXT        // "text/xml"
ContentType::BINARY          // "application/octet-stream"
ContentType::PDF             // "application/pdf"
ContentType::CSV             // "text/csv"
ContentType::ZIP             // "application/zip"
ContentType::JAVASCRIPT      // "application/javascript"
ContentType::CSS             // "text/css"
```

#### Converting Strings to ContentType Enum

```php
// Convert a string to a ContentType enum
$type = ContentType::fromString('application/json'); // Returns ContentType::JSON

// Safely try to convert a string to a ContentType enum
$type = ContentType::tryFromString('application/json'); // Returns ContentType::JSON
$type = ContentType::tryFromString('invalid/type');    // Returns null
```

#### Normalizing Content Types

The `normalizeContentType()` method is particularly useful when your API needs to accept both string content types and enum values:

```php
use Fetch\Enum\ContentType;

function processRequest($data, string|ContentType $contentType) {
    // Normalize the content type
    $normalizedType = ContentType::normalizeContentType($contentType);

    // If it's a known enum value, we can use the helper methods
    if ($normalizedType instanceof ContentType) {
        if ($normalizedType->isJson()) {
            return json_decode($data, true);
        }
    }

    // Otherwise, work with it as a string
    return $data;
}

// Both of these work:
processRequest($data, ContentType::JSON);
processRequest($data, 'application/json');
```

#### Checking Content Type Properties

```php
// Check if a content type is JSON
ContentType::JSON->isJson();  // true
ContentType::HTML->isJson();  // false

// Check if a content type is form-urlencoded
ContentType::FORM_URLENCODED->isForm();  // true
ContentType::MULTIPART->isForm();        // false

// Check if a content type is multipart form data
ContentType::MULTIPART->isMultipart();  // true
ContentType::JSON->isMultipart();       // false

// Check if a content type is text-based
ContentType::JSON->isText();            // true
ContentType::HTML->isText();            // true
ContentType::MULTIPART->isText();       // false
ContentType::BINARY->isText();          // false
```

## Using the Status Enum

The `Status` enum represents HTTP status codes and provides utilities for working with different response types.

```php
use Fetch\Enum\Status;

// Get a response
$response = fetch('https://api.example.com/users');

// Check status using enum comparison
if ($response->getStatus() === Status::OK) {
    // Process successful response
}

// Or using helper methods
if ($response->getStatus()->isSuccess()) {
    // Process successful response
} elseif ($response->getStatus()->isClientError()) {
    // Handle client error
}
```

### Status Enum Features

#### Available Values

The Status enum includes all standard HTTP status codes. Here are some common ones:

```php
// 2xx Success
Status::OK             // 200
Status::CREATED        // 201
Status::ACCEPTED       // 202
Status::NO_CONTENT     // 204

// 3xx Redirection
Status::MOVED_PERMANENTLY // 301
Status::FOUND            // 302
Status::NOT_MODIFIED     // 304

// 4xx Client Error
Status::BAD_REQUEST              // 400
Status::UNAUTHORIZED             // 401
Status::FORBIDDEN                // 403
Status::NOT_FOUND                // 404
Status::METHOD_NOT_ALLOWED       // 405
Status::UNPROCESSABLE_ENTITY     // 422
Status::TOO_MANY_REQUESTS        // 429

// 5xx Server Error
Status::INTERNAL_SERVER_ERROR    // 500
Status::BAD_GATEWAY              // 502
Status::SERVICE_UNAVAILABLE      // 503
Status::GATEWAY_TIMEOUT          // 504
```

See the [Status Enum API Reference](/api/status-enum) for a complete list of available status codes.

#### Converting Integers to Status Enum

```php
// Convert an integer to a Status enum
$status = Status::fromInt(200);  // Returns Status::OK

// Safely try to convert an integer to a Status enum
$status = Status::tryFromInt(200);           // Returns Status::OK
$status = Status::tryFromInt(999, Status::OK); // Returns Status::OK (default)
```

#### Getting the Reason Phrase

```php
// Get the reason phrase for a status code
Status::OK->phrase();                // "OK"
Status::NOT_FOUND->phrase();         // "Not Found"
Status::INTERNAL_SERVER_ERROR->phrase(); // "Internal Server Error"
```

#### Checking Status Code Categories

```php
// Informational responses (100-199)
Status::CONTINUE->isInformational();  // true
Status::OK->isInformational();        // false

// Success responses (200-299)
Status::OK->isSuccess();           // true
Status::CREATED->isSuccess();      // true
Status::NOT_FOUND->isSuccess();    // false

// Redirection responses (300-399)
Status::FOUND->isRedirection();       // true
Status::MOVED_PERMANENTLY->isRedirection(); // true
Status::OK->isRedirection();          // false

// Client error responses (400-499)
Status::BAD_REQUEST->isClientError();    // true
Status::NOT_FOUND->isClientError();      // true
Status::OK->isClientError();             // false

// Server error responses (500-599)
Status::INTERNAL_SERVER_ERROR->isServerError(); // true
Status::BAD_GATEWAY->isServerError();           // true
Status::NOT_FOUND->isServerError();             // false

// Any error (either client or server)
Status::NOT_FOUND->isError();               // true
Status::INTERNAL_SERVER_ERROR->isError();   // true
Status::OK->isError();                      // false
```

#### Special Status Properties

```php
// Check if a status code indicates the resource was not modified
Status::NOT_MODIFIED->isNotModified();  // true
Status::OK->isNotModified();            // false

// Check if a status code indicates an empty response
Status::NO_CONTENT->isEmpty();     // true
Status::NOT_MODIFIED->isEmpty();   // true
Status::OK->isEmpty();             // false

// Check if a response with this status is cacheable
Status::OK->isCacheable();            // true
Status::NOT_FOUND->isCacheable();     // true
Status::NO_CONTENT->isCacheable();    // false
```

## Best Practices for Working with Enums

1. **Use Enum Values Directly** - When possible, use enum values directly for better type safety:

```php
// Good - uses enum
$response = fetch_client()->request(Method::POST, '/users', $data);

// Also good - string is automatically converted
$response = fetch_client()->request('POST', '/users', $data);
```

2. **Take Advantage of Helper Methods** - Enums provide semantic helper methods:

```php
// Instead of checking numeric ranges
if ($status >= 200 && $status < 300) { ... }

// Use the helper methods
if ($status->isSuccess()) { ... }
```

3. **Leverage IDE Autocompletion** - Enums provide excellent autocomplete support:

```php
// Start typing Method:: and see all available options
$method = Method::POST;

// Similarly with ContentType:: and Status::
$contentType = ContentType::JSON;
$status = Status::OK;
```

4. **Accept Both Strings and Enums in Your APIs** - Use union types and normalizers:

```php
function sendRequest(string|Method $method, string $url) {
    // Convert string to enum if needed
    $methodEnum = $method instanceof Method
        ? $method
        : Method::tryFromString($method, Method::GET);

    // Now you can use the enum features
    if ($methodEnum->supportsRequestBody()) {
        // Add body
    }
}
```

5. **Compose with Enums** - Use enums for more expressive code:

```php
// Build a request with enums
$request = fetch_client()
    ->withHeader('Content-Type', ContentType::JSON->value)
    ->withBody($data, ContentType::JSON)
    ->sendRequest(Method::POST, 'https://api.example.com/users');

// Check response using enums
if ($response->getStatus() === Status::CREATED) {
    // Resource was created
}
```

## Integration with Response Object

The Fetch PHP Response object integrates well with Status and ContentType enums:

```php
$response = fetch('https://api.example.com/users');

// Get status as enum
$statusEnum = $response->statusEnum();

// Check specific status
if ($statusEnum === Status::OK) {
    // Process OK response
}

// Get content type as enum
$contentTypeEnum = $response->contentTypeEnum();

// Check content type
if ($contentTypeEnum === ContentType::JSON) {
    // Process JSON response
}

// Or use the helper methods
if ($response->hasJsonContent()) {
    $data = $response->json();
}
```

## Conclusion

Using enums in your Fetch PHP code leads to more readable, maintainable, and type-safe HTTP requests. The `Method`, `ContentType`, and `Status` enums provide a rich set of utilities that make working with HTTP concepts easier and more expressive.

Leverage these enums throughout your application to take full advantage of PHP 8.1's powerful enum feature and the enhancements provided by Fetch PHP.
