# Phase 6 Instructions: Comprehensive Testing and Coverage

**Status:** 📋 Ready to Execute
**Priority:** High
**Estimated Effort:** 4-6 hours

---

## Overview

**Problem:** While the codebase has good test coverage (446 tests, 1303 assertions), there are gaps in:
1. End-to-end "feature combo" tests (async + retries + profiling + caching together)
2. Concurrency edge cases (race conditions, promise rejection handling)
3. Helper error path coverage (HTTP errors, invalid inputs)
4. Integration scenarios that combine multiple features
5. Boundary conditions and error recovery

**Goal:** Achieve comprehensive test coverage that validates all feature combinations work correctly together, especially in concurrent scenarios.

---

## Current Test Coverage Analysis

### ✅ Well-Covered Areas

- **Core HTTP requests** - Sync GET, POST, PUT, DELETE, PATCH
- **Async/Promise operations** - Basic then(), catch(), finally() usage
- **Retry logic** - Basic retry scenarios with status codes
- **Configuration** - Option merging, normalization
- **URI handling** - Base URI, query params, path building
- **Concurrency safety** - Handler state isolation (added in Phase 1)

### ❌ Coverage Gaps

1. **Feature combinations** - Features tested in isolation, not together
2. **Concurrent promise rejection** - Multiple failing async requests
3. **Helper error handling** - fetch() with 4xx/5xx responses
4. **Cache + retry interactions** - Cached responses with retry logic
5. **Profiler + async scenarios** - Timing data for concurrent requests
6. **Connection pool limits** - Behavior when pool is exhausted
7. **Timeout + retry interactions** - Timeout triggering retry logic
8. **Large concurrent batches** - 100+ concurrent requests
9. **Memory leaks** - Long-running async loops
10. **Error propagation** - Exception chains through async stack

---

## Proposed Test Scenarios

### Scenario 1: Feature Combo - Async + Retry + Profiling

**Purpose:** Validate that async requests with retries correctly track timing data.

**Test File:** `tests/Integration/FeatureComboTest.php` (new file)

```php
<?php

namespace Tests\Integration;

use Fetch\Http\ClientHandler;
use Fetch\Support\FetchProfiler;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;

class FeatureComboTest extends TestCase
{
    public function test_async_request_with_retries_tracks_profiling_data(): void
    {
        // Mock handler: fail twice, then succeed
        $mockHandler = new MockHandler([
            new GuzzleResponse(503, [], 'Service Unavailable'),
            new GuzzleResponse(503, [], 'Service Unavailable'),
            new GuzzleResponse(200, [], json_encode(['success' => true])),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $profiler = new FetchProfiler();
        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');
        $handler->retry(3);

        // Fire async request with profiling
        $promise = $handler->async()->get('/api/data', [
            'profiler' => $profiler,
        ]);

        $response = await($promise);

        // Assert response succeeded after retries
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['success' => true], $response->json());

        // Assert profiling tracked all attempts
        $requests = $profiler->getRequests();
        $this->assertCount(3, $requests, 'Should track all 3 attempts (2 retries + 1 success)');

        // Verify each attempt was recorded
        foreach ($requests as $request) {
            $this->assertArrayHasKey('url', $request);
            $this->assertArrayHasKey('method', $request);
            $this->assertArrayHasKey('duration_ms', $request);
            $this->assertArrayHasKey('status_code', $request);
        }

        // First two should be 503, last one 200
        $this->assertEquals(503, $requests[0]['status_code']);
        $this->assertEquals(503, $requests[1]['status_code']);
        $this->assertEquals(200, $requests[2]['status_code']);
    }

    public function test_concurrent_async_requests_with_different_retry_configs(): void
    {
        // Mock handlers for two different endpoints
        $mock1 = new MockHandler([
            new GuzzleResponse(500, [], 'Error'),
            new GuzzleResponse(200, [], json_encode(['id' => 1])),
        ]);

        $mock2 = new MockHandler([
            new GuzzleResponse(503, [], 'Unavailable'),
            new GuzzleResponse(503, [], 'Unavailable'),
            new GuzzleResponse(503, [], 'Unavailable'),
            new GuzzleResponse(200, [], json_encode(['id' => 2])),
        ]);

        // Create two handlers with different retry configs
        $handler1 = ClientHandler::createWithClient(new Client(['handler' => HandlerStack::create($mock1)]));
        $handler1->retry(2); // Will succeed on second attempt

        $handler2 = ClientHandler::createWithClient(new Client(['handler' => HandlerStack::create($mock2)]));
        $handler2->retry(5); // Will succeed on fourth attempt

        // Fire both requests concurrently
        $promises = [
            'request1' => $handler1->async()->get('/endpoint1'),
            'request2' => $handler2->async()->get('/endpoint2'),
        ];

        $responses = await(all($promises));

        // Both should succeed with correct data
        $this->assertEquals(200, $responses['request1']->getStatusCode());
        $this->assertEquals(['id' => 1], $responses['request1']->json());

        $this->assertEquals(200, $responses['request2']->getStatusCode());
        $this->assertEquals(['id' => 2], $responses['request2']->json());
    }

    public function test_cached_response_with_retry_logic_does_not_retry(): void
    {
        // First request: succeed and cache
        $mockHandler = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['cached' => true])),
            // No second response - if retry fires, this will fail
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');
        $handler->retry(3); // Retry enabled
        $handler->withCache(); // Cache enabled

        // First request: cache the response
        $response1 = $handler->get('/api/data');
        $this->assertEquals(200, $response1->getStatusCode());

        // Second request: should return cached response WITHOUT retrying
        // (If it retries, MockHandler will fail because we only provided one response)
        $response2 = $handler->get('/api/data');
        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertEquals(['cached' => true], $response2->json());
    }
}
```

