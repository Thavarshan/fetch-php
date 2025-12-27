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
- **Retry Mechanics**: Configurable retry logic with exponential backoff for transient failures
- **RFC 7234 HTTP Caching**: Full caching support with ETag/Last-Modified revalidation, stale-while-revalidate, and stale-if-error
- **Connection Pooling**: Reuse TCP connections across requests with global connection pool and DNS caching
- **HTTP/2 Support**: Native HTTP/2 protocol support for improved performance
- **Debug & Profiling**: Built-in debugging and performance profiling capabilities
- **Type-Safe Enums**: Modern PHP 8.3+ enums for HTTP methods, content types, and status codes
- **Testing Utilities**: Built-in mock responses and request recording for testing
- **PHP-style Helper Functions**: Includes traditional PHP function helpers (`get()`, `post()`, etc.) for those who prefer that style
- **PSR Compliant**: Implements PSR-7 (HTTP Messages), PSR-18 (HTTP Client), and PSR-3 (Logger) standards

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
| Types             | Modern PHP 8.3+ enums                                   | String constants    |
| Helper Functions  | Multiple styles available                               | Limited             |

## Installation

```bash
composer require jerome/fetch-php
```

> **Requirements**: PHP 8.3 or higher

## Basic Usage

### JavaScript-style API (Promise Chaining)

```php
use function Matrix\Support\async;

// JavaScript-like promise chaining in PHP
async(fn() => fetch('https://api.example.com/users'))
    ->then(fn ($response) => $response->json())
    ->catch(fn ($error) => echo "Error: " . $error->getMessage())
    ->finally(fn () => echo "Request completed.");
```

Or, using the client handler for more control:

```php
$handler = fetch_client()->getHandler();
$handler->async();

$handler->get('https://api.example.com/users')
    ->then(fn ($response) => $response->json())
    ->catch(fn ($error) => echo "Error: " . $error->getMessage())
    ->finally(fn () => echo "Request completed.");
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

> **Note**: The async functions (`async`, `await`, `all`, `race`, `map`, `batch`, `retry`) are provided by the [jerome/matrix](https://packagist.org/packages/jerome/matrix) library, which is included as a dependency.

### Using Async/Await

```php
use function Matrix\Support\async;
use function Matrix\Support\await;

$response = await(async(fn() => fetch('https://api.example.com/users')));
$users = $response->json();
echo "Fetched " . count($users) . " users";
```

### Multiple Concurrent Requests with Async/Await

```php
// These async functions are provided by the Matrix library dependency
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\all;

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
use function Matrix\Support\async;
use function Matrix\Support\await;

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
use function Matrix\Support\async;
use function Matrix\Support\await;

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
use function Matrix\Support\race;

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
use function Matrix\Support\map;

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
use function Matrix\Support\batch;

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
use function Matrix\Support\retry;

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

Fetch PHP automatically retries transient failures with exponential backoff.

- Default: 1 retry attempt (`ClientHandler::DEFAULT_RETRIES`) with a 100 ms base delay
- Default delay: 100 ms base with exponential backoff (when retries configured)
- Retry triggers:
  - Network/connect errors (e.g., ConnectException)
  - HTTP status codes: 408, 429, 500, 502, 503, 504, 507, 509, 520-523, 525, 527, 530 (customizable)

Configure per-request:

```php
$response = fetch_client()
    ->retry(3, 200)                // 3 retries, 200ms base delay
    ->retryStatusCodes([429, 503]) // optional: customize which statuses retry
    ->retryExceptions([ConnectException::class]) // optional: customize exception types
    ->get('https://api.example.com/unstable');
```

Notes:

- HTTP error statuses do not throw; you receive the response. Retries happen internally when configured.
- Network failures are retried and, if all attempts fail, throw a `Fetch\Exceptions\RequestException`.

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
if ($response->successful()) {
    // HTTP status code
    echo $response->getStatusCode(); // 200

    // Response body as JSON (returns array by default)
    $user = $response->json();

    // Response body as object
    $userObject = $response->object();

    // Response body as array
    $userArray = $response->array();

    // Response body as string
    $body = $response->text();

    // Get a specific header
    $contentType = $response->getHeaderLine('Content-Type');

    // Check status code categories
    if ($response->isSuccess()) {
        echo "Request succeeded (2xx)";
    }

    if ($response->isOk()) {
        echo "Request returned 200 OK";
    }

    if ($response->isNotFound()) {
        echo "Resource not found (404)";
    }
}

