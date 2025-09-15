---
title: Logging
description: Learn how to configure and use logging with the Fetch HTTP package
---

# Logging

The Fetch PHP package provides built-in support for logging HTTP requests and responses. This guide explains how to configure and use logging to help with debugging and monitoring.

## PSR-3 Logger Integration

The package integrates with any PSR-3 compatible logger, such as Monolog:

```php
use Fetch\Http\ClientHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a PSR-3 compatible logger
$logger = new Logger('http');
$logger->pushHandler(new StreamHandler('logs/http.log', Logger::DEBUG));

// Set the logger on a client
$client = ClientHandler::create();
$client->setLogger($logger);

// Now all requests and responses will be logged
$response = $client->get('https://api.example.com/users');
```

## Using Logger with Helper Functions

You can also set a logger on the global client:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a logger
$logger = new Logger('http');
$logger->pushHandler(new StreamHandler('logs/http.log', Logger::DEBUG));

// Configure the global client with the logger
$client = fetch_client();
$client->setLogger($logger);

// All requests will now be logged
$response = fetch('https://api.example.com/users');
```

## What Gets Logged

The package logs the following events:

1. **Requests**: HTTP method, URI, and sanitized options
2. **Responses**: Status code, reason phrase, and timing
3. **Retry Attempts**: When retries occur due to errors

### Request Logging

```
[2023-01-15 14:30:10] http.DEBUG: Sending HTTP request {"method":"GET","uri":"https://api.example.com/users","options":{"timeout":30,"headers":{"User-Agent":"MyApp/1.0","Accept":"application/json"}}}
```

### Response Logging

```
[2023-01-15 14:30:11] http.DEBUG: Received HTTP response {"status_code":200,"reason":"OK","duration":0.532,"content_length":"1250"}
```

### Retry Logging

```
[2023-01-15 14:30:12] http.INFO: Retrying request {"attempt":1,"max_attempts":3,"uri":"https://api.example.com/unstable-endpoint","method":"GET","error":"Connection timed out","code":28}
```

## Security and Sensitive Data

The package automatically redacts sensitive information in logs:

- Headers: Authorization, X-API-Key, API-Key, X-Auth-Token, Cookie, Set-Cookie (case-insensitive)
- Options: Basic auth credentials (`auth`)

For example, this request:

```php
$client->withToken('secret-token')
    ->withHeader('X-API-Key', 'private-key')
    ->get('https://api.example.com/users');
```

Would be logged as:

```
[2023-01-15 14:30:10] http.DEBUG: Sending HTTP request {"method":"GET","uri":"https://api.example.com/users","options":{"timeout":30,"headers":{"Authorization":"[REDACTED]","X-API-Key":"[REDACTED]"}}}
```

## Custom Logging Configuration

For more control over logging, you can configure different log levels for different events:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

// Create a logger with multiple handlers
$logger = new Logger('http');

// Debug level logs to a rotating file (1MB max size, keep 10 files)
$logger->pushHandler(new RotatingFileHandler('logs/http-debug.log', 10, Logger::DEBUG));

// Info level and above goes to main log
$logger->pushHandler(new StreamHandler('logs/http.log', Logger::INFO));

// Errors go to a separate file
$logger->pushHandler(new StreamHandler('logs/http-error.log', Logger::ERROR));

// Set the logger
$client = ClientHandler::create();
$client->setLogger($logger);
```

## Logging Request and Response Bodies

By default, the package doesn't log request or response bodies to avoid excessive log sizes and potential security issues. If you need this information for debugging, you can create a custom middleware:

```php
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Client;
use Fetch\Http\ClientHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a logger
$logger = new Logger('http-verbose');
$logger->pushHandler(new StreamHandler('logs/http-verbose.log', Logger::DEBUG));

// Create a message formatter that includes bodies
$formatter = new MessageFormatter(
    "Request: {method} {uri} HTTP/{version}\n" .
    "Request Headers: {req_headers}\n" .
    "Request Body: {req_body}\n" .
    "Response: HTTP/{version} {code} {phrase}\n" .
    "Response Headers: {res_headers}\n" .
    "Response Body: {res_body}"
);

// Create middleware with the formatter
$middleware = Middleware::log($logger, $formatter, 'debug');

// Create a handler stack with the middleware
$stack = HandlerStack::create();
$stack->push($middleware);

// Create a Guzzle client with the stack
$guzzleClient = new Client(['handler' => $stack]);

// Create a ClientHandler with the custom Guzzle client
$client = ClientHandler::createWithClient($guzzleClient);

// Use the client
$response = $client->post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

## Logging with Status Enums

When logging with status codes, you can use the type-safe Status enum for better readability:

```php
use Fetch\Enum\Status;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('http');
$logger->pushHandler(new StreamHandler('logs/http.log', Logger::DEBUG));

