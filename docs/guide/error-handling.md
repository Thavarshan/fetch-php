# Error Handling

Properly handling errors is crucial when working with HTTP requests. Fetch PHP provides robust error handling mechanisms for both synchronous and asynchronous requests, allowing you to gracefully manage network failures, server errors, and invalid responses.

## Response Status Checking

Before diving into exception handling, it's important to understand how Fetch PHP allows you to check response status:

```php
$response = fetch('https://api.example.com/users/1');

// Check if the response was successful (status code 200-299)
if ($response->ok()) {
    $user = $response->json();
} else {
    // Handle error response
    echo "Error: " . $response->status() . " " . $response->statusText();
}

// More specific status checks
if ($response->successful()) {  // 200-299
    echo "Success!";
} elseif ($response->failed()) {  // 400+
    echo "Request failed";
} elseif ($response->clientError()) {  // 400-499
    echo "Client error";
} elseif ($response->serverError()) {  // 500-599
    echo "Server error";
}
```

## Synchronous Error Handling

For synchronous requests, you can use PHP's standard `try/catch` blocks to handle exceptions:

```php
try {
    $response = fetch('https://api.example.com/nonexistent');

    if ($response->ok()) {
        $data = $response->json();
        print_r($data);
    } else {
        echo "HTTP Error: " . $response->status() . " - " . $response->statusText();
    }
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage();
}
```

### Handling Specific Exceptions

You can catch specific exception types to handle different error scenarios:

```php
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

try {
    $response = fetch('https://api.example.com/users');
    $data = $response->json();
} catch (ConnectException $e) {
    // Handle connection errors (e.g., network down, DNS failure)
    echo "Connection error: " . $e->getMessage();
} catch (RequestException $e) {
    // Handle request errors with response (e.g., 404, 500)
    if ($e->hasResponse()) {
        $response = $e->getResponse();
        echo "API error: " . $response->getStatusCode() . " " . $response->getReasonPhrase();
    } else {
        echo "Request error without response: " . $e->getMessage();
    }
} catch (\Throwable $e) {
    // Handle any other exceptions
    echo "Other error: " . $e->getMessage();
}
```

## Asynchronous Error Handling

For asynchronous requests, Fetch PHP uses Promise-based error handling with the `.catch()` method:

```php
use function async;
use Fetch\Interfaces\Response as ResponseInterface;

async(fn () => fetch('https://api.example.com/nonexistent'))
    ->then(function (ResponseInterface $response) {
        if ($response->ok()) {
            return $response->json();
        }
        // Convert HTTP errors to exceptions to be caught below
        throw new \Exception("HTTP error: " . $response->status() . " " . $response->statusText());
    })
    ->then(function ($data) {
        echo "Data: " . json_encode($data);
    })
    ->catch(function (\Throwable $e) {
        echo "Error: " . $e->getMessage();
    });
```

### Async/Await Error Handling

You can also use try/catch with await for a more synchronous-looking code structure:

```php
use function async;
use function await;

try {
    $response = await(async(fn () => fetch('https://api.example.com/nonexistent')));

    if ($response->ok()) {
        $data = $response->json();
        print_r($data);
    } else {
        throw new \Exception("HTTP error: " . $response->status() . " " . $response->statusText());
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
```

## Configuring HTTP Error Handling

By default, Fetch PHP will throw exceptions for HTTP errors (4xx and 5xx responses). You can disable this behavior using the `http_errors` option:

```php
$response = fetch('https://api.example.com/nonexistent', [
    'http_errors' => false  // Don't throw exceptions for 4xx/5xx responses
]);

if ($response->ok()) {
    $data = $response->json();
} else {
    echo "HTTP Error: " . $response->status() . " - " . $response->statusText();
}
```

## Retry Mechanism

Fetch PHP includes built-in retry functionality for handling transient errors:

```php
// Retry up to 3 times with exponential backoff starting at 100ms
$response = fetch('https://api.example.com/unstable', [
    'retries' => 3,
    'retry_delay' => 100
]);

// Using the fluent API
$response = fetch()
    ->retry(3, 100)  // 3 retries, 100ms initial delay
    ->get('https://api.example.com/unstable');
```

The retry mechanism uses exponential backoff with jitter:
- First retry: ~100ms delay
- Second retry: ~200ms delay
- Third retry: ~400ms delay

Only certain types of errors trigger retries by default:
- Server errors (5xx status codes)
- Specific client errors (408 Request Timeout, 429 Too Many Requests)
- Connection exceptions

