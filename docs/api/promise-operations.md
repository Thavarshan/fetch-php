# Promise Operations

Fetch PHP provides several utility functions for working with promises, allowing you to manage multiple asynchronous operations. These operations are powered by the Matrix library, which uses PHP Fibers to implement true non-blocking concurrency.

## Core Promise Operations

### `all()`

Executes multiple promises concurrently and waits for all of them to complete. Returns a promise that resolves with an array of all the fulfillment values.

```php
function all(array $promises): PromiseInterface
```

#### Parameters

- `$promises`: An array of promises to run concurrently. Can use string keys for named results.

#### Return Value

- A `PromiseInterface` that resolves with an array of all results, preserving the keys from the input array.

#### Throws

- Rejects with the first rejection reason if any of the promises reject.

### `race()`

Returns a promise that fulfills or rejects as soon as one of the promises in the iterable fulfills or rejects, with the value or reason from that promise.

```php
function race(array $promises): PromiseInterface
```

#### Parameters

- `$promises`: An array of promises to race against each other.

#### Return Value

- A `PromiseInterface` that resolves with the value of the first promise to resolve.

#### Throws

- Rejects with the reason of the first promise to reject.

### `any()`

Returns a promise that fulfills when any of the promises fulfills, with the first fulfillment value. It rejects only if all of the promises reject.

```php
function any(array $promises): PromiseInterface
```

#### Parameters

- `$promises`: An array of promises to monitor.

#### Return Value

- A `PromiseInterface` that resolves with the value of the first promise to fulfill.

#### Throws

- Rejects with an array of all rejection reasons if all promises reject.

## Examples

### Parallel API Requests with all()

```php
use function async;
use function await;
use function all;

// Create multiple promises with named keys
$promises = [
    'users' => async(fn () => fetch('https://api.example.com/users')),
    'posts' => async(fn () => fetch('https://api.example.com/posts')),
    'comments' => async(fn () => fetch('https://api.example.com/comments'))
];

try {
    // Execute all requests in parallel
    $results = await(all($promises));

    // Access results using the keys from the original promises array
    $users = $results['users']->json();
    $posts = $results['posts']->json();
    $comments = $results['comments']->json();

    echo "Fetched {$users['total']} users, {$posts['total']} posts, and {$comments['total']} comments";
} catch (\Throwable $e) {
    echo "One of the requests failed: " . $e->getMessage();
}
```

### Using all() with ClientHandler

```php
$client = fetch();

// Create multiple promises
$promises = [
    'users' => $client->async()->get('https://api.example.com/users'),
    'posts' => $client->async()->get('https://api.example.com/posts'),
    'comments' => $client->async()->get('https://api.example.com/comments')
];

// Execute all requests in parallel
$client->all($promises)
    ->then(function ($results) {
        $users = $results['users']->json();
        $posts = $results['posts']->json();
        $comments = $results['comments']->json();

        echo "Fetched {$users['total']} users, {$posts['total']} posts, and {$comments['total']} comments";
    })
    ->catch(function ($error) {
        echo "One of the requests failed: " . $error->getMessage();
    });
```

### Fastest Response with race()

```php
use function async;
use function await;
use function race;

// Create promises for multiple mirrors of the same API
$promises = [
    async(fn () => fetch('https://api1.example.com/data')),
    async(fn () => fetch('https://api2.example.com/data')),
    async(fn () => fetch('https://api3.example.com/data'))
];

try {
    // Get the result from whichever responds first
    $response = await(race($promises));
    $data = $response->json();
    echo "Got data from the fastest server";
} catch (\Throwable $e) {
    echo "At least one server failed: " . $e->getMessage();
}
```

### Using race() with ClientHandler

```php
$client = fetch();

// Create promises for multiple mirrors of the same API
$promises = [
    $client->async()->get('https://api1.example.com/data'),
    $client->async()->get('https://api2.example.com/data'),
    $client->async()->get('https://api3.example.com/data')
];

// Get the result from whichever responds first
$client->race($promises)
    ->then(function ($response) {
        $data = $response->json();
        echo "Got data from the fastest server";
    })
    ->catch(function ($error) {
        echo "At least one server failed: " . $error->getMessage();
    });
```

### First Successful Response with any()

```php
use function async;
use function await;
use function any;

// Create promises for multiple servers that might fail
$promises = [
    async(fn () => fetch('https://api1.example.com/data')),
    async(fn () => fetch('https://api2.example.com/data')),
    async(fn () => fetch('https://api3.example.com/data'))
];

try {
    // Get the first successful response, ignoring failures
    $response = await(any($promises));
    $data = $response->json();
    echo "Got data from a working server";
} catch (\Throwable $e) {
    echo "All servers failed";
}
```

### Using any() with ClientHandler

