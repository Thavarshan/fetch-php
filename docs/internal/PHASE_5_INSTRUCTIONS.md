# Phase 5 Instructions: Strengthen Global Helpers and URI Handling

**Status:** 📋 Ready to Execute
**Priority:** Low-Medium
**Estimated Effort:** 2-3 hours

---

## Overview

**Problem:** Global helpers and URI handling have some areas that could be improved:
1. Mixed return types in helpers (`ResponseInterface | ClientHandlerInterface`)
2. No enum enforcement for methods/content types
3. URI handling still has some coupling to handler state (though Phase 1 improved this)
4. Limited test coverage for error/async paths

**Goal:** Make helpers more predictable, better typed, and thoroughly tested.

---

## Current Issues

### 1. Mixed Return Types in Helpers

**Current Code (`src/Fetch/Support/helpers.php`):**
```php
/**
 * @return ResponseInterface|ClientHandlerInterface|Client
 */
function fetch(string|RequestInterface|null $resource = null, ?array $options = [])
{
    // If no resource, return handler for chaining
    if ($resource === null) {
        return fetch_client();  // Returns ClientHandlerInterface
    }

    // If RequestInterface, send and return response
    if ($resource instanceof RequestInterface) {
        return HttpResponse::createFromBase($psr);  // Returns ResponseInterface
    }

    // Otherwise return response
    return fetch_client()->fetch($resource, $processedOptions);  // Returns ResponseInterface
}
```

**Problem:**
- Union type makes it unclear what you'll get back
- IDE autocomplete is confused
- Type checking is difficult
- Not as predictable as JavaScript's `fetch()` which always returns Promise

### 2. No Enum Enforcement

**Current Code:**
```php
// Users can pass strings instead of enums
$handler->sendRequest('GET', '/api/data');  // String accepted
$handler->sendRequest(Method::GET, '/api/data');  // Enum also accepted
```

**Issue:**
- Internal code should use enums for type safety
- Strings are error-prone (typos: 'GETT', 'get', etc.)
- Enums provide IDE autocomplete

### 3. Helper Test Coverage

**Current Gaps:**
- Error handling in helpers (HTTP errors)
- Async usage via helpers
- Edge cases (empty URLs, null options, etc.)
- Content type enforcement

---

## Proposed Solutions

### Solution 1: Clarify Return Types with Overloads (PHP 8.0+)

**Approach:** Use docblock overloads to provide clear type hints.

**Implementation:**

```php
/**
 * Perform an HTTP request similar to JavaScript's fetch API.
 *
 * @overload fetch(): ClientHandlerInterface When called with no arguments, returns handler for chaining
 * @overload fetch(string $url, array $options = []): ResponseInterface When called with URL, returns response
 * @overload fetch(RequestInterface $request): ResponseInterface When called with PSR-7 request, returns response
 *
 * @param  string|RequestInterface|null  $resource  URL or request object
 * @param  array<string, mixed>|null  $options  Request options
 * @return ResponseInterface|ClientHandlerInterface Response or handler
 */
function fetch(...) { }
```

**Better Alternative - Split into Focused Helpers:**

```php
/**
 * Get the fetch client instance for chaining.
 *
 * @return ClientHandlerInterface
 */
function fetch_client(): ClientHandlerInterface
{
    return GlobalServices::client();
}

/**
 * Perform an HTTP request.
 *
 * @param  string|RequestInterface  $resource  URL or PSR-7 request
 * @param  array<string, mixed>  $options  Request options
 * @return ResponseInterface The response
 */
function fetch(string|RequestInterface $resource, array $options = []): ResponseInterface
{
    if ($resource instanceof RequestInterface) {
        $psr = fetch_client()->sendRequest($resource);
        return HttpResponse::createFromBase($psr);
    }

    $processedOptions = process_request_options($options);

    if (isset($options['base_uri'])) {
        return handle_request_with_base_uri($resource, $options, $processedOptions);
    }

    return fetch_client()->fetch($resource, $processedOptions);
}
```

