---
title: Error Handling
description: Learn how to handle errors and exceptions with the Fetch HTTP package
---

# Error Handling

This guide explains how to handle errors when making HTTP requests with the Fetch HTTP package.

## Response Status Checking

The most common way to handle HTTP errors is by checking the response status:

```php
// Make a request
$response = get('https://api.example.com/users/123');

if ($response->successful()) {
    // Status code is 2xx - process the response
    $user = $response->json();
    echo "Found user: {$user['name']}";
} else {
    // Error occurred - handle based on status code
    echo "Error: " . $response->status() . " " . $response->statusText();
}
```

## Status Category Methods

The `Response` class provides methods to check different status code categories:

```php
$response = get('https://api.example.com/users/123');

if ($response->successful()) {
    // Status code is 2xx
    $user = $response->json();
    echo "Found user: {$user['name']}";
} elseif ($response->isClientError()) {
    // Status code is 4xx
    echo "Client error: " . $response->status();
} elseif ($response->isServerError()) {
    // Status code is 5xx
    echo "Server error: " . $response->status();
} elseif ($response->isRedirection()) {
    // Status code is 3xx
    echo "Redirect to: " . $response->header('Location');
}
```

## Specific Status Code Methods

For handling specific status codes, the `Response` class provides dedicated methods:

```php
if ($response->isOk()) {
    // 200 OK
    $data = $response->json();
} elseif ($response->isNotFound()) {
    // 404 Not Found
    echo "Resource not found";
} elseif ($response->isUnauthorized()) {
    // 401 Unauthorized
    echo "Authentication required";
} elseif ($response->isForbidden()) {
    // 403 Forbidden
    echo "Access denied";
} elseif ($response->isUnprocessableEntity()) {
    // 422 Unprocessable Entity
    $errors = $response->json()['errors'] ?? [];
    foreach ($errors as $field => $messages) {
        echo "{$field}: " . implode(', ', $messages) . "\n";
    }
}
```

## Using Status Enums

Fetch PHP provides type-safe enums for status codes, which you can use for more explicit comparisons:

```php
use Fetch\Enum\Status;

// Get the status as an enum
$statusEnum = $response->statusEnum();

// Compare with enum values
if ($statusEnum === Status::OK) {
    // Status is exactly 200 OK
} elseif ($statusEnum === Status::NOT_FOUND) {
    // Status is exactly 404 Not Found
} elseif ($statusEnum === Status::TOO_MANY_REQUESTS) {
    // Status is exactly 429 Too Many Requests
}

// Check using isStatus() with enum
if ($response->isStatus(Status::CREATED)) {
    // Status is 201 Created
}
```

## Exception Handling

When network errors or other exceptions occur, they are thrown as PHP exceptions:

```php
use Fetch\Exceptions\NetworkException;
use Fetch\Exceptions\RequestException;
use Fetch\Exceptions\ClientException;
use Fetch\Exceptions\TimeoutException;

try {
    $response = get('https://api.example.com/users');

    if ($response->failed()) {
        throw new \Exception("Request failed with status: " . $response->status());
    }

    $users = $response->json();
} catch (NetworkException $e) {
    // Network-related issues (DNS failure, connection refused, etc.)
    echo "Network error: " . $e->getMessage();
} catch (TimeoutException $e) {
    // Request timed out
    echo "Request timed out: " . $e->getMessage();
} catch (RequestException $e) {
    // HTTP request errors
    echo "Request error: " . $e->getMessage();

    // If the exception has a response, you can still access it
    if ($e->hasResponse()) {
        $errorResponse = $e->getResponse();
        $statusCode = $errorResponse->status();
        $errorDetails = $errorResponse->json()['error'] ?? 'Unknown error';
        echo "Status: {$statusCode}, Error: {$errorDetails}";
    }
} catch (ClientException $e) {
    // General client errors
    echo "Client error: " . $e->getMessage();
} catch (\Exception $e) {
    // Catch any other exceptions
    echo "Error: " . $e->getMessage();
}
```

## Handling JSON Decoding Errors

When decoding JSON responses, you may encounter parsing errors:

```php
try {
    $data = $response->json();
} catch (\RuntimeException $e) {
    // JSON parsing failed
    echo "Failed to decode JSON: " . $e->getMessage();

    // You can access the raw response body
    $rawBody = $response->body();
    echo "Raw response: " . $rawBody;
}
```

