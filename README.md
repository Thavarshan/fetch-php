[![Fetch PHP](./assets/Banner.jpg)](https://github.com/Thavarshan/fetch-php)

# Fetch PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jerome/fetch-php.svg)](https://packagist.org/packages/jerome/fetch-php)
[![Tests](https://github.com/Thavarshan/fetch-php/actions/workflows/run-tests.yml/badge.svg?label=tests&branch=main)](https://github.com/Thavarshan/fetch-php/actions/workflows/run-tests.yml)
[![Check & fix styling](https://github.com/Thavarshan/fetch-php/actions/workflows/laravel-pint.yml/badge.svg)](https://github.com/Thavarshan/fetch-php/actions/workflows/laravel-pint.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/jerome/fetch-php.svg)](https://packagist.org/packages/jerome/fetch-php)

**Fetch PHP** is a modern HTTP client library for PHP that brings JavaScript's `fetch` API experience to PHP. Built on top of Guzzle and powered by the **Matrix** package for true asynchronous capabilities using PHP Fibers, Fetch PHP allows you to write asynchronous HTTP code with a clean, intuitive JavaScript-like syntax.

With support for both synchronous and asynchronous requests, a fluent chainable API, and powerful retry mechanics, Fetch PHP streamlines HTTP operations in your PHP applications.

Make sure to check out [Matrix](https://github.com/Thavarshan/matrix) for more information on how Fetch PHP is powered by PHP Fibers.

Full documentation can be found [here](https://fetch-php.thavarshan.com/)

---

## Key Features

- **JavaScript-like Syntax**: Write HTTP requests just like you would in JavaScript with the `fetch()` function
- **True Async with PHP Fibers**: Leverage PHP 8.1+ Fibers for non-blocking I/O
- **Promise-based API**: Use familiar `.then()` and `.catch()` methods for async operations
- **Fluent Interface**: Build requests with a clean, chainable API
- **Built on Guzzle**: Benefit from Guzzle's robust functionality with a more elegant API
- **Retry Mechanics**: Automatically retry failed requests with exponential backoff

## Why Choose Fetch PHP?

### Beyond Guzzle

While Guzzle is a powerful HTTP client, Fetch PHP enhances the experience by providing:

- **True async with PHP Fibers**: Unlike Guzzle's Promise-based concurrency, Fetch PHP uses PHP Fibers for genuine asynchronous operations
- **JavaScript-like API**: Enjoy the familiar `async`/`await` pattern from JavaScript
- **Task Lifecycle Management**: Start, pause, resume, cancel, and retry tasks with fine-grained control
- **Simplified Error Handling**: Handle errors more elegantly with promise-based callbacks

| Feature | Fetch PHP | Guzzle |
|---------|-----------|--------|
| Async Operations | True async with PHP Fibers | Promise-based concurrency |
| API Style | JavaScript-like fetch + async/await | PHP-style Promises |
| Task Control | Full lifecycle management | Limited to Promise chains |
| Error Handling | Customizable error handlers | Standard Promise rejection |
| Syntax | Clean, minimal | More verbose |

## Installation

```bash
composer require jerome/fetch-php
```

> **Requirements**: PHP 8.1 or higher

## Basic Usage

### Synchronous Requests

```php
// Simple GET request
$response = fetch('https://api.example.com/users');
$users = $response->json();

// POST request with JSON body
$response = fetch('https://api.example.com/users', [
    'method' => 'POST',
    'headers' => ['Content-Type' => 'application/json'],
    'body' => ['name' => 'John Doe', 'email' => 'john@example.com'],
]);
```

### Asynchronous Requests

```php
use function async;
use function await;
use Fetch\Interfaces\Response as ResponseInterface;

// Using Promise-style chaining
async(fn () => fetch('https://api.example.com/users'))
    ->then(fn (ResponseInterface $response) => $response->json())
    ->catch(fn (\Throwable $e) => print "Error: " . $e->getMessage());

// Using async/await syntax
try {
    $response = await(async(fn () => fetch('https://api.example.com/users')));
    $users = $response->json();
} catch (\Throwable $e) {
    echo 'Error: ' . $e->getMessage();
}
```

### Fluent API

```php
// Chain methods to build your request
$response = fetch()
    ->baseUri('https://api.example.com')
    ->withHeaders(['Accept' => 'application/json'])
    ->withToken('your-auth-token')
    ->withQueryParameters(['page' => 1, 'limit' => 10])
    ->get('/users');

// Async with fluent API
async(fn () => fetch()
    ->baseUri('https://api.example.com')
    ->withBody(['name' => 'John Doe'])
    ->post('/users'))
    ->then(fn ($response) => $response->json());
```

## Advanced Usage

### Concurrent Requests

```php
use function all;
use function await;
use function async;

// Prepare multiple async requests
$userPromise = async(fn () => fetch('https://api.example.com/users'));
$postsPromise = async(fn () => fetch('https://api.example.com/posts'));
$commentsPromise = async(fn () => fetch('https://api.example.com/comments'));

// Wait for all to complete
$results = await(all([
    'users' => $userPromise,
    'posts' => $postsPromise,
    'comments' => $commentsPromise
]));

// Access results
$users = $results['users']->json();
$posts = $results['posts']->json();
$comments = $results['comments']->json();
```

### Task Lifecycle Management

```php
use Task;
use Enum\TaskStatus;

// Create a task for a request
$task = async(fn () => fetch('https://api.example.com/large-dataset'));

// Start the task
$task->start();

// Check the task status
if ($task->getStatus() === TaskStatus::RUNNING) {
    // You can pause the task if needed
    $task->pause();

    // And resume it later
    $task->resume();
}

// Cancel the task if needed
$task->cancel();

// Retry the task if it failed
if ($task->getStatus() === TaskStatus::FAILED) {
    $task->retry();
}

// Get the result when complete
if ($task->getStatus() === TaskStatus::COMPLETED) {
    $response = $task->getResult();
    $data = $response->json();
}
```

### Configuring Retries

```php
// Retry up to 3 times with a 100ms delay between retries
$response = fetch('https://api.example.com/unstable-endpoint', [
    'retries' => 3,
    'retry_delay' => 100
]);

// Or with fluent API
$response = fetch()
    ->retry(3, 100)
    ->get('https://api.example.com/unstable-endpoint');
```

### Working with Responses

```php
$response = fetch('https://api.example.com/users/1');

// Check if request was successful
if ($response->ok()) {
    // HTTP status code
    echo $response->status(); // 200

    // Response body as JSON
    $user = $response->json();

    // Response body as string
    $body = $response->body();

    // Get a specific header
    $contentType = $response->header('Content-Type');
}
```

## Advanced Configuration

### Authentication

```php
// Basic auth
$response = fetch('https://api.example.com/secure', [
    'auth' => ['username', 'password']
]);

// Bearer token
$response = fetch()
    ->withToken('your-oauth-token')
    ->get('https://api.example.com/secure');
```

### Proxies

```php
$response = fetch('https://api.example.com', [
    'proxy' => 'http://proxy.example.com:8080'
]);

// Or with fluent API
$response = fetch()
    ->withProxy('http://proxy.example.com:8080')
    ->get('https://api.example.com');
```

## Error Handling

```php
// Synchronous error handling
try {
    $response = fetch('https://api.example.com/nonexistent');

    if ($response->failed()) {
        echo "Request failed with status: " . $response->status();
    }
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage();
}

// Asynchronous error handling
async(fn () => fetch('https://api.example.com/nonexistent'))
    ->then(function ($response) {
        if ($response->ok()) {
            return $response->json();
        }
        throw new \Exception("Request failed with status: " . $response->status());
    })
    ->catch(function (\Throwable $e) {
        echo "Error: " . $e->getMessage();

        // You could retry the request here
        return retry();
    });
```

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

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
