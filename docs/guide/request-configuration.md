---
title: Request Configuration
description: Learn how to configure and work with Request objects in the Fetch HTTP package
---

# Request Configuration

This guide demonstrates how to configure and work with `Request` objects in the Fetch HTTP package.

## Creating Requests

The `Request` class provides multiple ways to create requests for different HTTP methods and content types.

### Basic Request Creation

```php
use Fetch\Http\Request;
use Fetch\Enum\Method;

// Create a basic GET request
$request = new Request(Method::GET, 'https://api.example.com/users');

// Alternatively, using string method
$request = new Request('GET', 'https://api.example.com/users');

// Create with headers
$request = new Request(
    Method::GET,
    'https://api.example.com/users',
    ['Accept' => 'application/json']
);

// Create with a body
$request = new Request(
    Method::POST,
    'https://api.example.com/users',
    ['Content-Type' => 'application/json'],
    '{"name": "John Doe", "email": "john@example.com"}'
);
```

### HTTP Method Factory Methods

The `Request` class provides convenient static methods for creating requests with specific HTTP methods:

```php
// GET request
$request = Request::get('https://api.example.com/users');

// POST request
$request = Request::post(
    'https://api.example.com/users',
    '{"name": "John Doe"}',
    ['Accept' => 'application/json'],
    'application/json'
);

// PUT request
$request = Request::put('https://api.example.com/users/123', '{"name": "John Smith"}');

// PATCH request
$request = Request::patch('https://api.example.com/users/123', '{"status": "active"}');

// DELETE request
$request = Request::delete('https://api.example.com/users/123');

// HEAD request
$request = Request::head('https://api.example.com/users');

// OPTIONS request
$request = Request::options('https://api.example.com/users');
```

### Content Type Factory Methods

For common content types, specialized factory methods are available:

```php
use Fetch\Enum\Method;

// JSON request
$request = Request::json(
    Method::POST,
    'https://api.example.com/users',
    ['name' => 'John Doe', 'email' => 'john@example.com']
);

// Form request
$request = Request::form(
    Method::POST,
    'https://api.example.com/login',
    ['username' => 'johndoe', 'password' => 'secret']
);

// Multipart request (file upload)
$request = Request::multipart(
    Method::POST,
    'https://api.example.com/upload',
    [
        [
            'name' => 'file',
            'contents' => file_get_contents('/path/to/file.jpg'),
            'filename' => 'upload.jpg',
            'headers' => ['Content-Type' => 'image/jpeg']
        ],
        [
            'name' => 'description',
            'contents' => 'Profile picture upload'
        ]
    ]
);
```

## Working with Request Properties

### Headers

```php
// Get all headers
$headers = $request->getHeaders();

// Check if a header exists
if ($request->hasHeader('Content-Type')) {
    $contentType = $request->getHeaderLine('Content-Type');
}

// Add a header
$newRequest = $request->withHeader('X-API-Key', 'your-api-key');

// Add a value to an existing header
$newRequest = $request->withAddedHeader('Accept', 'application/xml');

// Remove a header
$newRequest = $request->withoutHeader('X-Old-Header');
```

### URI and Query Parameters

```php
// Get the URI
$uri = $request->getUri();
$url = (string) $uri;
$path = $uri->getPath();
$query = $uri->getQuery();

// Add a single query parameter
$newRequest = $request->withQueryParam('page', 2);

// Add multiple query parameters
$newRequest = $request->withQueryParams([
    'page' => 2,
    'per_page' => 20,
    'sort' => 'created_at'
]);

// Change the URI completely
use GuzzleHttp\Psr7\Uri;
$newUri = new Uri('https://api.example.com/different-path');
$newRequest = $request->withUri($newUri);
```

### Request Method

```php
// Get the method
$method = $request->getMethod();

// Get the method as enum
$methodEnum = $request->getMethodEnum();

// Check if the method supports a request body
if ($request->supportsRequestBody()) {
    // Add body to the request
}

// Change the method
$newRequest = $request->withMethod('PATCH');
```

### Request Target

The request target is the path and query part of the URL in most cases, but can be customized for special HTTP use cases.

```php
// Get the request target
$target = $request->getRequestTarget(); // e.g., "/users?page=1"

// Set a custom request target
$newRequest = $request->withRequestTarget('*'); // For OPTIONS * HTTP/1.1
```

## Working with Request Bodies

### Setting Different Body Types

```php
// Set a raw string body
$newRequest = $request->withBody('Raw request content');

// Set a JSON body
$newRequest = $request->withJsonBody([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Set a form body
$newRequest = $request->withFormBody([
    'username' => 'johndoe',
    'password' => 'secret'
]);
```

### Reading the Request Body

```php
// Get the body as a string
$bodyString = $request->getBodyAsString();

// Parse JSON body
try {
    $jsonData = $request->getBodyAsJson();
    echo "Name: " . $jsonData['name'];
} catch (InvalidArgumentException $e) {
    echo "Invalid JSON: " . $e->getMessage();
}

// Parse form parameters
$formParams = $request->getBodyAsFormParams();
$username = $formParams['username'] ?? null;
```