**Benefits:**
- ✅ Clear return types
- ✅ Better IDE support
- ✅ Easier to document
- ✅ More predictable

### Solution 2: Internal Enum Usage

**Approach:** Convert string methods to enums internally.

**Implementation:**

```php
// In helpers.php - process_request_options()
function process_request_options(array $options): array
{
    // Method - normalize to enum internally
    $method = $options['method'] ?? 'GET';

    // Convert string to enum for type safety
    if (is_string($method)) {
        try {
            $method = Method::from(strtoupper($method));
        } catch (\ValueError $e) {
            throw new InvalidArgumentException("Invalid HTTP method: {$method}");
        }
    }

    $processedOptions['method'] = $method;  // Store enum, not string

    // Content type - normalize to enum
    if (isset($options['content_type'])) {
        $contentType = $options['content_type'];
        if (is_string($contentType)) {
            try {
                $contentType = ContentType::from($contentType);
            } catch (\ValueError $e) {
                // Allow custom content types, store as-is
            }
        }
        $processedOptions['content_type'] = $contentType;
    }

    return $processedOptions;
}
```

### Solution 3: Strengthen URI Handling

**Already mostly done in Phase 1!** But we can add some final touches:

**Add validation helpers:**

```php
// In HandlesUris trait
protected function validateUri(string $uri): void
{
    if (empty($uri)) {
        throw new InvalidArgumentException('URI cannot be empty');
    }

    // Check for obviously invalid URLs
    if (preg_match('/\s/', $uri)) {
        throw new InvalidArgumentException('URI cannot contain whitespace');
    }
}

protected function isValidUri(string $uri): bool
{
    try {
        $this->validateUri($uri);
        return true;
    } catch (InvalidArgumentException $e) {
        return false;
    }
}
```

---

## Step-by-Step Implementation

### Step 1: Improve Helper Return Types

**File:** `src/Fetch/Support/helpers.php`

**Option A: Keep current behavior but improve docs**
```php
/**
 * Perform an HTTP request similar to JavaScript's fetch API.
 *
 * Usage:
 * - fetch() → Returns handler for chaining
 * - fetch($url) → Returns response
 * - fetch($url, $options) → Returns response
 * - fetch($request) → Returns response (PSR-7)
 *
 * @param  string|RequestInterface|null  $resource
 * @param  array<string, mixed>|null  $options
 * @return ResponseInterface|ClientHandlerInterface
 *         - ClientHandlerInterface when $resource is null
 *         - ResponseInterface when $resource is provided
 */
function fetch(...)
```

**Option B: Make fetch() always require a resource** (Breaking change - NOT recommended)
```php
// Don't do this - would break existing code!
function fetch(string|RequestInterface $resource, array $options = []): ResponseInterface
```

**Recommendation:** Stick with Option A for now. The current API is established.

### Step 2: Add Internal Enum Conversion

**File:** `src/Fetch/Support/helpers.php`

**In `process_request_options()`:**
```php
function process_request_options(array $options): array
{
    $processedOptions = [];

    // Method - convert string to enum internally for type safety
    $method = $options['method'] ?? 'GET';
    if (is_string($method)) {
        $method = strtoupper($method);
        // Validate it's a known method
        if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'], true)) {
            throw new InvalidArgumentException("Invalid HTTP method: {$method}");
        }
    } elseif ($method instanceof Method) {
        $method = $method->value;
    }

    $processedOptions['method'] = $method;

    // Rest of the processing...
    return $processedOptions;
}
```

### Step 3: Add URI Validation Helpers

**File:** `src/Fetch/Concerns/HandlesUris.php`

