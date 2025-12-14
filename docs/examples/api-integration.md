---
title: API Integration Examples
description: Examples of integrating with various APIs using the Fetch HTTP package
---

# API Integration Examples

This page demonstrates how to integrate with real-world APIs using the Fetch HTTP package. These examples follow best practices for API consumption and show common patterns for handling authentication, pagination, and other API-specific requirements.

## Creating an API Client Class

For organized API integration, it's often helpful to create a dedicated client class:

```php
class GitHubClient
{
    private string $baseUrl = 'https://api.github.com';
    private string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function getUser(string $username)
    {
        return $this->request("users/{$username}");
    }

    public function getRepositories(string $username)
    {
        return $this->request("users/{$username}/repos");
    }

    public function createIssue(string $owner, string $repo, array $data)
    {
        return $this->request("repos/{$owner}/{$repo}/issues", 'POST', $data);
    }

    private function request(string $endpoint, string $method = 'GET', array $data = null)
    {
        $url = "{$this->baseUrl}/{$endpoint}";
        $options = [
            'method' => $method,
            'headers' => [
                'Authorization' => "Bearer {$this->token}",
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'MyApp/1.0'
            ]
        ];

        if ($data !== null && ($method === 'POST' || $method === 'PUT' || $method === 'PATCH')) {
            $options['json'] = $data;
        } elseif ($data !== null) {
            $options['query'] = $data;
        }

        $response = fetch($url, $options);

        if ($response->failed()) {
            throw new \RuntimeException(
                "GitHub API error: " . $response->status() . " " . $response->json()['message'] ?? '',
                $response->status()
            );
        }

        return $response->json();
    }
}

// Usage
$github = new GitHubClient('your-github-token');
$user = $github->getUser('octocat');
$repos = $github->getRepositories('octocat');
$issue = $github->createIssue('octocat', 'Hello-World', [
    'title' => 'Bug report',
    'body' => 'I found a bug in the code',
    'labels' => ['bug', 'priority']
]);
```

## Working with Pagination

Many APIs use pagination for large result sets. Here's how to handle common pagination patterns:

### Offset-Based Pagination

```php
function getAllUsers()
{
    $allUsers = [];
    $page = 1;
    $perPage = 100;
    $hasMore = true;

    while ($hasMore) {
        $response = get('https://api.example.com/users', [
            'page' => $page,
            'per_page' => $perPage
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "Failed to fetch users: " . $response->status()
            );
        }

        $data = $response->json();
        $users = $data['data'] ?? $data;

        // Add this page of results to our collection
        $allUsers = array_merge($allUsers, $users);

        // Check if there are more pages
        $hasMore = count($users) === $perPage;
        $page++;

        // Avoid infinite loops
        if ($page > 100) {
            throw new \RuntimeException("Too many pages, possible infinite loop");
        }
    }

    return $allUsers;
}
```

### Cursor-Based Pagination

```php
function getAllOrders()
{
    $allOrders = [];
    $cursor = null;

    do {
        $queryParams = ['limit' => 100];
        if ($cursor) {
            $queryParams['after'] = $cursor;
        }

        $response = get('https://api.example.com/orders', $queryParams);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "Failed to fetch orders: " . $response->status()
            );
        }

        $data = $response->json();
        $orders = $data['data'] ?? $data;

        // Add this page of results to our collection
        $allOrders = array_merge($allOrders, $orders);

        // Get the next cursor
        $cursor = $data['pagination']['next_cursor'] ?? null;

    } while ($cursor);

    return $allOrders;
}
```

### Link Header Pagination (GitHub style)

```php
function getAllComments(string $repo, string $issueNumber)
{
    $allComments = [];
    $url = "https://api.github.com/repos/{$repo}/issues/{$issueNumber}/comments";

    while ($url) {
        $response = fetch($url, [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'MyApp/1.0'
            ]
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "Failed to fetch comments: " . $response->status()
            );
        }

        // Add this page of results to our collection
        $comments = $response->json();
        $allComments = array_merge($allComments, $comments);

        // Parse Link header to get the next page URL
        $linkHeader = $response->header('Link');
        $url = null;

        if ($linkHeader) {
            // Extract the "next" URL if it exists
            preg_match('/<([^>]*)>;\s*rel="next"/', $linkHeader, $matches);
            if (isset($matches[1])) {
                $url = $matches[1];
            }
        }
    }

    return $allComments;
}
```

## Handling Rate Limits

Many APIs implement rate limiting. Here's how to handle it:

```php
function fetchWithRateLimitHandling(string $url)
{
    $maxRetries = 5;
    $attempt = 0;

    while ($attempt < $maxRetries) {
        $response = fetch($url);

        if ($response->status() !== 429) {
            // Not rate limited, return the response
            return $response;
        }

        // We've been rate limited
        $attempt++;

        // Get retry-after header if available
        $retryAfter = $response->header('Retry-After');

        if ($retryAfter) {
            // Retry-After can be a timestamp or seconds
            $delay = is_numeric($retryAfter) ? (int)$retryAfter : (strtotime($retryAfter) - time());
            $delay = max(1, $delay); // Ensure at least 1 second
        } else {
            // Default exponential backoff
            $delay = pow(2, $attempt);
        }

        echo "Rate limited. Retrying in {$delay} seconds...\n";
        sleep($delay);
    }

    throw new \RuntimeException("Exceeded maximum retries for rate limiting");
}
```

