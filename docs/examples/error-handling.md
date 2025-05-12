---
title: Error Handling Examples
description: Examples of robust error handling strategies with the Fetch HTTP package
---

# Error Handling Examples

This page demonstrates robust error handling techniques when working with HTTP requests using the Fetch HTTP package.

## Basic Error Handling

Checking for success and handling errors:

```php
use function Fetch\Http\fetch;

$response = fetch('https://api.example.com/users');

if ($response->successful()) {
    // Status code is 2xx
    $users = $response->json();
    // Process the users...
} elseif ($response->clientError()) {
    // Status code is 4xx
    echo "Client error: " . $response->status();
} elseif ($response->serverError()) {
    // Status code is 5xx
    echo "Server error: " . $response->status();
} else {
    // Other status code
    echo "Unexpected status: " . $response->status();
}
```

## Using Specific Status Methods

The Response class provides dedicated methods for checking specific status codes:

```php
use function Fetch\Http\fetch;

$response = fetch('https://api.example.com/users/123');

if ($response->isOk()) {
    // 200 OK
    $user = $response->json();
    echo "Found user: " . $user['name'];
} elseif ($response->isNotFound()) {
    // 404 Not Found
    echo "User not found";
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

## Try-Catch Exception Handling

Handling network and other exceptions:

```php
use function Fetch\Http\fetch;
use Fetch\Exceptions\NetworkException;
use Fetch\Exceptions\RequestException;
use Fetch\Exceptions\TimeoutException;

try {
    $response = fetch('https://api.example.com/users');

    if ($response->failed()) {
        throw new \Exception("Request failed with status: " . $response->status());
    }

    $users = $response->json();
    // Process users...
} catch (NetworkException $e) {
    // Network-related issues (DNS failure, connection refused, etc.)
    echo "Network error: " . $e->getMessage();
    logError('network', $e->getMessage());
} catch (TimeoutException $e) {
    // Request timed out
    echo "Request timed out: " . $e->getMessage();
    logError('timeout', $e->getMessage());
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

    logError('request', $e->getMessage());
} catch (\Exception $e) {
    // Catch any other exceptions
    echo "Error: " . $e->getMessage();
    logError('general', $e->getMessage());
}
```

## Handling Validation Errors

Working with validation errors from APIs:

```php
use function Fetch\Http\post;

function createUser(array $userData)
{
    $response = post('https://api.example.com/users', $userData);

    if ($response->successful()) {
        return $response->json();
    }

    if ($response->isUnprocessableEntity()) {
        $errors = $response->json()['errors'] ?? [];

        $formattedErrors = [];
        foreach ($errors as $field => $messages) {
            $formattedErrors[$field] = is_array($messages) ? $messages : [$messages];
        }

        throw new ValidationException(
            "Validation failed: " . json_encode($formattedErrors),
            $formattedErrors
        );
    }

    throw new \RuntimeException(
        "Failed to create user: " . $response->status() . " " . $response->body()
    );
}