**Add new methods:**
```php
/**
 * Validate a URI string.
 *
 * @throws InvalidArgumentException If URI is invalid
 */
protected function validateUriString(string $uri): void
{
    if (empty(trim($uri))) {
        throw new InvalidArgumentException('URI cannot be empty or whitespace');
    }

    // Check for whitespace (common mistake)
    if (preg_match('/\s/', $uri)) {
        throw new InvalidArgumentException(
            'URI cannot contain whitespace. Did you mean to URL-encode it?'
        );
    }
}

/**
 * Check if a URI string is valid.
 */
protected function isValidUriString(string $uri): bool
{
    try {
        $this->validateUriString($uri);
        return true;
    } catch (InvalidArgumentException $e) {
        return false;
    }
}
```

**Update `buildFullUriFromContext()` to use validation:**
```php
protected function buildFullUriFromContext(\Fetch\Support\RequestContext $context): string
{
    $uri = $context->getUri();
    $baseUri = $context->getOption('base_uri', '');
    $queryParams = $context->getOption('query', []);

    // Validate URI first
    $this->validateUriString($uri);  // ✅ Add validation

    // Normalize URIs before processing
    $uri = $this->normalizeUri($uri);
    // ... rest of method
}
```

### Step 4: Add Comprehensive Helper Tests

**File:** `tests/Unit/GlobalHelpersTest.php` (new file)

```php
<?php

namespace Tests\Unit;

use Fetch\Enum\Method;
use Fetch\Http\ClientHandler;
use Fetch\Http\Response;
use PHPUnit\Framework\TestCase;

class GlobalHelpersTest extends TestCase
{
    public function test_fetch_with_no_arguments_returns_handler(): void
    {
        $result = fetch();

        $this->assertInstanceOf(ClientHandler::class, $result);
    }

    public function test_fetch_with_url_returns_response(): void
    {
        // This would need a real HTTP request or mock
        // Skip for now or use mocking
        $this->markTestSkipped('Requires HTTP mocking setup');
    }

    public function test_get_helper_returns_response(): void
    {
        $this->markTestSkipped('Requires HTTP mocking setup');
    }

    public function test_post_helper_with_json_body(): void
    {
        $this->markTestSkipped('Requires HTTP mocking setup');
    }

    public function test_process_request_options_normalizes_method(): void
    {
        $options = ['method' => 'post'];

        $processed = process_request_options($options);

        $this->assertEquals('POST', $processed['method']);
    }

    public function test_process_request_options_validates_method(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP method');

        process_request_options(['method' => 'INVALID']);
    }

    public function test_process_request_options_handles_method_enum(): void
    {
        $options = ['method' => Method::DELETE];

        $processed = process_request_options($options);

        $this->assertEquals('DELETE', $processed['method']);
    }

    public function test_extract_body_and_content_type_prefers_json(): void
    {
        $options = [
            'json' => ['foo' => 'bar'],
            'body' => 'ignored',
        ];

        [$body, $contentType] = extract_body_and_content_type($options);

        $this->assertEquals(['foo' => 'bar'], $body);
        $this->assertEquals('application/json', $contentType);
    }
}
```

**File:** `tests/Unit/HandlesUrisTest.php` (new file)

```php
<?php

namespace Tests\Unit;

use Fetch\Http\ClientHandler;
use PHPUnit\Framework\TestCase;

class HandlesUrisTest extends TestCase
{
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new class extends ClientHandler {
            public function exposeValidateUriString(string $uri): void
            {
                $this->validateUriString($uri);
            }

            public function exposeIsValidUriString(string $uri): bool
            {
                return $this->isValidUriString($uri);
            }
        };
    }

    public function test_validate_uri_string_accepts_valid_uri(): void
    {
        // Should not throw
        $this->handler->exposeValidateUriString('https://example.com');
        $this->handler->exposeValidateUriString('/api/users');
        $this->handler->exposeValidateUriString('/api/users?page=1');

        $this->assertTrue(true);
    }

    public function test_validate_uri_string_rejects_empty_uri(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URI cannot be empty');

        $this->handler->exposeValidateUriString('');
    }

    public function test_validate_uri_string_rejects_whitespace(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URI cannot contain whitespace');

        $this->handler->exposeValidateUriString('/api/users with spaces');
    }

    public function test_is_valid_uri_string_returns_boolean(): void
    {
        $this->assertTrue($this->handler->exposeIsValidUriString('https://example.com'));
        $this->assertFalse($this->handler->exposeIsValidUriString(''));
        $this->assertFalse($this->handler->exposeIsValidUriString('bad uri'));
    }
}
```