## Webhook Processing Example

Handling incoming webhooks from an API:

```php
function processWebhook()
{
    // Get the raw POST data
    $payload = file_get_contents('php://input');

    // Verify the signature (example for GitHub webhooks)
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
    $secret = 'your-webhook-secret';

    $calculatedSignature = 'sha1=' . hash_hmac('sha1', $payload, $secret);
    if (!hash_equals($calculatedSignature, $signature)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid signature']);
        return;
    }

    // Parse the payload
    $data = json_decode($payload, true);
    $event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';

    // Process based on event type
    switch ($event) {
        case 'push':
            handlePushEvent($data);
            break;
        case 'pull_request':
            handlePullRequestEvent($data);
            break;
        // Handle other event types
        default:
            // Unknown event type
            http_response_code(400);
            echo json_encode(['error' => 'Unsupported event type']);
            return;
    }

    // Acknowledge receipt
    http_response_code(200);
    echo json_encode(['message' => 'Webhook processed successfully']);
}

function handlePushEvent(array $data)
{
    $repo = $data['repository']['full_name'];
    $branch = str_replace('refs/heads/', '', $data['ref']);
    $commits = $data['commits'];

    // Process the push event
    // ...

    // You might want to make API calls based on the webhook
    $response = get("https://api.github.com/repos/{$repo}/commits", [
        'sha' => $branch,
        'per_page' => 10
    ], [
        'headers' => [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'MyApp/1.0'
        ]
    ]);

    // Process the response
    // ...
}
```

## Working with GraphQL APIs

```php
function graphqlRequest(string $query, array $variables = [])
{
    $response = post('https://api.example.com/graphql', [
        'query' => $query,
        'variables' => $variables
    ], [
        'headers' => [
            'Authorization' => 'Bearer your-token',
            'Content-Type' => 'application/json'
        ]
    ]);

    if (!$response->successful()) {
        throw new \RuntimeException(
            "GraphQL request failed: " . $response->status()
        );
    }

    $data = $response->json();

    // Check for GraphQL errors
    if (isset($data['errors'])) {
        $errorMessages = array_map(function ($error) {
            return $error['message'];
        }, $data['errors']);

        throw new \RuntimeException(
            "GraphQL errors: " . implode(', ', $errorMessages)
        );
    }

    return $data['data'];
}

// Example usage
$query = '
    query GetUser($id: ID!) {
        user(id: $id) {
            id
            name
            email
            posts {
                title
                createdAt
            }
        }
    }
';

$data = graphqlRequest($query, ['id' => '123']);
$user = $data['user'];
```

## FetchWatch-Style Health Monitor

This example combines retries, shared pooling, caching, debug snapshots, and async batching to monitor several internal services in parallel.

```php
use Fetch\Cache\MemoryCache;
use Fetch\Http\ClientHandler;
use Fetch\Support\FetchProfiler;
use function async;
use function await;
use function map;

$handler = ClientHandler::create()
    ->baseUri('https://status.internal.example')
    ->withConnectionPool([
        'max_connections' => 100,
        'dns_cache_ttl' => 600,
    ])
    ->withCache(new MemoryCache(), [
        'default_ttl' => 60,
        'stale_if_error' => 30,
    ])
    ->retry(2, 200)
    ->withDebug([
        'response_body' => 512,
        'timing' => true,
        'memory' => true,
    ])
    ->withProfiler(new FetchProfiler);

$checks = [
    'api' => '/api/health',
    'billing' => '/billing/health',
    'notifications' => '/notifications/health',
];

$results = await(map($checks, function (string $path, string $service) use ($handler) {
    return async(function () use ($handler, $path, $service) {
        $response = $handler
            ->withQueryParameter('check', 'deep')
            ->get($path);

        return [
            'service' => $service,
            'status' => $response->successful() ? 'ok' : 'down',
            'payload' => $response->json(),
            'debug' => $response->getDebugInfo()?->toArray(['response_body' => 256]),
        ];
    });
}));

foreach ($results as $result) {
    printf(
        "[%s] %s (%s)\n",
        strtoupper($result['service']),
        $result['status'],
        $result['payload']['version'] ?? 'unknown'
    );

    if ($result['status'] !== 'ok' && $result['debug']) {
        // Persist debug snapshot for later inspection
        file_put_contents(
            __DIR__ . "/logs/{$result['service']}-last.json",
            json_encode($result['debug'], JSON_PRETTY_PRINT)
        );
    }
}
```

*Highlights:*

