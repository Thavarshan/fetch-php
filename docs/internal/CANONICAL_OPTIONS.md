# Canonical Option Names

**Last Updated:** 2025-12-02
**Status:** Official Documentation

This document defines the canonical (preferred) names for all request options in Fetch PHP, along with their legacy alternatives for backward compatibility.

---

## Overview

Fetch PHP supports multiple names for some options to maintain backward compatibility with older code. However, **canonical names** are preferred and should be used in new code and documentation.

### Normalization Process

All options are automatically normalized to canonical names via `RequestOptions::normalizeOptionKeys()` during the merge process. This ensures:
- ✅ Backward compatibility - Legacy keys still work
- ✅ Consistency - Internal code uses only canonical names
- ✅ Future-proof - Easy to deprecate legacy keys if needed

---

## Canonical vs Legacy Options

### Retry Configuration

| Canonical | Legacy | Type | Description |
|-----------|--------|------|-------------|
| `retries` | `max_retries` | `int` | Maximum number of retry attempts (0 = no retries) |
| `retry_delay` | - | `int` | Base delay between retries in milliseconds |

**Example:**
```php
// ✅ Canonical (preferred)
$handler->sendRequest('GET', '/api/endpoint', [
    'retries' => 3,
    'retry_delay' => 200,
]);

// ⚠️ Legacy (still works, but normalized to 'retries')
$handler->sendRequest('GET', '/api/endpoint', [
    'max_retries' => 3,
    'retry_delay' => 200,
]);
```

**Rationale:** `retries` is more concise and matches the public method name `retry()`.

---

## All Canonical Option Names

### HTTP Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `method` | `string\|Method` | `GET` | HTTP method |
| `uri` | `string` | - | Request URI (relative or absolute) |
| `base_uri` | `string` | - | Base URI for relative requests |
| `headers` | `array` | `[]` | HTTP headers |
| `query` | `array` | `[]` | Query string parameters |
| `timeout` | `int` | `30` | Request timeout in seconds |

### Body Options (Mutually Exclusive)

| Option | Type | Description | Precedence |
|--------|------|-------------|------------|
| `json` | `array` | JSON body (auto-encoded) | Highest |
| `form_params` | `array` | Form-urlencoded body | Medium |
| `multipart` | `array` | Multipart form data | Low |
| `body` | `string` | Raw body content | Lowest |

**Body Precedence Rule:** `json` > `form_params` > `multipart` > `body`

### Async & Concurrency

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `async` | `bool` | `false` | Execute request asynchronously |

### Retry & Resilience

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `retries` | `int` | `1` | Maximum retry attempts |
| `retry_delay` | `int` | `100` | Base delay between retries (ms) |
| `retry_status_codes` | `array` | See defaults | HTTP status codes to retry |
| `retry_exceptions` | `array` | See defaults | Exception types to retry |

### Caching

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `cache` | `bool\|array` | `false` | Enable caching (bool) or cache config (array) |

### Authentication

| Option | Type | Description |
|--------|------|-------------|
| `auth` | `array` | Basic auth `[username, password]` |
| `token` | `string` | Bearer token |

### Debug & Profiling

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `profiler` | `ProfilerInterface` | - | Profiler instance for performance tracking |

### Guzzle Pass-Through Options

These options are passed directly to Guzzle:

| Option | Type | Description |
|--------|------|-------------|
| `verify` | `bool\|string` | SSL certificate verification |
| `proxy` | `string\|array` | Proxy configuration |
| `cookies` | `bool\|CookieJarInterface` | Cookie jar |
| `allow_redirects` | `bool\|array` | Redirect configuration |
| `cert` | `string\|array` | Client certificate |
| `ssl_key` | `string\|array` | SSL key |
| `stream` | `bool` | Stream response body |
| `connect_timeout` | `int` | Connection timeout |
| `read_timeout` | `int` | Read timeout |
| `debug` | `bool\|resource` | Debug output |
| `sink` | `string\|resource` | Response body destination |
| `version` | `string` | HTTP protocol version |
| `decode_content` | `bool` | Auto-decode compressed responses |
| `curl` | `array` | cURL-specific options |

