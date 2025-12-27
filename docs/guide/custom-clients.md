---
title: Custom Clients
description: Learn how to create and configure custom clients for different API connections
---

# Custom Clients

This guide explains how to create and configure custom clients for different API connections in the Fetch HTTP package.

## Creating Custom Clients

There are several ways to create custom client instances tailored to specific APIs or use cases.

### Using Factory Methods

The simplest way to create a custom client is using the factory methods:

```php
use Fetch\Http\ClientHandler;

// Create a client with base URI
$githubClient = ClientHandler::createWithBaseUri('https://api.github.com');

// Create a client with a custom Guzzle client
$guzzleClient = new \GuzzleHttp\Client([
    'timeout' => 60,
    'verify' => false  // Disable SSL verification (not recommended for production)
]);
$customClient = ClientHandler::createWithClient($guzzleClient);

// Create a basic client and customize it
$basicClient = ClientHandler::create()
    ->timeout(30)
    ->withHeaders([
        'User-Agent' => 'MyApp/1.0',
        'Accept' => 'application/json'
    ]);
```

### Cloning with Options

You can create clones of existing clients with modified options:

```php
// Create a base client
$baseClient = ClientHandler::createWithBaseUri('https://api.example.com')
    ->withHeaders([
        'User-Agent' => 'MyApp/1.0',
        'Accept' => 'application/json'
    ]);

// Create a clone with authentication for protected endpoints
$authClient = $baseClient->withClonedOptions([
    'headers' => [
        'Authorization' => 'Bearer ' . $token
    ]
]);

// Create another clone with different timeout
$longTimeoutClient = $baseClient->withClonedOptions([
    'timeout' => 60
]);
```

## Using Type-Safe Enums

You can use the library's enums for type-safe client configuration:

```php
use Fetch\Enum\Method;
use Fetch\Enum\ContentType;

// Create a client with type-safe configuration
$client = ClientHandler::create()
    ->withBody($data, ContentType::JSON)
    ->request(Method::POST, 'https://api.example.com/users');

// Configure retries with enums
use Fetch\Enum\Status;

$client = ClientHandler::create()
    ->retry(3, 100)
    ->retryStatusCodes([
        Status::TOO_MANY_REQUESTS->value,
        Status::SERVICE_UNAVAILABLE->value,
        Status::GATEWAY_TIMEOUT->value
    ])
    ->get('https://api.example.com/flaky-endpoint');
```

## Creating API Service Classes

For more organized code, you can create service classes that encapsulate API functionality:

```php
class GitHubApiService
{
    private \Fetch\Http\ClientHandler $client;

    public function __construct(string $token)
    {
        $this->client = \Fetch\Http\ClientHandler::createWithBaseUri('https://api.github.com')
            ->withToken($token)
            ->withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'MyApp/1.0'
            ]);
    }

    public function getUser(string $username)
    {
        return $this->client->get("/users/{$username}")->json();
    }

    public function getRepositories(string $username)
    {
        return $this->client->get("/users/{$username}/repos")->json();
    }

    public function createIssue(string $owner, string $repo, array $issueData)
    {
        return $this->client->post("/repos/{$owner}/{$repo}/issues", $issueData)->json();
    }
}

// Usage
$github = new GitHubApiService('your-github-token');
$user = $github->getUser('octocat');
$repos = $github->getRepositories('octocat');
```

## Client Configuration for Different APIs

Different APIs often have different requirements. Here are examples for popular APIs:

### REST API Client

```php
$restClient = ClientHandler::createWithBaseUri('https://api.example.com')
    ->withToken('your-api-token')
    ->withHeaders([
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ]);
```

### GraphQL API Client

```php
$graphqlClient = ClientHandler::createWithBaseUri('https://api.example.com/graphql')
    ->withToken('your-api-token')
    ->withHeaders([
        'Content-Type' => 'application/json'
    ]);

// Example GraphQL query
$response = $graphqlClient->post('', [
    'query' => '
        query GetUser($id: ID!) {
            user(id: $id) {
                id
                name
                email
            }
        }
    ',
    'variables' => [
        'id' => '123'
    ]
]);
```

### OAuth 2.0 Client

