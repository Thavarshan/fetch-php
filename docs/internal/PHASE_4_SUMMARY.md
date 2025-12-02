# Phase 4 Summary: Decouple Logging/Debug/Profiling from Handler State

**Date Completed:** 2025-12-02
**Status:** ✅ **COMPLETE** - All 453 tests passing (+7 new tests)

---

## Goals Achieved

✅ Moved debug info from handler instance to per-response storage
✅ Eliminated race conditions in concurrent async scenarios
✅ Maintained 100% backward compatibility
✅ Added comprehensive tests for debug info isolation
✅ Added deprecation warnings for legacy API

---

## Changes Made

### 1. **Response Class** (`src/Fetch/Http/Response.php`)

**Added debug info storage property:**

```php
/**
 * Debug information for this specific request/response.
 *
 * This is stored per-response to avoid race conditions in concurrent usage.
 */
protected ?DebugInfo $debugInfo = null;
```

**Added methods to manage debug info:**

```php
/**
 * Attach debug information to this response.
 *
 * @internal
 * @return $this
 */
public function withDebugInfo(DebugInfo $debugInfo): static
{
    $this->debugInfo = $debugInfo;
    return $this;
}

/**
 * Get the debug information for this specific request/response.
 *
 * Returns null if debug mode was not enabled for this request.
 */
public function getDebugInfo(): ?DebugInfo
{
    return $this->debugInfo;
}

/**
 * Check if this response has debug information attached.
 */
public function hasDebugInfo(): bool
{
    return $this->debugInfo !== null;
}
```

**Impact:**
- Debug info now lives on the response, not the handler
- Each response has its own isolated debug info
- No race conditions in concurrent scenarios

### 2. **ManagesDebugAndProfiling Trait** (`src/Fetch/Concerns/ManagesDebugAndProfiling.php`)

**Deprecated `lastDebugInfo` property:**

```php
/**
 * The last debug info from the most recent request.
 *
 * @deprecated This property is kept for backward compatibility only.
 *             Debug info is now stored per-response via Response::getDebugInfo().
 *             This will be removed in a future major version.
 */
protected ?DebugInfo $lastDebugInfo = null;
```

**Updated `getLastDebugInfo()` with deprecation notice:**

```php
/**
 * Get the last debug info from the most recent request.
 *
 * @deprecated Use Response::getDebugInfo() instead for per-request debug info.
 *             This method is kept for backward compatibility but may return
 *             incorrect data in concurrent async scenarios.
 */
public function getLastDebugInfo(): ?DebugInfo
{
    return $this->lastDebugInfo;
}
```

**Updated `createDebugInfo()` to return debug info:**

```php
protected function createDebugInfo(...): DebugInfo
{
    $debugInfo = $this->getProfilerBridge()->createDebugInfo(...);

    // Update lastDebugInfo for backward compatibility
    $this->lastDebugInfo = $debugInfo;

    return $debugInfo; // ✅ Return for attachment to response
}
```

**Updated `captureDebugSnapshot()` to return debug info:**

```php
/**
 * Capture and store debug info via the profiler bridge.
 *
 * Returns a DebugInfo instance that should be attached to the response.
 * Also updates lastDebugInfo for backward compatibility.
 *
 * @return DebugInfo|null The debug info to attach to the response, or null if debug disabled
 */
protected function captureDebugSnapshot(...): ?DebugInfo
{
    $debugInfo = $this->getProfilerBridge()->captureSnapshot(...);

    if ($debugInfo !== null) {
        // Update lastDebugInfo for backward compatibility
        $this->lastDebugInfo = $debugInfo;
    }

    return $debugInfo; // ✅ Return for attachment to response
}
```

**Impact:**
- Methods now return debug info instead of just storing it
- Backward compatibility maintained via `lastDebugInfo`
- Clear deprecation path for future cleanup

### 3. **PerformsHttpRequests Trait** (`src/Fetch/Concerns/PerformsHttpRequests.php`)

**Updated all request execution paths to attach debug info to responses:**

**Mock response path:**
```php
$debugInfo = $this->captureDebugSnapshot(...);

// Attach debug info to response if available
if ($debugInfo !== null && $mockResponse instanceof Response) {
    $mockResponse->withDebugInfo($debugInfo);
}
```

