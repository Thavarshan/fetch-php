# Asynchronous API

Fetch PHP provides powerful asynchronous capabilities through its integration with the Matrix library, which leverages PHP Fibers for true non-blocking operations. This API allows you to perform HTTP requests in the background without blocking the main execution thread, similar to JavaScript's async/await pattern.

## Core Async Functions

### `async()`

Wraps a callable into an asynchronous function that returns a promise.

```php
function async(callable $callable): PromiseInterface
```

#### Parameters

- `$callable`: The function to execute asynchronously

#### Return Value

- A `PromiseInterface` that resolves with the callable's result

### `await()`

Awaits the resolution of a promise and returns its value.

```php
function await(PromiseInterface $promise): mixed
```

#### Parameters

- `$promise`: The promise to await

#### Return Value

- The resolved value of the promise (any type)

#### Throws

- `\Throwable`: If the promise is rejected

### `all()`

Runs multiple promises concurrently and waits for all to complete.

```php
function all(array $promises): PromiseInterface
```

#### Parameters

- `$promises`: An array of promises to run concurrently

#### Return Value

- A `PromiseInterface` that resolves with an array of all results

### `race()`

Returns a promise that resolves with the value of the first resolved promise in the array.

```php
function race(array $promises): PromiseInterface
```

#### Parameters

- `$promises`: An array of promises to race

#### Return Value

- A `PromiseInterface` that resolves with the first result

### `any()`

Returns a promise that resolves when any promise resolves, or rejects when all promises reject.

```php
function any(array $promises): PromiseInterface
```

#### Parameters

- `$promises`: An array of promises

#### Return Value

- A `PromiseInterface` that resolves with the first successful result, or rejects with an array of all rejection reasons

## Promise Methods

When using `async()` to create a promise, the following methods are available on the returned promise object:

### `then()`

Adds a fulfillment handler and optionally a rejection handler.

```php
public function then(callable $onFulfilled, ?callable $onRejected = null): PromiseInterface
```

#### Parameters

- `$onFulfilled`: Function called when the promise fulfills
- `$onRejected`: (Optional) Function called when the promise rejects

#### Return Value

- A new `PromiseInterface` for chaining

### `catch()`

Adds a rejection handler.

```php
public function catch(callable $onRejected): PromiseInterface
```

#### Parameters

- `$onRejected`: Function called when the promise rejects

#### Return Value

- A new `PromiseInterface` for chaining

### `finally()`

Adds a handler that is called when the promise settles (either fulfills or rejects).

```php
public function finally(callable $onFinally): PromiseInterface
```

#### Parameters

- `$onFinally`: Function called when the promise settles

#### Return Value

- A new `PromiseInterface` for chaining

## ClientHandler Async Methods

The `ClientHandler` class provides several methods specifically for asynchronous operations:

### `async()`

Enables asynchronous mode for the next request.

```php
public function async(?bool $async = true): self
```

### `wrapAsync()`

Wraps a callable in an async function.

```php
public function wrapAsync(callable $callable): PromiseInterface
```

### `awaitPromise()`

Waits for a promise to resolve.

```php
public function awaitPromise(PromiseInterface $promise): mixed
```

### `all()`

Runs multiple promises concurrently.

```php
public function all(array $promises): PromiseInterface
```

### `race()`

Returns the first promise to resolve.

```php
public function race(array $promises): PromiseInterface
```

### `any()`

Returns the first promise to succeed.

```php
public function any(array $promises): PromiseInterface
```

### `then()`

Chains a success handler to the next request.

```php
public function then(callable $onFulfilled, ?callable $onRejected = null): PromiseInterface
```

### `catch()`

Chains an error handler to the next request.

```php
public function catch(callable $onRejected): PromiseInterface
```

### `finally()`

Chains a completion handler to the next request.

```php
public function finally(callable $onFinally): PromiseInterface
```

## Examples

### Basic Async Request

```php
use function async;
use function await;
use Fetch\Interfaces\Response as ResponseInterface;

// Create an async request
$promise = async(fn () => fetch('https://api.example.com/users'));

// Handle the promise
$promise
    ->then(function (ResponseInterface $response) {
        $users = $response->json();
        echo "Fetched " . count($users) . " users";
        return $users;
    })
    ->catch(function (\Throwable $e) {
        echo "Error: " . $e->getMessage();
    });
```

