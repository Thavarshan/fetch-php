---
title: Testing
description: Learn how to test code that uses the Fetch HTTP package
---

# Testing

This guide explains how to test code that uses the Fetch HTTP package. Properly testing HTTP-dependent code is crucial for creating reliable applications.

## Mock Responses

The Fetch HTTP package provides built-in utilities for creating mock responses:

```php
use Fetch\Http\ClientHandler;
use Fetch\Http\Response;

// Create a basic mock response
$mockResponse = ClientHandler::createMockResponse(
    200,  // Status code
    ['Content-Type' => 'application/json'],  // Headers
    '{"name": "John Doe", "email": "john@example.com"}'  // Body
);

// Create a JSON response directly from PHP data
$mockJsonResponse = ClientHandler::createJsonResponse(
    ['name' => 'Jane Doe', 'email' => 'jane@example.com'],  // Data (will be JSON-encoded)
    201,  // Status code
    ['X-Custom-Header' => 'Value']  // Additional headers
);
```

## Mock Client with Guzzle MockHandler

For testing code that uses the Fetch HTTP package, you can set up a mock handler to return predefined responses:

```php
use Fetch\Http\ClientHandler;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;

// Create a mock handler with an array of responses
$mock = new MockHandler([
    new Response(200, ['Content-Type' => 'application/json'], '{"id": 1, "name": "Test User"}'),
    new Response(404, [], '{"error": "Not found"}'),
    new Response(500, [], '{"error": "Server error"}')
]);

// Create a handler stack with the mock handler
$stack = HandlerStack::create($mock);

// Create a Guzzle client with the stack
$guzzleClient = new Client(['handler' => $stack]);

// Create a ClientHandler with the mock client
$client = ClientHandler::createWithClient($guzzleClient);

// First request will return 200 response
$response1 = $client->get('https://api.example.com/users/1');
echo $response1->status();  // 200
echo $response1->json()['name'];  // "Test User"

// Second request will return 404 response
$response2 = $client->get('https://api.example.com/users/999');
echo $response2->status();  // 404

// Third request will return 500 response
$response3 = $client->get('https://api.example.com/error');
echo $response3->status();  // 500
```

## Testing a Service Class

Here's how to test a service class that uses the Fetch HTTP package:

```php
use PHPUnit\Framework\TestCase;
use Fetch\Http\ClientHandler;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;

class UserService
{
    private ClientHandler $client;

    public function __construct(ClientHandler $client)
    {
        $this->client = $client;
    }

    public function getUser(int $id): array
    {
        $response = $this->client->get("/users/{$id}");

        if ($response->isNotFound()) {
            throw new \RuntimeException("User {$id} not found");
        }

        return $response->json();
    }

    public function createUser(array $userData): array
    {
        $response = $this->client->post('/users', $userData);

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to create user: " . $response->status());
        }

        return $response->json();
    }
}

class UserServiceTest extends TestCase
{
    private function createMockClient(array $responses): ClientHandler
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $stack]);

        return ClientHandler::createWithClient($guzzleClient);
    }

    public function testGetUserReturnsUserData(): void
    {
        // Arrange
        $expectedUser = ['id' => 1, 'name' => 'Test User'];
        $mockResponses = [
            new Response(200, ['Content-Type' => 'application/json'], json_encode($expectedUser))
        ];
        $client = $this->createMockClient($mockResponses);
        $userService = new UserService($client);

        // Act
        $user = $userService->getUser(1);

        // Assert
        $this->assertEquals($expectedUser, $user);
    }

    public function testGetUserThrowsExceptionForNotFound(): void
    {
        // Arrange
        $mockResponses = [
            new Response(404, ['Content-Type' => 'application/json'], '{"error": "Not found"}')
        ];
        $client = $this->createMockClient($mockResponses);
        $userService = new UserService($client);

        // Assert & Act
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User 999 not found');

        $userService->getUser(999);
    }

    public function testCreateUserReturnsCreatedUser(): void
    {
        // Arrange
        $userData = ['name' => 'New User', 'email' => 'new@example.com'];
        $expectedUser = array_merge(['id' => 123], $userData);
        $mockResponses = [
            new Response(201, ['Content-Type' => 'application/json'], json_encode($expectedUser))
        ];
        $client = $this->createMockClient($mockResponses);
        $userService = new UserService($client);

        // Act
        $user = $userService->createUser($userData);

        // Assert
        $this->assertEquals($expectedUser, $user);
    }
}
```

