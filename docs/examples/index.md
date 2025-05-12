---
title: Basic Examples
description: Basic examples for using the Fetch HTTP package
---

# Basic Examples

This page provides basic examples of how to use the Fetch HTTP package to make common HTTP requests.

## Basic GET Requests

Making a simple GET request:

```php
// Make a GET request
$response = fetch('https://api.example.com/users');

// Check if the request was successful
if ($response->successful()) {
    // Parse the JSON response
    $users = $response->json();

    // Use the data
    foreach ($users as $user) {
        echo $user['name'] . "\n";
    }
} else {
    echo "Error: " . $response->status() . " " . $response->statusText();
}
```

Using the `get()` helper function:

```php
// Make a GET request with query parameters
$response = get('https://api.example.com/users', [
    'page' => 1,
    'per_page' => 20,
    'sort' => 'name'
]);

// Access data using array syntax
if ($response->successful()) {
    echo "Total users: " . $response['meta']['total'] . "\n";

    foreach ($response['data'] as $user) {
        echo "- " . $user['name'] . "\n";
    }
}
```

## POST Requests

Creating a resource:

```php
// Create a user with JSON data
$response = post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'role' => 'admin'
]);

// Check for success and get the created resource
if ($response->successful()) {
    $user = $response->json();
    echo "Created user with ID: " . $user['id'] . "\n";
} elseif ($response->isUnprocessableEntity()) {
    // Handle validation errors
    $errors = $response->json()['errors'];
    foreach ($errors as $field => $messages) {
        echo "$field: " . implode(', ', $messages) . "\n";
    }
} else {
    echo "Error: " . $response->status();
}
```

Submitting a form:

```php
// Send form data
$response = fetch('https://api.example.com/contact', [
    'method' => 'POST',
    'form' => [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'message' => 'Hello, I have a question about your product.'
    ]
]);

if ($response->successful()) {
    echo "Form submitted successfully!";
} else {
    echo "Failed to submit form: " . $response->status();
}
```

## PUT and PATCH Requests

Updating a resource completely (PUT):

```php
// Update a user with all fields
$response = put('https://api.example.com/users/123', [
    'name' => 'John Smith',
    'email' => 'john.smith@example.com',
    'role' => 'editor'
]);

if ($response->successful()) {
    echo "User updated successfully!";
}
```

Updating a resource partially (PATCH):

```php
// Update only specific fields
$response = patch('https://api.example.com/users/123', [
    'role' => 'admin'
]);

if ($response->successful()) {
    echo "User role updated successfully!";
}
```

## DELETE Requests

Deleting a resource:

```php
// Delete a user
$response = delete('https://api.example.com/users/123');

if ($response->successful()) {
    echo "User deleted successfully!";
} elseif ($response->isNotFound()) {
    echo "User not found!";
} else {
    echo "Failed to delete user: " . $response->status();
}
```

Bulk delete with a request body:

```php
// Delete multiple users
$response = delete('https://api.example.com/users', [
    'ids' => [123, 456, 789]
]);

if ($response->successful()) {
    $result = $response->json();
    echo "Deleted " . $result['deleted_count'] . " users";
}
```

## Working with Headers

Adding custom headers:

```php
// Send a request with custom headers
$response = fetch('https://api.example.com/data', [
    'headers' => [
        'X-API-Version' => '2.0',
        'Accept-Language' => 'en-US',
        'Cache-Control' => 'no-cache',
        'X-Request-ID' => uniqid()
    ]
]);

// Get response headers
$contentType = $response->header('Content-Type');
$rateLimitRemaining = $response->header('X-RateLimit-Remaining');

echo "Content Type: $contentType\n";
echo "Rate Limit Remaining: $rateLimitRemaining\n";
```

## Request Timeout

Setting request timeout:

```php
// Set a 5-second timeout
$response = fetch('https://api.example.com/slow-operation', [
    'timeout' => 5
]);

// Or with the fluent interface
$response = fetch()
    ->timeout(5)
    ->get('https://api.example.com/slow-operation');
```

## Working with Different Response Types

### JSON Responses

```php
$response = get('https://api.example.com/users/123');

// Get as associative array (default)
$userData = $response->json();
echo $userData['name'];

// Get as object
$userObject = $response->object();
echo $userObject->name;

// Using array access syntax directly on response
$name = $response['name'];
$email = $response['email'];
```

### XML Responses

```php
$response = get('https://api.example.com/feed', null, [
    'headers' => ['Accept' => 'application/xml']
]);

// Parse XML
$xml = $response->xml();

// Work with SimpleXMLElement
foreach ($xml->item as $item) {
    echo (string)$item->title . "\n";
    echo (string)$item->description . "\n";
}
```

### Raw Responses

```php
// Get raw response body (for non-JSON/XML content)
$response = get('https://api.example.com/text-content');
$content = $response->body();

// Or using text() method
$text = $response->text();

echo "Content length: " . strlen($content) . " bytes";
```

## Configuring Global Defaults

Setting global configuration for all requests:

```php
// Configure once at application bootstrap
fetch_client([
    'base_uri' => 'https://api.example.com',
    'timeout' => 10,
    'headers' => [
        'User-Agent' => 'MyApp/1.0',
        'Accept' => 'application/json'
    ]
]);

// Now use simplified calls in your code
$users = get('/users')->json();  // Uses base_uri
$user = post('/users', ['name' => 'John'])->json();
```

## Method Chaining

Using the fluent interface:

```php
$response = fetch()
    ->withHeader('X-API-Key', 'your-api-key')
    ->withQueryParam('include', 'comments,likes')
    ->withQueryParam('sort', 'created_at')
    ->timeout(5)
    ->get('https://api.example.com/posts');

if ($response->successful()) {
    $posts = $response->json();
    // Process posts
}
```

## Next Steps

Now that you're familiar with the basics, check out these more advanced examples:

- [API Integration Examples](/examples/api-integration) - Real-world API integration patterns
- [Async Patterns](/examples/async-patterns) - Working with asynchronous requests
- [Error Handling](/examples/error-handling) - Robust error handling strategies
- [File Handling](/examples/file-handling) - Uploading and downloading files
- [Authentication](/examples/authentication) - Working with different authentication schemes
