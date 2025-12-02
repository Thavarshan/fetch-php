# Phase 4 Instructions: Decouple Logging/Debug/Profiling from Handler State

**Status:** 📋 Ready to Execute
**Priority:** Medium
**Estimated Effort:** 2-3 hours

---

## Overview

**Problem:** Debug information (`lastDebugInfo`) is stored on the handler instance, creating a race condition when multiple concurrent requests use the same handler. Each request overwrites the previous debug info.

**Goal:** Make debug information per-request instead of shared handler state, ensuring concurrent requests don't interfere with each other's debug data.

---

## Current Architecture Issues

### 1. Race Condition in Debug Info

**Current Code (ManagesDebugAndProfiling):**
```php
trait ManagesDebugAndProfiling
{
    protected ?DebugInfo $lastDebugInfo = null;  // ❌ Shared state!

    public function getLastDebugInfo(): ?DebugInfo
    {
        return $this->lastDebugInfo;  // ❌ Returns whatever was last written
    }

    protected function createDebugInfo(...): DebugInfo
    {
        $this->lastDebugInfo = $this->getProfilerBridge()->createDebugInfo(...);
        // ❌ Overwrites previous value!
        return $this->lastDebugInfo;
    }
}
```

**Problem:**
- Request A starts, sets `lastDebugInfo`
- Request B starts (on same handler), overwrites `lastDebugInfo`
- Request A completes, but `getLastDebugInfo()` returns B's info
- **Result:** Debug info leaks between concurrent requests

### 2. ProfilerBridge Captures State

**Current Flow:**
```
Request 1 → Handler → ProfilerBridge → lastDebugInfo (stored on handler)
                ↓
Request 2 → Handler → ProfilerBridge → lastDebugInfo (OVERWRITES!)
```

---

## Proposed Solution

### Option A: Store Debug Info on Response (Recommended)

**Approach:** Attach debug info directly to the Response object so each response carries its own debug data.

**Changes:**

1. **Add debug info to Response:**
```php
// src/Fetch/Http/Response.php
class Response implements ResponseInterface
{
    private ?DebugInfo $debugInfo = null;

    public function withDebugInfo(DebugInfo $debugInfo): self
    {
        $clone = clone $this;
        $clone->debugInfo = $debugInfo;
        return $clone;
    }

    public function getDebugInfo(): ?DebugInfo
    {
        return $this->debugInfo;
    }
}
```

2. **Return debug info from request execution:**
```php
// src/Fetch/Concerns/PerformsHttpRequests.php
protected function executeSyncRequest(...): ResponseInterface
{
    // ... execute request ...

    $debugInfo = $this->createDebugInfo(...);  // Create but don't store
    $response = Response::createFromBase($psrResponse);

    if ($debugInfo !== null) {
        $response = $response->withDebugInfo($debugInfo);
    }

    return $response;
}
```

3. **Keep `getLastDebugInfo()` for backward compat:**
```php
// src/Fetch/Concerns/ManagesDebugAndProfiling.php
/**
 * @deprecated Store debug info on response instead
 */
public function getLastDebugInfo(): ?DebugInfo
{
    // Could return null or maintain a weak reference
    // This is for backward compatibility only
    return $this->lastDebugInfo;
}
```

**Pros:**
- ✅ Each response has its own debug info
- ✅ No race conditions
- ✅ Idiomatic (data travels with response)
- ✅ Easy to access: `$response->getDebugInfo()`

**Cons:**
- ⚠️ API change (but backward compatible)
- ⚠️ Users need to adapt to new pattern

### Option B: Store in ProfilerBridge with Request ID

**Approach:** Use request IDs to track debug info separately.

**Changes:**

1. **ProfilerBridge stores per-request:**
```php
class ProfilerBridge
{
    private array $debugInfoByRequestId = [];  // Map of requestId => DebugInfo

    public function storeDebugInfo(string $requestId, DebugInfo $info): void
    {
        $this->debugInfoByRequestId[$requestId] = $info;
    }

    public function getDebugInfo(string $requestId): ?DebugInfo
    {
        return $this->debugInfoByRequestId[$requestId] ?? null;
    }

    public function clearOldDebugInfo(int $maxAge = 60): void
    {
        // Periodic cleanup to prevent memory leak
    }
}
```

**Pros:**
- ✅ No API changes
- ✅ Internal implementation detail

