---
title: Promise Operations
description: Learn how to work with promises in the Fetch HTTP package
---

# Promise Operations

This guide covers how to work with promises in the Fetch HTTP package. The package implements a Promise-based API similar to JavaScript promises, allowing for sophisticated asynchronous programming patterns.

## Basic Promise Concepts

Promises represent values that may not be available yet. They're used for asynchronous operations like HTTP requests. In the Fetch HTTP package, promises are represented by the `PromiseInterface` from React's Promise library.

## Creating Promises

There are several ways to create promises:

```php
// Create a promise from an async function
$promise = async(function() {
    return fetch('https://api.example.com/users');
});

// Create a resolved promise with a value
$resolvedPromise = resolve(['name' => 'John', 'email' => 'john@example.com']);

// Through the ClientHandler
$handler = fetch_client()->getHandler();
$resolvedPromise = $handler->resolve(['name' => 'John']);

// Create a rejected promise with an error
$rejectedPromise = reject(new \Exception('Something went wrong'));

// Through the ClientHandler
$handler = fetch_client()->getHandler();
$rejectedPromise = $handler->reject(new \Exception('Something went wrong'));
```

## Promise Methods

### then()

The `then()` method registers callbacks for when a promise resolves successfully or fails:

```php
$promise = async(function() {
    return fetch('https://api.example.com/users');
});

$promise->then(
    function ($response) {
        // Success callback
        $users = $response->json();
        echo "Fetched " . count($users) . " users";
        return $users;
    },
    function ($error) {
        // Error callback
        echo "Error: " . $error->getMessage();
    }
);
```

The `then()` method returns a new promise that resolves with the return value of the callback.

### catch()

The `catch()` method is a shorthand for handling errors:

```php
$promise = async(function() {
    return fetch('https://api.example.com/users');
});

$promise
    ->then(function ($response) {
        $users = $response->json();
        echo "Fetched " . count($users) . " users";
        return $users;
    })
    ->catch(function ($error) {
        echo "Error: " . $error->getMessage();
    });
```

### finally()

The `finally()` method registers a callback that runs when the promise settles, regardless of whether it was resolved or rejected:

```php
$promise = async(function() {
    return fetch('https://api.example.com/users');
});

$promise
    ->then(function ($response) {
        $users = $response->json();
        echo "Fetched " . count($users) . " users";
    })
    ->catch(function ($error) {
        echo "Error: " . $error->getMessage();
    })
    ->finally(function () {
        echo "Request completed.";
    });
```

## Using with ClientHandler

The `ClientHandler` provides methods for working with promises:

```php
// Get the handler from the client
$handler = fetch_client()->getHandler();

// Enable async mode
$handler->async();

// Make an async request
$promise = $handler->get('https://api.example.com/users');

// Add callbacks
$promise->then(
    function ($response) {
        $users = $response->json();
        return $users;
    }
)->catch(
    function ($error) {
        echo "Error: " . $error->getMessage();
    }
);

// Wait for a promise to resolve
$result = $handler->awaitPromise($promise);
```

## Combining Multiple Promises

The package provides several functions for working with multiple promises:

### all()

The `all()` function waits for all promises to resolve, or rejects if any promise fails:

```php
// Create multiple promises
$usersPromise = async(function() {
    return fetch('https://api.example.com/users');
});

$postsPromise = async(function() {
    return fetch('https://api.example.com/posts');
});

$commentsPromise = async(function() {
    return fetch('https://api.example.com/comments');
});

// Wait for all to complete
all([
    'users' => $usersPromise,
    'posts' => $postsPromise,
    'comments' => $commentsPromise
])->then(function ($results) {
    // $results is an array with keys 'users', 'posts', 'comments'
    $users = $results['users']->json();
    $posts = $results['posts']->json();
    $comments = $results['comments']->json();

    echo "Fetched " . count($users) . " users, " .
         count($posts) . " posts, and " .
         count($comments) . " comments";
});

// Using the handler
$handler = fetch_client()->getHandler();
$results = $handler->awaitPromise($handler->all([
    'users' => $usersPromise,
    'posts' => $postsPromise
]));
```

If you use numeric keys, the results will be returned in the same order:

```php
all([
    $usersPromise,
    $postsPromise,
    $commentsPromise
])->then(function ($results) {
    // $results is an indexed array
    $users = $results[0]->json();
    $posts = $results[1]->json();
    $comments = $results[2]->json();
});
```

### race()

The `race()` function waits for the first promise to settle (resolve or reject):

```php
// Create promises for redundant endpoints
$promises = [
    async(fn() => fetch('https://api1.example.com/data')),
    async(fn() => fetch('https://api2.example.com/data')),
    async(fn() => fetch('https://api3.example.com/data'))
];

// Get the result from whichever completes first (success or failure)
race($promises)
    ->then(function ($response) {
        $data = $response->json();
        echo "Got data from the fastest source";
    });

// Using the handler
$handler = fetch_client()->getHandler();
$result = $handler->awaitPromise($handler->race($promises));
```

