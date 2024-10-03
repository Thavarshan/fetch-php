# Asynchronous HTTP Requests

FetchPHP provides a powerful and flexible API for making asynchronous HTTP requests using PHP Fibers. This API allows you to manage asynchronous tasks in a way similar to JavaScript’s `async/await`, with additional task control and error handling capabilities.

## Basic Async Fetch API

FetchPHP allows you to make asynchronous HTTP requests using the `async()` function. Below is an example of an asynchronous POST request:

### **Asynchronous POST Request Example**

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

In this example:

- **async()**: Executes the request asynchronously.
- **then()**: Handles the success scenario when the request completes.
- **catch()**: Catches any exceptions or errors that occur during the request.

The request sends a POST request to `https://example.com` and processes the JSON response if successful, or catches and displays the error if something goes wrong.

## Fluent API for Asynchronous Requests

FetchPHP’s Fluent API can also be used to build asynchronous requests. This API allows for greater flexibility by chaining methods to customize the request.

### **Asynchronous POST Request Using Fluent API**

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

In this example:

- **baseUri()**: Sets the base URL.
- **withHeaders()**: Adds the `Content-Type` header.
- **withBody()**: Sets the JSON body for the request.
- **withToken()**: Adds a Bearer token for authentication.
- **post()**: Sends a POST request to `/posts`.

This request will run asynchronously, and on success, the JSON response will be processed. If an error occurs, it will be caught by the `catch()` block.

## Task Management in Asynchronous Requests

FetchPHP provides task management features such as pausing, resuming, and canceling tasks. These controls can be useful in long-running processes or when dealing with high concurrency.

### **Example: Managing Async Task Lifecycle**

```php
use Matrix\Task;
use Matrix\Enum\TaskStatus;

$task = new Task(function () {
    return fetch('https://example.com/api/resource');
});

// Start the task
$task->start();

// Pause the task if needed
$task->pause();

// Resume the task
$task->resume();

// Cancel the task if required
$task->cancel();

// Get only if completed properly
$result = $task->getResult();
```

In this example, a long-running asynchronous task is started, paused, resumed, and potentially canceled based on the task’s lifecycle needs.

## Advanced Async Examples

### **Asynchronous GET Request with Fluent API**

```php
use Fetch\Interfaces\Response as ResponseInterface;

$data = null;

async(fn () => fetch()
    ->baseUri('https://example.com')
    ->withQueryParameters(['page' => 1])
    ->withToken('fake-bearer-auth-token')
    ->get('/resources'))
    ->then(fn (ResponseInterface $response) => $data = $response->json())  // Success handler
    ->catch(fn (Throwable $e) => $e->getMessage());                // Error handler

echo $data;
```

This example demonstrates an asynchronous GET request where query parameters and a Bearer token are used to retrieve data from an API.

### **Retry Logic with Asynchronous Requests**

You can implement retry logic in asynchronous requests by utilizing the `retry()` method:

```php
use Fetch\Interfaces\Response as ResponseInterface;

$data = null;

async(fn () => fetch()
    ->baseUri('https://example.com')
    ->withHeaders('Content-Type', 'application/json')
    ->withBody(['key' => 'value'])
    ->retry(3, 1000)  // Retry 3 times with a 1-second delay between retries
    ->post('/posts'))
    ->then(fn (ResponseInterface $response) => $data = $response->json())  // Success handler
    ->catch(fn (Throwable $e) => $e->getMessage());                // Error handler

echo $data;
```

In this example:

- **retry(3, 1000)**: The request will be retried up to 3 times with a delay of 1000 milliseconds between each retry.

## Error Handling in Asynchronous Requests

FetchPHP makes it easy to handle errors in asynchronous requests using the `catch()` method. Errors can include failed network connections, invalid responses, or server errors.

### **Asynchronous Error Handling Example**

```php
use Fetch\Interfaces\Response as ResponseInterface;

$data = null;

async(fn () => fetch('https://nonexistent-url.com'))
    ->then(fn (ResponseInterface $response) => $data = $response->json())  // Success handler
    ->catch(fn (Throwable $e) => "Error: " . $e->getMessage());    // Error handler

echo $data;
```

In this example, any errors that occur during the request are caught and handled gracefully.

## Handling JSON and Other Response Types

Just like synchronous requests, asynchronous requests allow you to handle different types of response content:

### **Example: Handling JSON Response**

```php
$data = null;

async(fn () => fetch('https://example.com/api/resource'))
    ->then(fn ($response) => $data = $response->json())
    ->catch(fn (Throwable $e) => $e->getMessage());

echo $data;
```

- **json()**: Parses the response as JSON.

---

FetchPHP’s asynchronous API, combined with PHP Fibers, provides a robust and flexible way to manage HTTP requests. The Fluent API makes it easier to construct complex requests while the asynchronous control mechanisms allow for better task management.

For more examples of task management, refer to the [Matrix Documentation](https://github.com/Thavarshan/matrix).