**Cons:**
- ❌ More complex
- ❌ Requires memory management
- ❌ Request IDs might not always be available

### Option C: Thread-Local Storage (Not Recommended)

**Approach:** Use PHP fibers or similar to store debug info in thread-local context.

**Cons:**
- ❌ Requires PHP 8.1+ Fibers
- ❌ Adds complexity
- ❌ Not as clear as Option A

---

## Recommended Approach: Option A

**Store debug info on Response objects.**

---

## Step-by-Step Implementation

### Step 1: Add Debug Info to Response

**File:** `src/Fetch/Http/Response.php`

**Add property and methods:**
```php
private ?DebugInfo $debugInfo = null;

public function withDebugInfo(DebugInfo $debugInfo): self
{
    $clone = clone $this;
    $clone->debugInfo = $debugInfo;
    return $clone;
}

public function getDebugInfo(): ?DebugInfo
{
    return $this->debugInfo;
}

public function hasDebugInfo(): bool
{
    return $this->debugInfo !== null;
}
```

### Step 2: Update Response Interface

**File:** `src/Fetch/Interfaces/Response.php`

**Add methods:**
```php
public function getDebugInfo(): ?\Fetch\Support\DebugInfo;
public function hasDebugInfo(): bool;
```

### Step 3: Attach Debug Info in Request Execution

**File:** `src/Fetch/Concerns/PerformsHttpRequests.php`

**In `executeSyncRequest()`:**
```php
// After getting PSR response
$response = Response::createFromBase($psrResponse);

// Capture debug snapshot
$connectionStats = method_exists($this, 'getConnectionDebugStats')
    ? $this->getConnectionDebugStats()
    : [];

$debugInfo = $this->getProfilerBridge()->captureSnapshot(
    $method,
    $uri,
    $options,
    $psrResponse,
    $startTime,
    $startMemory,
    $connectionStats
);

if ($debugInfo !== null) {
    $response = $response->withDebugInfo($debugInfo);
    // Also store on handler for backward compat
    $this->lastDebugInfo = $debugInfo;
}

return $response;
```

**In `executeAsyncRequest()`:**
```php
return async(function () use (...): ResponseInterface {
    $response = $this->executeSyncRequest(...);
    // Debug info already attached by executeSyncRequest
    return $response;
});
```

### Step 4: Update Mock/Cache Returns

**Everywhere a response is returned early (mock, cache), attach debug info:**

```php
// Mock response
if ($mockResponse !== null) {
    $debugInfo = $this->createDebugInfo(...);
    if ($debugInfo !== null) {
        $mockResponse = $mockResponse->withDebugInfo($debugInfo);
    }
    return $mockResponse;
}

// Cached response
if ($cachedResult['response'] !== null) {
    $debugInfo = $this->createDebugInfo(...);
    if ($debugInfo !== null) {
        $cachedResult['response'] = $cachedResult['response']->withDebugInfo($debugInfo);
    }
    return $cachedResult['response'];
}
```

### Step 5: Update ManagesDebugAndProfiling

**File:** `src/Fetch/Concerns/ManagesDebugAndProfiling.php`

**Mark `getLastDebugInfo()` as backward compat:**
```php
/**
 * Get the last debug info from the most recent request.
 *
 * @deprecated Access debug info via $response->getDebugInfo() instead.
 *             This method is kept for backward compatibility but may not
 *             return accurate info when using concurrent requests.
 */
public function getLastDebugInfo(): ?DebugInfo
{
    return $this->lastDebugInfo;
}
```

**Make `createDebugInfo()` not store on handler:**
```php
protected function createDebugInfo(...): DebugInfo
{
    // Create debug info but DON'T store on handler
    // Caller will attach to response
    return $this->getProfilerBridge()->createDebugInfo(...);
}

// Remove this line from trait:
// $this->lastDebugInfo = $debugInfo;  ❌ Don't do this!
```

### Step 6: Add Tests

**File:** `tests/Unit/ResponseDebugInfoTest.php`