### any()

The `any()` function waits for the first promise to resolve, ignoring rejections unless all promises reject:

```php
// Create promises with some that might fail
$promises = [
    async(fn() => fetch('https://api1.example.com/data')), // Might fail
    async(fn() => fetch('https://api2.example.com/data')), // Might fail
    async(fn() => fetch('https://api3.example.com/data'))
];

// Get the first successful result
any($promises)
    ->then(function ($response) {
        $data = $response->json();
        echo "Got data from the first successful source";
    })
    ->catch(function ($errors) {
        echo "All requests failed!";
    });

// Using the handler
$handler = fetch_client()->getHandler();
try {
    $result = $handler->awaitPromise($handler->any($promises));
} catch (\Exception $e) {
    echo "All requests failed!";
}
```

### Using await() with Promise Combinators

You can also use `await()` with the promise combinators for a more synchronous-looking code:

```php
await(async(function() {
    // Wait for multiple promises with all()
    $results = await(all([
        'users' => async(fn() => fetch('https://api.example.com/users')),
        'posts' => async(fn() => fetch('https://api.example.com/posts'))
    ]));

    $users = $results['users']->json();
    $posts = $results['posts']->json();

    echo "Fetched " . count($users) . " users and " . count($posts) . " posts";
}));
```

## Sequential Operations

You can perform sequential asynchronous operations using `await()`:

```php
await(async(function() {
    // First request: get auth token
    $authResponse = await(async(fn() =>
        fetch('https://api.example.com/auth/login', [
            'method' => 'POST',
            'json' => [
                'username' => 'user',
                'password' => 'pass'
            ]
        ])
    ));

    $token = $authResponse->json()['token'];

    // Second request: use token to get user data
    $userResponse = await(async(fn() =>
        fetch('https://api.example.com/me', [
            'token' => $token
        ])
    ));

    return $userResponse->json();
}));
```

## Controlled Concurrency with map()

The `map()` function applies an async function to each item in an array with controlled concurrency:

```php
use function Matrix\Support\map;

// List of user IDs
$userIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

// Fetch user details for each ID, with at most 3 concurrent requests
$responses = await(map($userIds, function ($userId) {
    return async(function() use ($userId) {
        return fetch("https://api.example.com/users/{$userId}");
    });
}, 3));

$users = [];
foreach ($responses as $response) {
    $users[] = $response->json();
}

echo "Fetched details for " . count($users) . " users";
```

You can also use the handler's map method:

```php
$handler = fetch_client()->getHandler();
$responses = $handler->awaitPromise($handler->map($userIds, function($id) use ($handler) {
    return $handler->wrapAsync(function() use ($id) {
        return fetch("https://api.example.com/users/{$id}");
    });
}, 3));
```

## Batch Processing

For processing items in batches rather than one at a time:

```php
use function Matrix\Support\batch;

// List of user IDs
$userIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];

// Process in batches of 5 with max 2 concurrent batches
$results = await(batch(
    $userIds,
    function ($batchOfIds) {
        return async(function() use ($batchOfIds) {
            $queryString = implode(',', $batchOfIds);
            return fetch("https://api.example.com/users?ids={$queryString}");
        });
    },
    5,   // Batch size
    2    // Concurrency
));

// Process results from each batch
foreach ($results as $response) {
    $batchUsers = $response->json();
    echo "Processed batch with " . count($batchUsers) . " users\n";
}
```

## Timeout Handling

You can add timeouts to promises:

```php
use function Matrix\Support\timeout;

try {
    // Add a 5-second timeout to a request
    $response = await(timeout(
        async(fn() => fetch('https://api.example.com/slow-endpoint')),
        5.0
    ));

    $data = $response->json();
} catch (\Matrix\Exceptions\TimeoutException $e) {
    echo "Timeout occurred: " . $e->getMessage();
}

// Or using the handler
$handler = fetch_client()->getHandler();
try {
    $response = $handler->awaitPromise($promise, 5.0); // 5 second timeout
} catch (\RuntimeException $e) {
    echo "Timeout: " . $e->getMessage();
}
```

## Retry Handling

For operations that might fail, you can use the `retry()` function:

```php
use function Matrix\Support\retry;

$result = await(retry(
    function() {
        return async(function() {
            return fetch('https://api.example.com/unstable-endpoint');
        });
    },
    3,  // Max attempts
    function ($attempt) {
        // Exponential backoff strategy
        return min(pow(2, $attempt) * 100, 1000);
    }
));

// Process the successful response
$data = $result->json();
```

## Advanced Promise Patterns

### Promise Chaining

You can chain promises to transform values or perform sequential operations:

```php
async(function() {
    return fetch('https://api.example.com/users');
})
->then(function ($response) {
    return $response->json();
})
->then(function ($users) {
    // Filter users
    return array_filter($users, function ($user) {
        return $user['active'] === true;
    });
})
->then(function ($activeUsers) {
    // Extract emails
    return array_map(function ($user) {
        return $user['email'];
    }, $activeUsers);
})
->then(function ($emails) {
    echo "Active user emails: " . implode(', ', $emails);
});
```

### Error Handling with try/catch

Using `await()` allows for traditional try/catch error handling:

```php
await(async(function() {
    try {
        $response = await(async(function() {
            return fetch('https://api.example.com/users');
        }));

        if ($response->failed()) {
            throw new \Exception("API error: " . $response->status());
        }

        $users = $response->json();

        if (empty($users)) {
            throw new \Exception("No users found");
        }

        return $users[0]['name'];
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage();
        return "Unknown user";
    }
}));
```

### Dynamic Promise Creation

You can create promises dynamically based on previous results:

```php
await(async(function() {
    // Get all users
    $usersResponse = await(async(function() {
        return fetch('https://api.example.com/users');
    }));

    $users = $usersResponse->json();

    // Create an array of promises for each user's posts
    $promises = [];
    foreach ($users as $user) {
        $userId = $user['id'];
        $promises[$userId] = async(function() use ($userId) {
            return fetch("https://api.example.com/users/{$userId}/posts");
        });
    }

    // Execute all promises concurrently
    $postResponses = await(all($promises));

    // Process the results
    $userPosts = [];
    foreach ($postResponses as $userId => $response) {
        $userPosts[$userId] = $response->json();
    }

    return $userPosts;
}));
```

## Working with Specific Response Methods

The `Response` class in Fetch PHP provides many helpful methods that work well with promises:

```php
await(async(function() {
    $response = await(async(fn() => fetch('https://api.example.com/users/1')));

    // Check if successful
    if ($response->successful()) {
        $user = $response->json();
        echo "User: {$user['name']}\n";

        // Check specific properties
        if ($response->hasJsonContent()) {
            // Content is JSON
        }

        // Check status codes
        if ($response->isOk()) {
            // Status is 200 OK
        } else if ($response->isNotFound()) {
            // Status is 404 Not Found
        }
    }
}));
```

## Best Practices

1. **Use async/await for Readability**: The async/await pattern makes asynchronous code more readable by making it look like synchronous code.

   ```php
   // Instead of nested then() callbacks:
   await(async(function() {
       $response = await(async(fn() => fetch('https://api.example.com/users')));
       $users = $response->json();
       // Process users directly
   }));
   ```

2. **Always Handle Errors**: Use try/catch with await or catch() with promises to handle errors.

3. **Avoid Nesting**: Use async/await to avoid the "callback hell" or "pyramid of doom" problem.

4. **Manage Concurrency**: Use `map()` or `batch()` with reasonable concurrency limits to avoid server overload.

5. **Control Timeouts**: Set appropriate timeouts with the `timeout()` function to prevent operations from hanging.

6. **Use Promise Combinators**: Leverage `all()`, `race()`, and `any()` for managing multiple concurrent operations.

7. **Use Type-Safe Enums with Responses**: Take advantage of response methods like `statusEnum()` for type safety.

   ```php
   use Fetch\Enum\Status;

   await(async(function() {
       $response = await(async(fn() => fetch('https://api.example.com/users')));

       if ($response->statusEnum() === Status::OK) {
           // Status is exactly 200 OK
       }
   }));
   ```

8. **Consider Memory Usage**: Be mindful of memory usage when working with large datasets.

## Debugging Async/Await Code

Debugging asynchronous code can be challenging. Here are some tips:

1. **Break Complex Operations**: Split complex async operations into smaller steps.

2. **Add Logging**: Log interim results to track the flow of execution.

   ```php
   await(async(function() {
       echo "Fetching users...\n";
       $response = await(async(fn() => fetch('https://api.example.com/users')));

       echo "Processing response...\n";
       $users = $response->json();

       echo "Found " . count($users) . " users\n";
       return $users;
   }));
   ```

3. **Use try/catch Blocks**: Place try/catch blocks around specific operations to catch errors at their source.

4. **Check Promise States**: If things aren't working as expected, check if promises are resolving or rejecting.

5. **Use the Handler's Debug Method**: The ClientHandler's `debug()` method can provide useful information.

   ```php
   $handler = fetch_client()->getHandler();
   $debugInfo = $handler->debug();
   print_r($debugInfo);
   ```

## Next Steps

- Explore [Asynchronous Requests](/guide/async-requests) for practical examples
- Learn about [Error Handling](/guide/error-handling) in asynchronous code
- See [Retry Handling](/guide/retry-handling) for making async requests more resilient
- Check out [Type-Safe Enums](/guide/working-with-enums) for working with HTTP concepts
