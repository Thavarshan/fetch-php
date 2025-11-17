---
title: Testing API
description: API reference for Fetch PHP testing utilities
---

# Testing API

Comprehensive testing utilities for mocking HTTP requests, recording/replaying responses, and making assertions.

## MockServer

The MockServer class provides a powerful interface for mocking HTTP requests in tests.

### Static Methods

#### `fake()`

Set up fake responses for HTTP requests.

```php
// Mock all requests with empty 200 responses
MockServer::fake();

// Mock specific URLs
MockServer::fake([
    'https://api.example.com/users' => MockResponse::json(['users' => []]),
    'POST https://api.example.com/users' => MockResponse::created(['id' => 123]),
]);

// Use callback for dynamic responses
MockServer::fake(function ($request) {
    return MockResponse::json(['dynamic' => true]);
});
```

**Parameters:**
- `$patterns` (array|Closure|null): URL patterns mapped to responses, or a callback

#### `preventStrayRequests()`

Prevent requests that don't match any registered fakes.

```php
MockServer::preventStrayRequests();

get('https://unmocked-url.com'); // Throws InvalidArgumentException
```

#### `allowStrayRequests()`

Allow stray requests to specific URL patterns.

```php
MockServer::allowStrayRequests([
    'https://localhost/*',
    'http://127.0.0.1:*',
]);
```

**Parameters:**
- `$patterns` (array): URL patterns to allow

#### `recorded()`

Get all recorded requests and responses.

```php
$records = MockServer::recorded();

// With filter
$postRecords = MockServer::recorded(function ($record) {
    return $record['request']->getMethod() === 'POST';
});
```

**Parameters:**
- `$filter` (Closure|null): Optional filter callback

**Returns:** `array<array{request: Request, response: ResponseInterface}>`

#### `assertSent()`

Assert that a request matching the criteria was sent.

```php
// By URL pattern
MockServer::assertSent('https://api.example.com/users');
MockServer::assertSent('POST https://api.example.com/users');

// With callback
MockServer::assertSent(function ($request, $response) {
    return $request->hasHeader('Authorization');
});

// Specific number of times
MockServer::assertSent('https://api.example.com/users', 2);
```

**Parameters:**
- `$pattern` (string|Closure): URL pattern or callback
- `$times` (int|null): Expected number of times (null = at least once)

#### `assertNotSent()`

Assert that a request matching the criteria was not sent.

```php
MockServer::assertNotSent('https://api.example.com/posts');

MockServer::assertNotSent(function ($request) {
    return $request->getMethod() === 'DELETE';
});
```

**Parameters:**
- `$pattern` (string|Closure): URL pattern or callback

#### `assertSentCount()`

Assert the exact number of requests sent.

```php
MockServer::assertSentCount(3);
```

**Parameters:**
- `$count` (int): Expected number of requests

#### `assertNothingSent()`

Assert that no requests were sent.

```php
MockServer::assertNothingSent();
```

#### `resetInstance()`

Reset the MockServer singleton instance.

```php
protected function tearDown(): void
{
    MockServer::resetInstance();
    parent::tearDown();
}
```

## MockResponse

Fluent builder for creating mock HTTP responses.

### Static Factory Methods

#### `create()`

Create a basic mock response.

```php
$response = MockResponse::create(200, 'Hello World', ['X-Custom' => 'value']);
```

**Parameters:**
- `$status` (int): HTTP status code (default: 200)
- `$body` (mixed): Response body (default: '')
- `$headers` (array): Response headers (default: [])

**Returns:** `MockResponse`

#### `json()`

Create a JSON response.

```php
$response = MockResponse::json(['name' => 'John'], 200);
```

**Parameters:**
- `$data` (array|object): Data to encode as JSON
- `$status` (int): HTTP status code (default: 200)
- `$headers` (array): Additional headers (default: [])

**Returns:** `MockResponse`

#### `sequence()`

Create a response sequence.

```php
$sequence = MockResponse::sequence()
    ->push(500, 'Error')
    ->push(200, 'Success');
```

**Parameters:**
- `$responses` (array): Initial responses (default: [])

**Returns:** `MockResponseSequence`

### Convenience Methods

#### Success Responses

```php
MockResponse::ok($body, $headers)              // 200
MockResponse::created($body, $headers)          // 201
MockResponse::noContent($headers)               // 204
```

#### Client Error Responses

```php
MockResponse::badRequest($body, $headers)            // 400
MockResponse::unauthorized($body, $headers)          // 401
MockResponse::forbidden($body, $headers)             // 403
MockResponse::notFound($body, $headers)              // 404
MockResponse::unprocessableEntity($body, $headers)   // 422
```

#### Server Error Responses

```php
MockResponse::serverError($body, $headers)           // 500
MockResponse::serviceUnavailable($body, $headers)    // 503
```

### Instance Methods

#### `delay()`

Set a delay before returning the response.

```php
$response = MockResponse::ok()->delay(100); // 100ms delay
```

**Parameters:**
- `$milliseconds` (int): Delay in milliseconds

**Returns:** `self`

#### `throw()`

Throw an exception instead of returning a response.

```php
$response = MockResponse::ok()->throw(new \RuntimeException('Network error'));
```