---

### Scenario 2: Concurrent Promise Rejection Handling

**Purpose:** Validate that multiple failing async requests handle errors correctly.

**Test File:** `tests/Integration/ConcurrentErrorHandlingTest.php` (new file)

```php
<?php

namespace Tests\Integration;

use Fetch\Http\ClientHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

class ConcurrentErrorHandlingTest extends TestCase
{
    public function test_multiple_concurrent_failures_each_reject_independently(): void
    {
        // Mock handler with multiple failures
        $mockHandler = new MockHandler([
            new GuzzleResponse(404, [], 'Not Found'),
            new GuzzleResponse(500, [], 'Server Error'),
            new ConnectException('Connection refused', new Request('GET', '/api/endpoint3')),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');

        // Fire three concurrent requests that will all fail differently
        $promises = [
            'req1' => $handler->async()->get('/endpoint1')->catch(fn($e) => ['error' => '404']),
            'req2' => $handler->async()->get('/endpoint2')->catch(fn($e) => ['error' => '500']),
            'req3' => $handler->async()->get('/endpoint3')->catch(fn($e) => ['error' => 'connection']),
        ];

        $results = await(all($promises));

        // Each should have failed with its own error
        $this->assertEquals(['error' => '404'], $results['req1']);
        $this->assertEquals(['error' => '500'], $results['req2']);
        $this->assertEquals(['error' => 'connection'], $results['req3']);
    }

    public function test_race_rejects_if_first_promise_fails(): void
    {
        // Mock handler: first response is error
        $mockHandler = new MockHandler([
            new GuzzleResponse(500, [], 'Error'),
            new GuzzleResponse(200, [], json_encode(['success' => true])), // Never reached
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);

        // Race between fast error and slow success
        $promises = [
            $handler->async()->get('/fast-error'),
            $handler->async()->get('/slow-success'),
        ];

        try {
            $result = await(race($promises));
            $this->fail('Should have thrown exception');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('500', $e->getMessage());
        }
    }

    public function test_all_rejects_if_any_promise_fails(): void
    {
        // Mock handler: second request fails
        $mockHandler = new MockHandler([
            new GuzzleResponse(200, [], json_encode(['id' => 1])),
            new GuzzleResponse(500, [], 'Error'),
            new GuzzleResponse(200, [], json_encode(['id' => 3])), // Never reached
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);

        $promises = [
            $handler->async()->get('/endpoint1'),
            $handler->async()->get('/endpoint2'), // Will fail
            $handler->async()->get('/endpoint3'),
        ];

        try {
            $results = await(all($promises));
            $this->fail('Should have thrown exception when one promise fails');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('500', $e->getMessage());
        }
    }
}
```

