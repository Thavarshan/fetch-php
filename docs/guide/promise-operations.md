---
title: Promise Operations
description: Learn how to work with promises in the Fetch HTTP package
---

# Promise Operations

This guide covers the various promise operations available in the Fetch HTTP package for working with asynchronous requests. The package implements a Promise-based API similar to JavaScript promises, allowing for sophisticated asynchronous programming patterns.

## Basic Promise Concepts

Promises represent values that may not be available yet. They're used for asynchronous operations like HTTP requests. In the Fetch HTTP package, promises are represented by the `PromiseInterface`.

## Creating Promises

There are several ways to create promises:

```php
use Fetch\Http\ClientHandler;

$client = new ClientHandler();

// Create a promise from an async HTTP request
$promise = $client->async()->get('https://api.example.com/users');

// Create a resolved promise with a value
$resolvedPromise = $client->resolve(['name' => 'John', 'email' => 'john@example.com']);

// Create a rejected promise with an error
$rejectedPromise = $client->reject(new \Exception('Something went wrong'));

// Wrap a callable to run asynchronously
$customPromise = $client->wrapAsync(function () {
    // Perform some operation
    $result = ['status' => 'success'];
    return $result;
});
```

## Promise Methods

### then()

The `then()` method is used to register callbacks for when a promise resolves successfully or fails:

```php
$promise = $client->async()->get('https://api.example.com/users');

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
$promise = $client->async()->get('https://api.example.com/users');

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
$promise = $client->async()->get('https://api.example.com/users');

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

## Combining Multiple Promises

The Fetch HTTP package provides several methods for working with multiple promises:

### all()

The `all()` method waits for all promises to resolve, or rejects if any promise fails:

```php
// Create multiple promises
$usersPromise = $client->async()->get('https://api.example.com/users');
$postsPromise = $client->async()->get('https://api.example.com/posts');
$commentsPromise = $client->async()->get('https://api.example.com/comments');

