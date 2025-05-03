# Asynchronous Requests

Fetch PHP provides powerful asynchronous HTTP capabilities through PHP Fibers, allowing you to make non-blocking requests similar to JavaScript's Promise-based fetch API. This guide shows you how to use Fetch PHP's asynchronous features to handle concurrent requests efficiently.

## Basic Asynchronous Requests

To make asynchronous requests with Fetch PHP, you'll use the `async()` function from the Matrix package, which returns a Promise that you can chain with `then()` and `catch()`.

### Simple Async Request

```php
use function async;
use Fetch\Interfaces\Response as ResponseInterface;

// Create an asynchronous request
async(fn () => fetch('https://api.example.com/users'))
    ->then(function (ResponseInterface $response) {
        $users = $response->json();
        echo "Fetched " . count($users) . " users";
    })
    ->catch(function (\Throwable $e) {
        echo "Error: " . $e->getMessage();
    });
```

In this example:

- The request is wrapped in an `async()` function that returns a Promise
- `then()` handles the successful response
- `catch()` handles any errors that occur

### Async/Await Pattern

You can also use the `await()` function to wait for a Promise to resolve, similar to JavaScript's async/await pattern:

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

## Fluent API with Async

You can combine Fetch PHP's fluent API with asynchronous requests for more complex operations:

```php
use function async;
use function await;

async(fn () => fetch()
    ->baseUri('https://api.example.com')
    ->withHeaders(['Accept' => 'application/json'])
    ->withToken('your-access-token')
    ->withJson(['name' => 'John Doe', 'email' => 'john@example.com'])
    ->post('/users'))
    ->then(function ($response) {
        $newUser = $response->json();
        echo "Created user with ID: " . $newUser['id'];
    })
    ->catch(function ($error) {
        echo "Error: " . $error->getMessage();
    });
```

## Handling Multiple Concurrent Requests

One of the key benefits of asynchronous requests is the ability to run multiple operations concurrently. Fetch PHP provides several ways to manage concurrent requests.

### Running Requests in Parallel

```php
use function async;
use function await;
use function all;

// Create promises for multiple requests
$usersPromise = async(fn () => fetch('https://api.example.com/users'));
$postsPromise = async(fn () => fetch('https://api.example.com/posts'));
$commentsPromise = async(fn () => fetch('https://api.example.com/comments'));

// Wait for all promises to resolve
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
```

### Racing Requests

Sometimes you want to use the result of whichever request completes first:

```php
use function async;
use function await;
use function race;

// Create promises for multiple endpoints that return the same data
$primaryPromise = async(fn () => fetch('https://primary-api.example.com/data'));
$backupPromise = async(fn () => fetch('https://backup-api.example.com/data'));

// Get the result from whichever completes first
$response = await(race([$primaryPromise, $backupPromise]));
$data = $response->json();

echo "Got data from the fastest source";
```

### First Successful Request

If you want to get the first successful request (ignoring failures):

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

## Task Lifecycle Management

Fetch PHP, powered by Matrix, provides fine-grained control over the lifecycle of asynchronous tasks.

### Starting and Cancelling Tasks

```php
use function async;
use Task;

// Create a task but don't start it yet
$task = async(fn () => fetch('https://api.example.com/large-dataset'));

// Start the task when ready
$task->start();

// Later, if needed, cancel the task
$task->cancel();
```

### Pause and Resume

For long-running operations, you can pause and resume tasks:

```php
use function async;
use Enum\TaskStatus;

$task = async(fn () => fetch('https://api.example.com/large-dataset'));
$task->start();

// Pause the task
$task->pause();
echo "Task paused. Status: " . $task->getStatus();

// Resume the task later
$task->resume();
echo "Task resumed. Status: " . $task->getStatus();

// Check if the task is completed
if ($task->getStatus() === TaskStatus::COMPLETED) {
    $response = $task->getResult();
    $data = $response->json();
}
```

### Retry Logic

You can implement retry logic for tasks that might fail:

```php
use function async;
use Enum\TaskStatus;

$task = async(fn () => fetch('https://api.example.com/unstable-endpoint'));
$task->start();

// If the task fails, retry it
if ($task->getStatus() === TaskStatus::FAILED) {
    echo "Task failed. Retrying...";
    $task->retry();
}

// Once completed successfully, get the result
if ($task->getStatus() === TaskStatus::COMPLETED) {
    $response = $task->getResult();
    $data = $response->json();
}
```

## Error Handling

Proper error handling is crucial for asynchronous operations. Fetch PHP provides multiple ways to handle errors in async requests.

### Using then/catch Chain

```php
use function async;

async(fn () => fetch('https://api.example.com/users'))
    ->then(function ($response) {
        if ($response->ok()) {
            return $response->json();
        }

        // Handle HTTP error statuses
        throw new \Exception("API error: " . $response->status() . " " . $response->statusText());
    })
    ->then(function ($data) {
        // Process the data
        echo "Successfully processed " . count($data) . " items";
    })
    ->catch(function (\Throwable $e) {
        echo "Error occurred: " . $e->getMessage();

        // Log the error, notify the user, etc.
    })
    ->finally(function () {
        // This runs regardless of success or failure
        echo "Request completed";
    });
```

