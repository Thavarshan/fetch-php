# Error Handling

Handling errors is an essential part of any HTTP request workflow, and FetchPHP provides mechanisms to handle errors for both synchronous and asynchronous requests. Whether dealing with network failures, server errors, or invalid responses, FetchPHP gives you the flexibility to handle errors gracefully.

## Synchronous Error Handling

In synchronous requests, FetchPHP will throw an exception when an HTTP error occurs (for example, 4xx or 5xx responses). These exceptions can be caught using PHP’s native `try/catch` structure.

### **Synchronous Error Handling Example**

```php
<?php

try {
    $response = fetch('https://nonexistent-url.com');

    if ($response->ok()) {
        $data = $response->json();
        print_r($data);
    } else {
        echo "Error: " . $response->statusText();
    }
} catch (\Throwable $e) {
    echo "Caught Exception: " . $e->getMessage();
}
```

In this example:

- **try/catch**: Catches exceptions and displays the error message.
- **ok()**: Checks whether the response is successful (2xx status codes).
- **statusText()**: Retrieves the response's status text (e.g., "Not Found").

## Asynchronous Error Handling

In asynchronous requests, FetchPHP uses the `.catch()` method to handle errors that occur during the request. This works similarly to JavaScript’s `Promise`-based error handling.

### **Asynchronous Error Handling Example**

```php
use Fetch\Interfaces\Response as ResponseInterface;

$data = null;

async(fn () => fetch('https://nonexistent-url.com'))
    ->then(fn (ResponseInterface $response) => $data = $response->json())  // Success handler
    ->catch(fn (Throwable $e) => $error = "Error: " . $e->getMessage());    // Error handler

echo $error;
```

In this example:

- **catch()**: Catches any errors or exceptions during the asynchronous request.
- The error message is printed when the request fails.

## Handling HTTP Errors

FetchPHP automatically throws exceptions for HTTP errors (4xx and 5xx responses). You can disable this behavior by setting the `http_errors` option to `false` in the request.

### **Disabling HTTP Errors Example**

```php
$response = fetch('https://example.com/nonexistent', [
    'http_errors' => false  // Disable automatic HTTP exceptions
]);

if ($response->ok()) {
    echo $response->json();
} else {
    echo "HTTP Error: " . $response->getStatusCode() . " - " . $response->statusText();
}
```

In this example:

- **http_errors**: Disables automatic exception throwing for HTTP error responses.
- **status()**: Retrieves the response status code (e.g., 404).
- **statusText()**: Retrieves the status text (e.g., "Not Found").

## Custom Error Handling Logic

You can implement custom error handling logic with FetchPHP by catching exceptions and performing actions like retries, logging, or fallback mechanisms.

### **Custom Error Handling Example**

```php
<?php

try {
    $response = fetch('https://example.com/api/resource');

    if ($response->ok()) {
        echo $response->json();
    } else {
        // Custom error logic for HTTP errors
        throw new \Exception('Server error: ' . $response->statusText());
    }
} catch (\Throwable $e) {
    // Custom handling: log error, retry, or show user-friendly message
    logError($e->getMessage());
    echo "There was an error processing your request. Please try again.";
}
```

In this example:

- If the request fails, a custom error message is thrown, and additional logic such as logging can be implemented.

## Retry Logic for Failed Requests

FetchPHP’s fluent API provides a `retry()` method that can be used to retry failed requests. This is especially useful in cases of network failures or intermittent errors.

### **Retry Example**

```php
use Fetch\Interfaces\Response as ResponseInterface;

$data = null;
$error = null;

async(fn () => fetch()
    ->baseUri('https://example.com')
    ->withHeaders('Content-Type', 'application/json')
    ->withBody(['key' => 'value'])
    ->retry(3, 1000)  // Retry 3 times with a 1-second delay between retries
    ->post('/posts'))
    ->then(fn (ResponseInterface $response) => $data = $response->json())  // Success handler
    ->catch(fn (Throwable $e) => $error = "Error: " . $e->getMessage());    // Error handler

echo $error;
```

In this example:

- **retry(3, 1000)**: Retries the request up to 3 times with a 1-second delay between each retry if it fails.
- You can adjust the retry count and delay as needed.

## Handling JSON and Other Response Types

You can handle different response types and implement error handling based on the response content. For instance, if the server returns invalid JSON, FetchPHP will throw an exception when trying to parse the response as JSON.

### **Handling Invalid JSON Response Example**

```php
<?php

try {
    $response = fetch('https://example.com/invalid-json');

    $data = $response->json();  // This will throw an error if the response is not valid JSON
} catch (\Throwable $e) {
    echo "Invalid JSON response: " . $e->getMessage();
}
```

In this example:

- The `json()` method will throw an exception if the response body is not valid JSON.

## Custom Exception Handling

FetchPHP allows you to handle exceptions in a more structured way, including network errors, request timeouts, or invalid responses. For example, you can catch specific exceptions thrown by Guzzle or create custom exceptions to handle various error scenarios.

### **Custom Exception Example**

```php
use GuzzleHttp\Exception\RequestException;

try {
    $response = fetch('https://example.com/api/resource');
} catch (RequestException $e) {
    echo "Network error: " . $e->getMessage();
} catch (\Throwable $e) {
    echo "Other error: " . $e->getMessage();
}
```

In this example:

- **RequestException**: Catches network-related issues like timeouts or connection failures.
- **\Throwable**: Catches all other exceptions, including invalid responses or internal errors.

## Handling Timeouts

You can configure request timeouts and catch exceptions when the request exceeds the timeout limit.

### **Timeout Handling Example**

```php
<?php

try {
    $response = fetch('https://example.com/api/resource', [
        'timeout' => 2,  // Timeout in 2 seconds
    ]);
} catch (\Throwable $e) {
    echo "Request timed out: " . $e->getMessage();
}
```

In this example:

- If the request takes longer than 2 seconds, it will throw a timeout exception, which can be caught and handled.

---

FetchPHP provides flexible and robust error-handling mechanisms for both synchronous and asynchronous requests. By using the `try/catch` structure, `.catch()` for asynchronous requests, and features like `retry()`, you can effectively manage errors in your applications. Make sure to implement proper error handling in your workflow to improve reliability and user experience.
