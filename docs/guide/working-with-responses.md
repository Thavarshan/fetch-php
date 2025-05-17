---
title: Working with Responses
description: Learn how to handle and work with HTTP responses in the Fetch PHP package
---

# Working with Responses

This guide explains how to work with the `Response` class in the Fetch PHP package, which provides a rich set of methods to handle HTTP responses in a convenient and intuitive way.

## Response Basics

When you make an HTTP request with Fetch PHP, you get a `Response` object that represents the HTTP response:

```php
// Make a simple request
$response = fetch('https://api.example.com/users');

// The $response is an instance of Fetch\Http\Response
```

The `Response` class extends PSR-7's `ResponseInterface` and adds many helpful methods to work with HTTP responses.

## Checking Status Codes

### Basic Status Code Checks

```php
// Get the status code
$statusCode = $response->getStatusCode(); // Standard PSR-7 method
$statusCode = $response->status();        // Shorthand method

// Get the status text
$statusText = $response->getReasonPhrase(); // Standard PSR-7 method
$statusText = $response->statusText();      // Shorthand method
```

### Category-Based Status Checks

```php
// Check if the response is successful (2xx)
if ($response->successful()) {
    // Status code is in the 2xx range
}

// Check for informational responses (1xx)
if ($response->isInformational()) {
    // Status code is in the 1xx range
}

// Check for redirection responses (3xx)
if ($response->isRedirection() || $response->redirect()) {
    // Status code is in the 3xx range
}

// Check for client errors (4xx)
if ($response->isClientError() || $response->clientError()) {
    // Status code is in the 4xx range
}

// Check for server errors (5xx)
if ($response->isServerError() || $response->serverError()) {
    // Status code is in the 5xx range
}

// Check if the response failed (4xx or 5xx)
if ($response->failed()) {
    // Status code is either 4xx or 5xx
}
```

### Specific Status Code Checks

```php
// Check for specific status codes
if ($response->isOk()) {                 // 200 OK
    // Handle OK response
} elseif ($response->isCreated()) {      // 201 Created
    // Handle resource creation
} elseif ($response->isAccepted()) {     // 202 Accepted
    // Handle accepted but processing
} elseif ($response->isNoContent()) {    // 204 No Content
    // Handle no content
} elseif ($response->isUnauthorized()) { // 401 Unauthorized
    // Handle unauthorized
} elseif ($response->isForbidden()) {    // 403 Forbidden
    // Handle forbidden
} elseif ($response->isNotFound()) {     // 404 Not Found
    // Handle not found
} elseif ($response->isUnprocessableEntity()) { // 422 Unprocessable Entity
    // Handle validation errors
}

// Check any status code using isStatus()
if ($response->isStatus(418)) { // I'm a teapot 🫖
    echo "This server is a teapot!";
}
```

### Using Status Enums

```php
use Fetch\Enum\Status;

// Get the status code as an enum
$statusEnum = $response->statusEnum();

// Compare with status enums
if ($statusEnum === Status::OK) {
    // Status is exactly 200 OK
} elseif ($statusEnum === Status::NOT_FOUND) {
    // Status is exactly 404 Not Found
}

// Check status with isStatus() using an enum
if ($response->isStatus(Status::CREATED)) {
    // Status is 201 Created
}
```

## Accessing Response Body

### Getting Raw Content

```php
// Get the body as a string
$bodyContents = (string) $response->getBody(); // Standard PSR-7 method
$bodyContents = $response->body();             // Shorthand method
$bodyContents = $response->text();             // Alternative method

// Get the body as a binary string (array buffer)
$binaryData = $response->arrayBuffer();

// Get the body as a stream (similar to a JavaScript Blob)
$stream = $response->blob();
while (!feof($stream)) {
    $chunk = fread($stream, 4096);
    // Process chunk...
}
fclose($stream);
```

### Working with JSON

```php
// Parse the body as JSON (returns an array by default)
$data = $response->json();

// Parse as an object instead of an array
$object = $response->json(false);
$object = $response->object(); // Shorthand

// Parse specifically as an array
$array = $response->array();

// Handle JSON parsing errors
try {
    $data = $response->json();
} catch (RuntimeException $e) {
    echo "Failed to parse JSON: " . $e->getMessage();
}

// Silently handle errors (returns empty array/object on error)
$data = $response->json(true, false); // Don't throw on error
$object = $response->object(false);   // Don't throw on error
$array = $response->array(false);     // Don't throw on error
```

### Array Access for JSON Responses

The `Response` class implements `ArrayAccess`, allowing you to access JSON data directly:

```php
// Access JSON data like an array
$name = $response['name'];
$email = $response['email'];

// Check if a key exists
if (isset($response['address'])) {
    $address = $response['address'];
}

// Get a value with a default
$role = $response->get('role', 'user');
```

### Working with XML

```php
// Parse the body as XML
try {
    $xml = $response->xml();

    // Work with the SimpleXMLElement
    echo $xml->user->name;
    foreach ($xml->items->item as $item) {
        echo $item->name . ": " . $item->price . "\n";
    }
} catch (RuntimeException $e) {
    echo "Failed to parse XML: " . $e->getMessage();
}

// Silently handle XML parsing errors
$xml = $response->xml(0, false); // Don't throw on error
if ($xml !== null) {
    // Work with the XML
}
```

## Working with Headers