### Checking Content Types

```php
// Get the content type
$contentTypeEnum = $request->getContentTypeEnum();

// Check specific content types
if ($request->hasJsonContent()) {
    $data = $request->getBodyAsJson();
} elseif ($request->hasFormContent()) {
    $data = $request->getBodyAsFormParams();
} elseif ($request->hasMultipartContent()) {
    // Handle multipart content
} elseif ($request->hasTextContent()) {
    $text = $request->getBodyAsString();
}

// Set a content type
$newRequest = $request->withContentType('application/xml');
// Or using enum
use Fetch\Enum\ContentType;
$newRequest = $request->withContentType(ContentType::XML);
```

## Authentication

```php
// Add Bearer token authentication
$request = $request->withBearerToken('your-oauth-token');

// Add Basic authentication
$request = $request->withBasicAuth('username', 'password');
```

## Immutability and Chaining

All request modification methods return a new instance, allowing for method chaining:

```php
$request = Request::get('https://api.example.com/users')
    ->withQueryParam('role', 'admin')
    ->withHeader('Accept', 'application/json')
    ->withBearerToken('your-oauth-token');
```

## Creating Advanced Requests

### Complete API Request Example

```php
use Fetch\Http\Request;
use Fetch\Enum\Method;
use Fetch\Enum\ContentType;

// Create a complete API request
$request = Request::json(
    Method::POST,
    'https://api.example.com/orders',
    [
        'items' => [
            ['product_id' => 123, 'quantity' => 2],
            ['product_id' => 456, 'quantity' => 1]
        ],
        'customer' => [
            'id' => 789,
            'address' => [
                'street' => '123 Main St',
                'city' => 'Anytown',
                'zip' => '12345'
            ]
        ],
        'payment' => [
            'method' => 'credit_card',
            'token' => 'token_123456'
        ]
    ]
)
    ->withHeader('Accept', 'application/json')
    ->withHeader('X-API-Version', '2.0')
    ->withBearerToken('your-oauth-token');

// Check the content type
if ($request->hasJsonContent()) {
    $orderData = $request->getBodyAsJson();
    echo "Order contains " . count($orderData['items']) . " items";
}
```

### File Upload Request

```php
// Create a file upload request using multipart
$request = Request::multipart(
    Method::POST,
    'https://api.example.com/uploads',
    [
        [
            'name' => 'file',
            'contents' => file_get_contents('/path/to/document.pdf'),
            'filename' => 'document.pdf',
            'headers' => [
                'Content-Type' => 'application/pdf'
            ]
        ],
        [
            'name' => 'type',
            'contents' => 'invoice'
        ],
        [
            'name' => 'description',
            'contents' => 'Monthly invoice #12345'
        ]
    ]
)->withBearerToken('your-oauth-token');
```

### GraphQL Request

```php
// Create a GraphQL request
$request = Request::json(
    Method::POST,
    'https://api.example.com/graphql',
    [
        'query' => '
            query GetUser($id: ID!) {
                user(id: $id) {
                    id
                    name
                    email
                    posts {
                        id
                        title
                    }
                }
            }
        ',
        'variables' => [
            'id' => '123'
        ],
        'operationName' => 'GetUser'
    ]
)->withBearerToken('your-oauth-token');
```

## Converting Between PSR-7 and Request Objects

Since the `Request` class implements the PSR-7 `RequestInterface`, it can be used with any PSR-7 compatible HTTP client or middleware:

```php
// Use with a PSR-18 client
use Psr\Http\Client\ClientInterface;

function sendRequest(ClientInterface $client)
{
    $request = Request::get('https://api.example.com/users')
        ->withHeader('Accept', 'application/json');

    $response = $client->sendRequest($request);
    return $response;
}

// Create from an existing PSR-7 request
use Psr\Http\Message\RequestInterface;

function customizeRequest(RequestInterface $psrRequest)
{
    // Convert to our Request class if not already
    if (!$psrRequest instanceof Request) {
        $request = new Request(
            $psrRequest->getMethod(),
            $psrRequest->getUri(),
            $psrRequest->getHeaders(),
            (string)$psrRequest->getBody(),
            $psrRequest->getProtocolVersion()
        );
    } else {
        $request = $psrRequest;
    }

    // Now we can use our custom methods
    $request = $request->withBearerToken('token')
        ->withJsonBody(['custom' => 'data']);

    return $request;
}
```

## Next Steps

Now that you understand how to configure requests, you may want to explore:

- [Making Requests](/guide/making-requests) - Different ways to make HTTP requests
- [Working with Responses](/guide/working-with-responses) - How to handle response objects
- [Authentication](/guide/authentication) - More on authentication options
- [File Uploads](/guide/file-uploads) - Detailed guide on uploading files