// Custom log processing
$logger->pushProcessor(function ($record) {
    // If we have a response status in the context, convert it to a human-readable format
    if (isset($record['context']['status_code'])) {
        $statusCode = $record['context']['status_code'];
        $statusEnum = Status::tryFrom($statusCode);
        if ($statusEnum) {
            $record['context']['status_text'] = $statusEnum->phrase();
        }
    }
    return $record;
});

// Set the logger
$client = ClientHandler::create();
$client->setLogger($logger);
```

## Logging in Different Environments

It's often useful to adjust logging behavior based on the environment:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NullHandler;
use Monolog\Formatter\LineFormatter;
use Fetch\Http\ClientHandler;

function createLogger(): Logger
{
    $env = getenv('APP_ENV') ?: 'production';
    $logger = new Logger('http');

    // Configure based on environment
    switch ($env) {
        case 'development':
            // In development, log everything to stdout with details
            $formatter = new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                null, true, true
            );
            $handler = new StreamHandler('php://stdout', Logger::DEBUG);
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);
            break;

        case 'testing':
            // In testing, don't log anything
            $logger->pushHandler(new NullHandler());
            break;

        case 'staging':
            // In staging, log to files with rotation
            $logger->pushHandler(new StreamHandler('logs/http.log', Logger::INFO));
            break;

        default:
            // In production, only log warnings and above
            $logger->pushHandler(new StreamHandler('logs/http.log', Logger::WARNING));
            break;
    }

    return $logger;
}

// Create a client with the environment-specific logger
$client = ClientHandler::create();
$client->setLogger(createLogger());
```

## Log Analysis and Troubleshooting

HTTP logs can be invaluable for troubleshooting issues. Here are some techniques for analyzing logs:

### Identifying Slow Requests

Look for response logs with high duration values:

```
grep "duration" logs/http.log | sort -k5 -nr | head -10
```

This will show the 10 slowest requests based on the duration field.

### Finding Error Patterns

Search for failed requests:

```
grep "status_code\":4" logs/http.log  # Client errors (4xx)
grep "status_code\":5" logs/http.log  # Server errors (5xx)
```

### Tracking Retry Patterns

Identify endpoints that frequently require retries:

```
grep "Retrying request" logs/http.log | sort | uniq -c | sort -nr
```

This will show the most frequently retried endpoints.

## Logging to External Services

For production environments, you might want to send logs to external monitoring services:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SlackWebhookHandler;
use Fetch\Http\ClientHandler;

// Create a logger that sends critical errors to Slack
$logger = new Logger('http');

// Log to file
$logger->pushHandler(new StreamHandler('logs/http.log', Logger::INFO));

// Also send critical errors to Slack
$logger->pushHandler(new SlackWebhookHandler(
    'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
    '#api-errors',
    'API Monitor',
    true,
    null,
    false,
    false,
    Logger::CRITICAL
));

// Use the logger
$client = ClientHandler::create();
$client->setLogger($logger);
```

## Logging in Asynchronous Requests

When making asynchronous requests, logging still works the same way:

```php
use function async;
use function await;
use function all;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a logger
$logger = new Logger('http');
$logger->pushHandler(new StreamHandler('logs/http-async.log', Logger::DEBUG));

// Set up the client with the logger
$client = ClientHandler::create();
$client->setLogger($logger);

// Use async/await pattern
await(async(function() use ($client) {
    // Process multiple requests in parallel
    $results = await(all([
        'users' => async(fn() => $client->get('https://api.example.com/users')),
        'posts' => async(fn() => $client->get('https://api.example.com/posts')),
        'comments' => async(fn() => $client->get('https://api.example.com/comments'))
    ]));

    // All requests will be logged
    return $results;
}));

// Or using the traditional promise approach
$handler = $client->getHandler();
$handler->async();

// Create promises for multiple requests
$usersPromise = $handler->get('https://api.example.com/users');
$postsPromise = $handler->get('https://api.example.com/posts');

