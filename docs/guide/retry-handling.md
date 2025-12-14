---
title: Retry Handling
description: Learn how to configure automatic retry behavior for failed HTTP requests
---

# Retry Handling

The Fetch PHP package includes built-in retry functionality to handle transient failures gracefully. This guide explains how to configure and use the retry mechanism.

## Basic Retry Configuration

You can configure retries using the `retry()` method on the ClientHandler:

```php
use Fetch\Http\ClientHandler;

$response = ClientHandler::create()
    ->retry(3, 100)  // Retry up to 3 times with initial delay of 100ms
    ->get('https://api.example.com/unstable-endpoint');
```

Using helper functions:

```php
$response = fetch('https://api.example.com/unstable-endpoint', [
    'retries' => 3,        // Retry up to 3 times
    'retry_delay' => 100   // Initial delay of 100ms
]);
```

## How Retry Works

When a request fails due to a retryable error (network issues or certain HTTP status codes), the package will:

1. Wait for a specified delay
2. Apply exponential backoff with jitter (randomness)
3. Retry the request
4. Repeat until success or the maximum retry count is reached

The delay increases exponentially with each retry attempt:

- First retry: Initial delay (e.g., 100ms)
- Second retry: ~2x initial delay + jitter
- Third retry: ~4x initial delay + jitter
- And so on...

The jitter (random variation) helps prevent multiple clients from retrying simultaneously, which can worsen outages.

## Using Type-Safe Enums with Retries

You can use the `Status` enum for more type-safe retry configuration:

```php
use Fetch\Http\ClientHandler;
use Fetch\Enum\Status;

$response = ClientHandler::create()
    ->retry(3, 100)
    ->retryStatusCodes([
        Status::TOO_MANY_REQUESTS->value,
        Status::SERVICE_UNAVAILABLE->value,
        Status::GATEWAY_TIMEOUT->value
    ])
    ->get('https://api.example.com/unstable-endpoint');
```

## Customizing Retryable Status Codes

By default, the client retries on these HTTP status codes:

- 408 (Request Timeout)
- 429 (Too Many Requests)
- 500, 502, 503, 504 (Server Errors)
- And several other common error codes

You can customize which status codes should trigger retries:

```php
$client = ClientHandler::create()
    ->retry(3, 100)
    ->retryStatusCodes([429, 503, 504]);  // Only retry on these status codes

$response = $client->get('https://api.example.com/unstable-endpoint');
```

## Customizing Retryable Exceptions

By default, the client retries on network-related exceptions like `ConnectException`. You can customize which exception types should trigger retries:

```php
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

$client = ClientHandler::create()
    ->retry(3, 100)
    ->retryExceptions([
        ConnectException::class,
        RequestException::class
    ]);

$response = $client->get('https://api.example.com/unstable-endpoint');
```

## Checking Retry Configuration

You can check the current retry configuration:

```php
$client = ClientHandler::create()->retry(3, 200);

$maxRetries = $client->getMaxRetries();        // 3
$retryDelay = $client->getRetryDelay();        // 200
$statusCodes = $client->getRetryableStatusCodes();  // Array of status codes
$exceptions = $client->getRetryableExceptions();    // Array of exception classes
```

## Global Retry Configuration

You can set up global retry settings that apply to all requests:

```php
// Configure global retry settings
fetch_client([
    'retries' => 3,
    'retry_delay' => 100
]);

// All requests will now use these retry settings
$response = fetch('https://api.example.com/users');
```

## Logging Retries

If you've set up a logger, retry attempts will be automatically logged:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Fetch\Http\ClientHandler;

// Create a logger
$logger = new Logger('http');
$logger->pushHandler(new StreamHandler('logs/http.log', Logger::INFO));

// Create a client with logging and retries
$client = ClientHandler::create();
$client->setLogger($logger);
$client->retry(3, 100);

// Send a request that might require retries
$response = $client->get('https://api.example.com/unstable-endpoint');

// Retry attempts will be logged to logs/http.log
```

A typical retry log entry looks like:

```
[2023-09-15 14:30:12] http.INFO: Retrying request {"attempt":1,"max_attempts":3,"uri":"https://api.example.com/unstable-endpoint","method":"GET","error":"Connection timed out","code":28}
```

## Asynchronous Retries

Retries also work with asynchronous requests:

```php
use function async;
use function await;
use function retry;