---

## Precedence Rules

### Option Merging

Options are merged with the following precedence (lowest to highest):

1. **Factory defaults** - From `GlobalServices`
2. **Client-level defaults** - Set via `setDefaultOptions()`
3. **Handler-level options** - Set via `withOptions()`
4. **Request-level options** - Passed to `sendRequest()`

**Example:**
```php
// 1. Factory defaults: retries = 1
// 2. Client defaults
ClientHandler::setDefaultOptions(['retries' => 2]);

// 3. Handler options
$handler = ClientHandler::create();
$handler->retry(3); // Sets handler-level retries = 3

// 4. Request options (wins)
$handler->sendRequest('GET', '/api/data', ['retries' => 5]); // Uses 5
```

### Body Option Precedence

When multiple body options are provided, only one is used:

```php
$handler->sendRequest('POST', '/api/data', [
    'json' => ['foo' => 'bar'],    // ✅ Used (highest precedence)
    'form_params' => ['x' => 'y'], // ❌ Ignored
    'body' => 'raw content',       // ❌ Ignored
]);
```

### Canonical vs Legacy

When both canonical and legacy keys are present, the canonical key wins:

```php
$handler->sendRequest('GET', '/api/data', [
    'retries' => 10,       // ✅ Used (canonical)
    'max_retries' => 5,    // ❌ Ignored (legacy)
]);
```

---

## Migration Guide

### From Legacy to Canonical

**Before (Legacy):**
```php
$handler->sendRequest('GET', '/api/data', [
    'max_retries' => 5,
]);
```

**After (Canonical):**
```php
$handler->sendRequest('GET', '/api/data', [
    'retries' => 5,
]);
```

### No Breaking Changes

⚠️ **Important:** Legacy keys are **not deprecated** and will continue to work indefinitely. This migration is **optional** and recommended only for:
- New code
- Code refactoring
- Documentation updates

Existing code using `max_retries` will continue to function correctly.

---

## Implementation Details

### Normalization Location

Option key normalization happens in:
- **`RequestOptions::normalizeOptionKeys()`** - Core normalization logic
- **`RequestOptions::merge()`** - Calls normalization during merge
- **`RequestContext::fromOptions()`** - Accepts both canonical and legacy (with fallback)

### Testing

Comprehensive tests in [tests/Unit/RequestOptionsTest.php](../../tests/Unit/RequestOptionsTest.php) verify:
- ✅ Legacy keys are normalized to canonical form
- ✅ Canonical keys are preserved
- ✅ Canonical keys take precedence when both are present
- ✅ Other options are not affected by normalization
- ✅ Backward compatibility is maintained

---

## Best Practices

### DO ✅

- Use canonical names in new code
- Use canonical names in documentation
- Use canonical names in examples
- Test with both canonical and legacy keys

### DON'T ❌

- Deprecate legacy keys without major version bump
- Remove normalization logic
- Assume users will migrate immediately
- Document legacy keys as primary options

---

## Future Considerations

### Potential Deprecation Path (Not Implemented)

If we ever want to deprecate legacy keys in a future major version:

1. **v1.x (Current):** Both keys work, automatic normalization
2. **v2.0:** Trigger deprecation warnings for legacy keys
3. **v3.0:** Remove support for legacy keys (breaking change)

This path is **not currently planned** and serves only as documentation of the _possible_ approach.

---

## Summary

- ✅ **Canonical options:** `retries`, `retry_delay`, etc.
- ⚠️ **Legacy options:** `max_retries` (still works)
- 🔄 **Auto-normalization:** All options normalized during merge
- 📘 **Documentation:** Use canonical names
- 🔒 **Backward compat:** Legacy keys supported indefinitely
- 🧪 **Well-tested:** 14 new tests verify normalization

**Use canonical names for clarity and consistency!** 🎯
