---
title: Asynchronous Requests
description: Learn how to make asynchronous HTTP requests with the Fetch HTTP package
---

# Asynchronous Requests

This guide explains how to work with asynchronous HTTP requests in the Fetch HTTP package. Asynchronous requests allow you to execute multiple HTTP operations in parallel, which can significantly improve performance when making multiple independent requests.

## Understanding Async/Await in PHP

Fetch PHP brings JavaScript-like async/await patterns to PHP through the Matrix library, which is integrated into the package. While PHP doesn't have native language support for async/await, the package provides functions that enable similar functionality.

The key functions for async operations are:

- `async()` - Wraps a function to run asynchronously, returning a Promise
- `await()` - Waits for a Promise to resolve and returns its value
- `all()` - Runs multiple Promises concurrently and waits for all to complete
- `race()` - Runs multiple Promises concurrently and returns the first to complete
- `any()` - Returns the first Promise to successfully resolve

## Making Asynchronous Requests

To make asynchronous requests, wrap your `fetch()` calls with the `async()` function:

```php
// From `jerome/matrix` included in Fetch PHP
use function async;
use function await;
use function all;

// Create a promise for an async request
$promise = async(function() {
    return fetch('https://api.example.com/users');
});

// Wait for the promise to resolve
$response = await($promise);
$users = $response->json();
```

## Multiple Concurrent Requests

One of the main benefits of async requests is the ability to execute multiple HTTP requests in parallel:

```php
// Create promises for multiple requests
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
$results = await(all([
    'users' => $usersPromise,
    'posts' => $postsPromise,
    'comments' => $commentsPromise
]));

// Process the results
$users = $results['users']->json();
$posts = $results['posts']->json();
$comments = $results['comments']->json();

echo "Fetched " . count($users) . " users, " .
     count($posts) . " posts, and " .
     count($comments) . " comments";
```

## Promise Chaining

You can chain operations to be executed when a promise resolves:

```php
async(function() {
    return fetch('https://api.example.com/users');
})
->then(function($response) {
    // This runs when the request succeeds
    $users = $response->json();
    echo "Fetched " . count($users) . " users";
    return $users;
})
->then(function($users) {
    // Process the users
    return array_map(function($user) {
        return $user['name'];
    }, $users);
})
->then(function($userNames) {
    echo "User names: " . implode(', ', $userNames);
});
```

## Error Handling

Handle errors in asynchronous code with try/catch in an async function or the `catch()` method:

```php
// Using try/catch with await
async(function() {
    try {
        $response = await(async(function() {
            return fetch('https://api.example.com/users/999');
        }));

        if ($response->isNotFound()) {
            throw new \Exception("User not found");
        }

        return $response->json();
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
});

// Using catch() with promises
async(function() {
    return fetch('https://api.example.com/users/999');
})
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

## Using `race()` to Get the First Result

Sometimes you may want whichever request finishes first:

```php
// Create promises for redundant endpoints
$promises = [
    async(fn() => fetch('https://api1.example.com/data')),
    async(fn() => fetch('https://api2.example.com/data')),
    async(fn() => fetch('https://api3.example.com/data'))
];

// Get the result from whichever completes first
$response = await(race($promises));
$data = $response->json();
echo "Got data from the fastest source";
```

## Using `any()` to Get the First Success

To get the first successful result (ignoring failures):

```php
// Create promises for redundant endpoints
$promises = [
    async(fn() => fetch('https://api1.example.com/data')),
    async(fn() => fetch('https://api2.example.com/data')),
    async(fn() => fetch('https://api3.example.com/data'))
];

// Get the first successful result
try {
    $response = await(any($promises));
    $data = $response->json();
    echo "Got data from the first successful source";
} catch (\Exception $e) {
    echo "All requests failed";
}
```

## Controlled Concurrency with `map()`

For processing many items with controlled parallelism:

```php
use function Matrix\map;

// List of user IDs to fetch
$userIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

// Process at most 3 requests at a time
$responses = await(map($userIds, function($id) {
    return async(function() use ($id) {
        return fetch("https://api.example.com/users/{$id}");
    });
}, 3));