## Testing History

You can also use `GuzzleHttp\Middleware::history()` to capture request/response history for testing:

```php
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;
use Fetch\Http\ClientHandler;
use Psr\Http\Message\RequestInterface;

class ClientHistoryTest extends \PHPUnit\Framework\TestCase
{
    public function testRequestContainsExpectedHeaders(): void
    {
        // Set up a history container
        $container = [];
        $history = Middleware::history($container);

        // Create a stack with the history middleware
        $stack = HandlerStack::create();
        $stack->push($history);

        // Add a mock response
        $mock = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(200, [], '{}')
        ]);
        $stack->setHandler($mock);

        // Create a Guzzle client with the stack
        $guzzleClient = new Client(['handler' => $stack]);

        // Create a ClientHandler with the client
        $client = ClientHandler::createWithClient($guzzleClient);

        // Make a request
        $client->withToken('test-token')
            ->withHeader('X-Custom-Header', 'CustomValue')
            ->get('https://api.example.com/resource');

        // Assert request contained expected headers
        $this->assertCount(1, $container);
        $transaction = $container[0];
        $request = $transaction['request'];

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('https://api.example.com/resource', (string) $request->getUri());
        $this->assertEquals('Bearer test-token', $request->getHeaderLine('Authorization'));
        $this->assertEquals('CustomValue', $request->getHeaderLine('X-Custom-Header'));
    }
}
```

## Testing Asynchronous Requests

For testing asynchronous code:

```php
class AsyncTest extends \PHPUnit\Framework\TestCase
{
    public function testAsyncRequests(): void
    {
        // Create mock responses
        $mock = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(200, [], '{"id":1,"name":"User 1"}'),
            new \GuzzleHttp\Psr7\Response(200, [], '{"id":2,"name":"User 2"}')
        ]);

        $stack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $stack]);
        $client = ClientHandler::createWithClient($guzzleClient);

        // Create async requests
        $promise1 = $client->async()->get('https://api.example.com/users/1');
        $promise2 = $client->async()->get('https://api.example.com/users/2');

        // Wait for both to complete
        $results = $client->all(['user1' => $promise1, 'user2' => $promise2]);

        // Convert the promise to a response
        $responses = $client->awaitPromise($results);

        // Assert responses
        $this->assertEquals(200, $responses['user1']->status());
        $this->assertEquals('User 1', $responses['user1']->json()['name']);

        $this->assertEquals(200, $responses['user2']->status());
        $this->assertEquals('User 2', $responses['user2']->json()['name']);
    }
}
```

## Testing with Custom Response Factory

You can create a helper for generating test responses:

```php
class ResponseFactory
{
    public static function userResponse(int $id, string $name, string $email): \Fetch\Http\Response
    {
        return \Fetch\Http\ClientHandler::createJsonResponse([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'created_at' => '2023-01-01T00:00:00Z'
        ]);
    }

    public static function usersListResponse(array $users): \Fetch\Http\Response
    {
        return \Fetch\Http\ClientHandler::createJsonResponse([
            'data' => $users,
            'meta' => [
                'total' => count($users),
                'page' => 1,
                'per_page' => count($users)
            ]
        ]);
    }

    public static function errorResponse(int $status, string $message): \Fetch\Http\Response
    {
        return \Fetch\Http\ClientHandler::createJsonResponse(
            ['error' => $message],
            $status
        );
    }

    public static function validationErrorResponse(array $errors): \Fetch\Http\Response
    {
        return \Fetch\Http\ClientHandler::createJsonResponse(
            [
                'message' => 'Validation failed',
                'errors' => $errors
            ],
            422
        );
    }
}

// Usage in tests
class UserServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testGetUser(): void
    {
        $mockResponses = [
            ResponseFactory::userResponse(1, 'John Doe', 'john@example.com')
        ];

        // Create client and test...
    }

    public function testValidationError(): void
    {
        $mockResponses = [
            ResponseFactory::validationErrorResponse([
                'email' => ['The email must be a valid email address.']
            ])
        ];

        // Create client and test...
    }
}
```

## Testing HTTP Error Handling

Test how your code handles various HTTP errors:

```php
class ErrorHandlingTest extends \PHPUnit\Framework\TestCase
{
    public function testHandles404Gracefully(): void
    {
        $mock = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(404, [], '{"error": "Not found"}')
        ]);

        $stack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $stack]);
        $client = ClientHandler::createWithClient($guzzleClient);

        $userService = new UserService($client);

        try {
            $userService->getUser(999);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('User 999 not found', $e->getMessage());
        }
    }

    public function testHandlesNetworkError(): void
    {
        $mock = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Exception\ConnectException(
                'Connection refused',
                new \GuzzleHttp\Psr7\Request('GET', 'https://api.example.com/users/1')
            )
        ]);

        $stack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $stack]);
        $client = ClientHandler::createWithClient($guzzleClient);

        $userService = new UserService($client);

        $this->expectException(\RuntimeException::class);
        $userService->getUser(1);
    }
}
```

## Integration Tests with Real APIs

Sometimes you'll want to run integration tests against real APIs. This should typically be done in a separate test suite that can be opted into:

```php
/**
 * @group integration
 */
class GithubApiIntegrationTest extends \PHPUnit\Framework\TestCase
{
    private \Fetch\Http\ClientHandler $client;

    protected function setUp(): void
    {
        // Skip if no API token is configured
        if (empty(getenv('GITHUB_API_TOKEN'))) {
            $this->markTestSkipped('No GitHub API token available');
        }

        $this->client = \Fetch\Http\ClientHandler::createWithBaseUri('https://api.github.com')
            ->withToken(getenv('GITHUB_API_TOKEN'))
            ->withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'ApiTests'
            ]);
    }

    public function testCanFetchUserProfile(): void
    {
        $response = $this->client->get('/user');

        $this->assertTrue($response->successful());
        $this->assertEquals(200, $response->status());

        $user = $response->json();
        $this->assertArrayHasKey('login', $user);
        $this->assertArrayHasKey('id', $user);
    }
}
```

## Using Test Doubles

You can create test doubles (stubs, mocks) for your service classes:

```php
interface UserRepositoryInterface
{
    public function find(int $id): ?array;
    public function create(array $data): array;
}

class ApiUserRepository implements UserRepositoryInterface
{
    private \Fetch\Http\ClientHandler $client;

    public function __construct(\Fetch\Http\ClientHandler $client)
    {
        $this->client = $client;
    }

    public function find(int $id): ?array
    {
        $response = $this->client->get("/users/{$id}");

        if ($response->isNotFound()) {
            return null;
        }

        return $response->json();
    }

    public function create(array $data): array
    {
        $response = $this->client->post('/users', $data);

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to create user: " . $response->status());
        }

        return $response->json();
    }
}

class UserServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateUserCallsRepository(): void
    {
        // Create a mock repository
        $repository = $this->createMock(UserRepositoryInterface::class);

        // Set up expectations
        $userData = ['name' => 'Test User', 'email' => 'test@example.com'];
        $createdUser = array_merge(['id' => 123], $userData);

        $repository->expects($this->once())
            ->method('create')
            ->with($userData)
            ->willReturn($createdUser);

        // Use the mock in our service
        $userService = new UserService($repository);
        $result = $userService->createUser($userData);

        $this->assertEquals($createdUser, $result);
    }
}

class UserService
{
    private UserRepositoryInterface $repository;

    public function __construct(UserRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function createUser(array $userData): array
    {
        // Validate data, process business logic, etc.

        return $this->repository->create($userData);
    }
}
```

## Best Practices

1. **Mock External Services**: Always mock external API calls in unit tests.

2. **Test Various Response Types**: Test how your code handles success, client errors, server errors, and network issues.

3. **Use Test Data Factories**: Create factories for generating test data consistently.

4. **Separate Integration Tests**: Keep integration tests that hit real APIs separate from unit tests.

5. **Test Asynchronous Code**: If you're using async features, test them specifically.

6. **Verify Request Parameters**: Use history middleware to verify that requests are made with the expected parameters.

7. **Abstract HTTP Logic**: Use the repository pattern to abstract HTTP logic, making it easier to mock for tests.

8. **Test Response Parsing**: Test that your code correctly handles and parses various response formats.

9. **Test Rate Limiting Handling**: Test that your code handles rate limiting (429 responses) appropriately.

10. **Test Authentication**: Test that authentication tokens are properly included in requests.

## Next Steps

- Explore [Dependency Injection](/guide/custom-clients#dependency-injection-with-clients) for more testable code
- Learn about [Error Handling](/guide/error-handling) for robust applications
- See [Custom Clients](/guide/custom-clients) for creating specialized clients
