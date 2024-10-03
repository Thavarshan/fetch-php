# Getting Started

FetchPHP is a modern HTTP client for PHP that mimics the JavaScript `fetch()` API, providing both synchronous and asynchronous request handling. Whether you're familiar with JavaScript's `fetch()` or Laravel's HTTP client, FetchPHP provides a similar, intuitive API. It is powered by the Guzzle HTTP client for synchronous requests and Matrix for asynchronous task management using PHP Fibers.

## Core Features

FetchPHP offers several key features to simplify HTTP requests in PHP:

- **JavaScript-like `fetch()` API**: Similar to JavaScript's `fetch()`, making it intuitive for developers.
- **Fluent API**: Chain methods for flexible and readable HTTP request building.
- **Asynchronous Support**: Manage asynchronous tasks via PHP Fibers, powered by Matrix.
- **Powered by Guzzle**: Synchronous requests use the reliable Guzzle HTTP client.
- **Error Handling**: Comprehensive error management with both synchronous and asynchronous request support.

## Usage Examples

### JavaScript-like Fetch API

FetchPHP allows you to easily perform synchronous HTTP requests with a syntax similar to JavaScript’s `fetch()`.

#### **Synchronous Example**

```php
$response = fetch('https://example.com', [
    'method' => 'POST',
    'headers' => [
        'Content-Type' => 'application/json',
    ],
    'body' => json_encode(['key' => 'value']),
]);

$data = $response->json();
```

This example sends a POST request with a JSON body and retrieves the JSON response.

### JavaScript-like Async Fetch API

FetchPHP also supports asynchronous requests using a syntax similar to JavaScript’s async/await.

#### **Asynchronous Example**

```php
use Fetch\Interfaces\Response as ResponseInterface;

$data = null;

async(fn () => fetch('https://example.com', [
    'method' => 'POST',
    'headers' => [
        'Content-Type' => 'application/json',
    ],
    'body' => json_encode(['key' => 'value']),
]))
    ->then(fn (ResponseInterface $response) => $data = $response->json())  // Success handler
    ->catch(fn (Throwable $e) => $e->getMessage());                // Error handler

echo $data;
```

This example asynchronously sends a POST request and processes the JSON response, or handles an error using `.catch()`.

### Fluent API

FetchPHP’s fluent API allows you to chain methods to build and send HTTP requests more elegantly and flexibly.

#### **Synchronous Example Using Fluent API**

```php
$response = fetch()
    ->baseUri('https://example.com')
    ->withHeaders('Content-Type', 'application/json')
    ->withBody(['key' => 'value'])
    ->withToken('fake-bearer-auth-token')
    ->post('/posts');

$data = $response->json();
```

This fluent API example sends a POST request to `/posts` with a JSON body and Bearer token authorization.

### Fluent API in Async

You can also use the fluent API for asynchronous requests:

#### **Asynchronous Example Using Fluent API**

```php
use Fetch\Interfaces\Response as ResponseInterface;

$data = null;

async(fn () => fetch()
    ->baseUri('https://example.com')
    ->withHeaders('Content-Type', 'application/json')
    ->withBody(['key' => 'value'])
    ->withToken('fake-bearer-auth-token')
    ->post('/posts'))
    ->then(fn (ResponseInterface $response) => $data = $response->json())  // Success handler
    ->catch(fn (Throwable $e) => $e->getMessage());                // Error handler

echo $data;
```

This example asynchronously sends a POST request using the fluent API, handles the response, or catches any errors.

## Task Lifecycle Management

FetchPHP, powered by Matrix, allows you to manage long-running or asynchronous tasks with more control over their lifecycle.

### **Example: Task Lifecycle Control**

```php
use Matrix\Task;
use Matrix\Enum\TaskStatus;

// Define a long-running task
$task = new Task(function () {
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

$result = $task->getResult();
```

## Error Handling

FetchPHP provides flexible error handling for both synchronous and asynchronous requests.

### **Synchronous Error Handling Example**

```php
$response = fetch('https://nonexistent-url.com');

if ($response->ok()) {
    echo $response->json();
} else {
    echo "Error: " . $response->statusText();
}
```

### **Asynchronous Error Handling Example**

```php
use Fetch\Interfaces\Response as ResponseInterface;

$data = null;

async(fn () => fetch('https://nonexistent-url.com'))
    ->then(fn (ResponseInterface $response) => $data = $response->json())
    ->catch(fn (\Throwable $e) => echo "Error: " . $e->getMessage());

echo $data;
```

## Proxy and Authentication Support

FetchPHP includes built-in support for proxies and authentication:

### **Proxy Example**

```php
$response = fetch('https://example.com')
    ->withProxy('tcp://localhost:8080')
    ->get();

echo $response->statusText();
```

### **Authentication Example**

```php
$response = fetch('https://example.com/secure-endpoint')
    ->withAuth('username', 'password')
    ->get();

echo $response->statusText();
```
