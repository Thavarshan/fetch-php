# Fetch PHP Refactor Plan

**Date:** 2025-12-02
**Purpose:** Document the current architecture and planned refactoring to improve concurrency safety, cohesion, and maintainability.

## Executive Summary

This document outlines a systematic refactoring of Fetch PHP to eliminate stateful handler patterns, improve separation of concerns, and enhance concurrent request handling. The refactor preserves the existing public API while making the internal architecture more robust and maintainable.

---

## Current Architecture Analysis

### Core Components

#### 1. **ClientHandler** (`src/Fetch/Http/ClientHandler.php`)
- **Role:** Main HTTP client implementation; composes 8 traits
- **Size:** ~600 LOC
- **State:**
  - `protected array $options` - Request options (mutable, shared across calls)
  - `protected ?int $timeout` - Timeout setting (mutable)
  - `protected int $maxRetries` - Retry count (mutable)
  - `protected int $retryDelay` - Retry delay (mutable)
  - `protected bool $isAsync` - Async mode flag (mutable)
  - `protected LoggerInterface $logger` - PSR-3 logger
  - `protected string $logLevel` - Logging level
  - `protected ?CacheManager $cacheManager` - Cache manager
  - `protected ?DebugInfo $lastDebugInfo` - Debug info from last request (RACE CONDITION)
  - `protected ?ProfilerBridge $profilerBridge` - Profiling bridge

**Issues:**
- ❌ **God class** - too many responsibilities
- ❌ **Shared mutable state** across concurrent requests
- ❌ **State restoration pattern** in `PerformsHttpRequests::sendRequest()` is brittle
- ❌ `lastDebugInfo` property subject to race conditions in concurrent usage
- ❌ Mixed responsibilities: HTTP, caching, retries, logging, profiling, pooling

#### 2. **RequestContext** (`src/Fetch/Support/RequestContext.php`)
- **Role:** Immutable value object for per-request config
- **State:** All readonly properties
- **Strengths:**
  - ✅ Immutable design with `with*()` methods returning new instances
  - ✅ Type-safe property access
  - ✅ Clear precedence rules documented
  - ✅ `toGuzzleOptions()` for clean transformation

**Issues:**
- ⚠️ **Underutilized** - traits still read from handler state (`$this->options`) instead of passing context
- ⚠️ Integration is partial: context is built but then handler state is mutated anyway

#### 3. **RequestOptions** (`src/Fetch/Support/RequestOptions.php`)
- **Role:** Static utility for options normalization and merging
- **Strengths:**
  - ✅ Body option conflict resolution
  - ✅ Clear precedence documentation
  - ✅ Validation methods

**Issues:**
- ⚠️ Could be more tightly integrated with RequestContext

### Trait Responsibilities

#### **PerformsHttpRequests**
- Implements HTTP method handlers (get, post, put, patch, delete, etc.)
- `sendRequest()` orchestrates the entire request lifecycle
- **Current flow:**
  1. Merge options into RequestContext
  2. **MUTATE handler state** (`options`, `timeout`, `maxRetries`, etc.)
  3. Store original state in closure
  4. Execute request (sync or async)
  5. Restore state via closure in `finally` or `always()`

**Problems:**
- ❌ State mutation + restoration is **not thread-safe**
- ❌ If async request is fired and handler reused before `always()` runs, state leaks
- ❌ Complex flow makes reasoning difficult
- ❌ Tight coupling to handler properties

#### **ConfiguresRequests**
- Fluent API for configuring requests (`withHeaders`, `withBody`, `timeout`, etc.)
- `withOptions()` mutates `$this->options` and maps retry values to handler properties

**Problems:**
- ❌ Direct mutation of shared handler state
- ❌ Inconsistent naming: `retries` vs `max_retries` accepted
- ❌ No clear distinction between "long-lived config" and "per-request overrides"

#### **HandlesUris**
- URI building and validation
- **Reads from `$this->options`** directly (`base_uri`, `query`)

**Problems:**
- ❌ Tight coupling to handler state instead of receiving context as parameter

#### **ManagesDebugAndProfiling**
- Debug mode configuration
- Profiling lifecycle hooks
- Stores `$this->lastDebugInfo` (mutable, shared)

**Problems:**
- ❌ `lastDebugInfo` stored on handler instance → **race condition** in concurrent usage
- ❌ Debug info returned by methods but often ignored by callers

#### **ManagesRetries**
- Retry logic with backoff/jitter
- Reads retry config from handler properties

**Problems:**
- ❌ Retry state (max attempts, delay, status codes) stored on handler, not per-request
- ❌ No per-request override aside from `withOptions` mutation

#### **ManagesPromises**
- Provides `then()`, `catch()`, `finally()` methods
- **Not in public interface** (`ClientHandlerInterface`)

