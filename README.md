[![Fetch PHP](./assets/Banner.jpg)](https://github.com/Thavarshan/fetch-php)

# About Fetch PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jerome/fetch-php.svg)](https://packagist.org/packages/jerome/fetch-php)
[![Tests](https://github.com/Thavarshan/fetch-php/actions/workflows/run-tests.yml/badge.svg?label=tests&branch=main)](https://github.com/Thavarshan/fetch-php/actions/workflows/run-tests.yml)
[![Check & fix styling](https://github.com/Thavarshan/fetch-php/actions/workflows/php-cs-fixer.yml/badge.svg?label=code%20style&branch=main)](https://github.com/Thavarshan/fetch-php/actions/workflows/php-cs-fixer.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/jerome/fetch-php.svg)](https://packagist.org/packages/jerome/fetch-php)

**FetchPHP** is a powerful PHP HTTP client library built on top of the Guzzle HTTP client, designed to mimic the behavior of JavaScript’s `fetch` API. It leverages **Matrix** for true asynchronous capabilities using PHP Fibers, allowing developers to use a **JavaScript-like async/await** syntax. FetchPHP also offers a **fluent API** inspired by Laravel's HTTP client for more flexible and readable request building.

---

### **Why Choose FetchPHP Over Guzzle?**

Guzzle is a robust and widely-used HTTP client for PHP, and it does support asynchronous requests using Promises. However, **FetchPHP** goes a step further by offering **true asynchronous task management** via PHP Fibers, powered by **Matrix**. Here’s what sets FetchPHP apart:

- **True Async Task Management with Fibers**: While Guzzle uses Promises for async operations, FetchPHP harnesses PHP’s native Fibers (introduced in PHP 8.1) to provide **true non-blocking concurrency**. This allows more **fine-grained control** over task execution, lifecycle management (e.g., pausing, resuming, retrying), and error handling.

- **JavaScript-Like `async`/`await` Syntax**: FetchPHP introduces a syntax that will feel familiar to developers who use JavaScript’s async/await functionality. With `async()`, developers can write asynchronous PHP code in a more readable and intuitive way.

- **Fluent API**: FetchPHP provides a **fluent, chainable API** that makes constructing and managing HTTP requests easier and more flexible, similar to Laravel’s HTTP client. This contrasts with Guzzle’s Promise-based API, which can feel more rigid and less intuitive for managing complex tasks.

- **Error Handling and Task Lifecycle Control**: FetchPHP, using Matrix, allows developers to handle errors at a more granular level. Tasks can be paused, resumed, canceled, or retried dynamically, and errors can be managed through customizable handlers. Guzzle’s Promises handle errors in a less flexible way, usually through chained `.then()` and `.catch()` methods.

### **How FetchPHP's Async Task Management is Different from Guzzle**

Here’s a breakdown of FetchPHP’s **AsyncHelper** and **Task** classes, which manage true asynchronous behavior, compared to Guzzle’s Promise-based approach:

- **Fiber-Based Concurrency**: FetchPHP uses PHP Fibers to run tasks asynchronously. Fibers allow tasks to be paused, resumed, and canceled mid-execution, which isn’t possible with Guzzle’s Promises. This gives FetchPHP a true **multi-tasking** advantage.

- **Task Lifecycle Management**: FetchPHP allows you to start, pause, resume, cancel, and retry tasks directly using the `Task` class. Guzzle does not offer built-in lifecycle management for Promises at this level. In FetchPHP, you can track the status of a task (e.g., `PENDING`, `RUNNING`, `PAUSED`, `COMPLETED`, `FAILED`, `CANCELED`), providing more control over long-running or asynchronous processes.

- **Custom Error Handling**: FetchPHP provides a customizable `ErrorHandler` that developers can use to manage retries, logging, and error resolution. This allows you to handle failures dynamically and retry tasks when needed, offering a level of **error recovery** beyond Guzzle’s Promises.

#### **Example: Managing Asynchronous Tasks with FetchPHP**

```php
<?php

use Fetch\Http\ClientHandler;
use Matrix\AsyncHelper;

$response = async(fn () => fetch('https://example.com', [
    'method' => 'POST',
    'headers' => ['Content-Type' => 'application/json'],
    'body' => json_encode(['key' => 'value']),
]))->then(fn ($response) => $response->json())
  ->catch(fn ($e) => $e->getMessage());

// FetchPHP manages task lifecycle (start, pause, resume, cancel)
```

#### **Lifecycle Control Example with FetchPHP**

```php
<?php

use Matrix\Task;

// Define a long-running task
$task = new Task(function () {
    // Task operation here
    return "Task completed!";
});

// Start the task
$task->start();

// Pause and resume the task dynamically
$task->pause();
$task->resume();

// Cancel the task if needed
$task->cancel();

// Retry the task if it fails
if ($task->getStatus() === TaskStatus::FAILED) {
    $task->retry();
}
```

---

### **Why FetchPHP is Better for Asynchronous PHP**

While Guzzle is a fantastic tool for making HTTP requests, FetchPHP brings **modern PHP capabilities** with **PHP 8 Fibers**, making it more powerful for developers who need **true asynchronous task management** with a **JavaScript-like syntax**. FetchPHP is designed to make your code more flexible, readable, and efficient when managing complex HTTP operations, especially when concurrency and non-blocking I/O are crucial.

---

## **Installation**

To install FetchPHP, run the following command:

```bash
composer require jerome/fetch-php
```

FetchPHP requires PHP 8.1 or above due to its use of Fibers for async tasks.

---

## **Core Features**

- **JavaScript-like fetch API** for HTTP requests (synchronous and asynchronous).
- **Fluent API** for building requests with a readable and chainable syntax.
- **Asynchronous support** with PHP Fibers via the Matrix package.
- **Built on Guzzle** for robust and reliable HTTP client functionality.

---

## **Usage Examples**

### **JavaScript-like Fetch API (Synchronous)**

```php
<?php

$response = fetch('https://example.com', [
    'method' => 'POST',
    'headers' => [
        'Content-Type' => 'application/json',
    ],
    'body' => json_encode(['key' => 'value']),
]);

$data = $response->json();
```

### **JavaScript-like Fetch API (Asynchronous)**

```php
<?php

$response = async(fn () => fetch('https://example.com', [
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode(['key' => 'value']),
    ]))
    ->then(fn (ResponseInterface $response) => $response->json())
    ->catch(fn (Throwable $e) => $e->getMessage());
```

---

### **Using the Fluent API**

The **fluent API** allows for a more flexible and readable way of building and sending HTTP requests:

#### **Synchronous Example**

```php
<?php

$response = fetch()
    ->baseUri('https://example.com')
    ->withHeaders('Content-Type', 'application/json')
    ->withBody(json_encode(['key' => 'value']))
    ->withToken('fake-bearer-auth-token')
    ->post('/posts');

$data = $response->json();
```

#### **Asynchronous Example**

```php
<?php

$response = async(fn () => fetch()
    ->baseUri('https://example.com')
    ->withHeaders('Content-Type', 'application/json')
    ->withBody(json_encode(['key' => 'value']))
    ->withToken('fake-bearer-auth-token')
    ->post('/posts'))
    ->then(fn (ResponseInterface $response) => $response->json())
    ->catch(fn (Throwable $e) => $e->getMessage());
```

---

### **Using the ClientHandler Class**

The `ClientHandler` class is responsible for managing HTTP requests, including synchronous and asynchronous handling. You can use it directly for more advanced use cases:

#### **Basic Example with ClientHandler**

```php
<?php

use Fetch\Http\ClientHandler;

$response = ClientHandler::handle('GET', 'https://example.com', [
    'headers' => ['Accept' => 'application/json']
]);

$data = $response->json();
```

#### **Asynchronous Example with ClientHandler**

```php
<?php

use Fetch\Http\ClientHandler;
use Matrix\AsyncHelper;

$response = async(fn () => ClientHandler::handle('POST', 'https://example.com', [
    'headers' => ['Content-Type' => 'application/json'],
    'body' => json_encode(['key' => 'value']),
]))->then(fn ($response) => $response->json())
  ->catch(fn ($e) => $e->getMessage());
```

---

## **Request Options**

FetchPHP accepts an array of options to configure requests:

- **`method`**: HTTP method (e.g., `'GET'`, `'POST'`, etc.). Default is `'GET'`.
- **`headers`**: Array of HTTP headers.
- **`body`**: Request body for methods like POST, PUT, PATCH.
- **`json`**: JSON data to send as the request body.
- **`timeout`**: Timeout for the request in seconds.
- **`auth`**: Array for HTTP Basic or Digest authentication.
- **`proxy`**: Proxy server URL for routing requests.
- **`client`**: A custom Guzzle client instance.

---

### **Error Handling**

Both synchronous and asynchronous requests handle errors gracefully. Here's how you can manage errors:

#### **Synchronous Error Handling**

```php
<?php

$response = fetch('https://nonexistent-url.com');

if ($response->ok()) {
    echo $response->json();
} else {
    echo "Error: " . $response->statusText();
}
```

#### **Asynchronous Error Handling**

```php
<?php

$response = async(fn () => fetch('https://nonexistent-url.com'))
    ->then(fn ($response) => $response->json())
    ->catch(fn ($e) => $e->getMessage());

echo $response;
```

---

## **Proxy and Authentication Support**

FetchPHP supports proxies and authentication out of the box:

### **Proxy Example**

```php
<?php

$response = fetch('https://example.com', [
    'proxy' => 'tcp://localhost:8080'
]);

echo $response->statusText();
```

### **Authentication Example**

```php
<?php

$response = fetch('https://example.com/secure-endpoint', [
    'auth' => ['username', 'password']
]);

echo $response->statusText();
```

---

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

---

## Contributing

Contributions are what make the open-source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/amazing-feature`)
3. Commit your Changes (`git commit -m 'Add some amazing-feature'`)
4. Push to the Branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## Authors

- **[Jerome Thayananthajothy]** - *Initial work* - [Thavarshan](https://github.com/Thavarshan)

See also the list of [contributors](https://github.com/Thavarshan/fetch-php/contributors) who participated in this project.

## Acknowledgments

- Thanks to **Guzzle HTTP** for providing the underlying HTTP client that powers synchronous requests.