### Using try/catch with await

```php
use function async;
use function await;

try {
    $response = await(async(fn () => fetch('https://api.example.com/users')));

    if ($response->failed()) {
        throw new \Exception("API returned error status: " . $response->status());
    }

    $users = $response->json();
    echo "Successfully fetched " . count($users) . " users";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();

    // Implement fallback behavior if needed
} finally {
    echo "Operation complete";
}
```

## Advanced Retry Strategies

For more complex retry logic, you can implement custom retry strategies:

```php
use function async;
use function await;

$maxRetries = 5;
$retryDelay = 1000; // Start with 1 second delay

$makeRequestWithRetry = function () use (&$makeRequestWithRetry, &$maxRetries, &$retryDelay) {
    return async(fn () => fetch('https://api.example.com/unstable-endpoint'))
        ->catch(function (\Throwable $e) use (&$makeRequestWithRetry, &$maxRetries, &$retryDelay) {
            if ($maxRetries > 0) {
                $maxRetries--;
                echo "Request failed. Retrying in " . ($retryDelay / 1000) . " seconds...";

                // Exponential backoff with jitter
                $jitter = rand(-100, 100) / 1000; // ±100ms jitter
                usleep(($retryDelay + ($retryDelay * $jitter)) * 1000);
                $retryDelay *= 2; // Double the delay for next retry

                // Try again
                return $makeRequestWithRetry();
            }

            // No more retries, propagate the error
            throw $e;
        });
};

try {
    $response = await($makeRequestWithRetry());
    $data = $response->json();
    echo "Success after retries!";
} catch (\Throwable $e) {
    echo "All retry attempts failed: " . $e->getMessage();
}
```

## Real-world Examples

### Fetching User Data and Related Information

```php
use function async;
use function await;
use function all;

// Function to fetch a user and their related data
async function fetchUserWithRelatedData($userId) {
    // First fetch the user
    $userResponse = await(async(fn () => fetch("https://api.example.com/users/{$userId}")));
    $user = $userResponse->json();

    // Then fetch related data in parallel
    $relatedDataPromises = [
        'posts' => async(fn () => fetch("https://api.example.com/users/{$userId}/posts")),
        'comments' => async(fn () => fetch("https://api.example.com/users/{$userId}/comments")),
        'followers' => async(fn () => fetch("https://api.example.com/users/{$userId}/followers"))
    ];

    // Wait for all related data to be fetched
    $relatedData = await(all($relatedDataPromises));

    // Combine the user with their related data
    return [
        'user' => $user,
        'posts' => $relatedData['posts']->json(),
        'comments' => $relatedData['comments']->json(),
        'followers' => $relatedData['followers']->json()
    ];
}

// Usage
try {
    $userData = await(async(fn () => fetchUserWithRelatedData(123)));
    echo "User {$userData['user']['name']} has {$userData['followers']['total']} followers";
} catch (\Throwable $e) {
    echo "Error fetching user data: " . $e->getMessage();
}
```

### Processing Data in Batches

```php
use function async;
use function await;
use function all;

// Process a large dataset in parallel batches
async function processLargeDataset() {
    // First, get the total count
    $countResponse = await(async(fn () => fetch('https://api.example.com/items/count')));
    $totalItems = $countResponse->json()['count'];
    $batchSize = 100;
    $batches = ceil($totalItems / $batchSize);

    $results = [];

    // Process in batches of 5 concurrent requests
    for ($i = 0; $i < $batches; $i += 5) {
        $batchPromises = [];

        // Create up to 5 batch promises
        for ($j = 0; $j < 5 && ($i + $j) < $batches; $j++) {
            $offset = ($i + $j) * $batchSize;
            $batchPromises["batch-{$offset}"] = async(fn () =>
                fetch("https://api.example.com/items?offset={$offset}&limit={$batchSize}")
            );
        }

        // Wait for this group of batches to complete
        $batchResults = await(all($batchPromises));

        // Process the results
        foreach ($batchResults as $key => $response) {
            $items = $response->json();
            $results = array_merge($results, $items);
            echo "Processed {$key} with " . count($items) . " items\n";
        }
    }

    return $results;
}

// Usage
try {
    $allItems = await(async(fn () => processLargeDataset()));
    echo "Processed " . count($allItems) . " items in total";
} catch (\Throwable $e) {
    echo "Error processing dataset: " . $e->getMessage();
}
```

## Performance Considerations

When working with asynchronous requests, keep these performance considerations in mind:

1. **Memory Management**: Be careful when processing large datasets asynchronously, as all data remains in memory until the task completes.

2. **Concurrency Limits**: Avoid making too many concurrent requests to the same domain, as most servers have rate limits.

3. **Event Loop**: Asynchronous operations in PHP rely on the event loop provided by ReactPHP. Ensure you don't block the event loop with CPU-intensive operations.

4. **Error Handling**: Always implement proper error handling for async operations to prevent unhandled promise rejections.

5. **Task Cancellation**: Remember to cancel tasks that are no longer needed to free up resources.