---

### Scenario 3: Helper Error Path Coverage

**Purpose:** Test global helper functions with error scenarios.

**Test File:** `tests/Unit/HelperErrorPathsTest.php` (new file)

```php
<?php

namespace Tests\Unit;

use Fetch\Http\ClientHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;

class HelperErrorPathsTest extends TestCase
{
    public function test_fetch_with_404_returns_response_not_exception(): void
    {
        // Mock 404 response
        $mockHandler = new MockHandler([
            new GuzzleResponse(404, [], 'Not Found'),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');

        // 404 is a valid HTTP response, not an error
        $response = $handler->get('/not-found');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', $response->getBody());
    }

    public function test_fetch_with_empty_uri_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        fetch(''); // Empty URI
    }

    public function test_fetch_with_invalid_method_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP method');

        fetch('https://example.com', ['method' => 'INVALID']);
    }

    public function test_post_helper_with_malformed_json_throws_exception(): void
    {
        // This test validates input validation
        // Note: JSON encoding errors should be caught early
        $this->expectException(\InvalidArgumentException::class);

        post('https://example.com', ['json' => "\xB1\x31"]); // Invalid UTF-8
    }

    public function test_fetch_with_timeout_zero_uses_default(): void
    {
        $handler = fetch_client();
        $handler->timeout(0); // Should use default or minimum

        // Verify timeout is set to a safe value
        $this->assertGreaterThan(0, $handler->getOption('timeout', 1));
    }
}
```

---

### Scenario 4: Large Concurrent Batches

**Purpose:** Test behavior with 100+ concurrent requests.

**Test File:** `tests/Integration/LargeConcurrentBatchTest.php` (new file)

```php
<?php

namespace Tests\Integration;

use Fetch\Http\ClientHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;

class LargeConcurrentBatchTest extends TestCase
{
    public function test_100_concurrent_requests_complete_successfully(): void
    {
        // Create 100 mock responses
        $responses = [];
        for ($i = 0; $i < 100; $i++) {
            $responses[] = new GuzzleResponse(200, [], json_encode(['id' => $i]));
        }

        $mockHandler = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');

        // Fire 100 concurrent requests
        $promises = [];
        for ($i = 0; $i < 100; $i++) {
            $promises["req_{$i}"] = $handler->async()->get("/item/{$i}");
        }

        $responses = await(all($promises));

        // All should succeed
        $this->assertCount(100, $responses);

        // Verify each response has correct ID
        for ($i = 0; $i < 100; $i++) {
            $this->assertEquals(['id' => $i], $responses["req_{$i}"]->json());
        }
    }

    public function test_batch_helper_with_large_dataset(): void
    {
        // Create 200 mock responses
        $mockResponses = [];
        for ($i = 0; $i < 200; $i++) {
            $mockResponses[] = new GuzzleResponse(200, [], json_encode(['id' => $i]));
        }

        $mockHandler = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');

        // Use batch() helper with concurrency limit
        $items = range(0, 199);

        $results = batch($items, function ($item) use ($handler) {
            return $handler->async()->get("/item/{$item}");
        }, concurrency: 10); // Process 10 at a time

        $responses = await($results);

        // All should succeed
        $this->assertCount(200, $responses);
    }

    public function test_memory_usage_remains_stable_during_large_batch(): void
    {
        $startMemory = memory_get_usage();

        // Create 500 mock responses
        $mockResponses = array_fill(0, 500, new GuzzleResponse(200, [], 'OK'));

        $mockHandler = new MockHandler($mockResponses);
        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);

        // Fire 500 requests
        $promises = [];
        for ($i = 0; $i < 500; $i++) {
            $promises[] = $handler->async()->get("/item/{$i}");
        }

        await(all($promises));

        $endMemory = memory_get_usage();
        $memoryIncrease = $endMemory - $startMemory;

        // Memory increase should be reasonable (< 50MB for 500 requests)
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 'Memory leak detected');
    }
}
```