**Parameters:**
- `$throwable` (Throwable): Exception to throw

**Returns:** `self`

## MockResponseSequence

Manages a sequence of responses for testing retry logic and flaky endpoints.

### Instance Methods

#### `push()`

Add a response to the sequence.

```php
$sequence->push(200, 'First response', ['X-Header' => 'value']);
```

**Parameters:**
- `$status` (int): HTTP status code (default: 200)
- `$body` (mixed): Response body (default: '')
- `$headers` (array): Response headers (default: [])

**Returns:** `self`

#### `pushJson()`

Add a JSON response to the sequence.

```php
$sequence->pushJson(['data' => 'value'], 201);
```

**Parameters:**
- `$data` (array|object): Data to encode as JSON
- `$status` (int): HTTP status code (default: 200)
- `$headers` (array): Additional headers (default: [])

**Returns:** `self`

#### `pushStatus()`

Add a status-only response to the sequence.

```php
$sequence->pushStatus(404);
```

**Parameters:**
- `$status` (int): HTTP status code
- `$headers` (array): Response headers (default: [])

**Returns:** `self`

#### `pushResponse()`

Add a MockResponse instance to the sequence.

```php
$sequence->pushResponse(MockResponse::ok('Test'));
```

**Parameters:**
- `$response` (MockResponse): MockResponse instance

**Returns:** `self`

#### `whenEmpty()`

Set the default response when the sequence is exhausted.

```php
$sequence->whenEmpty(MockResponse::ok('default'));
```

**Parameters:**
- `$response` (MockResponse): Default response

**Returns:** `self`

#### `loop()`

Make the sequence loop back to the beginning when exhausted.

```php
$sequence->loop();
```

**Returns:** `self`

#### `reset()`

Reset the sequence to the beginning.

```php
$sequence->reset();
```

**Returns:** `self`

## Recorder

Record and replay HTTP requests and responses.

### Static Methods

#### `start()`

Start recording requests and responses.

```php
Recorder::start();
```

#### `stop()`

Stop recording and return the recordings.

```php
$recordings = Recorder::stop();
```

**Returns:** `array<array{request: Request, response: ResponseInterface, timestamp: float}>`

#### `replay()`

Replay recordings by setting up mock responses.

```php
Recorder::replay($recordings);
```

**Parameters:**
- `$recordings` (array): Recordings to replay

#### `exportToJson()`

Export recordings to JSON format.

```php
$json = Recorder::exportToJson();
file_put_contents('recordings.json', $json);
```

**Returns:** `string`

#### `importFromJson()`

Import recordings from JSON and replay them.

```php
$json = file_get_contents('recordings.json');
Recorder::importFromJson($json);
```

**Parameters:**
- `$json` (string): JSON string of recordings

**Throws:** `InvalidArgumentException` if JSON is invalid

#### `clear()`

Clear all recordings.

```php
Recorder::clear();
```

#### `isRecording()`

Check if recording is currently active.

```php
if (Recorder::isRecording()) {
    // Recording is active
}
```

**Returns:** `bool`

#### `getRecordings()`

Get all current recordings.

```php
$recordings = Recorder::getRecordings();
```

**Returns:** `array<array{request: Request, response: ResponseInterface, timestamp: float}>`

#### `reset()`

Reset the recorder state.

```php
Recorder::reset();
```

#### `resetInstance()`

Reset the singleton instance completely.

```php
Recorder::resetInstance();
```

## Usage Examples

### Basic Test Setup

```php
use PHPUnit\Framework\TestCase;
use Fetch\Testing\MockServer;
use Fetch\Testing\MockResponse;

class MyServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MockServer::fake([
            'https://api.example.com/users' => MockResponse::json(['users' => []]),
        ]);
    }

    protected function tearDown(): void
    {
        MockServer::resetInstance();
        parent::tearDown();
    }

    public function test_fetches_users(): void
    {
        $response = get('https://api.example.com/users');

        $this->assertEquals(200, $response->status());
        MockServer::assertSent('https://api.example.com/users');
    }
}
```

### Testing Retry Logic

```php
public function test_retries_on_failure(): void
{
    MockServer::fake([
        'https://api.example.com/unstable' => MockResponse::sequence()
            ->pushStatus(503)
            ->pushStatus(503)
            ->pushStatus(200),
    ]);

    $response = retry(fn() => get('https://api.example.com/unstable'), 3);

    $this->assertEquals(200, $response->status());
    MockServer::assertSent('https://api.example.com/unstable', 3);
}
```

### Recording and Replaying

```php
public function test_records_and_replays(): void
{
    // Record real requests
    MockServer::fake([
        'https://api.example.com/users' => MockResponse::json(['id' => 1]),
    ]);

    Recorder::start();
    get('https://api.example.com/users');
    $recordings = Recorder::stop();

    // Reset and replay
    MockServer::resetInstance();
    Recorder::replay($recordings);

    $response = get('https://api.example.com/users');
    $this->assertEquals(['id' => 1], $response->json());
}
```

## See Also

- [Testing Guide](/guide/testing) - Complete testing guide with examples
- [Error Handling](/guide/error-handling) - Testing error scenarios
- [Retry Handling](/guide/retry-handling) - Testing retry logic