```php
$client = fetch();

// Create promises for multiple servers that might fail
$promises = [
    $client->async()->get('https://api1.example.com/data'),
    $client->async()->get('https://api2.example.com/data'),
    $client->async()->get('https://api3.example.com/data')
];

// Get the first successful response, ignoring failures
$client->any($promises)
    ->then(function ($response) {
        $data = $response->json();
        echo "Got data from a working server";
    })
    ->catch(function ($error) {
        echo "All servers failed";
    });
```

## Advanced Use Cases

### Dynamic Batch Processing

```php
use function async;
use function await;
use function all;

/**
 * Process a large dataset in parallel batches
 */
async function processItems(array $items, int $batchSize = 10): array {
    $results = [];
    $batches = array_chunk($items, $batchSize);

    foreach ($batches as $batch) {
        $promises = [];

        // Create a promise for each item in the batch
        foreach ($batch as $index => $item) {
            $promises[$index] = async(fn () => processItem($item));
        }

        // Process this batch in parallel
        $batchResults = await(all($promises));
        $results = array_merge($results, $batchResults);
    }

    return $results;
}

/**
 * Process a single item (e.g., make an API request)
 */
function processItem($item) {
    return fetch("https://api.example.com/process/{$item}")
        ->json();
}

// Usage
$items = range(1, 100); // 100 items to process
try {
    $results = await(async(fn () => processItems($items, 10)));
    echo "Processed " . count($results) . " items";
} catch (\Throwable $e) {
    echo "Processing failed: " . $e->getMessage();
}
```

### Conditional Parallel Requests

```php
use function async;
use function await;
use function all;

/**
 * Fetch user data and conditionally fetch related data
 */
async function getUserWithRelatedData(int $userId): array {
    // First fetch the user
    $userResponse = await(async(fn () => fetch("https://api.example.com/users/{$userId}")));
    $user = $userResponse->json();

    // Prepare conditional promises
    $promises = [];

    // Only fetch posts if the user has posts
    if ($user['post_count'] > 0) {
        $promises['posts'] = async(fn () => fetch("https://api.example.com/users/{$userId}/posts"));
    }

    // Only fetch followers if the user has followers
    if ($user['follower_count'] > 0) {
        $promises['followers'] = async(fn () => fetch("https://api.example.com/users/{$userId}/followers"));
    }

    // Always fetch settings
    $promises['settings'] = async(fn () => fetch("https://api.example.com/users/{$userId}/settings"));

    // Execute all promises in parallel
    $relatedData = await(all($promises));

    // Process the results
    $result = ['user' => $user];

    if (isset($relatedData['posts'])) {
        $result['posts'] = $relatedData['posts']->json();
    }

    if (isset($relatedData['followers'])) {
        $result['followers'] = $relatedData['followers']->json();
    }

    $result['settings'] = $relatedData['settings']->json();

    return $result;
}

// Usage
try {
    $userData = await(async(fn () => getUserWithRelatedData(123)));
    echo "User: " . $userData['user']['name'];

    if (isset($userData['posts'])) {
        echo ", Posts: " . count($userData['posts']);
    }

    if (isset($userData['followers'])) {
        echo ", Followers: " . count($userData['followers']);
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
```

## Performance Tips

1. **Balance Concurrency**: While parallel requests can improve performance, too many concurrent requests might overwhelm servers or trigger rate limiting. Consider using smaller batch sizes when processing large datasets.

2. **Error Handling**: When using `all()`, remember that it fails fast if any promise is rejected. If you need to handle individual failures, consider using `Promise\allSettled()` from ReactPHP (if available) or implement similar functionality yourself.

3. **Timeout Management**: Set appropriate timeouts for your promises to prevent hanging operations. A common pattern is to race a promise against a timeout:

```php
use function async;
use function await;
use function race;

function withTimeout(PromiseInterface $promise, int $timeoutMs): PromiseInterface {
    $timeout = async(function () use ($timeoutMs) {
        return new Promise(function ($resolve, $reject) use ($timeoutMs) {
            getLoop()->addTimer($timeoutMs / 1000, function () use ($reject) {
                $reject(new \Exception("Operation timed out after {$timeoutMs}ms"));
            });
        });
    });

    return race([$promise, $timeout]);
}

// Usage
try {
    $promise = async(fn () => fetch('https://api.example.com/slow-operation'));
    $response = await(withTimeout($promise, 5000)); // 5 second timeout
    $data = $response->json();
} catch (\Throwable $e) {
    if (strpos($e->getMessage(), 'timed out') !== false) {
        echo "Operation timed out";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
```

4. **Memory Management**: When processing large datasets, be mindful of memory usage. Consider using generators or iterators to process data in chunks rather than loading everything into memory at once.

5. **Event Loop Awareness**: The event loop must continue running for promises to resolve. Avoid blocking operations inside async callbacks, as they will block the entire event loop.
