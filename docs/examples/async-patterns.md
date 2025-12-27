---
title: Async Patterns Examples
description: Examples of advanced asynchronous request patterns using the Fetch HTTP package
---

# Async Patterns Examples

This page demonstrates advanced asynchronous patterns for making concurrent and sequential HTTP requests with the Fetch HTTP package.

## Basic Async Requests

Making simple asynchronous requests:

```php
use function fetch;
use function Matrix\Support\async;
use function Matrix\Support\await;

// Create an async function
$fetchUsers = async(function() {
    return fetch('https://api.example.com/users');
});

// Execute the async function and wait for the result
$response = await($fetchUsers);
$users = $response->json();

echo "Fetched " . count($users) . " users";
```

## Parallel Requests

Making multiple requests in parallel:

```php
use function fetch;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\all;

// Create async functions for different endpoints
$fetchUsers = async(function() {
    return fetch('https://api.example.com/users');
});

$fetchPosts = async(function() {
    return fetch('https://api.example.com/posts');
});

$fetchComments = async(function() {
    return fetch('https://api.example.com/comments');
});

// Execute all requests in parallel
$results = await(all([
    'users' => $fetchUsers,
    'posts' => $fetchPosts,
    'comments' => $fetchComments
]));

// Process the results
$users = $results['users']->json();
$posts = $results['posts']->json();
$comments = $results['comments']->json();

echo "Fetched " . count($users) . " users, " .
     count($posts) . " posts, and " .
     count($comments) . " comments";
```

## Sequential Requests with Dependencies

Making sequential requests where each depends on the result of the previous one:

```php
use function fetch;
use function Matrix\Support\async;
use function Matrix\Support\await;

await(async(function() {
    // First, get a list of users
    $usersResponse = await(async(function() {
        return fetch('https://api.example.com/users');
    }));

    $users = $usersResponse->json();

    // Get the first user's ID
    $userId = $users[0]['id'];

    // Then, fetch posts for that user
    $postsResponse = await(async(function() use ($userId) {
        return fetch("https://api.example.com/users/{$userId}/posts");
    }));

    $posts = $postsResponse->json();

    // Get the first post's ID
    $postId = $posts[0]['id'];

    // Finally, fetch comments for that post
    $commentsResponse = await(async(function() use ($postId) {
        return fetch("https://api.example.com/posts/{$postId}/comments");
    }));

    $comments = $commentsResponse->json();

    return [
        'user' => $users[0],
        'posts' => $posts,
        'comments' => $comments
    ];
}));
```

## Racing Requests (First to Complete)

Using `race()` to get the result from whichever request finishes first:

```php
use function fetch;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\race;

// Create promises for multiple mirror servers
$promises = [
    async(fn() => fetch('https://mirror1.example.com/resource')),
    async(fn() => fetch('https://mirror2.example.com/resource')),
    async(fn() => fetch('https://mirror3.example.com/resource'))
];

try {
    // Get the response from whichever server responds first
    $response = await(race($promises));

    if ($response->successful()) {
        $data = $response->json();
        echo "Got data from the fastest mirror";
    } else {
        echo "The fastest mirror returned an error: " . $response->status();
    }
} catch (\Exception $e) {
    echo "All mirrors failed: " . $e->getMessage();
}
```

## First Successful Request

Using `any()` to get the first successful result, ignoring failures:

```php
use function fetch;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\any;

// Create promises for redundant endpoints
$promises = [
    async(fn() => fetch('https://api1.example.com/data')),  // Might fail
    async(fn() => fetch('https://api2.example.com/data')),  // Might fail
    async(fn() => fetch('https://api3.example.com/data'))   // Should work
];

try {
    // Get the first successful response
    $response = await(any($promises));
    $data = $response->json();
    echo "Got data from a working endpoint";
} catch (\Exception $e) {
    echo "All endpoints failed: " . $e->getMessage();
}
```

## Controlled Concurrency with map()

Process many items concurrently, but limit how many run at once:

```php
use function fetch;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\map;

// List of user IDs to process
$userIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

// Process up to 3 requests concurrently
$results = await(map($userIds, function($userId) {
    return async(function() use ($userId) {
        $response = fetch("https://api.example.com/users/{$userId}");

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to fetch user {$userId}");
        }

        return $response->json();
    });
}, 3));

echo "Successfully fetched " . count($results) . " users";

// Process the results
foreach ($results as $index => $user) {
    echo "User {$index}: {$user['name']}\n";
}
```

## Batch Processing

Process items in batches rather than individually:

```php
use function fetch;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\batch;

// List of item IDs to process
$itemIds = range(1, 100);

// Process in batches of 10 with max 2 concurrent batches
$batchResults = await(batch(
    $itemIds,
    function($batchIds) {
        return async(function() use ($batchIds) {
            // Convert array to comma-separated string
            $idString = implode(',', $batchIds);

            // Fetch multiple items in a single request
            $response = fetch("https://api.example.com/items?ids={$idString}");

            if (!$response->successful()) {
                throw new \RuntimeException(
                    "Failed to fetch batch: " . $response->status()
                );
            }

            return $response->json();
        });
    },
    10,  // Batch size
    2    // Concurrency
));

// Flatten results
$allItems = [];
foreach ($batchResults as $batchItems) {
    $allItems = array_merge($allItems, $batchItems);
}

echo "Fetched " . count($allItems) . " items in batches";
```

## Timeout Handling

Adding timeouts to async operations:

```php
use function fetch;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\timeout;

try {
    // Add a 5-second timeout to a potentially slow request
    $response = await(timeout(
        async(fn() => fetch('https://api.example.com/slow-operation')),
        5.0,
        "Request timed out after 5 seconds"
    ));

    $data = $response->json();
    echo "Operation completed successfully";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Retry Logic

Implementing retry logic with async requests:

```php
use function fetch;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\retry;
use function Matrix\Support\delay;

// Define a function that might fail
$fetchData = function() {
    return async(function() {
        $response = fetch('https://api.example.com/unstable-endpoint');

        if (!$response->successful()) {
            throw new \RuntimeException(
                "API error: " . $response->status()
            );
        }

        return $response->json();
    });
};

// Retry the function up to 3 times with exponential backoff
try {
    $data = await(retry(
        $fetchData,
        3,  // Max attempts
        function($attempt, $error) {
            // Define backoff strategy
            if ($attempt >= 3) {
                return null; // Stop retrying
            }

            // Exponential backoff: 0.5s, 1s, 2s
            return pow(2, $attempt - 1) * 0.5;
        }
    ));

    echo "Successfully fetched data after retries";
} catch (\Exception $e) {
    echo "Failed after all retry attempts: " . $e->getMessage();
}
```

## Rate Limiting

Implementing a rate-limited API client:

```php
use function fetch;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\delay;

class RateLimitedClient
{
    private int $requestsPerSecond;
    private float $minTimeBetweenRequests;
    private ?float $lastRequestTime = null;

    public function __construct(int $requestsPerSecond = 5)
    {
        $this->requestsPerSecond = $requestsPerSecond;
        $this->minTimeBetweenRequests = 1.0 / $requestsPerSecond;
    }

    public async function request(string $url)
    {
        // Check if we need to wait
        if ($this->lastRequestTime !== null) {
            $timeSinceLastRequest = microtime(true) - $this->lastRequestTime;
            $timeToWait = $this->minTimeBetweenRequests - $timeSinceLastRequest;

            if ($timeToWait > 0) {
                // Wait before making the next request
                await(delay($timeToWait));
            }
        }

        // Record request time and make the request
        $this->lastRequestTime = microtime(true);
        return fetch($url);
    }

    public async function batchProcess(array $urls)
    {
        $results = [];

        foreach ($urls as $url) {
            $response = await($this->request($url));
            $results[] = $response->json();
        }

        return $results;
    }
}

// Usage
await(async(function() {
    $client = new RateLimitedClient(5); // 5 requests per second

    $urls = [
        'https://api.example.com/resource1',
        'https://api.example.com/resource2',
        'https://api.example.com/resource3',
        'https://api.example.com/resource4',
        'https://api.example.com/resource5'
    ];

    $results = await($client->batchProcess($urls));

    echo "Processed " . count($results) . " resources with rate limiting";
}));
```

## Pagination with Async

Handling paginated API results with async requests:

```php
use function fetch;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\all;

await(async(function() {
    // Get the first page to determine total pages
    $firstPageResponse = await(async(function() {
        return fetch('https://api.example.com/posts?page=1&per_page=50');
    }));

    $firstPage = $firstPageResponse->json();
    $totalItems = (int)$firstPageResponse->header('X-Total-Count');
    $perPage = 50;
    $totalPages = ceil($totalItems / $perPage);

    echo "Found {$totalItems} total items across {$totalPages} pages\n";

    // Create an async request for each additional page
    $pagePromises = [];
    for ($page = 2; $page <= $totalPages; $page++) {
        $pagePromises[$page] = async(function() use ($page, $perPage) {
            return fetch("https://api.example.com/posts?page={$page}&per_page={$perPage}");
        });
    }

    // Fetch all pages concurrently
    $pageResponses = await(all($pagePromises));

    // Combine all results
    $allPosts = $firstPage;
    foreach ($pageResponses as $response) {
        $allPosts = array_merge($allPosts, $response->json());
    }

    echo "Successfully fetched all {$totalItems} posts";
    return $allPosts;
}));
```

## Dependency Graph Execution

Execute requests with complex dependencies:

```php
use function fetch;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\all;