// ArrayAccess support
$name = $response['name']; // Access JSON response data directly

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
if ($response->statusEnum() === Status::OK) {
    // Process successful response
}

// Or use the isStatus helper
if ($response->isStatus(Status::OK)) {
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

    if (!$response->successful()) {
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
        if ($response->successful()) {
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

## Caching (sync-only)

> **Note:** Caching is available for synchronous requests only. Async requests intentionally bypass the cache.

Fetch PHP implements RFC 7234-aware HTTP caching with ETag/Last-Modified revalidation, `stale-while-revalidate`, and `stale-if-error` support. The default backend is an in-memory cache (`MemoryCache`), but you can use `FileCache` or implement your own backend via `CacheInterface`.

### Cache Behavior

- **Cacheable methods by default**: `GET`, `HEAD`
- **Cacheable status codes**: 200, 203, 204, 206, 300, 301, 404, 410 (RFC 7234 defaults)
- **Cache-Control headers respected**: `no-store`, `no-cache`, `max-age`, `s-maxage`, etc.
- **Revalidation**: Automatically adds `If-None-Match` (ETag) and `If-Modified-Since` (Last-Modified) headers for stale entries
- **304 Not Modified**: Merges headers and returns cached body
- **Vary headers**: Supports cache variance by headers (default: Accept, Accept-Encoding, Accept-Language)

### Basic Cache Setup

```php
use Fetch\Cache\MemoryCache;
use Fetch\Cache\FileCache;

$handler = fetch_client()->getHandler();

// Enable cache with in-memory backend (default)
$handler->withCache();

// Or use file-based cache
$handler->withCache(new FileCache('/path/to/cache'));

// Disable cache
$handler->withoutCache();

$response = $handler->get('https://api.example.com/users');
```

### Advanced Cache Configuration

```php
$handler->withCache(null, [
    'default_ttl' => 3600,                  // Default TTL in seconds (overridden by Cache-Control)
    'respect_cache_headers' => true,        // Honor Cache-Control headers (default: true)
    'is_shared_cache' => false,             // Act as shared cache (respects s-maxage)
    'stale_while_revalidate' => 60,         // Serve stale for 60s while revalidating
    'stale_if_error' => 300,                // Serve stale for 300s if backend fails
    'vary_headers' => ['Accept', 'Accept-Language'], // Headers to vary cache by
    'cache_methods' => ['GET', 'HEAD'],     // Cacheable HTTP methods
    'cache_status_codes' => [200, 301],     // Cacheable status codes
]);
```

### Per-Request Cache Control

```php
// Force a fresh request (bypass cache)
$response = $handler->withOptions(['cache' => ['force_refresh' => true]])
    ->get('https://api.example.com/users');

// Custom TTL for specific request
$response = $handler->withOptions(['cache' => ['ttl' => 600]])
    ->get('https://api.example.com/users');

// Custom cache key
$response = $handler->withOptions(['cache' => ['key' => 'custom:users']])
    ->get('https://api.example.com/users');

// Cache POST/PUT payloads (requires allowing the method globally)
$handler->withCache(null, [
    'cache_methods' => ['GET', 'HEAD', 'POST'],
]);
$report = $handler->withOptions([
    'cache' => [
        'ttl' => 120,
        'cache_body' => true, // include the JSON body in the cache key
    ],
])->post('https://api.example.com/reports', ['range' => 'weekly']);

Useful patterns:

- **Force refresh**: set `force_refresh => true` on the request to ignore stored entries.
- **Cache POST/PUT**: allow the verb in `cache_methods` via `withCache()` and set `cache_body => true` so the request body participates in the cache key.
- **Static assets**: pin a custom `key` for predictable lookups regardless of URL params.
```

## Connection Pooling & HTTP/2

Connection pooling enables reuse of TCP connections across multiple requests, reducing latency and improving performance. The pool is **shared globally** across all handler instances, and includes DNS caching for faster lookups.

### Enable Connection Pooling

```php
$handler = fetch_client()->getHandler();

// Enable with default settings
$handler->withConnectionPool(true);

// Or configure with custom options
$handler->withConnectionPool([
    'enabled' => true,
    'max_connections' => 50,        // Total connections across all hosts
    'max_per_host' => 10,           // Max connections per host
    'max_idle_per_host' => 5,       // Idle sockets kept per host
    'keep_alive_timeout' => 60,     // Connection lifetime in seconds
    'connection_timeout' => 5,      // Dial timeout in seconds
    'dns_cache_ttl' => 300,         // DNS cache TTL in seconds
    'connection_warmup' => false,
    'warmup_connections' => 0,
]);
```

### Enable HTTP/2

```php
// Enable HTTP/2 (requires curl with HTTP/2 support)
$handler->withHttp2(true);

// Or configure with options
$handler->withHttp2([
    'enabled' => true,
    // Additional HTTP/2 configuration options...
]);
```

### Pool Management

```php
// Get pool statistics
$stats = $handler->getPoolStats();
// Returns: connections_created, connections_reused, total_requests, average_latency, reuse_rate

// Close all active connections
$handler->closeAllConnections();

// Reset pool and DNS cache (useful for testing)
$handler->resetPool();
```

> **Note**: The connection pool is static/global and shared across all handlers. Call `resetPool()` in your test teardown to ensure isolation between tests.

## Debugging & Profiling

Enable debug snapshots and optional profiling:

```php
$handler = fetch_client()->getHandler();

// Enable debug with default options (captures everything)
$handler->withDebug();

// Or enable with specific options
$handler->withDebug([
    'request_headers' => true,
    'request_body' => true,
    'response_headers' => true,
    'response_body' => 1024,  // Truncate response body at 1024 bytes
    'timing' => true,
    'memory' => true,
    'dns_resolution' => true,
]);

// Enable profiling
$handler->withProfiler(new \Fetch\Support\FetchProfiler);

// Set log level (requires PSR-3 logger to be configured)
$handler->withLogLevel('info'); // default: debug

$response = $handler->get('https://api.example.com/users');

// Preferred: read per-response debug snapshot
$responseDebug = $response->getDebugInfo();

// Legacy fallback for BC: handler-level snapshot (may lag in concurrent flows)
$lastDebug = $handler->getLastDebugInfo();
```

## Testing Support

Fetch PHP includes built-in testing utilities for mocking HTTP responses:

```php
use Fetch\Testing\MockServer;
use Fetch\Testing\MockResponse;

// Mock a single response
MockServer::fake([
    'GET https://api.example.com/users/1' => MockResponse::json([
        'id' => 1,
        'name' => 'Ada Lovelace',
    ]),
]);

$response = fetch('https://api.example.com/users/1');
// Returns mocked response without making an actual HTTP request
MockServer::assertSent('GET https://api.example.com/users/1');

// Mock a sequence of responses
MockServer::fake([
    'https://api.example.com/users/*' => MockResponse::sequence([
        MockResponse::json(['id' => 1]),
        MockResponse::json(['id' => 2]),
        MockResponse::notFound(),
    ]),
]);

fetch('https://api.example.com/users/alpha'); // gets id 1
fetch('https://api.example.com/users/beta');  // gets id 2
fetch('https://api.example.com/users/omega'); // 404 from sequence
```

## Advanced Response Features

### Response Status Checks

```php
$response = fetch('https://api.example.com/data');

// Status category checks
$response->isInformational(); // 1xx
$response->isSuccess();       // 2xx
$response->isRedirection();   // 3xx
$response->isClientError();   // 4xx
$response->isServerError();   // 5xx

// Specific status checks
$response->isOk();            // 200
$response->isCreated();       // 201
$response->isNoContent();     // 204
$response->isNotFound();      // 404
$response->isForbidden();     // 403
$response->isUnauthorized();  // 401

// Generic status check
$response->isStatus(Status::CREATED);
$response->isStatus(201);
```

### Response Helpers

```php
// Check if response contains JSON
if ($response->isJson()) {
    $data = $response->json();
}

// Get response as different types with error handling
$data = $response->json(assoc: true, throwOnError: false);
$object = $response->object(throwOnError: false);
$array = $response->array(throwOnError: false);
```

## Connection Pool Management

Clean up connections or reset the pool (useful in tests):

```php
$handler = fetch_client()->getHandler();

// Close all active connections
$handler->closeAllConnections();

// Reset the entire pool and DNS cache (useful in tests)
$handler->resetPool();

// Get pool statistics
$stats = $handler->getPoolStats();
// Returns: connections_created, connections_reused, total_requests, average_latency, reuse_rate
```

## Async Notes

- Async requests use the same pipeline (mocking, profiling, logging) but bypass caching by design.
- Matrix helpers (`async`, `await`, `all`, `race`, `map`, `batch`, `retry`) are re-exported in `Fetch\Support\helpers.php`.
- Errors are wrapped with method/URL context while preserving the original exception chain.
- Use `$handler->async()` to enable async mode, or use the Matrix async utilities directly.

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