---

### Scenario 5: Timeout + Retry Interactions

**Purpose:** Validate timeout triggers retry logic correctly.

**Test File:** Add to existing `tests/Unit/ManagesRetriesTest.php`

```php
public function test_timeout_triggers_retry_logic(): void
{
    // Mock handler: first request times out, second succeeds
    $mockHandler = new MockHandler([
        new \GuzzleHttp\Exception\ConnectException(
            'cURL error 28: Operation timed out',
            new \GuzzleHttp\Psr7\Request('GET', '/api/data')
        ),
        new GuzzleResponse(200, [], json_encode(['success' => true])),
    ]);

    $handlerStack = HandlerStack::create($mockHandler);
    $guzzleClient = new Client(['handler' => $handlerStack]);

    $handler = ClientHandler::createWithClient($guzzleClient);
    $handler->retry(2);
    $handler->timeout(1); // 1 second timeout

    // Should retry after timeout and succeed
    $response = $handler->get('/api/data');

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals(['success' => true], $response->json());
}

public function test_retry_respects_retry_delay_between_attempts(): void
{
    $attempts = [];

    $mockHandler = new MockHandler([
        new GuzzleResponse(503, [], 'Unavailable'),
        new GuzzleResponse(503, [], 'Unavailable'),
        new GuzzleResponse(200, [], json_encode(['success' => true])),
    ]);

    $handlerStack = HandlerStack::create($mockHandler);

    // Add middleware to track timing
    $handlerStack->push(\GuzzleHttp\Middleware::tap(function () use (&$attempts) {
        $attempts[] = microtime(true);
    }));

    $guzzleClient = new Client(['handler' => $handlerStack]);

    $handler = ClientHandler::createWithClient($guzzleClient);
    $handler->retry(3);
    $handler->retryDelay(200); // 200ms delay between retries

    $response = $handler->get('/api/data');

    $this->assertCount(3, $attempts, 'Should have made 3 attempts');

    // Verify delays between attempts (should be ~200ms)
    $delay1 = ($attempts[1] - $attempts[0]) * 1000; // Convert to ms
    $delay2 = ($attempts[2] - $attempts[1]) * 1000;

    // Allow some variance (150-250ms range)
    $this->assertGreaterThan(150, $delay1, 'First retry delay too short');
    $this->assertLessThan(250, $delay1, 'First retry delay too long');

    $this->assertGreaterThan(150, $delay2, 'Second retry delay too short');
    $this->assertLessThan(250, $delay2, 'Second retry delay too long');
}
```

---

## Testing Checklist