## Custom Retry Logic

For more advanced retry scenarios, you can implement custom retry logic:

```php
use function async;
use function await;

$maxRetries = 5;
$backoffDelay = 100; // milliseconds

$attemptRequest = function ($retriesLeft = $maxRetries, $delay = $backoffDelay) use (&$attemptRequest) {
    return async(function () use ($retriesLeft, $delay, $attemptRequest) {
        try {
            $response = fetch('https://api.example.com/unstable');

            // Retry on certain status codes even with successful connection
            if ($response->status() === 429 || $response->status() >= 500) {
                if ($retriesLeft > 0) {
                    echo "Got status " . $response->status() . ", retrying...\n";

                    // Add jitter to prevent thundering herd
                    $jitter = rand(-20, 20) / 100; // ±20% jitter
                    $actualDelay = $delay * (1 + $jitter);

                    // Sleep with microseconds (convert ms to μs)
                    usleep($actualDelay * 1000);

                    // Retry with exponential backoff
                    return await($attemptRequest($retriesLeft - 1, $delay * 2));
                }
            }

            return $response;
        } catch (\Throwable $e) {
            if ($retriesLeft > 0) {
                echo "Request failed: " . $e->getMessage() . ", retrying...\n";

                // Sleep with jitter
                $jitter = rand(-20, 20) / 100;
                $actualDelay = $delay * (1 + $jitter);
                usleep($actualDelay * 1000);

                // Retry with exponential backoff
                return await($attemptRequest($retriesLeft - 1, $delay * 2));
            }

            // No more retries, rethrow the exception
            throw $e;
        }
    });
};

try {
    $response = await($attemptRequest());
    $data = $response->json();
    echo "Success after retries!";
} catch (\Throwable $e) {
    echo "All retry attempts failed: " . $e->getMessage();
}
```

## Handling Timeouts

You can configure request timeouts to prevent your application from hanging:

```php
// Set a 5-second timeout
$response = fetch('https://api.example.com/slow-endpoint', [
    'timeout' => 5
]);

// Using the fluent API
$response = fetch()
    ->timeout(5)
    ->get('https://api.example.com/slow-endpoint');
```

When a request times out, Fetch PHP will throw an exception:

```php
try {
    $response = fetch('https://api.example.com/slow-endpoint', ['timeout' => 2]);
    $data = $response->json();
} catch (\Throwable $e) {
    echo "Request timed out or failed: " . $e->getMessage();
}
```

## Handling JSON Parsing Errors

When working with JSON responses, parsing errors can occur if the server returns invalid JSON:

```php
try {
    $response = fetch('https://api.example.com/users');

    // This will throw if the response is not valid JSON
    $data = $response->json();
} catch (\Throwable $e) {
    echo "Failed to parse JSON: " . $e->getMessage();

    // You can still access the raw response body
    $rawBody = $response->body();
    echo "Raw response: " . $rawBody;
}
```

## Best Practices for Error Handling

1. **Always check response status**: Use `$response->ok()` or more specific status methods before processing the response.

2. **Use appropriate exception handling**: Wrap request code in try/catch blocks to handle network errors and other exceptions.

3. **Implement retries for transient errors**: Use the retry mechanism for operations that may fail temporarily.

4. **Set reasonable timeouts**: Configure timeouts to prevent hanging requests.

5. **Provide meaningful error messages**: Convert technical errors to user-friendly messages when appropriate.

6. **Log errors for debugging**: Log detailed error information while showing simplified messages to users.

7. **Handle different content types properly**: Use the appropriate method for parsing the response based on its content type.

Example of comprehensive error handling:

```php
try {
    $response = fetch('https://api.example.com/users')
        ->retry(3, 100)  // Retry up to 3 times
        ->timeout(5);    // 5-second timeout

    if ($response->ok()) {
        try {
            $data = $response->json();
            // Process data...
        } catch (\Throwable $e) {
            // Handle JSON parsing errors
            log_error("JSON parsing error: " . $e->getMessage());
            return "Unable to process the response data";
        }
    } else {
        // Handle HTTP error responses
        $errorMessage = "API error: " . $response->status();
        log_error($errorMessage . " - " . $response->body());
        return "Unable to fetch users at this time";
    }
} catch (\Throwable $e) {
    // Handle network errors and other exceptions
    log_error("Request exception: " . $e->getMessage());
    return "Connection error. Please try again later";
}
```
