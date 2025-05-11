---
title: ContentType Enum API Reference
description: API reference for the ContentType enum in the Fetch HTTP client package
---

# ContentType Enum

The `ContentType` enum represents common MIME types (content types) used in HTTP requests and responses. It provides type-safe constants for content types and helper methods to work with them.

## Namespace

```php
namespace Fetch\Enum;
```

## Definition

```php
enum ContentType: string
{
    case JSON = 'application/json';
    case FORM_URLENCODED = 'application/x-www-form-urlencoded';
    case MULTIPART = 'multipart/form-data';
    case TEXT = 'text/plain';
    case HTML = 'text/html';
    case XML = 'application/xml';
    case XML_TEXT = 'text/xml';
    case BINARY = 'application/octet-stream';
    case PDF = 'application/pdf';
    case CSV = 'text/csv';
    case ZIP = 'application/zip';
    case JAVASCRIPT = 'application/javascript';
    case CSS = 'text/css';

    /**
     * Get a content type from a string.
     *
     * @throws \ValueError If the content type is invalid
     */
    public static function fromString(string $contentType): self
    {
        return self::from(strtolower($contentType));
    }

    /**
     * Try to get a content type from a string, or return default.
     */
    public static function tryFromString(string $contentType, ?self $default = null): ?self
    {
        return self::tryFrom(strtolower($contentType)) ?? $default;
    }

    /**
     * Check if the content type is JSON.
     */
    public function isJson(): bool
    {
        return $this === self::JSON;
    }

    /**
     * Check if the content type is a form.
     */
    public function isForm(): bool
    {
        return $this === self::FORM_URLENCODED;
    }

    /**
     * Check if the content type is multipart.
     */
    public function isMultipart(): bool
    {
        return $this === self::MULTIPART;
    }

    /**
     * Check if the content type is text-based.
     */
    public function isText(): bool
    {
        return match ($this) {
            // These are text-based content types
            self::JSON, self::FORM_URLENCODED, self::TEXT, self::HTML, self::XML, self::CSV => true,
            // These are binary/non-text content types
            self::MULTIPART => false,
            // Default for any new enum values added in the future
            default => false,
        };
    }
}
```

## Available Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `ContentType::JSON` | `"application/json"` | JSON format |
| `ContentType::FORM_URLENCODED` | `"application/x-www-form-urlencoded"` | Form URL encoded format (standard form submission) |
| `ContentType::MULTIPART` | `"multipart/form-data"` | Multipart format (for file uploads) |
| `ContentType::TEXT` | `"text/plain"` | Plain text |
| `ContentType::HTML` | `"text/html"` | HTML content |
| `ContentType::XML` | `"application/xml"` | XML format |
| `ContentType::XML_TEXT` | `"text/xml"` | XML in text format |
| `ContentType::BINARY` | `"application/octet-stream"` | Binary data |
| `ContentType::PDF` | `"application/pdf"` | PDF document |
| `ContentType::CSV` | `"text/csv"` | CSV data |
| `ContentType::ZIP` | `"application/zip"` | ZIP archive |
| `ContentType::JAVASCRIPT` | `"application/javascript"` | JavaScript code |
| `ContentType::CSS` | `"text/css"` | CSS stylesheet |

## Methods

### fromString()

Converts a string to a ContentType enum value. Throws an exception if the string doesn't match any valid content type.

```php
public static function fromString(string $contentType): self
```

**Parameters:**

- `$contentType`: A string representing a MIME type

**Returns:**

- The corresponding ContentType enum value

**Throws:**

- `\ValueError` if the string doesn't represent a valid content type

**Example:**

```php
$type = ContentType::fromString('application/json'); // Returns ContentType::JSON
```

### tryFromString()

Attempts to convert a string to a ContentType enum value. Returns a default value if the string doesn't match any valid content type.

```php
public static function tryFromString(string $contentType, ?self $default = null): ?self
```

**Parameters:**

- `$contentType`: A string representing a MIME type
- `$default`: The default enum value to return if the string doesn't match (defaults to null)

**Returns:**

- The corresponding ContentType enum value or the default value

**Example:**

```php
$type = ContentType::tryFromString('application/json'); // Returns ContentType::JSON
$type = ContentType::tryFromString('invalid/type', ContentType::JSON); // Returns ContentType::JSON
```

### isJson()

Checks if the content type is JSON.

```php
public function isJson(): bool
```

**Returns:**

- `true` if the content type is JSON, `false` otherwise

**Example:**

```php
if ($contentType->isJson()) {
    // Handle JSON content
}
```

### isForm()

Checks if the content type is form URL encoded.

```php
public function isForm(): bool
```

**Returns:**

- `true` if the content type is form URL encoded, `false` otherwise

**Example:**

```php
if ($contentType->isForm()) {
    // Handle form data
}
```

### isMultipart()

Checks if the content type is multipart form data.

```php
public function isMultipart(): bool
```

**Returns:**

- `true` if the content type is multipart form data, `false` otherwise

**Example:**

```php
if ($contentType->isMultipart()) {
    // Handle multipart form data (file uploads)
}
```

### isText()

Checks if the content type is text-based.

```php
public function isText(): bool
```

**Returns:**

- `true` if the content type is text-based (JSON, FORM_URLENCODED, TEXT, HTML, XML, CSV), `false` otherwise

**Example:**

```php
if ($contentType->isText()) {
    // Handle text-based content
} else {
    // Handle binary content
}
```

## Usage Examples

### With HTTP Requests

```php
use Fetch\Enum\ContentType;

// POST request with JSON
$response = fetch_client()->post(
    'https://api.example.com/users',
    ['name' => 'John Doe'],
    ContentType::JSON
);

// POST request with form data
$response = fetch_client()->post(
    'https://api.example.com/users',
    ['name' => 'John Doe'],
    ContentType::FORM_URLENCODED
);
```

### Setting Content Type Header

```php
use Fetch\Enum\ContentType;

// Using the enum value directly as a header
$client = fetch_client()
    ->withHeader('Content-Type', ContentType::JSON->value)
    ->post('https://api.example.com/users', $data);
```

### Working with Request Body

```php
use Fetch\Enum\ContentType;

// Configuring request body with specific content type
$client = fetch_client()->withBody($data, ContentType::XML);

// Using with the Request object
$request = new Request('POST', 'https://api.example.com/users');
$request = $request->withContentType(ContentType::JSON->value);
```

### Content Type Detection

```php
use Fetch\Enum\ContentType;

function processResponse($response)
{
    $contentType = $response->getHeaderLine('Content-Type');
    $parsedType = ContentType::tryFromString($contentType);

    return match($parsedType) {
        ContentType::JSON => json_decode($response->getBody(), true),
        ContentType::XML => simplexml_load_string($response->getBody()),
        ContentType::TEXT, ContentType::HTML => $response->getBody(),
        default => throw new RuntimeException("Unsupported content type: {$contentType}")
    };
}
```

### Working with File Uploads

```php
use Fetch\Enum\ContentType;

// Setting up a multipart file upload
$response = fetch_client()->post(
    'https://api.example.com/upload',
    [
        [
            'name' => 'file',
            'contents' => fopen('/path/to/file.jpg', 'r'),
            'filename' => 'file.jpg'
        ],
        [
            'name' => 'description',
            'contents' => 'File description'
        ]
    ],
    ContentType::MULTIPART
);
```