// All requests will be logged, even though they're async
$handler->all(['users' => $usersPromise, 'posts' => $postsPromise])
    ->then(function ($results) {
        // Process results
    });
```

## Context-Aware Logging

You can create a custom logger that adds context to each log entry:

```php
use Fetch\Http\ClientHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\ProcessIdProcessor;

// Create a logger with additional context
$logger = new Logger('http');
$logger->pushHandler(new StreamHandler('logs/http.log', Logger::DEBUG));

// Add request information (IP, URL, etc.)
$logger->pushProcessor(new WebProcessor());

// Add file and line where the log was triggered
$logger->pushProcessor(new IntrospectionProcessor());

// Add process ID
$logger->pushProcessor(new ProcessIdProcessor());

// Add custom context
$logger->pushProcessor(function ($record) {
    $record['extra']['user_id'] = $_SESSION['user_id'] ?? null;
    $record['extra']['request_id'] = $_SERVER['HTTP_X_REQUEST_ID'] ?? uniqid();
    return $record;
});

// Use the logger
$client = ClientHandler::create();
$client->setLogger($logger);
```

## Structured Logging

For easier log parsing and analysis, you might want to use JSON-formatted logs:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;
use Fetch\Http\ClientHandler;

// Create a logger with JSON formatting
$logger = new Logger('http');

$handler = new StreamHandler('logs/http.json.log', Logger::DEBUG);
$handler->setFormatter(new JsonFormatter());
$logger->pushHandler($handler);

// Use the logger
$client = ClientHandler::create();
$client->setLogger($logger);
```

This will produce logs in JSON format that can be easily parsed by log analysis tools.

## Logging Request IDs

To correlate multiple log entries for a single client request, you can use request IDs:

```php
// Generate a request ID at the start of the application
$requestId = uniqid('req-', true);

// Create a processor that adds the request ID to all log entries
$requestIdProcessor = function ($record) use ($requestId) {
    $record['extra']['request_id'] = $requestId;
    return $record;
};

// Create a logger with the processor
$logger = new Logger('http');
$logger->pushProcessor($requestIdProcessor);
$logger->pushHandler(new StreamHandler('logs/http.log', Logger::DEBUG));

// Use the logger
$client = ClientHandler::create();
$client->setLogger($logger);

// Add the request ID to all requests as well
$client->withHeader('X-Request-ID', $requestId);
```

## Logging Debug Information

You can use the `debug()` method to get detailed information about a request for logging purposes:

```php
$client = ClientHandler::create();
$response = $client->get('https://api.example.com/users');

// Get debug information after the request
$debugInfo = $client->debug();

// Log it manually if needed
$logger->debug('Request debug information', $debugInfo);

// Debug info includes:
// - uri: The full URI
// - method: The HTTP method used
// - headers: Request headers (sensitive data redacted)
// - options: Other request options
// - is_async: Whether the request was asynchronous
// - timeout: The timeout setting
// - retries: The number of retries configured
// - retry_delay: The retry delay setting
```

## Best Practices

1. **Don't Log Sensitive Data**: Be careful about logging request and response bodies that might contain sensitive information.

2. **Use Different Log Levels**: Use appropriate log levels (DEBUG, INFO, WARNING, ERROR) to categorize log entries.

3. **Rotate Log Files**: Implement log rotation to prevent logs from growing too large.

4. **Add Context**: Include request IDs, user IDs, and other contextual information to make logs more useful.

5. **Structure Logs**: Use structured logging (e.g., JSON format) for easier parsing and analysis.

6. **Monitor Error Rates**: Set up alerts for increases in error rates or other anomalies.

7. **Correlation IDs**: Use correlation IDs to trace requests across multiple services.

8. **Regular Log Analysis**: Regularly analyze logs to identify issues and optimize performance.

9. **Adjust Based on Environment**: Use different logging configurations for different environments.

10. **Performance Consideration**: Be mindful of logging performance impact, especially in high-traffic applications.

11. **Use Type-Safe Enums**: When logging status codes or content types, consider using the enums for better readability.

12. **Log Asynchronous Operations**: Make sure to apply the same logging principles to asynchronous requests.

## Next Steps

- Learn about [Error Handling](/guide/error-handling) for comprehensive error management
- Explore [Retry Handling](/guide/retry-handling) for handling transient errors
- See [Testing](/guide/testing) for how to test code with logging
- Check out [Asynchronous Requests](/guide/async-requests) for logging in async operations