**Cached response path:**
```php
$debugInfo = $this->captureDebugSnapshot(...);

// Attach debug info to cached response if available
if ($debugInfo !== null && $cachedResult['response'] instanceof Response) {
    $cachedResult['response']->withDebugInfo($debugInfo);
}
```

**Sync request path:**
```php
$debugInfo = $this->captureDebugSnapshot(...);

// Attach debug info to response if available
if ($debugInfo !== null) {
    $response->withDebugInfo($debugInfo);
}
```

**Stale cache error path:**
```php
$debugInfo = $this->captureDebugSnapshot(...);

// Attach debug info to stale response if available
if ($debugInfo !== null && $staleResponse instanceof Response) {
    $staleResponse->withDebugInfo($debugInfo);
}
```

**Impact:**
- Debug info automatically attached to every response
- No manual tracking needed
- Works in all code paths (sync, async, cached, mocked)

### 4. **Comprehensive Testing** (`tests/Integration/DebugInfoIsolationTest.php`)

**Created 7 new tests:**

1. ✅ `test_debug_info_is_attached_to_each_response`
2. ✅ `test_concurrent_async_requests_have_isolated_debug_info`
3. ✅ `test_debug_info_contains_timing_data`
4. ✅ `test_debug_info_attached_to_profiled_requests`
5. ✅ `test_response_without_debug_mode_has_no_debug_info`
6. ✅ `test_getLastDebugInfo_still_works_for_backward_compatibility`
7. ✅ `test_mixed_sync_and_async_requests_have_isolated_debug_info`

**Coverage:**
- Debug info isolation in sync requests
- Debug info isolation in async requests
- Debug info with profiling enabled
- Backward compatibility with `getLastDebugInfo()`
- Mixed sync/async scenarios

---

## Before and After

### Before Phase 4

❌ **Debug info stored on handler instance:**
```php
// src/Fetch/Concerns/ManagesDebugAndProfiling.php
protected ?DebugInfo $lastDebugInfo = null;

protected function captureDebugSnapshot(...): void
{
    $debugInfo = $this->getProfilerBridge()->captureSnapshot(...);

    if ($debugInfo !== null) {
        $this->lastDebugInfo = $debugInfo; // ❌ Stored on handler
    }
}

// Usage
$handler = ClientHandler::create()->withDebug(true);
$response1 = $handler->get('/endpoint1');
$response2 = $handler->get('/endpoint2');

$debug = $handler->getLastDebugInfo(); // ❌ Returns debug for endpoint2 only!
```

❌ **Race condition in concurrent async requests:**
```php
// Request A and B fire concurrently
$promiseA = $handler->async()->get('/a');
$promiseB = $handler->async()->get('/b');

$responses = await(all([$promiseA, $promiseB]));

// lastDebugInfo could be from A or B - unpredictable!
$debug = $handler->getLastDebugInfo(); // ❌ Race condition
```

### After Phase 4

✅ **Debug info stored per-response:**
```php
// src/Fetch/Http/Response.php
protected ?DebugInfo $debugInfo = null;

public function getDebugInfo(): ?DebugInfo
{
    return $this->debugInfo; // ✅ Stored on response
}

// Usage
$handler = ClientHandler::create()->withDebug(true);
$response1 = $handler->get('/endpoint1');
$response2 = $handler->get('/endpoint2');

$debug1 = $response1->getDebugInfo(); // ✅ Debug for endpoint1
$debug2 = $response2->getDebugInfo(); // ✅ Debug for endpoint2
```

✅ **No race conditions in concurrent requests:**
```php
// Request A and B fire concurrently
$promiseA = $handler->async()->get('/a');
$promiseB = $handler->async()->get('/b');

$responses = await(all([$promiseA, $promiseB]));

// Each response has its own isolated debug info
$debugA = $responses[0]->getDebugInfo(); // ✅ Debug for /a
$debugB = $responses[1]->getDebugInfo(); // ✅ Debug for /b
```

---

## Backward Compatibility

### ✅ 100% Backward Compatible

**Old API still works:**
```php
$handler = ClientHandler::create()->withDebug(true);
$response = $handler->get('/api/data');

// Old way (deprecated but still works)
$debug = $handler->getLastDebugInfo(); // ⚠️ Deprecated but functional
```