### Using Async/Await Pattern

```php
use function async;
use function await;

try {
    // Wait for the async request to complete
    $response = await(async(fn () => fetch('https://api.example.com/users')));

    // Process the response
    $users = $response->json();
    echo "Fetched " . count($users) . " users";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
```

### Fluent API with Async

```php
use function async;
use function await;

// Create an async request with the fluent API
try {
    $response = await(async(fn () => fetch()
        ->withToken('your-access-token')
        ->get('https://api.example.com/users')));

    $users = $response->json();
    echo "Fetched " . count($users) . " users";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
```

### Using ClientHandler Async Methods

```php
$client = fetch();

// Enable async mode and add handlers
$client
    ->async()
    ->withToken('your-access-token')
    ->get('https://api.example.com/users')
    ->then(function ($response) {
        $users = $response->json();
        echo "Fetched " . count($users) . " users";
        return $users;
    })
    ->catch(function ($error) {
        echo "Error: " . $error->getMessage();
    });
```

### Multiple Concurrent Requests

```php
use function async;
use function await;
use function all;

// Create promises for multiple requests
$usersPromise = async(fn () => fetch('https://api.example.com/users'));
$postsPromise = async(fn () => fetch('https://api.example.com/posts'));
$commentsPromise = async(fn () => fetch('https://api.example.com/comments'));

// Wait for all promises to resolve
try {
    $results = await(all([
        'users' => $usersPromise,
        'posts' => $postsPromise,
        'comments' => $commentsPromise
    ]));

    // Process the results
    $users = $results['users']->json();
    $posts = $results['posts']->json();
    $comments = $results['comments']->json();

    echo "Fetched {$users['total']} users, {$posts['total']} posts, and {$comments['total']} comments";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
```

### Using race() to Get the First Response

```php
use function async;
use function await;
use function race;

// Create promises for multiple endpoints that return the same data
$primaryPromise = async(fn () => fetch('https://primary-api.example.com/data'));
$backupPromise = async(fn () => fetch('https://backup-api.example.com/data'));

// Get the result from whichever completes first
try {
    $response = await(race([$primaryPromise, $backupPromise]));
    $data = $response->json();
    echo "Got data from the fastest source";
} catch (\Throwable $e) {
    echo "All requests failed: " . $e->getMessage();
}
```

### Using any() to Get the First Successful Response

```php
use function async;
use function await;
use function any;

// Create promises for multiple endpoints
$promises = [
    async(fn () => fetch('https://api1.example.com/data')),
    async(fn () => fetch('https://api2.example.com/data')),
    async(fn () => fetch('https://api3.example.com/data'))
];

// Get the first successful response
try {
    $response = await(any($promises));
    $data = $response->json();
    echo "Got data from a successful endpoint";
} catch (\Throwable $e) {
    echo "All requests failed: " . $e->getMessage();
}
```

### Advanced Promise Chaining

```php
use function async;

async(fn () => fetch('https://api.example.com/users'))
    ->then(function ($response) {
        $users = $response->json();

        // Return a new promise to fetch the first user's details
        return async(fn () => fetch('https://api.example.com/users/' . $users[0]['id']));
    })
    ->then(function ($response) {
        $user = $response->json();
        echo "User details: " . $user['name'];

        // Return a new promise to fetch the user's posts
        return async(fn () => fetch('https://api.example.com/users/' . $user['id'] . '/posts'));
    })
    ->then(function ($response) {
        $posts = $response->json();
        echo "User has " . count($posts) . " posts";
    })
    ->catch(function ($error) {
        echo "Error in the chain: " . $error->getMessage();
    });
```

## Performance Considerations

When working with asynchronous requests, keep these performance considerations in mind:

1. **Memory Management**: Asynchronous operations keep data in memory until they complete, so be mindful of memory usage when handling large responses.

2. **Concurrency Limits**: Avoid making too many concurrent requests to the same server, as this may trigger rate limiting or IP blocking.

3. **Error Handling**: Always handle errors in asynchronous code to prevent unhandled promise rejections.

4. **Event Loop**: The event loop must continue running for promises to resolve. Make sure not to block the event loop with CPU-intensive operations.

5. **Request Timeouts**: Configure timeouts appropriately to prevent hanging promises in case of slow or unresponsive servers.