**Problems:**
- ❌ Methods available on concrete class but not on interface → LSP violation
- ❌ Implicitly sets `isAsync = true` → blurs sync/async contract

#### **ManagesConnectionPool**
- HTTP/2 connection pooling
- Shares state across handlers

**Notes:**
- Generally well-encapsulated for pool state

#### **HandlesMocking**
- Test mocking support
- Creates mock responses

**Notes:**
- Reasonably isolated

### Global Helpers (`src/Fetch/Support/helpers.php`)

- `fetch()`, `get()`, `post()`, etc.
- **Return types:** Mixed (`ResponseInterface | ClientHandlerInterface | Client`)
- **Issues:**
  - ⚠️ Inconsistent return types
  - ⚠️ No enum enforcement for methods/content types
  - ⚠️ Limited test coverage for error/async paths

### Interface Hierarchy (`src/Fetch/Interfaces/ClientHandler.php`)

```
ClientHandler extends:
  - CacheableRequestHandler
  - DebuggableHandler
  - HttpClientAware
  - PoolAwareHandler
  - PromiseHandler
  - RequestConfigurator
  - RequestExecutor
  - RetryableHandler
```

**Problems:**
- ❌ Concrete `ClientHandler` exposes methods not in interface:
  - `then()`, `catch()`, `finally()` (from `ManagesPromises` trait)
  - `createMockResponse()`, `prepareGuzzleOptions()`, etc.
- ❌ Public surface larger than interface contract suggests

---

## Key Issues Summary

### 1. **Concurrency Safety** (HIGH PRIORITY)
- ❌ Shared mutable state in handler (`options`, `timeout`, `maxRetries`, `retryDelay`, `isAsync`)
- ❌ State restore pattern unsafe under concurrent async requests
- ❌ `lastDebugInfo` subject to race conditions
- ❌ If handler is reused before async `always()` callback runs, state leaks

### 2. **God Class Pattern** (HIGH PRIORITY)
- ❌ `ClientHandler` has too many responsibilities via trait composition
- ❌ Hard to reason about cross-cutting concerns
- ❌ Profiling, logging, retry, caching, HTTP all interleaved

### 3. **Configuration Inconsistency** (MEDIUM PRIORITY)
- ⚠️ Multiple option names for same thing (`retries` vs `max_retries`)
- ⚠️ `cache` option accepts bool or array with unclear semantics
- ⚠️ No clear boundary between "global defaults" and "per-request overrides"

### 4. **Interface/Implementation Mismatch** (MEDIUM PRIORITY)
- ❌ Public methods not declared in interface (`then`, `catch`, `finally`)
- ❌ Leads to LSP violations and discoverability issues

### 5. **Insufficient Context Passing** (MEDIUM PRIORITY)
- ⚠️ Traits read from `$this->options` instead of receiving `RequestContext` parameter
- ⚠️ `RequestContext` built but then handler state mutated anyway

### 6. **Testing Gaps** (LOW PRIORITY)
- ⚠️ No tests for concurrent handler usage
- ⚠️ Limited tests for helper error/async paths
- ⚠️ End-to-end tests combining async + retries + profiling + caching are sparse

---

## Refactor Strategy

### Guiding Principles

1. **Preserve Public API** - Maintain backward compatibility for users
2. **Incremental Changes** - Small, testable refactors over big rewrites
3. **Stateless Request Flow** - Eliminate per-request handler mutation
4. **Context Passing** - Use `RequestContext` as the source of truth
5. **Clear Separation** - HTTP concerns vs async vs retries vs logging
6. **Type Safety** - Leverage enums and strict types

### Six-Phase Plan

#### **PHASE 0: Orientation and Documentation** ✅
- [x] Scan and analyze codebase
- [x] Create this design document
- [x] Identify key issues and patterns

#### **PHASE 1: Make ClientHandler Per-Request Stateless** (HIGH PRIORITY)
**Goal:** Eliminate fragile "mutate → send → restore" pattern