// Retry asynchronous operations
$result = await(retry(
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

// Process the result
$data = $result->json();
```

## Real-World Examples

### Handling Rate Limits

APIs often implement rate limiting. You can configure your client to automatically retry when hitting rate limits:

```php
use Fetch\Enum\Status;

$client = ClientHandler::create()
    ->retry(3, 1000)  // Longer initial delay for rate limits
    ->retryStatusCodes([Status::TOO_MANY_REQUESTS->value])  // Only retry on Too Many Requests
    ->get('https://api.example.com/rate-limited-endpoint');
```

### Handling Network Instability

For unreliable network connections:

```php
$client = ClientHandler::create()
    ->retry(5, 200)  // More retries with moderate delay
    // Using default retryable status codes and exceptions
    ->get('https://api.example.com/endpoint');
```

### Handling Server Maintenance

For APIs that might be temporarily down for maintenance:

```php
use Fetch\Enum\Status;

$client = ClientHandler::create()
    ->retry(10, 5000)  // Many retries with long delay (5 seconds)
    ->retryStatusCodes([Status::SERVICE_UNAVAILABLE->value])  // Service Unavailable
    ->get('https://api.example.com/endpoint');
```

## Combining Retry with Timeout

You can combine retry logic with timeout settings:

```php
$client = ClientHandler::create()
    ->timeout(5)    // 5 second timeout for each attempt
    ->retry(3, 100) // 3 retries with 100ms initial delay
    ->get('https://api.example.com/endpoint');
```

## Implementing Advanced Retry Logic

For more complex scenarios, you can implement custom retry logic:

```php
use Fetch\Http\ClientHandler;
use Fetch\Http\Response;
use GuzzleHttp\Exception\RequestException;
use Fetch\Enum\Status;

function makeRequestWithCustomRetry(string $url, int $maxAttempts = 3): Response {
    $attempt = 0;

    while (true) {
        try {
            $client = ClientHandler::create();
            $response = $client->get($url);

            // Check if we got a success response
            if ($response->successful()) {
                return $response;
            }

            // Handle specific status codes
            if ($response->statusEnum() === Status::TOO_MANY_REQUESTS) {
                // Get retry-after header if available
                $retryAfter = $response->header('Retry-After');
                $delay = $retryAfter ? (int) $retryAfter * 1000 : 1000;
            } else {
                // Otherwise use exponential backoff
                $delay = 100 * (2 ** $attempt);
            }

            // Add some jitter (±20%)
            $jitter = mt_rand(-20, 20) / 100;
            $delay = (int) ($delay * (1 + $jitter));

            $attempt++;

            // Check if we've exceeded max attempts
            if ($attempt >= $maxAttempts) {
                return $response;  // Return the last response
            }

            // Wait before retrying
            usleep($delay * 1000);  // Convert ms to μs

        } catch (RequestException $e) {
            $attempt++;

            // Check if we've exceeded max attempts
            if ($attempt >= $maxAttempts) {
                throw $e;  // Rethrow the last exception
            }

            // Wait before retrying
            $delay = 100 * (2 ** $attempt);
            usleep($delay * 1000);
        }
    }
}

// Use the custom retry function
$response = makeRequestWithCustomRetry('https://api.example.com/users');
```

## Monitoring Retry Activity

The built-in retry strategy logs every attempt, so attaching a PSR-3 logger is the easiest way to see what happened:

```php
use Fetch\Http\ClientHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$logger = new Logger('retry');
$logger->pushHandler(new StreamHandler('logs/retry.log', Logger::INFO));

$client = ClientHandler::create()
    ->setLogger($logger)
    ->withLogLevel('info') // retry logs default to info
    ->retry(3, 100);

$response = $client->get('https://api.example.com/unstable');
```

Each retry attempt results in a log entry similar to:

```
[2024-03-22T11:14:33+00:00] retry.INFO: Retrying request {"attempt":1,"max_attempts":3,"uri":"https://api.example.com/unstable","method":"GET","error":"Connection timed out","code":28}
```

If you need to capture the number of attempts programmatically, wrap your call with a counter:

```php
$attempts = 0;

$response = retry(function () use (&$attempts) {
    $attempts++;

    return fetch('https://api.example.com/unstable');
}, attempts: 3);

echo "Tried {$attempts} times\n";
```

## Best Practices

1. **Use Type-Safe Enums**: Leverage the Status enum for clearer and safer code when configuring retryable status codes.

2. **Start with Conservative Settings**: Begin with a small number of retries (2-3) and moderate delays (100-200ms) and adjust based on your needs.

3. **Be Mindful of Server Load**: Excessive retries can amplify problems during outages. Be respectful of the services you're calling.

4. **Use Appropriate Timeout Values**: Set reasonable timeouts in conjunction with retries to avoid long-running requests.

5. **Limit Retryable Status Codes**: Only retry on status codes that indicate transient issues. Don't retry on client errors like 400, 401, or 404.

6. **Monitor Retry Activity**: Log retry attempts to identify recurring issues with specific endpoints.

7. **Consider Retry-After Headers**: For rate limiting (429), respect the Retry-After header if provided by the server.

8. **Add Jitter**: The built-in retry mechanism includes jitter, which helps prevent "thundering herd" problems.

9. **Combine with Logging**: Always add logging when using retries to track and debug retry patterns.

10. **Use Async Retries for Parallel Operations**: When working with async code, use the retry function for better integration with the async/await pattern.

## Next Steps

- Learn about [Error Handling](/guide/error-handling) for comprehensive error management
- Explore [Logging](/guide/logging) for monitoring request and retry activity
- See [Authentication](/guide/authentication) for handling authentication errors and retries
- Check out [Asynchronous Requests](/guide/async-requests) for integrating retries with async operations