```php
class OAuth2Client
{
    private \Fetch\Http\ClientHandler $client;
    private string $tokenEndpoint;
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;
    private ?int $expiresAt = null;

    public function __construct(
        string $baseUri,
        string $tokenEndpoint,
        string $clientId,
        string $clientSecret
    ) {
        $this->client = \Fetch\Http\ClientHandler::createWithBaseUri($baseUri);
        $this->tokenEndpoint = $tokenEndpoint;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    private function ensureToken()
    {
        if ($this->accessToken === null || time() > $this->expiresAt) {
            $this->refreshToken();
        }

        return $this->accessToken;
    }

    private function refreshToken()
    {
        $response = $this->client->post($this->tokenEndpoint, [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ]);

        $tokenData = $response->json();
        $this->accessToken = $tokenData['access_token'];
        $this->expiresAt = time() + ($tokenData['expires_in'] - 60); // Buffer of 60 seconds
    }

    public function get(string $uri, array $query = [])
    {
        $token = $this->ensureToken();

        return $this->client
            ->withToken($token)
            ->get($uri, $query)
            ->json();
    }

    public function post(string $uri, array $data)
    {
        $token = $this->ensureToken();

        return $this->client
            ->withToken($token)
            ->post($uri, $data)
            ->json();
    }

    // Add other methods as needed
}

// Usage
$oauth2Client = new OAuth2Client(
    'https://api.example.com',
    '/oauth/token',
    'your-client-id',
    'your-client-secret'
);

$resources = $oauth2Client->get('/resources', ['type' => 'active']);
```

## Asynchronous API Clients

You can create asynchronous API clients using the async features:

```php
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\all;

class AsyncApiClient
{
    private \Fetch\Http\ClientHandler $client;

    public function __construct(string $baseUri, string $token)
    {
        $this->client = \Fetch\Http\ClientHandler::createWithBaseUri($baseUri)
            ->withToken($token);
    }

    public function fetchUserAndPosts(int $userId)
    {
        return await(async(function() use ($userId) {
            // Execute requests in parallel
            $results = await(all([
                'user' => async(fn() => $this->client->get("/users/{$userId}")),
                'posts' => async(fn() => $this->client->get("/users/{$userId}/posts"))
            ]));

            // Process the results
            return [
                'user' => $results['user']->json(),
                'posts' => $results['posts']->json()
            ];
        }));
    }
}

// Usage
$client = new AsyncApiClient('https://api.example.com', 'your-token');
$data = $client->fetchUserAndPosts(123);
echo "User: {$data['user']['name']}, Posts: " . count($data['posts']);
```

## Customizing Handlers with Middleware

For advanced use cases, you can create a fully custom client with Guzzle middleware:

```php
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\MessageFormatter;
use Fetch\Http\ClientHandler;
use Psr\Http\Message\RequestInterface;

// Create a handler stack
$stack = HandlerStack::create();

// Add logging middleware
$logger = new \Monolog\Logger('http');
$logger->pushHandler(new \Monolog\Handler\StreamHandler('logs/http.log', \Monolog\Logger::DEBUG));

$messageFormat = "{method} {uri} HTTP/{version} {req_body} -> {code} {res_body}";
$stack->push(
    Middleware::log($logger, new MessageFormatter($messageFormat))
);

// Add custom header middleware
$stack->push(Middleware::mapRequest(function (RequestInterface $request) {
    return $request->withHeader('X-Custom-Header', 'CustomValue');
}));

// Add timing middleware
$stack->push(Middleware::mapRequest(function (RequestInterface $request) {
    return $request->withHeader('X-Request-Time', (string) time());
}));

// Create a Guzzle client with the stack
$guzzleClient = new Client([
    'handler' => $stack,
    'base_uri' => 'https://api.example.com'
]);

// Create a ClientHandler with the custom Guzzle client
$client = ClientHandler::createWithClient($guzzleClient);

// Use the client
$response = $client->get('/resources');
```

## Global Default Client

You can configure a global default client for all requests:

```php
// Configure the global client
fetch_client([
    'base_uri' => 'https://api.example.com',
    'timeout' => 30,
    'headers' => [
        'User-Agent' => 'MyApp/1.0',
        'Accept' => 'application/json'
    ]
]);

// All requests will use this configuration
$response = fetch('/users');  // Uses the base_uri
```