await(async(function() {
    // First level: fetch user and categories in parallel
    $results = await(all([
        'user' => async(fn() => fetch('https://api.example.com/user/123')),
        'categories' => async(fn() => fetch('https://api.example.com/categories'))
    ]));

    $user = $results['user']->json();
    $categories = $results['categories']->json();

    // Second level: fetch posts and recommended products based on user preferences
    $secondLevelResults = await(all([
        'posts' => async(function() use ($user) {
            $interestIds = implode(',', $user['interests']);
            return fetch("https://api.example.com/posts?interests={$interestIds}");
        }),
        'products' => async(function() use ($user, $categories) {
            $categoryIds = array_column($categories, 'id');
            $preferredCategories = array_intersect($categoryIds, $user['preferred_categories']);
            $categoryParam = implode(',', $preferredCategories);
            return fetch("https://api.example.com/products?categories={$categoryParam}");
        })
    ]));

    $posts = $secondLevelResults['posts']->json();
    $products = $secondLevelResults['products']->json();

    // Final level: get comments for the first post
    if (!empty($posts)) {
        $firstPostId = $posts[0]['id'];
        $commentsResponse = await(async(function() use ($firstPostId) {
            return fetch("https://api.example.com/posts/{$firstPostId}/comments");
        }));

        $comments = $commentsResponse->json();
    } else {
        $comments = [];
    }

    return [
        'user' => $user,
        'categories' => $categories,
        'posts' => $posts,
        'products' => $products,
        'comments' => $comments
    ];
}));
```

## Error Handling Patterns

Robust error handling for async requests:

```php
use function fetch;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\all;

await(async(function() {
    try {
        // Attempt to fetch data
        $response = await(async(function() {
            return fetch('https://api.example.com/users');
        }));

        if (!$response->successful()) {
            // Handle HTTP errors
            switch ($response->status()) {
                case 401:
                    throw new \RuntimeException("Authentication required");
                case 403:
                    throw new \RuntimeException("Permission denied");
                case 404:
                    throw new \RuntimeException("Resource not found");
                case 429:
                    throw new \RuntimeException("Rate limit exceeded");
                default:
                    throw new \RuntimeException(
                        "API error: " . $response->status() . " " . $response->body()
                    );
            }
        }

        return $response->json();
    } catch (\Exception $e) {
        // Log the error
        error_log("API error: " . $e->getMessage());

        // Attempt to use a fallback source
        try {
            $fallbackResponse = await(async(function() {
                return fetch('https://fallback-api.example.com/users');
            }));

            if ($fallbackResponse->successful()) {
                return $fallbackResponse->json();
            }
        } catch (\Exception $fallbackError) {
            error_log("Fallback API also failed: " . $fallbackError->getMessage());
        }

        // Return cached data or default value as last resort
        return getCachedUsers() ?? [];
    }
}));
```

## Async Cache Access

Using async requests with a caching layer:

```php
use function fetch;
use function Matrix\Support\async;
use function Matrix\Support\await;

class AsyncCachedAPI
{
    private $cache;
    private int $cacheTtl;

    public function __construct($cache, int $cacheTtl = 3600)
    {
        $this->cache = $cache;
        $this->cacheTtl = $cacheTtl;
    }

    public async function get(string $url)
    {
        $cacheKey = 'api_' . md5($url);

        // Try to get from cache first
        $cachedData = $this->cache->get($cacheKey);

        if ($cachedData !== null) {
            return json_decode($cachedData, true);
        }

        // Not in cache, fetch from API
        $response = await(async(function() use ($url) {
            return fetch($url);
        }));

        if (!$response->successful()) {
            throw new \RuntimeException(
                "API error: " . $response->status() . " " . $response->body()
            );
        }

        $data = $response->json();

        // Store in cache
        $this->cache->set($cacheKey, json_encode($data), $this->cacheTtl);

        return $data;
    }
}

// Usage with a PSR-16 compatible cache
await(async(function() use ($cache) {
    $api = new AsyncCachedAPI($cache);

    try {
        $users = await($api->get('https://api.example.com/users'));
        $posts = await($api->get('https://api.example.com/posts'));

        echo "Fetched " . count($users) . " users and " . count($posts) . " posts";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}));
```

## Next Steps

- Check out [API Integration Examples](/examples/api-integration) for real-world API integration patterns
- Explore [Error Handling](/examples/error-handling) for more robust error handling strategies
- See [Authentication](/examples/authentication) for authentication examples