To suppress JSON decoding errors:

```php
// Pass false to disable throwing exceptions
$data = $response->json(true, false);

// Or use the array method with error suppression
$data = $response->array(false);

// Or use the get method with a default
$value = $response->get('key', 'default value');
```

## Handling Validation Errors

Many APIs return validation errors with status 422 (Unprocessable Entity):

```php
$response = post('https://api.example.com/users', [
    'email' => 'invalid-email',
    'password' => '123'  // Too short
]);

if ($response->isUnprocessableEntity()) {
    $errors = $response->json()['errors'] ?? [];

    foreach ($errors as $field => $messages) {
        echo "- {$field}: " . implode(', ', $messages) . "\n";
    }
}
```

## Common API Error Formats

Different APIs structure their error responses differently. Here's how to handle some common formats:

### Standard JSON API Errors

```php
if ($response->failed()) {
    $errorData = $response->json(true, false); // Don't throw on parse errors

    // Format: { "error": { "code": "invalid_token", "message": "The token is invalid" } }
    if (isset($errorData['error']['message'])) {
        echo "Error: " . $errorData['error']['message'];
        echo "Code: " . $errorData['error']['code'] ?? 'unknown';
    }
    // Format: { "errors": [{ "title": "Invalid token", "detail": "The token is expired" }] }
    elseif (isset($errorData['errors']) && is_array($errorData['errors'])) {
        foreach ($errorData['errors'] as $error) {
            echo $error['title'] . ": " . ($error['detail'] ?? '') . "\n";
        }
    }
    // Format: { "message": "Validation failed", "errors": { "email": ["Invalid email"] } }
    elseif (isset($errorData['message']) && isset($errorData['errors'])) {
        echo $errorData['message'] . "\n";
        foreach ($errorData['errors'] as $field => $messages) {
            echo "- {$field}: " . implode(', ', $messages) . "\n";
        }
    }
    // Simple format: { "message": "An error occurred" }
    elseif (isset($errorData['message'])) {
        echo "Error: " . $errorData['message'];
    }
    // Fallback
    else {
        echo "Unknown error occurred. Status code: " . $response->status();
    }
}
```

## Retry on Error

You can automatically retry requests that fail due to transient errors:

```php
use Fetch\Http\ClientHandler;

$response = ClientHandler::create()
    // Retry up to 3 times with exponential backoff
    ->retry(3, 100)
    // Customize which status codes to retry
    ->retryStatusCodes([429, 503, 504])
    // Customize which exceptions to retry
    ->retryExceptions([\GuzzleHttp\Exception\ConnectException::class])
    ->get('https://api.example.com/unstable-endpoint');
```

## Handling Rate Limits

Many APIs implement rate limiting. Here's how to handle 429 Too Many Requests responses:

```php
$response = get('https://api.example.com/users');

if ($response->isTooManyRequests()) {
    // Check for Retry-After header (might be in seconds or a timestamp)
    $retryAfter = $response->header('Retry-After');

    if ($retryAfter !== null) {
        if (is_numeric($retryAfter)) {
            $waitSeconds = (int) $retryAfter;
        } else {
            // Parse HTTP date
            $waitSeconds = strtotime($retryAfter) - time();
        }

        echo "Rate limited. Please try again after {$waitSeconds} seconds.";

        // You could wait and retry automatically
        if ($waitSeconds > 0 && $waitSeconds < 60) {  // Only wait if reasonable
            sleep($waitSeconds);
            return get('https://api.example.com/users');
        }
    } else {
        echo "Rate limited. Please try again later.";
    }
}
```

## Asynchronous Error Handling

When working with asynchronous requests, you can use try/catch blocks with await or the catch method with promises:

```php
use function async;
use function await;

// Using try/catch with await
await(async(function() {
    try {
        $response = await(async(function() {
            return fetch('https://api.example.com/users/999');
        }));

        if ($response->failed()) {
            throw new \Exception("Request failed with status: " . $response->status());
        }

        return $response->json();
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}));

// Using catch() with promises
$handler = fetch_client()->getHandler();
$handler->async()
    ->get('https://api.example.com/users/999')
    ->then(function($response) {
        if ($response->failed()) {
            throw new \Exception("API returned error: " . $response->status());
        }
        return $response->json();
    })
    ->catch(function($error) {
        echo "Error: " . $error->getMessage();
        return [];
    });
```