**Changes:**
1. Refactor `PerformsHttpRequests::sendRequest()`:
   - Build `RequestContext` from merged options
   - **Pass context through execution stack** (don't mutate handler)
   - Remove state restoration closure
   - Ensure retry/cache/debug/profiling read from context, not handler properties

2. Update trait methods to accept `RequestContext` parameter:
   - `HandlesUris::buildFullUri(RequestContext $context)`
   - Retry logic reads from context
   - Profiling/logging reads from context

3. Keep handler "long-lived config" explicit:
   - Base URI, global headers, default timeout → seed RequestContext only
   - Never mutate during request execution

4. Add concurrency tests:
   - Fire multiple async requests on same handler
   - Assert no state interference

**Success Criteria:**
- ✅ Handler can be safely reused across concurrent async requests
- ✅ No shared mutable state modified during request execution
- ✅ All existing tests pass

#### **PHASE 2: Align Interfaces and Public Surface** (MEDIUM PRIORITY)
**Goal:** Bring interface contract and concrete implementation into alignment

**Changes:**
1. Analyze usage of `then()`, `catch()`, `finally()`:
   - If public API: Add to interface or create `AsyncClientHandler` interface
   - If internal: Make protected or move to dedicated helper

2. Review extra public methods:
   - `prepareGuzzleOptions()` → protected
   - `createMockResponse()` → keep public (testing utility)

3. Standardize error handling:
   - Ensure consistent exception types for sync vs async

**Success Criteria:**
- ✅ Interface declares all intended public methods
- ✅ No surprise LSP violations
- ✅ Clear documentation of async API

#### **PHASE 3: Standardize Configuration and Options Vocabulary** (MEDIUM PRIORITY)
**Goal:** Make configuration consistent and predictable

**Changes:**
1. Normalize option keys:
   - Choose canonical names (`max_retries` not `retries`)
   - Accept legacy keys but normalize internally
   - Document in docblocks and mark legacy as deprecated

2. Move retry/cache/debug config into `RequestContext`:
   - Make context the single source of truth
   - Avoid traits reading scattered handler properties

3. Update `ConfiguresRequests::withOptions()`:
   - Build new `RequestContext` instead of mutating handler
   - Deprecate direct property mutations

**Success Criteria:**
- ✅ Clear, documented option names
- ✅ Backward-compatible with legacy keys
- ✅ RequestContext owns all per-request config

#### **PHASE 4: Decouple Logging/Debug/Profiling from Handler State** (MEDIUM PRIORITY)
**Goal:** Make debug info per-request, not shared mutable handler state

**Changes:**
1. Move `lastDebugInfo` off handler instance:
   - Store in profiling bridge or return as value
   - Associate with response or request ID

2. Make debug snapshots explicit:
   - Return `DebugInfo` from methods
   - Avoid mutation of handler fields

3. Add end-to-end debug tests:
   - Profiling + async + retries + sensitive header redaction

**Success Criteria:**
- ✅ No race conditions in debug info capture
- ✅ Debug data correctly associated with each request
- ✅ Profiling works correctly with concurrent requests

#### **PHASE 5: Strengthen Global Helpers and URI Handling** (LOW PRIORITY)
**Goal:** Make helpers predictable and well-typed

**Changes:**
1. Global helpers:
   - Tighten return types in docblocks
   - Enforce enum usage internally
   - Add tests for error/async paths

2. URI handling:
   - Stop reading `$this->options` directly
   - Use `RequestContext` as parameter
   - Test URI construction under concurrent usage

**Success Criteria:**
- ✅ Predictable helper return types
- ✅ URI handling decoupled from handler state
- ✅ Comprehensive helper tests

#### **PHASE 6: Testing and Coverage** (LOW PRIORITY)
**Goal:** Close major testing gaps

**Changes:**
1. Add concurrency tests:
   - Multiple async requests on same handler
   - Different options per request
   - Assert isolation of responses, debug info, retry behavior

2. Add helper tests:
   - Error handling
   - Async/await integration

3. Add feature combo tests:
   - Async + retries + profiling + caching
   - Verify correct behavior and debug data

**Success Criteria:**
- ✅ All concurrency edge cases covered
- ✅ No regressions in existing tests
- ✅ High confidence in safety of concurrent usage

---

## Migration Path for Users

### No Breaking Changes Expected

All refactors are **internal architecture improvements**. The public API remains stable:

- ✅ Global helpers (`fetch()`, `get()`, `post()`, etc.) unchanged
- ✅ Fluent API (`withHeaders()`, `timeout()`, etc.) unchanged
- ✅ Enum usage remains the same
- ✅ Async utilities (`async()`, `await()`, etc.) unchanged

### Deprecations (Graceful)

- Legacy option keys (`retries` → `max_retries`) will be supported but marked deprecated in docblocks
- No runtime warnings; smooth transition

---

## Success Metrics

1. **Concurrency Safety:**
   - ✅ Handler can be safely reused across threads/fibers
   - ✅ No race conditions in state or debug info

2. **Code Quality:**
   - ✅ Reduced coupling between concerns
   - ✅ Clear separation: HTTP vs async vs retries vs logging
   - ✅ Easier to reason about request lifecycle

3. **Maintainability:**
   - ✅ Easier to add new features
   - ✅ Clearer trait responsibilities
   - ✅ Better test coverage

4. **User Experience:**
   - ✅ No breaking changes
   - ✅ More predictable behavior
   - ✅ Better error messages and debugging

---

## Next Steps

1. ✅ Complete PHASE 0 (this document)
2. Begin PHASE 1: Stateless request flow refactor
3. Add concurrency tests
4. Proceed through phases sequentially

---

## References

- Audit Summary (provided in task)
- Current codebase at `/Users/jerome/Projects/apps/fetch-php`
- PHPUnit test suite in `tests/`