```php
// Get all headers
$headers = $response->getHeaders(); // Standard PSR-7 method
$headers = $response->headers();    // Shorthand method

// Get a specific header
$contentType = $response->getHeaderLine('Content-Type'); // Standard PSR-7 method
$contentType = $response->header('Content-Type');        // Shorthand method

// Check if a header exists
if ($response->hasHeader('Content-Type')) {
    // Header exists
}
```

## Content Type Detection

```php
// Get the content type
$contentType = $response->contentType();

// Get the content type as an enum
$contentTypeEnum = $response->contentTypeEnum();

// Check specific content types
if ($response->hasJsonContent()) {
    // Response has JSON content
    $data = $response->json();
} elseif ($response->hasHtmlContent()) {
    // Response has HTML content
    $html = $response->text();
} elseif ($response->hasTextContent()) {
    // Response has text-based content
    $text = $response->text();
}
```

## Creating Responses Manually

The `Response` class provides several factory methods to create responses for testing or other purposes:

```php
use Fetch\Http\Response;
use Fetch\Enum\Status;

// Create a basic response
$response = new Response(200, ['Content-Type' => 'text/plain'], 'Hello World');

// Create using a Status enum
$response = new Response(Status::OK, ['Content-Type' => 'text/plain'], 'Hello World');

// Create a JSON response
$response = Response::withJson(
    ['name' => 'John', 'email' => 'john@example.com'],
    Status::OK
);

// Create a "No Content" response
$response = Response::noContent();

// Create a "Created" response
$response = Response::created(
    '/resources/123',
    ['id' => 123, 'name' => 'New Resource']
);

// Create a redirect response
$response = Response::withRedirect('/new-location', Status::FOUND);
```

## Real-World Examples

### Processing a Successful API Response

```php
$response = fetch('https://api.example.com/users/123');

if ($response->successful()) {
    $user = $response->json();

    echo "User details:\n";
    echo "Name: " . $user['name'] . "\n";
    echo "Email: " . $user['email'] . "\n";

    // Access using array syntax
    if (isset($response['address'])) {
        echo "Address: " . $response['address']['street'] . ", " .
             $response['address']['city'] . "\n";
    }
} else {
    echo "Failed to fetch user: " . $response->status() . " " . $response->statusText();
}
```

### Handling Different Response Types

```php
$response = fetch('https://api.example.com/resource');

// Check content type and process accordingly
if ($response->hasJsonContent()) {
    $data = $response->json();
    // Process JSON data
} elseif ($response->hasXmlContent()) {
    $xml = $response->xml();
    // Process XML data
} elseif ($response->hasHtmlContent()) {
    $html = $response->text();
    // Process HTML content
} else {
    $content = $response->text();
    // Process as plain text
}
```

### Handling Validation Errors

```php
$response = post('https://api.example.com/users', [
    'name' => '',
    'email' => 'invalid-email'
]);

if ($response->isUnprocessableEntity()) {
    $errors = $response->json()['errors'] ?? [];

    echo "Validation errors:\n";
    foreach ($errors as $field => $messages) {
        echo "- {$field}: " . implode(', ', $messages) . "\n";
    }
} elseif ($response->successful()) {
    $user = $response->json();
    echo "User created with ID: " . $user['id'];
} else {
    echo "Error: " . $response->status() . " " . $response->statusText();
}
```

### Downloading a File

```php
$response = fetch('https://example.com/files/document.pdf');

if ($response->successful()) {
    // Save to file
    file_put_contents('document.pdf', $response->body());
    echo "File downloaded successfully!";

    // Or process as a stream
    $stream = $response->blob();
    $outputFile = fopen('document.pdf', 'w');

    while (!feof($stream)) {
        fwrite($outputFile, fread($stream, 8192));
    }

    fclose($outputFile);
    fclose($stream);
} else {
    echo "Failed to download file: " . $response->status();
}
```

### Handling Authentication Responses

```php
$response = post('https://api.example.com/login', [
    'email' => 'user@example.com',
    'password' => 'password123'
]);

if ($response->isOk()) {
    $auth = $response->json();
    $token = $auth['token'];

    // Store the token for future requests
    $_SESSION['api_token'] = $token;

    echo "Successfully authenticated!";
} elseif ($response->isUnauthorized()) {
    echo "Invalid credentials";
} elseif ($response->isTooManyRequests()) {
    echo "Too many login attempts. Please try again later.";
} else {
    echo "Login failed: " . $response->status();
}
```

## Error Handling Best Practices

```php
try {
    $response = fetch('https://api.example.com/resource');

    // Check for HTTP errors
    if ($response->failed()) {
        if ($response->isNotFound()) {
            throw new \Exception("Resource not found");
        } elseif ($response->isUnauthorized()) {
            throw new \Exception("Authentication required");
        } else {
            throw new \Exception("API error: " . $response->status());
        }
    }

    // Process the response
    $data = $response->json();

    // ...
} catch (\Fetch\Exceptions\NetworkException $e) {
    // Handle network-related errors
    echo "Network error: " . $e->getMessage();
} catch (\Fetch\Exceptions\RequestException $e) {
    // Handle request-related errors
    echo "Request error: " . $e->getMessage();
} catch (\RuntimeException $e) {
    // Handle response parsing errors
    echo "Failed to parse response: " . $e->getMessage();
} catch (\Exception $e) {
    // Handle other errors
    echo "Error: " . $e->getMessage();
}
```

## Next Steps

- Learn about [Making Requests](/guide/making-requests)
- Explore [Asynchronous Requests](/guide/async-requests)
- Discover [Authentication](/guide/authentication)
- Check out [Error Handling](/guide/error-handling) for more detailed error handling strategies