## Custom Error Handling Class

For more advanced applications, you might want to create a dedicated error handler:

```php
class ApiErrorHandler
{
    /**
     * Handle a response that might contain errors.
     */
    public function handleResponse($response)
    {
        if ($response->successful()) {
            return $response;
        }

        switch ($response->status()) {
            case 401:
                throw new AuthenticationException("Authentication required");

            case 403:
                throw new AuthorizationException("You don't have permission to access this resource");

            case 404:
                throw new ResourceNotFoundException("The requested resource was not found");

            case 422:
                $errors = $response->json()['errors'] ?? [];
                throw new ValidationException("Validation failed", $errors);

            case 429:
                $retryAfter = $response->header('Retry-After');
                throw new RateLimitException("Too many requests", $retryAfter);

            case 500:
            case 502:
            case 503:
            case 504:
                throw new ServerException("Server error: " . $response->status());

            default:
                throw new ApiException("API error: " . $response->status());
        }
    }
}

// Usage
$errorHandler = new ApiErrorHandler();

try {
    $response = get('https://api.example.com/users');
    $errorHandler->handleResponse($response);

    // Process successful response
    $users = $response->json();
} catch (AuthenticationException $e) {
    // Handle authentication error
} catch (ValidationException $e) {
    // Handle validation errors
    $errors = $e->getErrors();
} catch (ApiException $e) {
    // Handle other API errors
}
```

## Debugging Errors

For debugging, you can get detailed information about a request:

```php
$handler = fetch_client()->getHandler();
$debugInfo = $handler->debug();

try {
    // Attempt the request
    $response = $handler->get('https://api.example.com/users');

    if ($response->failed()) {
        echo "Request failed with status: " . $response->status() . "\n";
        echo "Debug information:\n";
        print_r($debugInfo);
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Debug information:\n";
    print_r($debugInfo);
}
```

## Error Logging

You can use a PSR-3 compatible logger to log errors:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a logger
$logger = new Logger('api');
$logger->pushHandler(new StreamHandler('logs/api.log', Logger::ERROR));

// Set the logger on the client
$client = fetch_client();
$client->setLogger($logger);

// Now errors will be logged
try {
    $response = $client->get('https://api.example.com/users');

    if ($response->failed()) {
        // This will be logged by the client
        throw new \Exception("API request failed: " . $response->status());
    }
} catch (\Exception $e) {
    // Additional custom logging if needed
    $logger->error("Custom error handler: " . $e->getMessage());
}
```

## Error Handling with Retries and Logging

Combining retries, logging, and error handling for robust API interactions:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a logger
$logger = new Logger('api');
$logger->pushHandler(new StreamHandler('logs/api.log', Logger::INFO));

// Configure client with retry logic and logging
$client = fetch_client()
    ->getHandler()
    ->setLogger($logger)
    ->retry(3, 500) // 3 retries with 500ms initial delay
    ->retryStatusCodes([429, 500, 502, 503, 504]);

try {
    $response = $client->get('https://api.example.com/flaky-endpoint');

    if ($response->failed()) {
        if ($response->isUnauthorized()) {
            // Handle authentication issues
            throw new \Exception("Authentication required");
        } elseif ($response->isForbidden()) {
            // Handle permission issues
            throw new \Exception("Permission denied");
        } else {
            // Handle other errors
            throw new \Exception("API error: " . $response->status());
        }
    }

    // Process successful response
    $data = $response->json();

} catch (\Exception $e) {
    // Handle the exception after retries are exhausted
    $logger->error("Failed after retries: " . $e->getMessage());

    // Provide user-friendly message
    echo "We're having trouble connecting to the service. Please try again later.";
}
```

## Next Steps

- Learn about [Retry Handling](/guide/retry-handling) for automatic recovery from errors
- Explore [Logging](/guide/logging) for more advanced error logging
- See [Authentication](/guide/authentication) for handling authentication errors
- Check out [Asynchronous Requests](/guide/async-requests) for handling errors in async operations
