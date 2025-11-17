---
title: Testing with Mocks
description: Learn how to test HTTP-dependent code using Fetch PHP's powerful testing utilities
---

# Testing with Mocks

Fetch PHP provides comprehensive testing utilities including a powerful mock server, request recording/playback, and advanced assertion helpers for HTTP testing.

## Quick Start

The simplest way to get started with mocking:

```php
use Fetch\Testing\MockServer;
use Fetch\Testing\MockResponse;

// Set up fake responses
MockServer::fake([
    'https://api.example.com/users' => MockResponse::json(['users' => []]),
]);

// Make requests (they will be mocked)
$response = get('https://api.example.com/users');

// Assert requests were sent
MockServer::assertSent('https://api.example.com/users');
```

## MockServer

### Basic Mocking

Mock all requests with empty 200 responses:

```php
MockServer::fake();

$response = get('https://any-url.com'); // Returns 200 OK
```

### URL Pattern Matching

Mock specific URLs:

```php
MockServer::fake([
    'https://api.example.com/users' => MockResponse::json([
        'users' => ['John', 'Jane']
    ]),
    'https://api.example.com/posts' => MockResponse::json([
        'posts' => []
    ]),
]);
```

### HTTP Method Matching

Match specific HTTP methods:

```php
MockServer::fake([
    'GET https://api.example.com/users' => MockResponse::json(['users' => []]),
    'POST https://api.example.com/users' => MockResponse::created(['id' => 123]),
    'PUT https://api.example.com/users/123' => MockResponse::ok(['updated' => true]),
    'DELETE https://api.example.com/users/123' => MockResponse::noContent(),
]);
```

### Wildcard Patterns

Use wildcards for flexible matching:

```php
MockServer::fake([
    'https://api.example.com/users/*' => MockResponse::json(['user' => 'found']),
    'https://api.example.com/posts/*' => MockResponse::json(['post' => 'found']),
    '*' => MockResponse::notFound(), // Catch-all fallback
]);

$response1 = get('https://api.example.com/users/123'); // Matches
$response2 = get('https://api.example.com/users/456'); // Matches
```

### Dynamic Responses with Callbacks

Use callbacks for dynamic response generation:

```php
MockServer::fake(function ($request) {
    // Check authentication
    if ($request->hasHeader('Authorization')) {
        return MockResponse::json(['authenticated' => true]);
    }

    // Check URL
    if (str_contains((string) $request->getUri(), 'users')) {
        return MockResponse::json(['users' => []]);
    }

    // Check method
    if ($request->getMethod() === 'POST') {
        return MockResponse::created();
    }

    return MockResponse::unauthorized();
});
```

## MockResponse

### Creating Responses

```php
use Fetch\Testing\MockResponse;

// Basic response
$response = MockResponse::create(200, 'Hello World', ['X-Custom' => 'value']);

// JSON response
$response = MockResponse::json(['name' => 'John', 'age' => 30], 200);
```

### Convenience Methods

```php
// Success responses
MockResponse::ok('Success');
MockResponse::created(['id' => 123]);
MockResponse::noContent();

// Client error responses
MockResponse::badRequest('Invalid input');
MockResponse::unauthorized();
MockResponse::forbidden();
MockResponse::notFound();
MockResponse::unprocessableEntity(['errors' => ['field' => 'required']]);

// Server error responses
MockResponse::serverError('Internal error');
MockResponse::serviceUnavailable();
```

### Response Delays

Simulate slow responses:

```php
MockServer::fake([
    'https://api.example.com/slow' => MockResponse::ok('Done')->delay(100), // 100ms delay
]);

$start = microtime(true);
$response = get('https://api.example.com/slow');
$duration = (microtime(true) - $start) * 1000;
// Duration will be >= 100ms
```

### Throwing Exceptions

Simulate network errors:

```php
MockServer::fake([
    'https://api.example.com/error' => MockResponse::ok()->throw(
        new \RuntimeException('Network timeout')
    ),
]);

try {
    get('https://api.example.com/error');
} catch (\RuntimeException $e) {
    // Handle the error
}
```

## Response Sequences

Test retry logic and flaky endpoints:

```php
MockServer::fake([
    'https://api.example.com/flaky' => MockResponse::sequence()
        ->pushStatus(500) // First request fails
        ->pushStatus(500) // Second request fails
        ->pushStatus(200), // Third request succeeds
]);

$response1 = get('https://api.example.com/flaky'); // 500
$response2 = get('https://api.example.com/flaky'); // 500
$response3 = get('https://api.example.com/flaky'); // 200
```

Advanced sequence features:

```php
$sequence = MockResponse::sequence()
    ->push(200, 'First response')
    ->pushJson(['data' => 'second'], 201)
    ->pushStatus(404)
    ->whenEmpty(MockResponse::ok('default')) // Return this when exhausted
    ->loop(); // Or loop back to the beginning

MockServer::fake([
    'https://api.example.com/endpoint' => $sequence,
]);
```

## Assertions

### Assert Request Was Sent

```php
MockServer::fake(['*' => MockResponse::ok()]);

post('https://api.example.com/users', ['name' => 'John']);

// Assert by URL pattern
MockServer::assertSent('https://api.example.com/users');
MockServer::assertSent('POST https://api.example.com/users');

// Assert with callback
MockServer::assertSent(function ($request, $response) {
    return $request->hasHeader('Authorization') &&
           str_contains((string) $request->getBody(), 'John');
});

// Assert specific number of times
MockServer::assertSent('https://api.example.com/users', 1);
```

### Assert Request Was Not Sent

```php
MockServer::assertNotSent('https://api.example.com/posts');

MockServer::assertNotSent(function ($request) {
    return $request->getMethod() === 'DELETE';
});
```

### Assert Request Count

```php
MockServer::assertSentCount(3); // Exactly 3 requests
MockServer::assertNothingSent(); // No requests at all
```

## Request Recording

Record real or mocked requests and replay them later:

```php
use Fetch\Testing\Recorder;

// Start recording
Recorder::start();

// Make some requests
$response1 = get('https://api.example.com/users');
$response2 = post('https://api.example.com/users', ['name' => 'Jane']);

// Stop recording
$recordings = Recorder::stop();

// Later, replay the recordings
Recorder::replay($recordings);

// Now the same requests will return the recorded responses
$response = get('https://api.example.com/users'); // Returns recorded response
```

### Export and Import Recordings

```php
// Export to JSON for storage
Recorder::start();
get('https://api.example.com/users');
$json = Recorder::exportToJson();

// Save to file
file_put_contents('tests/fixtures/recordings.json', $json);

// Later, load and replay
$json = file_get_contents('tests/fixtures/recordings.json');
Recorder::importFromJson($json);
```

## Preventing Stray Requests

Ensure all requests are mocked in tests:

```php
MockServer::fake([
    'https://api.example.com/*' => MockResponse::ok(),
]);

MockServer::preventStrayRequests();

get('https://api.example.com/users'); // OK - matches pattern

get('https://other-api.com/data'); // Throws InvalidArgumentException
```

Allow specific URLs:

```php
MockServer::fake([
    'https://api.example.com/*' => MockResponse::ok(),
]);

MockServer::allowStrayRequests([
    'https://localhost/*',
    'http://127.0.0.1:*',
]);

get('https://api.example.com/users'); // Mocked
get('https://localhost/test'); // Allowed (real request)
```

## Testing a Service Class

Here's a complete example of testing a service class:

```php
use PHPUnit\Framework\TestCase;
use Fetch\Testing\MockServer;
use Fetch\Testing\MockResponse;

class UserService
{
    public function getAllUsers(): array
    {
        $response = get('https://api.example.com/users');
        return $response->json()['users'];
    }

    public function createUser(array $userData): array
    {
        $response = post('https://api.example.com/users', $userData);

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to create user: " . $response->status());
        }

        return $response->json();
    }

    public function getUser(int $id): array
    {
        $response = get("https://api.example.com/users/{$id}");

        if ($response->isNotFound()) {
            throw new \RuntimeException("User {$id} not found");
        }

        return $response->json();
    }
}

class UserServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MockServer::fake([
            'GET https://api.example.com/users' => MockResponse::json([
                'users' => [
                    ['id' => 1, 'name' => 'John'],
                    ['id' => 2, 'name' => 'Jane'],
                ]
            ]),
            'POST https://api.example.com/users' => MockResponse::created([
                'id' => 3,
                'name' => 'Bob',
            ]),
            'GET https://api.example.com/users/*' => MockResponse::json([
                'id' => 1,
                'name' => 'John',
            ]),
        ]);
    }

    protected function tearDown(): void
    {
        MockServer::resetInstance();
        parent::tearDown();
    }

    public function test_gets_all_users(): void
    {
        $service = new UserService();
        $users = $service->getAllUsers();

        $this->assertCount(2, $users);
        MockServer::assertSent('GET https://api.example.com/users');
    }

    public function test_creates_user(): void
    {
        $service = new UserService();
        $user = $service->createUser(['name' => 'Bob']);

        $this->assertEquals(3, $user['id']);

        MockServer::assertSent(function ($request) {
            $body = json_decode((string) $request->getBody(), true);
            return $body['name'] === 'Bob';
        });
    }

    public function test_handles_not_found(): void
    {
        MockServer::fake([
            'GET https://api.example.com/users/999' => MockResponse::notFound(),
        ]);

        $service = new UserService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User 999 not found');

        $service->getUser(999);
    }
}
```

