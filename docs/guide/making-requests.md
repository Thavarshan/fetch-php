---
title: Making Requests
description: Learn how to make HTTP requests using the Fetch HTTP package
---

# Making Requests

This guide covers different ways to make HTTP requests using the Fetch HTTP package, including the helper functions and the object-oriented interface.

## Using Helper Functions

The simplest way to make requests is using the global helper functions.

### The `fetch()` Function

The `fetch()` function is the primary way to make HTTP requests:

```php
use function Fetch\Http\fetch;

// Simple GET request
$response = fetch('https://api.example.com/users');

// POST request with JSON data
$response = fetch('https://api.example.com/users', [
    'method' => 'POST',
    'json' => ['name' => 'John Doe', 'email' => 'john@example.com']
]);
```

### HTTP Method Helpers

For common HTTP methods, you can use the dedicated helper functions:

```php
use function Fetch\Http\get;
use function Fetch\Http\post;
use function Fetch\Http\put;
use function Fetch\Http\patch;
use function Fetch\Http\delete;

// GET request
$response = get('https://api.example.com/users');

// POST request
$response = post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// PUT request
$response = put('https://api.example.com/users/123', [
    'name' => 'John Smith',
    'email' => 'john.smith@example.com'
]);

// PATCH request
$response = patch('https://api.example.com/users/123', [
    'status' => 'active'
]);

// DELETE request
$response = delete('https://api.example.com/users/123');
```

## Using the Object-Oriented Interface

For more control and reusability, you can use the object-oriented interface.

### Using the Client Class

The `Client` class provides a high-level interface:

```php
use Fetch\Http\Client;

$client = new Client();

// Make requests
$response = $client->get('https://api.example.com/users');
$response = $client->post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

### Using the ClientHandler Class

The `ClientHandler` class offers a fluent, chainable API with more options:

```php
use Fetch\Http\ClientHandler;

$handler = new ClientHandler();

// Configure and make a request
$response = $handler
    ->withHeaders([
        'Accept' => 'application/json',
        'X-API-Key' => 'your-api-key'
    ])
    ->withToken('your-oauth-token')
    ->get('https://api.example.com/users');
```

### Factory Methods

You can also use static factory methods to create preconfigured clients:

```php
// Create with base URI
$client = ClientHandler::createWithBaseUri('https://api.example.com');
$response = $client->get('/users'); // Uses the base URI

// Create with custom Guzzle client
$guzzleClient = new \GuzzleHttp\Client([
    'timeout' => 30,
    'verify' => false, // Disable SSL verification (not recommended for production)
]);
$client = ClientHandler::createWithClient($guzzleClient);
```

## Request Configuration Options

Whether you use the helper functions or the object-oriented interface, you can configure various aspects of your requests.

### Headers

```php
// Using fetch()
$response = fetch('https://api.example.com/users', [
    'headers' => [
        'Accept' => 'application/json',
        'User-Agent' => 'MyApp/1.0',
        'X-Custom-Header' => 'value'
    ]
]);

// Using ClientHandler
$response = ClientHandler::create()
    ->withHeader('Accept', 'application/json')
    ->withHeader('User-Agent', 'MyApp/1.0')
    ->withHeader('X-Custom-Header', 'value')
    ->get('https://api.example.com/users');

// Setting multiple headers at once
$response = ClientHandler::create()
    ->withHeaders([
        'Accept' => 'application/json',
        'User-Agent' => 'MyApp/1.0',
        'X-Custom-Header' => 'value'
    ])
    ->get('https://api.example.com/users');
```

### Query Parameters

```php
// Using fetch()
$response = fetch('https://api.example.com/users', [
    'query' => [
        'page' => 1,
        'per_page' => 20,
        'sort' => 'created_at',
        'order' => 'desc'
    ]
]);

// Using get() with query params as second argument
$response = get('https://api.example.com/users', [
    'page' => 1,
    'per_page' => 20
]);

// Using ClientHandler
$response = ClientHandler::create()
    ->withQueryParameter('page', 1)
    ->withQueryParameter('per_page', 20)
    ->withQueryParameter('sort', 'created_at')
    ->withQueryParameter('order', 'desc')
    ->get('https://api.example.com/users');

// Setting multiple query parameters at once
$response = ClientHandler::create()
    ->withQueryParameters([
        'page' => 1,
        'per_page' => 20,
        'sort' => 'created_at',
        'order' => 'desc'
    ])
    ->get('https://api.example.com/users');