// Using the function
try {
    $user = createUser([
        'name' => 'John',
        'email' => 'invalid-email'
    ]);

    echo "User created with ID: " . $user['id'];
} catch (ValidationException $e) {
    $errors = $e->getErrors();

    echo "Please correct the following errors:\n";
    foreach ($errors as $field => $messages) {
        echo "- {$field}: " . implode(', ', $messages) . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}

// ValidationException definition
class ValidationException extends \Exception
{
    private array $errors;

    public function __construct(string $message, array $errors, int $code = 0)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

## Handling Rate Limits

Detecting and handling rate limiting:

```php
use function Fetch\Http\fetch;

function fetchWithRateLimitHandling(string $url, int $maxRetries = 3)
{
    $attempts = 0;

    while ($attempts <= $maxRetries) {
        $response = fetch($url);

        if ($response->successful()) {
            return $response;
        }

        if ($response->status() === 429) {
            $attempts++;

            // Check for Retry-After header
            $retryAfter = $response->header('Retry-After');

            if ($retryAfter !== null) {
                // Retry-After can be in seconds or a HTTP date
                if (is_numeric($retryAfter)) {
                    $delay = (int)$retryAfter;
                } else {
                    $delay = max(1, strtotime($retryAfter) - time());
                }
            } else {
                // Use exponential backoff if no Retry-After header
                $delay = pow(2, $attempts);
            }

            if ($attempts <= $maxRetries) {
                echo "Rate limited. Retrying in {$delay} seconds (attempt {$attempts}/{$maxRetries})...\n";
                sleep($delay);
                continue;
            }
        }

        // If we get here, it's either not a rate limit or we've exceeded retries
        break;
    }

    // If we've exhausted all retries or it's not a rate limit issue
    if ($response->status() === 429) {
        throw new \RuntimeException("Rate limit exceeded after {$maxRetries} retries");
    }

    throw new \RuntimeException(
        "Request failed with status: " . $response->status() . " " . $response->body()
    );
}

// Using the function
try {
    $response = fetchWithRateLimitHandling('https://api.example.com/limited-endpoint');
    $data = $response->json();
    echo "Successfully fetched data after rate limiting";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Async Error Handling

Handling errors in asynchronous code:

```php
use function Fetch\Http\fetch;
use function Matrix\async;
use function Matrix\await;
use function Matrix\all;

await(async(function() {
    try {
        $response = await(async(function() {
            return fetch('https://api.example.com/users');
        }));

        if (!$response->successful()) {
            throw new \RuntimeException(
                "API error: " . $response->status() . " " . $response->body()
            );
        }

        return $response->json();
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}));

// Handling errors with promise combinators
try {
    $results = await(all([
        'users' => async(fn() => fetch('https://api.example.com/users')),
        'posts' => async(fn() => fetch('https://api.example.com/posts'))
    ]));

    // Check if any of the responses failed
    foreach ($results as $key => $response) {
        if (!$response->successful()) {
            echo "{$key} request failed with status: " . $response->status() . "\n";
        }
    }

    // Process successful responses
    $users = $results['users']->successful() ? $results['users']->json() : [];
    $posts = $results['posts']->successful() ? $results['posts']->json() : [];

} catch (\Exception $e) {
    echo "Error in async operations: " . $e->getMessage();
}
```

## Custom Error Handler Class

Creating a dedicated error handler:

```php
use function Fetch\Http\fetch;

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
            case 400:
                throw new BadRequestException(
                    $this->getErrorMessage($response, "Bad request")
                );

            case 401:
                throw new AuthenticationException(
                    $this->getErrorMessage($response, "Authentication required")
                );

            case 403:
                throw new AuthorizationException(
                    $this->getErrorMessage($response, "Permission denied")
                );

            case 404:
                throw new ResourceNotFoundException(
                    $this->getErrorMessage($response, "Resource not found")
                );

            case 422:
                $errors = $response->json()['errors'] ?? [];
                throw new ValidationException(
                    $this->getErrorMessage($response, "Validation failed"),
                    $errors
                );

            case 429:
                $retryAfter = $response->header('Retry-After');
                throw new RateLimitException(
                    $this->getErrorMessage($response, "Too many requests"),
                    $retryAfter
                );

            case 500:
            case 502:
            case 503:
            case 504:
                throw new ServerException(
                    $this->getErrorMessage($response, "Server error: " . $response->status())
                );

            default:
                throw new ApiException(
                    $this->getErrorMessage($response, "API error: " . $response->status())
                );
        }
    }

    /**
     * Extract an error message from the response.
     */
    private function getErrorMessage($response, string $default): string
    {
        $body = $response->json();

        if (isset($body['message'])) {
            return $body['message'];
        }

        if (isset($body['error'])) {
            return is_string($body['error']) ? $body['error'] : json_encode($body['error']);
        }

        if (isset($body['errors']) && is_array($body['errors'])) {
            if (isset($body['errors'][0])) {
                // If errors is a simple array of messages
                return implode(', ', $body['errors']);
            } else {
                // If errors is a field => messages structure
                $messages = [];
                foreach ($body['errors'] as $field => $fieldErrors) {
                    $messages[] = $field . ': ' . (is_array($fieldErrors) ? implode(', ', $fieldErrors) : $fieldErrors);
                }
                return implode('; ', $messages);
            }
        }

        return $default;
    }
}

// Exception classes
class ApiException extends \Exception {}
class BadRequestException extends ApiException {}
class AuthenticationException extends ApiException {}
class AuthorizationException extends ApiException {}
class ResourceNotFoundException extends ApiException {}
class ValidationException extends ApiException {
    private array $errors;

    public function __construct(string $message, array $errors, int $code = 0)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
class RateLimitException extends ApiException {
    private ?string $retryAfter;

    public function __construct(string $message, ?string $retryAfter = null, int $code = 0)
    {
        parent::__construct($message, $code);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): ?string
    {
        return $this->retryAfter;
    }
}
class ServerException extends ApiException {}

// Usage
$errorHandler = new ApiErrorHandler();

try {
    $response = fetch('https://api.example.com/users');
    $errorHandler->handleResponse($response);

    // Process successful response
    $users = $response->json();
} catch (AuthenticationException $e) {
    // Handle authentication error
    echo "Please log in to continue: " . $e->getMessage();
} catch (ValidationException $e) {
    // Handle validation errors
    echo "Validation errors: " . $e->getMessage();
} catch (RateLimitException $e) {
    // Handle rate limiting
    $retryAfter = $e->getRetryAfter();
    echo "Rate limited. Try again " . ($retryAfter ? "after {$retryAfter}" : "later");
} catch (ApiException $e) {
    // Handle other API errors
    echo "API error: " . $e->getMessage();
}
```

## Error Reporting and Logging

Comprehensive error reporting:

```php
use function Fetch\Http\fetch;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a logger
$logger = new Logger('api');
$logger->pushHandler(new StreamHandler('logs/api.log', Logger::ERROR));

function fetchWithLogging(string $url, array $options = [])
{
    global $logger;

    try {
        $startTime = microtime(true);
        $response = fetch($url, $options);
        $duration = microtime(true) - $startTime;

        // Log all non-successful responses
        if ($response->failed()) {
            $logger->warning("API request failed", [
                'url' => $url,
                'method' => $options['method'] ?? 'GET',
                'status' => $response->status(),
                'duration' => round($duration, 3),
                'response' => substr($response->body(), 0, 1000) // Limit response size in logs
            ]);
        }

        return $response;
    } catch (\Exception $e) {
        // Log exceptions
        $logger->error("API request exception", [
            'url' => $url,
            'method' => $options['method'] ?? 'GET',
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        throw $e;
    }
}

// Using the function
try {
    $response = fetchWithLogging('https://api.example