## Testing Retry Logic

Test how your code handles retry scenarios:

```php
public function test_retries_on_failure(): void
{
    MockServer::fake([
        'https://api.example.com/unstable' => MockResponse::sequence()
            ->pushStatus(503) // Service unavailable
            ->pushStatus(503) // Service unavailable
            ->pushJson(['success' => true], 200), // Success
    ]);

    $response = retry(function () {
        return get('https://api.example.com/unstable');
    }, 3, 100);

    $this->assertTrue($response->successful());
    $this->assertEquals(['success' => true], $response->json());

    // Verify it was called 3 times
    MockServer::assertSent('https://api.example.com/unstable', 3);
}
```

## Testing Authentication

Test authentication requirements:

```php
public function test_requires_authentication(): void
{
    MockServer::fake(function ($request) {
        if ($request->hasHeader('Authorization')) {
            return MockResponse::json(['data' => 'protected']);
        }
        return MockResponse::unauthorized(['error' => 'Missing token']);
    });

    // Without auth
    $response = get('https://api.example.com/protected');
    $this->assertFalse($response->successful());
    $this->assertEquals(401, $response->status());

    // With auth
    $response = fetch('https://api.example.com/protected', [
        'headers' => ['Authorization' => 'Bearer token'],
    ]);
    $this->assertTrue($response->successful());
    $this->assertEquals(['data' => 'protected'], $response->json());
}
```

## Testing Error Handling

Test various error scenarios:

```php
public function test_handles_network_errors(): void
{
    MockServer::fake([
        'https://api.example.com/error' => MockResponse::ok()->throw(
            new \RuntimeException('Connection timeout')
        ),
    ]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Connection timeout');

    get('https://api.example.com/error');
}

public function test_handles_server_errors(): void
{
    MockServer::fake([
        'https://api.example.com/server-error' => MockResponse::serverError(
            json_encode(['error' => 'Database connection failed'])
        ),
    ]);

    $response = get('https://api.example.com/server-error');

    $this->assertEquals(500, $response->status());
    $this->assertFalse($response->successful());
}
```

## Best Practices

1. **Reset MockServer in tearDown**: Always reset the MockServer instance in your test's `tearDown()` method:

```php
protected function tearDown(): void
{
    MockServer::resetInstance();
    parent::tearDown();
}
```

2. **Use specific patterns**: Prefer specific URL patterns over wildcards for better test clarity:

```php
// Good
MockServer::fake([
    'POST https://api.example.com/users' => MockResponse::created(),
]);

// Less specific
MockServer::fake([
    '*' => MockResponse::ok(),
]);
```

3. **Test edge cases**: Use sequences to test retry logic, rate limiting, and error recovery:

```php
MockResponse::sequence()
    ->pushStatus(429) // Rate limited
    ->pushStatus(429) // Still rate limited
    ->pushStatus(200); // Success after retry
```

4. **Verify request details**: Use assertion callbacks to verify request payloads, headers, and other details:

```php
MockServer::assertSent(function ($request) {
    $body = json_decode((string) $request->getBody(), true);
    return isset($body['required_field']) &&
           $request->hasHeader('Content-Type');
});
```

5. **Prevent stray requests in CI**: Use `preventStrayRequests()` in CI environments:

```php
if (getenv('CI')) {
    MockServer::preventStrayRequests();
}
```

6. **Keep test data in fixtures**: Store recorded requests/responses in JSON fixtures for reuse:

```php
$json = file_get_contents(__DIR__ . '/fixtures/user-api-responses.json');
Recorder::importFromJson($json);
```

## Integration Tests

For integration tests that need to hit real APIs:

```php
/**
 * @group integration
 */
class GithubApiIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        // Skip if no API token is configured
        if (empty(getenv('GITHUB_API_TOKEN'))) {
            $this->markTestSkipped('No GitHub API token available');
        }
    }

    public function test_can_fetch_user_profile(): void
    {
        $response = fetch('https://api.github.com/user', [
            'headers' => [
                'Authorization' => 'Bearer ' . getenv('GITHUB_API_TOKEN'),
                'Accept' => 'application/vnd.github.v3+json',
            ],
        ]);

        $this->assertTrue($response->successful());
        $this->assertEquals(200, $response->status());

        $user = $response->json();
        $this->assertArrayHasKey('login', $user);
    }
}
```

## Next Steps

- Learn about [Error Handling](/guide/error-handling) for robust applications
- Explore [Retry Handling](/guide/retry-handling) for resilient HTTP requests
- See [Asynchronous Requests](/guide/async-requests) for async testing patterns