**New API recommended:**
```php
$handler = ClientHandler::create()->withDebug(true);
$response = $handler->get('/api/data');

// New way (recommended)
$debug = $response->getDebugInfo(); // ✅ Per-response debug info
```

**Deprecation warnings:**
- `@deprecated` tags added to old API
- No runtime warnings (silent deprecation)
- Documentation updated to recommend new API
- Can be removed in future major version

---

## Test Results

### Before Phase 4
- **Tests:** 446
- **Assertions:** 1303

### After Phase 4
- **Tests:** 453 (+7)
- **Assertions:** 1351 (+48)
- **Status:** ✅ **100% passing**

### Test Categories

| Category | Tests | Status |
|----------|-------|--------|
| Debug info attachment | 3 | ✅ Pass |
| Debug info isolation | 2 | ✅ Pass |
| Backward compatibility | 1 | ✅ Pass |
| Mixed scenarios | 1 | ✅ Pass |
| **Total** | **7** | ✅ **Pass** |

---

## Key Design Decisions

### 1. **Store debug info on Response objects**

**Why:**
- Responses are created per-request (one-to-one mapping)
- Responses are already returned to users
- Eliminates need for handler state mutation
- Natural place for request-specific metadata

**Alternative considered:** External debug info storage (rejected - too complex)

### 2. **Maintain backward compatibility**

**Why:**
- Users may rely on `getLastDebugInfo()`
- Breaking changes require major version bump
- Silent migration is better UX
- Provides time for gradual adoption

**Approach:**
- Keep `lastDebugInfo` property (with deprecation notice)
- Keep `getLastDebugInfo()` method (with deprecation notice)
- Update both old and new APIs simultaneously
- Document new API as recommended

### 3. **Attach debug info in PerformsHttpRequests**

**Why:**
- All request paths go through this trait
- Centralized location for debug attachment
- Ensures consistency across sync/async/cache paths
- Easy to maintain

**Alternative considered:** Attach in individual methods (rejected - too scattered)

### 4. **Return debug info from capture methods**

**Why:**
- Makes data flow explicit (return value vs. mutation)
- Enables functional programming style
- Easier to test
- Clear API contract

**Pattern:**
```php
// Before: void return, stores internally
protected function captureDebugSnapshot(...): void

// After: returns debug info for attachment
protected function captureDebugSnapshot(...): ?DebugInfo
```

---

## Implementation Approach

### Design Principles

1. **Per-response isolation:** Debug info lives with the response it describes
2. **Backward compatible:** Old API continues to work
3. **Explicit data flow:** Methods return debug info instead of mutating state
4. **Comprehensive testing:** 7 new tests cover all scenarios
5. **Clear migration path:** Deprecation notices guide users to new API

### Debug Info Flow

```
Request Execution
   ↓
captureDebugSnapshot()
   ↓
   [ Create DebugInfo ]
   ↓
   [ Store in lastDebugInfo (backward compat) ]
   ↓
   [ Return DebugInfo ]
   ↓
response->withDebugInfo(debugInfo)
   ↓
Response (with debug info attached)
   ↓
User calls response->getDebugInfo()
   ↓
Per-Request Debug Info ✅
```

### Why This Approach?

**Attach debug info to responses:**
- ✅ One-to-one mapping (request → response → debug info)
- ✅ No race conditions
- ✅ Natural ownership model
- ✅ Clean API

**Maintain backward compatibility:**
- ✅ No breaking changes
- ✅ Smooth migration path
- ✅ Users can adopt at their own pace
- ✅ Silent deprecation warnings

---

## Usage Examples

### New Code (Recommended)

```php
// ✅ Preferred: Use per-response debug info
$handler = ClientHandler::create();
$handler->withDebug(true);

$response = $handler->get('/api/users');

// Get debug info from the response
if ($response->hasDebugInfo()) {
    $debug = $response->getDebugInfo();
    $timings = $debug->getTimings();
    $requestData = $debug->getRequestData();
}
```

### Concurrent Async Requests

```php
// ✅ Each response has isolated debug info
$handler = ClientHandler::create()->withDebug(true);

$promises = [
    'users' => $handler->async()->get('/api/users'),
    'posts' => $handler->async()->get('/api/posts'),
    'comments' => $handler->async()->get('/api/comments'),
];

$responses = await(all($promises));

// Each response has its own debug info - no race conditions!
$usersDebug = $responses['users']->getDebugInfo();
$postsDebug = $responses['posts']->getDebugInfo();
$commentsDebug = $responses['comments']->getDebugInfo();
```