```

### Request Body

#### JSON

```php
// Using fetch()
$response = fetch('https://api.example.com/users', [
    'method' => 'POST',
    'json' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'roles' => ['editor', 'admin']
    ]
]);

// Using post() helper (arrays are automatically sent as JSON)
$response = post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'roles' => ['editor', 'admin']
]);

// Using ClientHandler
$response = ClientHandler::create()
    ->withJson([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'roles' => ['editor', 'admin']
    ])
    ->post('https://api.example.com/users');
```

#### Form Data

```php
// Using fetch()
$response = fetch('https://api.example.com/login', [
    'method' => 'POST',
    'form' => [
        'username' => 'johndoe',
        'password' => 'secret',
        'remember' => true
    ]
]);

// Using ClientHandler
$response = ClientHandler::create()
    ->withFormParams([
        'username' => 'johndoe',
        'password' => 'secret',
        'remember' => true
    ])
    ->post('https://api.example.com/login');
```

#### Raw Body

```php
// Using fetch()
$response = fetch('https://api.example.com/webhook', [
    'method' => 'POST',
    'body' => 'Raw request content',
    'headers' => ['Content-Type' => 'text/plain']
]);

// Using ClientHandler
$response = ClientHandler::create()
    ->withBody('Raw request content', 'text/plain')
    ->post('https://api.example.com/webhook');
```

### Timeouts

```php
// Using fetch()
$response = fetch('https://api.example.com/slow-resource', [
    'timeout' => 30 // 30 seconds
]);

// Using ClientHandler
$response = ClientHandler::create()
    ->timeout(30)
    ->get('https://api.example.com/slow-resource');
```

### Base URI

```php
// Using fetch()
$response = fetch('/users', [
    'base_uri' => 'https://api.example.com'
]);

// Using ClientHandler
$client = ClientHandler::createWithBaseUri('https://api.example.com');
$response = $client->get('/users');
$anotherResponse = $client->get('/posts');
```

## Reusing Configuration

One of the advantages of the object-oriented interface is the ability to reuse configuration.

### Reusing a Client

```php
$client = ClientHandler::createWithBaseUri('https://api.example.com')
    ->withToken('your-oauth-token')
    ->withHeader('Accept', 'application/json');

// Use the same configuration for multiple requests
$users = $client->get('/users')->json();
$user = $client->get("/users/{$id}")->json();
$posts = $client->get('/posts')->json();
```

### Cloning with Modified Options

```php
// Create a base client
$baseClient = ClientHandler::createWithBaseUri('https://api.example.com')
    ->withHeaders([
        'User-Agent' => 'MyApp/1.0',
        'Accept' => 'application/json'
    ]);

// Create a clone with auth for protected endpoints
$authClient = $baseClient->withClonedOptions([
    'headers' => [
        'Authorization' => 'Bearer ' . $token
    ]
]);

// Another clone with different timeout
$longTimeoutClient = $baseClient->withClonedOptions([
    'timeout' => 60
]);

// Use the clients
$publicData = $baseClient->get('/public-data')->json();
$privateData = $authClient->get('/private-data')->json();
$largeReport = $longTimeoutClient->get('/large-report')->json();
```

## Setting Global Defaults

```php
// Set default options for all new ClientHandler instances
ClientHandler::setDefaultOptions([
    'timeout' => 15,
    'headers' => [
        'User-Agent' => 'MyApp/1.0',
        'Accept' => 'application/json'
    ]
]);

// Set up a global client configuration
fetch_client([
    'base_uri' => 'https://api.example.com',
    'headers' => [
        'User-Agent' => 'MyApp/1.0'
    ],
    'timeout' => 10
]);

// Now simple helper functions will use this configuration
$users = get('/users')->json();
```

## PSR-7 Compatibility

The package implements PSR-7 interfaces, making it compatible with other PSR-7 HTTP clients and middleware:

```php
// Create a PSR-7 request
use Fetch\Http\Request;
$request = Request::get('https://api.example.com/users')
    ->withHeader('Accept', 'application/json');

// Use with any PSR-18 client
$psr18Client = new SomePsr18Client();
$response = $psr18Client->sendRequest($request);

// Our Client class is PSR-18 compatible
$client = new \Fetch\Http\Client();
$response = $client->sendRequest($request);
```

## Next Steps

- Learn more about [Working with Responses](/guide/working-with-responses)
- Explore [Authentication](/guide/authentication) options
- Discover [Asynchronous Requests](/guide/async-requests)