### Feature Combinations
- [ ] Async + retry + profiling works together
- [ ] Concurrent async with different retry configs
- [ ] Cache + retry interaction (cached responses don't retry)
- [ ] Timeout + retry interaction (timeout triggers retry)
- [ ] Async + error handling (promise rejection)
- [ ] Batch + retry (batch with failing requests)

### Concurrency Edge Cases
- [ ] Multiple concurrent failures handle errors independently
- [ ] race() rejects if first promise fails
- [ ] all() rejects if any promise fails
- [ ] 100+ concurrent requests complete successfully
- [ ] Memory remains stable during large batches
- [ ] Handler state isolation with concurrent requests

### Helper Error Paths
- [ ] fetch() with 404 returns response (not exception)
- [ ] fetch() with empty URI throws exception
- [ ] fetch() with invalid method throws exception
- [ ] post() with malformed JSON throws exception
- [ ] Helpers validate input before sending requests

### Retry Logic
- [ ] Timeout triggers retry
- [ ] Retry delay is respected between attempts
- [ ] Retry status codes are honored
- [ ] Max retries limit is enforced
- [ ] Exponential backoff works correctly

### Profiling
- [ ] Profiling tracks all retry attempts
- [ ] Concurrent requests tracked independently
- [ ] Timing data is accurate
- [ ] Memory tracking works correctly

### Error Recovery
- [ ] Exception chains preserved through async stack
- [ ] Error context includes method and URI
- [ ] Retry errors are logged/tracked
- [ ] Failed promises don't leak memory

---

## Success Criteria

- [ ] All new tests passing (estimated 25-30 new tests)
- [ ] Total test count > 470 tests
- [ ] Total assertions > 1400
- [ ] No memory leaks in large concurrent batches
- [ ] All feature combinations tested
- [ ] Error paths have comprehensive coverage
- [ ] Integration tests validate real-world scenarios
- [ ] No regressions in existing tests

---

## Estimated Impact

**Files Changed:** ~8-10 files
**Tests Added:** 25-30 tests
**Assertions Added:** 100-150 assertions
**Coverage Increase:** +3-5%
**Breaking Changes:** None
**Backward Compat:** 100%

---

## Implementation Order

1. **Create FeatureComboTest.php** (3 tests)
   - Async + retry + profiling
   - Concurrent requests with different configs
   - Cache + retry interaction

2. **Create ConcurrentErrorHandlingTest.php** (3 tests)
   - Multiple concurrent failures
   - race() with errors
   - all() with errors

3. **Create HelperErrorPathsTest.php** (5 tests)
   - HTTP error responses
   - Invalid inputs
   - Edge cases

4. **Create LargeConcurrentBatchTest.php** (3 tests)
   - 100 concurrent requests
   - batch() helper with large dataset
   - Memory stability

5. **Enhance ManagesRetriesTest.php** (2 tests)
   - Timeout + retry
   - Retry delay verification

6. **Run full test suite and verify no regressions**

7. **Generate coverage report and identify any remaining gaps**

---

## Optional Enhancements (Future)

### Performance Benchmarking

```php
public function test_concurrent_requests_performance(): void
{
    $startTime = microtime(true);

    // Fire 50 concurrent requests
    $promises = [];
    for ($i = 0; $i < 50; $i++) {
        $promises[] = $handler->async()->get("/item/{$i}");
    }

    await(all($promises));

    $duration = microtime(true) - $startTime;

    // Should complete in < 2 seconds (sanity check)
    $this->assertLessThan(2.0, $duration);
}
```

### Stress Testing

```php
public function test_1000_sequential_requests_no_memory_leak(): void
{
    $startMemory = memory_get_usage();

    for ($i = 0; $i < 1000; $i++) {
        $response = $handler->get("/item/{$i}");
        unset($response); // Free memory
    }

    $endMemory = memory_get_usage();
    $leak = $endMemory - $startMemory;

    // Should be < 10MB for 1000 requests
    $this->assertLessThan(10 * 1024 * 1024, $leak);
}
```

---

## Notes

- Focus on **integration tests** that validate real-world usage patterns
- Prioritize **concurrency scenarios** as they're most likely to reveal issues
- Use **MockHandler** for predictable, fast tests
- Consider adding **performance benchmarks** as separate test suite
- Document **expected behavior** in test names and comments

---

## Completion Criteria

Phase 6 is complete when:
- ✅ All feature combination scenarios tested
- ✅ Concurrent error handling validated
- ✅ Helper error paths covered
- ✅ Large batch behavior verified
- ✅ No memory leaks detected
- ✅ All tests passing (470+ tests)
- ✅ Coverage report generated
- ✅ No regressions

---

**This is the final phase of the refactor plan. After completion, the codebase will have comprehensive test coverage, concurrency safety, clean architecture, and excellent backward compatibility.** 🎯