- **Retries** keep transient 5xx errors from triggering false alarms.
- **Connection pooling & DNS cache** minimize latency when hitting multiple services repeatedly.
- **Caching** allows the monitor to stay responsive even if a downstream service stalls (stale-if-error).
- **Debug snapshots + profiler** give you structured context (`X-Cache-Status`, timing, memory deltas) for any failing check.

## Integration with OAuth 2.0

```php
class OAuth2Client
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $tokenEndpoint;
    private string $authorizationEndpoint;
    private ?string $accessToken = null;
    private ?int $expiresAt = null;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $redirectUri,
        string $tokenEndpoint,
        string $authorizationEndpoint
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->tokenEndpoint = $tokenEndpoint;
        $this->authorizationEndpoint = $authorizationEndpoint;
    }

    public function getAuthorizationUrl(array $scopes = [], string $state = null): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes)
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return $this->authorizationEndpoint . '?' . http_build_query($params);
    }

    public function getAccessToken(string $code): array
    {
        $response = post($this->tokenEndpoint, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "Failed to get access token: " . $response->status() . " " . $response->body()
            );
        }

        $tokenData = $response->json();
        $this->accessToken = $tokenData['access_token'];

        if (isset($tokenData['expires_in'])) {
            $this->expiresAt = time() + $tokenData['expires_in'];
        }

        return $tokenData;
    }

    public function refreshToken(string $refreshToken): array
    {
        $response = post($this->tokenEndpoint, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "Failed to refresh token: " . $response->status() . " " . $response->body()
            );
        }

        $tokenData = $response->json();
        $this->accessToken = $tokenData['access_token'];

        if (isset($tokenData['expires_in'])) {
            $this->expiresAt = time() + $tokenData['expires_in'];
        }

        return $tokenData;
    }

    public function request(string $url, string $method = 'GET', array $data = null): array
    {
        if (!$this->accessToken) {
            throw new \RuntimeException("No access token available");
        }

        $options = [
            'method' => $method,
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}",
                'Accept' => 'application/json'
            ]
        ];

        if ($data !== null && ($method === 'POST' || $method === 'PUT' || $method === 'PATCH')) {
            $options['json'] = $data;
        } elseif ($data !== null) {
            $options['query'] = $data;
        }

        $response = fetch($url, $options);

        if ($response->status() === 401) {
            // Token might be expired
            throw new \RuntimeException("Unauthorized: Access token expired or invalid");
        }

        if (!$response->successful()) {
            throw new \RuntimeException(
                "API request failed: " . $response->status() . " " . $response->body()
            );
        }

        return $response->json();
    }
}

// Usage example (in a controller)
function oauthCallback()
{
    $code = $_GET['code'] ?? null;
    $state = $_GET['state'] ?? null;

    // Verify state to prevent CSRF
    if ($state !== $_SESSION['oauth_state']) {
        die('Invalid state parameter');
    }

    $oauth = new OAuth2Client(
        'your-client-id',
        'your-client-secret',
        'https://your-app.com/oauth/callback',
        'https://api.example.com/oauth/token',
        'https://api.example.com/oauth/authorize'
    );

    try {
        $tokenData = $oauth->getAccessToken($code);

        // Store tokens securely
        $_SESSION['access_token'] = $tokenData['access_token'];
        $_SESSION['refresh_token'] = $tokenData['refresh_token'];
        $_SESSION['expires_at'] = time() + $tokenData['expires_in'];

        // Fetch user profile with the token
        $user = $oauth->request('https://api.example.com/me');

        // Do something with the user data
        // ...

        // Redirect to dashboard
        header('Location: /dashboard');
        exit;

    } catch (\Exception $e) {
        die('Authentication error: ' . $e->getMessage());
    }
}
```

## Working with Multiple APIs

```php
class ApiManager
{
    private array $apis = [];

    public function register(string $name, $client): void
    {
        $this->apis[$name] = $client;
    }

    public function get(string $name)
    {
        if (!isset($this->apis[$name])) {
            throw new \InvalidArgumentException("API client '{$name}' not registered");
        }

        return $this->apis[$name];
    }
}

// Usage
$apiManager = new ApiManager();

// Register different API clients
$apiManager->register('github', new GitHubClient('github-token'));
$apiManager->register('stripe', new StripeClient('stripe-secret-key'));
$apiManager->register('slack', new SlackClient('slack-token'));

// Use them in your application
$githubClient = $apiManager->get('github');
$repos = $githubClient->getRepositories('octocat');

$stripeClient = $apiManager->get('stripe');
$charge = $stripeClient->createCharge(2000, 'usd', 'customer_id');

$slackClient = $apiManager->get('slack');
$slackClient->sendMessage('#general', 'Payment received!');
```

## Next Steps

- Check out [Async Patterns](/examples/async-patterns) to learn how to make API requests asynchronously
- Explore [Error Handling](/examples/error-handling) for robust error management in API integrations
- See [Authentication](/examples/authentication) for more authentication examples
