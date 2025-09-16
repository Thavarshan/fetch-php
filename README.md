# Fetch PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jerome/fetch-php.svg)](https://packagist.org/packages/jerome/fetch-php)
[![CI](https://github.com/Thavarshan/fetch-php/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/Thavarshan/fetch-php/actions/workflows/ci.yml)
[![Codecov](https://codecov.io/gh/Thavarshan/fetch-php/branch/main/graph/badge.svg)](https://codecov.io/gh/Thavarshan/fetch-php)
[![CodeQL](https://github.com/Thavarshan/fetch-php/actions/workflows/github-code-scanning/codeql/badge.svg)](https://github.com/Thavarshan/fetch-php/actions/workflows/github-code-scanning/codeql)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg)](https://phpstan.org/)
[![PHP Version](https://img.shields.io/packagist/php-v/jerome/fetch-php.svg)](https://packagist.org/packages/jerome/fetch-php)
[![License](https://img.shields.io/packagist/l/jerome/fetch-php.svg)](https://packagist.org/packages/jerome/fetch-php)
[![Total Downloads](https://img.shields.io/packagist/dt/jerome/fetch-php.svg)](https://packagist.org/packages/jerome/fetch-php)
[![GitHub Stars](https://img.shields.io/github/stars/Thavarshan/fetch-php.svg?style=social&label=Stars)](https://github.com/Thavarshan/fetch-php/stargazers)

**Fetch PHP** is a modern HTTP client library for PHP that brings JavaScript's `fetch` API experience to PHP. Built on top of Guzzle, Fetch PHP allows you to write HTTP code with a clean, intuitive JavaScript-like syntax while still maintaining PHP's familiar patterns.

With support for both synchronous and asynchronous requests, a fluent chainable API, and powerful retry mechanics, Fetch PHP streamlines HTTP operations in your PHP applications.

Full documentation can be found [here](https://fetch-php.thavarshan.com/)

---

## Key Features

- **JavaScript-like Syntax**: Write HTTP requests just like you would in JavaScript with the `fetch()` function and `async`/`await` patterns
- **Promise-based API**: Use familiar `.then()`, `.catch()`, and `.finally()` methods for async operations
- **Fluent Interface**: Build requests with a clean, chainable API
- **Built on Guzzle**: Benefit from Guzzle's robust functionality with a more elegant API
- **Retry Mechanics**: Automatically retry failed requests with exponential backoff
- **PHP-style Helper Functions**: Includes traditional PHP function helpers (`get()`, `post()`, etc.) for those who prefer that style

## Why Choose Fetch PHP?

### Beyond Guzzle

While Guzzle is a powerful HTTP client, Fetch PHP enhances the experience by providing:

- **JavaScript-like API**: Enjoy the familiar `fetch()` API and `async`/`await` patterns from JavaScript
- **Global client management**: Configure once, use everywhere with the global client
- **Simplified requests**: Make common HTTP requests with less code
- **Enhanced error handling**: Reliable retry mechanics and clear error information
- **Type-safe enums**: Use enums for HTTP methods, content types, and status codes

| Feature           | Fetch PHP                                               | Guzzle              |
| ----------------- | ------------------------------------------------------- | ------------------- |
| API Style         | JavaScript-like fetch + async/await + PHP-style helpers | PHP-style only      |
| Client Management | Global client + instance options                        | Instance-based only |
| Request Syntax    | Clean, minimal                                          | More verbose        |
| Types             | Modern PHP 8.1+ enums                                   | String constants    |
| Helper Functions  | Multiple styles available                               | Limited             |

## Installation

```bash
composer require jerome/fetch-php
```

> **Requirements**: PHP 8.3 or higher

## Basic Usage

### JavaScript-style API

```php
// Simple GET request
$response = fetch('https://api.example.com/users');
$users = $response->json();

// POST request with JSON body
$response = fetch('https://api.example.com/users', [
    'method' => 'POST',
    'json' => ['name' => 'John Doe', 'email' => 'john@example.com'],
]);
```

### PHP-style Helpers

```php
// GET request with query parameters
$response = get('https://api.example.com/users', ['page' => 1, 'limit' => 10]);

// POST request with JSON data
$response = post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

### Fluent API

```php
// Chain methods to build your request
$response = fetch_client()
    ->baseUri('https://api.example.com')
    ->withHeaders(['Accept' => 'application/json'])
    ->withToken('your-auth-token')
    ->withQueryParameters(['page' => 1, 'limit' => 10])
    ->get('/users');
```

## Async/Await Pattern

### Using Async/Await

```php
// Import async/await functions
use function async;
use function await;

// Wrap your fetch call in an async function
$promise = async(function() {
    return fetch('https://api.example.com/users');
});

// Await the result
$response = await($promise);
$users = $response->json();

echo "Fetched " . count($users) . " users";
```

### Multiple Concurrent Requests with Async/Await

```php
use function async;
use function await;
use function all;

// Execute an async function
await(async(function() {
    // Create multiple requests
    $results = await(all([
        'users' => async(fn() => fetch('https://api.example.com/users')),
        'posts' => async(fn() => fetch('https://api.example.com/posts')),
        'comments' => async(fn() => fetch('https://api.example.com/comments'))
    ]));

    // Process the results
    $users = $results['users']->json();
    $posts = $results['posts']->json();
    $comments = $results['comments']->json();

    echo "Fetched " . count($users) . " users, " .
         count($posts) . " posts, and " .
         count($comments) . " comments";
}));
```

### Sequential Requests with Async/Await

```php
use function async;
use function await;

await(async(function() {
    // First request: get auth token
    $authResponse = await(async(fn() =>
        fetch('https://api.example.com/auth/login', [
            'method' => 'POST',
            'json' => [
                'username' => 'user',
                'password' => 'pass'
            ]
        ])
    ));

    $token = $authResponse->json()['token'];

    // Second request: use token to get user data
    $userResponse = await(async(fn() =>
        fetch('https://api.example.com/me', [
            'token' => $token
        ])
    ));

    return $userResponse->json();
}));
```

### Error Handling with Async/Await

```php
use function async;
use function await;

try {
    $data = await(async(function() {
        $response = await(async(fn() =>
            fetch('https://api.example.com/users/999')
        ));

        if ($response->isNotFound()) {
            throw new \Exception("User not found");
        }

        return $response->json();
    }));

    // Process the data

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Traditional Promise-based Pattern

```php
// Set up an async request
// Get the handler for async operations
$handler = fetch_client()->getHandler();
$handler->async();

// Make the async request
$promise = $handler->get('https://api.example.com/users');

// Handle the result with callbacks
$promise->then(
    function ($response) {
        // Process successful response
        $users = $response->json();
        foreach ($users as $user) {
            echo $user['name'] . PHP_EOL;
        }
    },
    function ($exception) {
        // Handle errors
        echo "Error: " . $exception->getMessage();
    }
);
```

## Advanced Async Usage

### Concurrent Requests with Promise Utilities

```php
use function race;

// Create promises for redundant endpoints
$promises = [
    async(fn() => fetch('https://api1.example.com/data')),
    async(fn() => fetch('https://api2.example.com/data')),
    async(fn() => fetch('https://api3.example.com/data'))
];

// Get the result from whichever completes first
$response = await(race($promises));
$data = $response->json();
echo "Got data from the fastest source";
```

### Controlled Concurrency with Map

```php
use function map;

// List of user IDs to fetch
$userIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

// Process at most 3 requests at a time
$responses = await(map($userIds, function($id) {
    return async(function() use ($id) {
        return fetch("https://api.example.com/users/{$id}");
    });
}, 3));

// Process the responses
foreach ($responses as $index => $response) {
    $user = $response->json();
    echo "Processed user {$user['name']}\n";
}
```

### Batch Processing

```php
use function batch;

// Array of items to process
$items = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

// Process in batches of 3 with max 2 concurrent batches
$results = await(batch(
    $items,
    function($batch) {
        // Process a batch
        return async(function() use ($batch) {
            $batchResults = [];
            foreach ($batch as $id) {
                $response = await(async(fn() =>
                    fetch("https://api.example.com/users/{$id}")
                ));
                $batchResults[] = $response->json();
            }
            return $batchResults;
        });
    },
    3, // batch size
    2  // concurrency
));
```

### With Retries

```php
use function retry;

// Retry a flaky request up to 3 times with exponential backoff
$data = await(retry(
    function() {
        return async(function() {
            return fetch('https://api.example.com/unstable-endpoint');
        });
    },
    3, // max attempts
    function($attempt) {
        // Exponential backoff strategy
        return min(pow(2, $attempt) * 100, 1000);
    }
));
```

## Advanced Configuration

### Automatic Retries

Fetch PHP automatically retries transient failures with exponential backoff and jitter.

- Default attempts: initial try + 1 retry (configurable)
- Default delay: 100ms base with exponential backoff and jitter
- Retry triggers:
  - Network/connect errors (e.g., timeouts, DNS, connection refused)
  - HTTP status codes such as 408, 429, 500, 502, 503, 504 (customizable)

Configure per-request:

```php
$response = fetch_client()
    ->retry(3, 200)                // 3 retries, 200ms base delay
    ->retryStatusCodes([429, 503]) // optional: customize which statuses retry
    ->get('https://api.example.com/unstable');
```

Notes:

- HTTP error statuses do not throw; you receive the response. Retries happen internally when configured.
- Network failures are retried and, if all attempts fail, throw a `Fetch\\Exceptions\\RequestException`.

### Authentication

```php
// Basic auth
$response = fetch('https://api.example.com/secure', [
    'auth' => ['username', 'password']
]);

// Bearer token
$response = fetch_client()
    ->withToken('your-oauth-token')
    ->get('https://api.example.com/secure');
```

### Proxies

```php
$response = fetch('https://api.example.com', [
    'proxy' => 'http://proxy.example.com:8080'
]);

// Or with fluent API
$response = fetch_client()
    ->withProxy('http://proxy.example.com:8080')
    ->get('https://api.example.com');
```

### Global Client Configuration

```php
// Configure once at application bootstrap
fetch_client([
    'base_uri' => 'https://api.example.com',
    'headers' => [
        'User-Agent' => 'MyApp/1.0',
        'Accept' => 'application/json',
    ],
    'timeout' => 10,
]);

// Use the configured client throughout your application
function getUserData($userId) {
    return fetch_client()->get("/users/{$userId}")->json();
}

function createUser($userData) {
    return fetch_client()->post('/users', $userData)->json();
}
```

## Working with Responses

```php
$response = fetch('https://api.example.com/users/1');

// Check if request was successful
if ($response->isSuccess()) {
    // HTTP status code
    echo $response->getStatusCode(); // 200

    // Response body as JSON
    $user = $response->json();

    // Response body as string
    $body = $response->getBody()->getContents();

    // Get a specific header
    $contentType = $response->getHeaderLine('Content-Type');

    // Check status code categories
    if ($response->getStatus()->isSuccess()) {
        echo "Request succeeded";
    }
}
```

// Inspect retry-related statuses explicitly if needed
if ($response->getStatusCode() === 429) {
    // Handle rate limit response
}

## Working with Type-Safe Enums

```php
use Fetch\Enum\Method;
use Fetch\Enum\ContentType;
use Fetch\Enum\Status;

// Use enums for HTTP methods
$client = fetch_client();
$response = $client->request(Method::POST, '/users', $userData);

// Check HTTP status with enums
if ($response->getStatus() === Status::OK) {
    // Process successful response
}

// Content type handling
$response = $client->withBody($data, ContentType::JSON)->post('/users');
```

## Error Handling

```php
// Synchronous error handling
try {
    $response = fetch('https://api.example.com/nonexistent');

    if (!$response->isSuccess()) {
        echo "Request failed with status: " . $response->getStatusCode();
    }
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage();
}

// Asynchronous error handling
$handler = fetch_client()->getHandler();
$handler->async();

$promise = $handler->get('https://api.example.com/nonexistent')
    ->then(function ($response) {
        if ($response->isSuccess()) {
            return $response->json();
        }
        throw new \Exception("Request failed with status: " . $response->getStatusCode());
    })
    ->catch(function (\Throwable $e) {
        echo "Error: " . $e->getMessage();
    });
```

### Timeouts

Control both total request timeout and connection timeout:

```php
$response = fetch('https://api.example.com/data', [
    'timeout' => 15,          // total request timeout (seconds)
    'connect_timeout' => 5,   // connection timeout (seconds)
]);
```

If `connect_timeout` is not provided, it defaults to the `timeout` value.

### Logging and Redaction

When request/response logging is enabled via a logger, sensitive values are redacted:

- Headers: Authorization, X-API-Key, API-Key, X-Auth-Token, Cookie, Set-Cookie
- Options: `auth` credentials

Logged context includes method, URI, selected options (sanitized), status code, duration, and content length.

## License

This project is licensed under the **MIT License** – see the [LICENSE](LICENSE) file for full terms.

The MIT License allows you to:

- Use the software for any purpose, including commercial applications
- Modify and distribute the software
- Include it in proprietary software
- Use it without warranty or liability concerns

This permissive license encourages adoption while maintaining attribution requirements.

## Contributing

Contributions are welcome! We're currently looking for help with:

- Expanding test coverage
- Improving documentation
- Adding support for additional HTTP features

To contribute:

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/amazing-feature`)
3. Commit your Changes (`git commit -m 'Add some amazing-feature'`)
4. Push to the Branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Acknowledgments

- Thanks to **Guzzle HTTP** for providing the underlying HTTP client
- Thanks to all contributors who have helped improve this package
- Special thanks to the PHP community for their support and feedback