## Client Configuration for Testing

For testing, you can configure a client that returns mock responses:

```php
use Fetch\Http\ClientHandler;
use Fetch\Enum\Status;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Client;

// Create mock responses
$mock = new MockHandler([
    new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"id": 1, "name": "Test User"}'),
    new GuzzleResponse(404, ['Content-Type' => 'application/json'], '{"error": "Not found"}'),
    new GuzzleResponse(500, ['Content-Type' => 'application/json'], '{"error": "Server error"}')
]);

// Create a handler stack with the mock handler
$stack = HandlerStack::create($mock);

// Create a Guzzle client with the stack
$guzzleClient = new Client(['handler' => $stack]);

// Create a ClientHandler with the mock client
$client = ClientHandler::createWithClient($guzzleClient);

// First request - 200 OK
$response1 = $client->get('/users/1');
assert($response1->isOk());
assert($response1->json()['name'] === 'Test User');

// Second request - 404 Not Found
$response2 = $client->get('/users/999');
assert($response2->isNotFound());

// Third request - 500 Server Error
$response3 = $client->get('/error');
assert($response3->isServerError());
```

Alternatively, you can use the built-in mock response utilities:

```php
// Mock a successful response
$mockResponse = ClientHandler::createMockResponse(
    200,
    ['Content-Type' => 'application/json'],
    '{"id": 1, "name": "Test User"}'
);

// Using Status enum
$mockResponse = ClientHandler::createMockResponse(
    Status::OK,
    ['Content-Type' => 'application/json'],
    '{"id": 1, "name": "Test User"}'
);

// Mock a JSON response directly
$mockJsonResponse = ClientHandler::createJsonResponse(
    ['id' => 2, 'name' => 'Another User'],
    Status::OK
);
```

## Clients with Logging

You can create clients with logging enabled:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Fetch\Http\ClientHandler;

// Create a logger
$logger = new Logger('api');
$logger->pushHandler(new StreamHandler('logs/api.log', Logger::INFO));

// Create a client with the logger
$client = ClientHandler::create();
$client->setLogger($logger);

// Now all requests and responses will be logged
$response = $client->get('https://api.example.com/users');

// You can also set a logger on the global client
$globalClient = fetch_client();
$globalClient->setLogger($logger);
```

## Working with Multiple APIs

For applications that interact with multiple APIs:

```php
class ApiManager
{
    private array $clients = [];

    public function register(string $name, \Fetch\Http\ClientHandler $client): void
    {
        $this->clients[$name] = $client;
    }

    public function get(string $name): ?\Fetch\Http\ClientHandler
    {
        return $this->clients[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->clients[$name]);
    }
}

// Usage
$apiManager = new ApiManager();

// Register clients for different APIs
$apiManager->register('github', ClientHandler::createWithBaseUri('https://api.github.com')
    ->withToken('github-token')
    ->withHeaders(['Accept' => 'application/vnd.github.v3+json']));

$apiManager->register('stripe', ClientHandler::createWithBaseUri('https://api.stripe.com/v1')
    ->withAuth('sk_test_your_key', ''));

$apiManager->register('custom', ClientHandler::createWithBaseUri('https://api.custom.com')
    ->withToken('custom-token'));

// Use the clients
$githubUser = $apiManager->get('github')->get('/user')->json();
$stripeCustomers = $apiManager->get('stripe')->get('/customers')->json();
```

## Dependency Injection with Clients

For applications using dependency injection:

```php
// Service interface
interface UserServiceInterface
{
    public function getUser(int $id): array;
    public function createUser(array $userData): array;
}

// Implementation using Fetch
class UserApiService implements UserServiceInterface
{
    private \Fetch\Http\ClientHandler $client;

    public function __construct(\Fetch\Http\ClientHandler $client)
    {
        $this->client = $client;
    }

    public function getUser(int $id): array
    {
        return $this->client->get("/users/{$id}")->json();
    }

    public function createUser(array $userData): array
    {
        return $this->client->post('/users', $userData)->json();
    }
}