**Create new test file:**
```php
<?php

namespace Tests\Unit;

use Fetch\Http\Response;
use Fetch\Support\DebugInfo;
use PHPUnit\Framework\TestCase;

class ResponseDebugInfoTest extends TestCase
{
    public function test_response_can_store_debug_info(): void
    {
        $response = new Response(200);
        $debugInfo = DebugInfo::create('GET', 'https://example.com');

        $responseWithDebug = $response->withDebugInfo($debugInfo);

        $this->assertTrue($responseWithDebug->hasDebugInfo());
        $this->assertSame($debugInfo, $responseWithDebug->getDebugInfo());
    }

    public function test_response_without_debug_info_returns_null(): void
    {
        $response = new Response(200);

        $this->assertFalse($response->hasDebugInfo());
        $this->assertNull($response->getDebugInfo());
    }

    public function test_with_debug_info_returns_new_instance(): void
    {
        $response = new Response(200);
        $debugInfo = DebugInfo::create('GET', 'https://example.com');

        $responseWithDebug = $response->withDebugInfo($debugInfo);

        $this->assertNotSame($response, $responseWithDebug);
        $this->assertFalse($response->hasDebugInfo());
        $this->assertTrue($responseWithDebug->hasDebugInfo());
    }
}
```

**File:** `tests/Integration/ConcurrentRequestsTest.php`

**Add test for debug info isolation:**
```php
public function test_debug_info_isolated_per_request_on_response(): void
{
    // Create mock responses
    $mockHandler = new MockHandler([
        new GuzzleResponse(200, ['X-Request' => '1'], 'Response 1'),
        new GuzzleResponse(200, ['X-Request' => '2'], 'Response 2'),
    ]);

    $handlerStack = HandlerStack::create($mockHandler);
    $guzzleClient = new Client(['handler' => $handlerStack]);

    $handler = ClientHandler::createWithClient($guzzleClient);
    $handler->baseUri('https://api.example.com');
    $handler->withDebug(true);

    // Fire two requests
    $response1 = $handler->get('/test1');
    $response2 = $handler->get('/test2');

    // Each response should have its own debug info
    $this->assertTrue($response1->hasDebugInfo());
    $this->assertTrue($response2->hasDebugInfo());

    $debug1 = $response1->getDebugInfo();
    $debug2 = $response2->getDebugInfo();

    $this->assertNotNull($debug1);
    $this->assertNotNull($debug2);
    $this->assertNotSame($debug1, $debug2);

    // Verify debug info is correct for each request
    $this->assertStringContains('/test1', $debug1->getUri());
    $this->assertStringContains('/test2', $debug2->getUri());
}
```

### Step 7: Update Documentation

**File:** `docs/guide/debugging-and-profiling.md`

**Update examples to use new pattern:**
```php
// ✅ New way (preferred)
$response = $handler->get('/api/users');
$debugInfo = $response->getDebugInfo();

if ($debugInfo) {
    echo "Request took: " . $debugInfo->getDuration() . "ms\n";
    echo "Memory used: " . $debugInfo->getMemoryUsage() . " bytes\n";
}

// ⚠️ Old way (deprecated, may not work with concurrent requests)
$response = $handler->get('/api/users');
$debugInfo = $handler->getLastDebugInfo();
```

---

## Testing Checklist

- [ ] Response can store debug info
- [ ] Response without debug returns null
- [ ] `withDebugInfo()` returns new instance (immutable)
- [ ] Debug info attached in sync requests
- [ ] Debug info attached in async requests
- [ ] Debug info attached in cached responses
- [ ] Debug info attached in mock responses
- [ ] Concurrent requests have isolated debug info
- [ ] `getLastDebugInfo()` still works (backward compat)
- [ ] All existing tests pass

---

## Breaking Changes

**None!** This change is backward compatible:

- ✅ `getLastDebugInfo()` still works (but deprecated)
- ✅ New pattern available via `$response->getDebugInfo()`
- ✅ Existing code continues to function
- ✅ New code gets better isolation

---

## Success Criteria

- [ ] All 446+ tests passing
- [ ] Debug info isolated per request
- [ ] No race conditions in concurrent usage
- [ ] Backward compatibility maintained
- [ ] Documentation updated
- [ ] New tests added

---

## Estimated Impact

**Files Changed:** ~5 files
**Lines Added:** ~100 lines
**Tests Added:** ~5 tests
**Breaking Changes:** None
**Backward Compat:** 100%

---

## Notes

- This change builds on Phase 1's stateless refactor
- Debug info isolation is a natural consequence of per-request state
- The pattern (storing data on response) is idiomatic and clean
- Consider this pattern for future per-request data

---

## Next Steps After Completion

After Phase 4, proceed to Phase 5: Strengthen global helpers and URI handling.