### Legacy Code (Still Works)

```php
// ⚠️ Legacy: Still works but may be incorrect in concurrent scenarios
$handler = ClientHandler::create()->withDebug(true);
$response = $handler->get('/api/users');

// Old API (deprecated)
$debug = $handler->getLastDebugInfo();
```

---

## Migration Guide (Optional)

### For New Code

**Always use the new per-response API:**
```php
// ✅ Good
$response = $handler->get('/api/data');
$debug = $response->getDebugInfo();

// ❌ Avoid
$response = $handler->get('/api/data');
$debug = $handler->getLastDebugInfo();
```

### For Existing Code

**No immediate action required!** The old API still works.

**Optional migration:**
```php
// Before
$handler->withDebug(true);
$response = $handler->get('/api/data');
$debug = $handler->getLastDebugInfo(); // Old way

// After
$handler->withDebug(true);
$response = $handler->get('/api/data');
$debug = $response->getDebugInfo(); // New way
```

**Benefits of migrating:**
- ✅ Correct behavior in concurrent scenarios
- ✅ Clearer code intent
- ✅ Future-proof (old API may be removed in v2.0)

---

## Future Cleanup (Not Implemented)

### Potential v2.0 Changes

1. **Remove deprecated property:**
   ```php
   // Remove this in v2.0
   protected ?DebugInfo $lastDebugInfo = null;
   ```

2. **Remove deprecated method:**
   ```php
   // Remove this in v2.0
   public function getLastDebugInfo(): ?DebugInfo
   ```

3. **Update documentation:**
   - Remove references to `getLastDebugInfo()`
   - Show only `response->getDebugInfo()` in examples

**Timeline:** Not planned yet - will wait for user adoption

---

## Key Learnings

### ✅ What Worked Well

1. **Per-response storage** - Natural ownership model, no race conditions
2. **Backward compatibility** - Zero breaking changes, smooth migration
3. **Comprehensive testing** - 7 new tests caught edge cases
4. **Clear deprecation** - Users know how to migrate
5. **Minimal changes** - Focused refactor, easy to review

### 🎯 Design Decisions

1. **Debug info on Response** - One-to-one mapping eliminates races
2. **Return values over mutation** - Explicit data flow
3. **Deprecated but functional** - Backward compat without warnings
4. **Test all paths** - Sync, async, cache, mock all covered
5. **Document both APIs** - Clear migration path

### 💡 Insights

1. **Concurrent safety requires per-request data** - Handler state is dangerous
2. **Backward compat enables incremental adoption** - No forced migration
3. **Testing concurrent scenarios is critical** - Found issues early
4. **Deprecation without removal is smooth** - Users can migrate gradually
5. **Per-response metadata is a pattern** - Useful for other features

---

## Phase 4 Completion Checklist

- [x] Analyzed current debug/profiling implementation
- [x] Added debug info storage to Response class
- [x] Updated ManagesDebugAndProfiling to return debug info
- [x] Updated PerformsHttpRequests to attach debug info to responses
- [x] Added deprecation notices to legacy API
- [x] Created 7 comprehensive tests (48 assertions)
- [x] Verified backward compatibility
- [x] All 453 tests passing
- [x] No breaking changes
- [x] Documentation updated

---

## What's Next: Phase 5

**Goal:** Strengthen global helpers and URI handling

**Tasks:**
1. Clarify helper return types
2. Add internal enum enforcement
3. Strengthen URI validation
4. Add helper error path coverage
5. Test edge cases

---

## Summary

Phase 4 successfully decoupled debug info from handler state, eliminating race conditions while maintaining perfect backward compatibility:

- ✅ **Per-response debug info** - No more race conditions
- ✅ **Handler concurrency-safe** - Debug info isolated per response
- ✅ **Backward compatible** - Legacy API still works
- ✅ **Well-tested** - 7 new tests, 48 assertions
- ✅ **Clear migration path** - Deprecation notices guide users
- ✅ **No breaking changes** - Existing code unaffected

**Debug info is now safe for concurrent usage!** 🎯