### Step 5: Add Error Handling Tests

**File:** `tests/Integration/HelperErrorHandlingTest.php` (new file)

```php
<?php

namespace Tests\Integration;

use Fetch\Http\ClientHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;

class HelperErrorHandlingTest extends TestCase
{
    public function test_helpers_handle_http_errors(): void
    {
        $mockHandler = new MockHandler([
            new GuzzleResponse(404, [], 'Not Found'),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $guzzleClient = new Client(['handler' => $handlerStack]);

        $handler = ClientHandler::createWithClient($guzzleClient);
        $handler->baseUri('https://api.example.com');

        // Should not throw (404 is valid HTTP response)
        $response = $handler->get('/not-found');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_helpers_with_invalid_method_throw(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $options = ['method' => 'INVALID_METHOD'];
        process_request_options($options);
    }
}
```

### Step 6: Update Documentation

**File:** `README.md` and guides

**Update examples to show type clarity:**
```php
// ✅ Clear: fetch() with URL returns Response
$response = fetch('https://api.example.com/users');
echo $response->getStatusCode();

// ✅ Clear: fetch_client() returns Handler for chaining
$handler = fetch_client()
    ->baseUri('https://api.example.com')
    ->withHeaders(['Authorization' => 'Bearer token']);

$response = $handler->get('/users');

// ✅ Type-safe: Use enums
use Fetch\Enum\Method;

$response = $handler->sendRequest(Method::POST, '/api/data', [
    'json' => ['name' => 'John'],
]);
```

---

## Testing Checklist

- [ ] Helper return types documented clearly
- [ ] Invalid HTTP methods throw exceptions
- [ ] Method strings normalized to uppercase
- [ ] Method enums handled correctly
- [ ] URI validation rejects empty/whitespace URIs
- [ ] URI validation accepts valid URIs
- [ ] Helper error handling tested
- [ ] All existing tests still pass

---

## Breaking Changes

**None!** All changes are:
- Additive (new validation)
- Internal (enum conversion)
- Documentation improvements

---

## Success Criteria

- [ ] All 446+ tests passing
- [ ] Helper return types clearly documented
- [ ] URI validation added
- [ ] Method validation strengthened
- [ ] New tests added (~10-15 tests)
- [ ] Documentation updated

---

## Estimated Impact

**Files Changed:** ~5 files
**Lines Added:** ~150 lines
**Tests Added:** ~15 tests
**Breaking Changes:** None
**Backward Compat:** 100%

---

## Optional Enhancements (Future)

### 1. Async-Specific Helpers

```php
/**
 * Perform an async request (always returns promise).
 */
function fetch_async(string $url, array $options = []): PromiseInterface
{
    $options['async'] = true;
    return fetch_client()->sendRequest(Method::GET, $url, $options);
}
```

### 2. Type-Safe Builder Pattern

```php
/**
 * Create a request builder.
 */
function request(Method $method, string $url): RequestBuilder
{
    return new RequestBuilder($method, $url);
}

// Usage:
$response = request(Method::POST, '/api/users')
    ->withJson(['name' => 'John'])
    ->withHeader('Authorization', 'Bearer token')
    ->send();
```

### 3. Strict Mode Option

```php
// Enable strict type checking
fetch_client()->strictMode(true);

// Now only enums accepted, no string conversion
$handler->sendRequest('GET', '/api/data');  // ❌ Throws
$handler->sendRequest(Method::GET, '/api/data');  // ✅ OK
```

---

## Notes

- Most improvements are "nice to have" rather than critical
- Focus on tests and documentation
- Maintain backward compatibility
- Consider adding enhancements in a future major version

---

## Next Steps After Completion

After Phase 5, proceed to Phase 6: Testing and coverage.
