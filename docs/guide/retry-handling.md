---
title: Retry Handling
description: Learn how to configure automatic retry behavior for failed HTTP requests
---

# Retry Handling

The Fetch HTTP package includes built-in retry functionality to handle transient failures gracefully. This guide explains how to configure and use the retry mechanism.

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
use function Fetch\Http\fetch;

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
use function Fetch\Http\fetch_client;

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
$client = ClientHandler::create()
    ->setLogger($logger)
    ->retry(3, 100);

// Send a request that might require retries
$response = $client->get('https://api.example.com/unstable-endpoint');

// Retry attempts will be logged to logs/http.log
```

A typical retry log entry looks like:

```
[2023-09-15 14:30:12] http.INFO: Retrying request {"attempt":1,"max_attempts":3,"uri":"https://api.example.com/unstable-endpoint","method":"GET","error":"Connection timed out","code":28}
```

## Real-World Examples

### Handling Rate Limits

APIs often implement rate limiting. You can configure your client to automatically retry when hitting rate limits:

```php
$client = ClientHandler::create()
    ->retry(3, 1000)  // Longer initial delay for rate limits
    ->retryStatusCodes([429])  // Only retry on Too Many Requests
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
$client = ClientHandler::create()
    ->retry(10, 5000)  // Many retries with long delay (5 seconds)
    ->retryStatusCodes([503])  // Service Unavailable
    ->get('https://api.example.com/endpoint');
```

## Implementing Advanced Retry Logic

For more complex scenarios, you can implement custom retry logic:

```php
use Fetch\Http\ClientHandler;
use Fetch\Http\Response;
use GuzzleHttp\Exception\RequestException;

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
            if ($response->status() === 429) {
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

## Best Practices

1. **Start with Conservative Settings**: Begin with a small number of retries (2-3) and moderate delays (100-200ms) and adjust based on your needs.

2. **Be Mindful of Server Load**: Excessive retries can amplify problems during outages. Be respectful of the services you're calling.

3. **Use Appropriate Timeout Values**: Set reasonable timeouts in conjunction with retries to avoid long-running requests.

4. **Limit Retryable Status Codes**: Only retry on status codes that indicate transient issues. Don't retry on client errors like 400, 401, or 404.

5. **Monitor Retry Activity**: Log retry attempts to identify recurring issues with specific endpoints.

6. **Consider Retry-After Headers**: For rate limiting (429), respect the Retry-After header if provided by the server.

7. **Add Jitter**: The built-in retry mechanism includes jitter, which helps prevent "thundering herd" problems.

## Next Steps

- Learn about [Error Handling](/guide/error-handling) for comprehensive error management
- Explore [Logging](/guide/logging) for monitoring request and retry activity
- See [Authentication](/guide/authentication) for handling authentication errors and retries