// Process the responses
foreach ($responses as $id => $response) {
    $user = $response->json();
    echo "Processed user {$user['name']}\n";
}
```

## Sequential Async Requests

Sometimes you need to execute requests in sequence, where each depends on the previous:

```php
await(async(function() {
    // First request: get auth token
    $authResponse = await(async(function() {
        return fetch('https://api.example.com/auth/login', [
            'method' => 'POST',
            'json' => [
                'username' => 'user',
                'password' => 'pass'
            ]
        ]);
    }));

    $token = $authResponse->json()['token'];

    // Second request: use token to get user profile
    $profileResponse = await(async(function() use ($token) {
        return fetch('https://api.example.com/me', [
            'token' => $token
        ]);
    }));

    $user = $profileResponse->json();

    // Third request: get user's posts
    $postsResponse = await(async(function() use ($token, $user) {
        return fetch("https://api.example.com/users/{$user['id']}/posts", [
            'token' => $token
        ]);
    }));

    return $postsResponse->json();
}));
```

## Timeouts with async/await

You can set a timeout when waiting for a promise:

```php
use function Matrix\timeout;

try {
    // Add a 5-second timeout to a request
    $response = await(timeout(
        async(fn() => fetch('https://api.example.com/slow-endpoint')),
        5.0,
        "Request timed out after 5 seconds"
    ));

    $data = $response->json();
} catch (\Exception $e) {
    echo "Timeout error: " . $e->getMessage();
}
```

## Real-World Examples

### Fetching Related Resources

```php
await(async(function() {
    // Get a user and their related data in parallel
    $userId = 123;

    // First, get the user
    $userResponse = await(async(function() use ($userId) {
        return fetch("https://api.example.com/users/{$userId}");
    }));

    $user = $userResponse->json();

    // Then fetch posts and followers in parallel
    $related = await(all([
        'posts' => async(fn() => fetch("https://api.example.com/users/{$user['id']}/posts")),
        'followers' => async(fn() => fetch("https://api.example.com/users/{$user['id']}/followers"))
    ]));

    $data = [
        'user' => $user,
        'posts' => $related['posts']->json(),
        'followers' => $related['followers']->json()
    ];

    echo "User: {$data['user']['name']}\n";
    echo "Posts: " . count($data['posts']) . "\n";
    echo "Followers: " . count($data['followers']) . "\n";

    return $data;
}));
```

### Implementing Pagination with Async

```php
await(async(function() {
    $url = 'https://api.example.com/users';
    $perPage = 100;
    $allItems = [];

    // Get the first page
    $firstPageResponse = await(async(function() use ($url, $perPage) {
        return fetch("{$url}?per_page={$perPage}&page=1");
    }));

    $firstPageData = $firstPageResponse->json();
    $items = $firstPageData['items'] ?? $firstPageData;
    $allItems = array_merge($allItems, $items);

    $totalCount = (int)$firstPageResponse->header('X-Total-Count') ?? count($items);
    $totalPages = ceil($totalCount / $perPage);

    // If we have multiple pages, fetch them all in parallel
    if ($totalPages > 1) {
        $pagePromises = [];

        for ($page = 2; $page <= $totalPages; $page++) {
            $pagePromises[] = async(function() use ($url, $perPage, $page) {
                return fetch("{$url}?per_page={$perPage}&page={$page}");
            });
        }

        // Wait for all pages
        $pageResponses = await(all($pagePromises));

        // Process all responses
        foreach ($pageResponses as $response) {
            $pageData = $response->json();
            $pageItems = $pageData['items'] ?? $pageData;
            $allItems = array_merge($allItems, $pageItems);
        }
    }

    return $allItems;
}));
```

## Best Practices for Async Requests

1. **Use Async for Multiple Requests**: Asynchronous requests are most beneficial when making multiple independent HTTP requests.

2. **Control Concurrency**: Don't create too many concurrent requests. Use `map()` with a reasonable concurrency limit.

3. **Handle All Errors**: Always include error handling with try/catch or `.catch()` for async operations.

4. **Keep Functions Pure**: Avoid side effects in async functions for better predictability.

5. **Avoid Mixing Sync and Async**: When using async, make all your HTTP operations async for consistent code patterns.

6. **Be Mindful of Server Load**: While async lets you make many requests in parallel, be mindful of rate limits and server capacity.

## Next Steps

- Learn about [Promise Operations](/guide/promise-operations) for more advanced async patterns
- Explore [Error Handling](/guide/error-handling) for handling errors in async code
- See [Retry Handling](/guide/retry-handling) for making async requests more resilient