// Usage with a DI container
$container->singleton(\Fetch\Http\ClientHandler::class, function () {
    return ClientHandler::createWithBaseUri('https://api.example.com')
        ->withToken('api-token')
        ->withHeaders([
            'Accept' => 'application/json',
            'User-Agent' => 'MyApp/1.0'
        ]);
});

$container->singleton(UserServiceInterface::class, UserApiService::class);

// Usage in a controller
class UserController
{
    private UserServiceInterface $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    public function getUser(int $id)
    {
        return $this->userService->getUser($id);
    }
}
```

## Configuring Clients from Environment Variables

For applications using environment variables for configuration:

```php
function createClientFromEnv(string $prefix): \Fetch\Http\ClientHandler
{
    $baseUri = getenv("{$prefix}_BASE_URI");
    $token = getenv("{$prefix}_TOKEN");
    $timeout = getenv("{$prefix}_TIMEOUT") ?: 30;

    $client = ClientHandler::createWithBaseUri($baseUri)
        ->timeout((int) $timeout);

    if ($token) {
        $client->withToken($token);
    }

    return $client;
}

// Usage
$githubClient = createClientFromEnv('GITHUB_API');
$stripeClient = createClientFromEnv('STRIPE_API');
```

## Custom Retry Logic

You can create a client with custom retry logic:

```php
use Fetch\Enum\Status;

$client = ClientHandler::create()
    ->retry(3, 100)  // Basic retry configuration: 3 attempts, 100ms initial delay
    ->retryStatusCodes([
        Status::TOO_MANY_REQUESTS->value,
        Status::SERVICE_UNAVAILABLE->value,
        Status::GATEWAY_TIMEOUT->value
    ])  // Only retry these status codes
    ->retryExceptions([\GuzzleHttp\Exception\ConnectException::class]);

// Use the client
$response = $client->get('https://api.example.com/unstable-endpoint');
```

## Extending ClientHandler

For very specialized needs, you can extend the ClientHandler class:

```php
class GraphQLClientHandler extends \Fetch\Http\ClientHandler
{
    /**
     * Execute a GraphQL query.
     */
    public function query(string $query, array $variables = []): array
    {
        $response = $this->post('', [
            'query' => $query,
            'variables' => $variables
        ]);

        $data = $response->json();

        if (isset($data['errors'])) {
            throw new \RuntimeException('GraphQL Error: ' . json_encode($data['errors']));
        }

        return $data['data'] ?? [];
    }

    /**
     * Execute a GraphQL mutation.
     */
    public function mutation(string $mutation, array $variables = []): array
    {
        return $this->query($mutation, $variables);
    }
}

// Usage
$graphqlClient = new GraphQLClientHandler();
$graphqlClient->baseUri('https://api.example.com/graphql')
    ->withToken('your-token');

$userData = $graphqlClient->query('
    query GetUser($id: ID!) {
        user(id: $id) {
            id
            name
            email
        }
    }
', ['id' => '123']);
```

## Best Practices

1. **Use Type-Safe Enums**: Leverage the library's enums for type safety and better code readability.

2. **Organize by API**: Create separate client instances for different APIs.

3. **Configure Once**: Set up clients with all necessary options once, then reuse them.

4. **Use Dependency Injection**: Inject client instances rather than creating them in methods.

5. **Abstract APIs Behind Services**: Create service classes that use clients internally, exposing a domain-specific interface.

6. **Handle Authentication Properly**: Implement token refresh logic for OAuth flows.

7. **Use Timeouts Appropriately**: Configure timeouts based on the expected response time of each API.

8. **Log Requests and Responses**: Add logging for debugging and monitoring API interactions.

9. **Use Base URIs**: Always use base URIs to avoid repeating URL prefixes.

10. **Set Common Headers**: Configure common headers (User-Agent, Accept, etc.) once.

11. **Error Handling**: Implement consistent error handling for each client.

12. **Create Async Clients When Needed**: Use async/await for operations that benefit from parallelism.

## Next Steps

- Learn about [Testing](/guide/testing) for testing with custom clients
- Explore [Asynchronous Requests](/guide/async-requests) for working with async clients
- See [Authentication](/guide/authentication) for handling different authentication schemes
- Check out [Working with Responses](/guide/working-with-responses) for handling API responses