// Wait for all to complete
$client->all([
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
```

If you use numeric keys, the results will be returned in the same order:

```php
$client->all([
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

The `race()` method waits for the first promise to settle (resolve or reject):

```php
// Create promises for redundant endpoints
$promises = [
    $client->async()->get('https://api1.example.com/data'),
    $client->async()->get('https://api2.example.com/data'),
    $client->async()->get('https://api3.example.com/data')
];

// Get the result from whichever completes first (success or failure)
$client->race($promises)
    ->then(function ($response) {
        $data = $response->json();
        echo "Got data from the fastest source";
    });
```

### any()

The `any()` method waits for the first promise to resolve, ignoring rejections unless all promises reject:

```php
// Create promises with some that might fail
$promises = [
    $client->async()->get('https://api1.example.com/data'), // Might fail
    $client->async()->get('https://api2.example.com/data'), // Might fail
    $client->async()->get('https://api3.example.com/data')
];

// Get the first successful result
$client->any($promises)
    ->then(function ($response) {
        $data = $response->json();
        echo "Got data from the first successful source";
    })
    ->catch(function ($errors) {
        echo "All requests failed!";
    });
```

### sequence()

The `sequence()` method executes promises in sequence, with each promise potentially depending on the result of the previous one:

```php
$client->sequence([
    // First function - returns a promise
    function () use ($client) {
        return $client->async()->get('https://api.example.com/users');
    },

    // Second function - uses result from first promise
    function ($usersResponse) use ($client) {
        $users = $usersResponse->json();
        $firstUserId = $users[0]['id'];

        return $client->async()->get("https://api.example.com/users/{$firstUserId}/posts");
    },

    // Third function - uses result from second promise
    function ($postsResponse) use ($client) {
        $posts = $postsResponse->json();
        $firstPostId = $posts[0]['id'];

        return $client->async()->get("https://api.example.com/posts/{$firstPostId}/comments");
    }
])->then(function ($results) {
    // $results is an array containing the response from each promise
    $users = $results[0]->json();
    $posts = $results[1]->json();
    $comments = $results[2]->json();

    echo "Fetched " . count($users) . " users, " .
         count($posts) . " posts for first user, and " .
         count($comments) . " comments for first post";
});
```

### map()

The `map()` method applies a function to each item in an array, which returns a promise, with controlled concurrency:

```php
// List of user IDs
$userIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

// Fetch user details for each ID, with at most 3 concurrent requests
$client->map($userIds, function ($userId) use ($client) {
    return $client->async()->get("https://api.example.com/users/{$userId}");
}, 3)->then(function ($responses) {
    $users = [];
    foreach ($responses as $userId => $response) {
        $users[] = $response->json();
    }

    echo "Fetched details for " . count($users) . " users";
    return $users;
});
```

## Waiting for Promises

### awaitPromise()

The `awaitPromise()` method waits for a promise to resolve and returns its value:

```php
// Create a promise
$promise = $client->async()->get('https://api.example.com/users');

// Do other work...

// Wait for the promise to resolve
try {
    $response = $client->awaitPromise($promise);
    $users = $response->json();
    echo "Fetched " . count($users) . " users";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
```

You can also set a timeout:

```php
try {
    // Wait for at most 5 seconds
    $response = $client->awaitPromise($promise, 5.0);
    $users = $response->json();
} catch (\RuntimeException $e) {
    echo "Request timed out or failed: " . $e->getMessage();
}
```

## Advanced Promise Patterns

### Promise Chaining

You can chain promises to transform values or perform sequential operations:

```php
$client->async()
    ->get('https://api.example.com/users')
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

### Error Propagation

Errors propagate through promise chains until caught:

```php
$client->async()
    ->get('https://api.example.com/users')
    ->then(function ($response) {
        if ($response->failed()) {
            throw new \Exception("API error: " . $response->status());
        }
        return $response->json();
    })
    ->then(function ($users) {
        if (empty($users)) {
            throw new \Exception("No users found");
        }
        return $users[0];
    })
    ->then(function ($firstUser) {
        echo "First user: " . $firstUser['name'];
    })
    ->catch(function ($error) {
        // This catches any error thrown in any of the previous promises
        echo "Error: " . $error->getMessage();
    });
```

### Dynamic Promise Creation

You can create promises dynamically based on previous results:

```php
$client->async()
    ->get('https://api.example.com/users')
    ->then(function ($response) use ($client) {
        $users = $response->json();

        // Create an array of promises for each user
        $promises = [];
        foreach ($users as $user) {
            $promises[$user['id']] = $client->async()
                ->get("https://api.example.com/users/{$user['id']}/posts");
        }

        // Execute all the promises
        return $client->all($promises);
    })
    ->then(function ($userPostsResponses) {
        $userPosts = [];
        foreach ($userPostsResponses as $userId => $response) {
            $userPosts[$userId] = $response->json();
        }

        return $userPosts;
    })
    ->then(function ($userPosts) {
        // Process all users' posts
        foreach ($userPosts as $userId => $posts) {
            echo "User {$userId} has " . count($posts) . " posts\n";
        }
    });
```

## Best Practices

1. **Always Handle Errors**: Use `catch()` to handle errors in promise chains.

2. **Return Values**: Always return values from promise callbacks to pass them to the next step.

3. **Avoid Nesting**: Instead of nesting promises, use chaining for better readability.

4. **Manage Concurrency**: Use `map()` with a reasonable concurrency limit to avoid overloading servers.

5. **Consider Memory Usage**: When working with large datasets, be mindful of memory usage when all promises resolve.

6. **Timeout Management**: Set appropriate timeouts with `awaitPromise()` to avoid hanging operations.

## Debugging Promises

Debugging asynchronous code can be challenging. Here are some tips:

1. **Log at Each Step**:

```php
$client->async()
    ->get('https://api.example.com/users')
    ->then(function ($response) {
        echo "Response received: " . $response->status() . "\n";
        return $response->json();
    })
    ->then(function ($users) {
        echo "Parsed users: " . count($users) . "\n";
        return $users;
    });
```

2. **Use finally() for Logging**:

```php
$client->async()
    ->get('https://api.example.com/users')
    ->finally(function () {
        echo "Request completed at: " . date('Y-m-d H:i:s') . "\n";
    });
```

3. **Handle Rejections Explicitly**:

```php
$promise->then(
    function ($value) {
        echo "Success: ";
        print_r($value);
    },
    function ($reason) {
        echo "Failure: ";
        print_r($reason);
    }
);
```

## Next Steps

- Explore [Asynchronous Requests](/guide/async-requests) for practical examples
- Learn about [Error Handling](/guide/error-handling) in asynchronous code
