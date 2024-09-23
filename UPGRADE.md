# UPGRADE GUIDE

This document provides instructions for upgrading to version **v2.0.0** of FetchPHP.

## Upgrading from v1.x to v2.0.0

Version 2.0.0 introduces significant changes and refactors to improve the overall structure, maintainability, and performance of the library. This guide will help you transition smoothly from version 1.x to 2.0.0.

### Backward Compatibility

The following functions and methods remain backward-compatible:

- **`fetch()`**, **`fetch_async()`**, and **`fetchAsync()`** remain functional. However, `fetchAsync` is now deprecated.
- Core methods in the `Response` class, such as `json()`, `text()`, and `blob()`, continue to function as before.

**Action Required**: While no immediate changes are required for these functions, it’s recommended to transition from `fetchAsync` to `fetch_async` to future-proof your code.

---

### Changes to Response Class

The `Response` class has been enhanced to align more closely with Laravel’s response object.

#### Notable Changes

- **`getStatusCode()`** has been renamed to **`status()`**. This aligns with Laravel’s method names for responses. However, for backward compatibility, the old method still works.
- **New Methods** have been introduced:
  - `header()`
  - `headers()`
  - `content()`
  - `status()`

These methods provide additional flexibility and control over the response.

**Action Required**: If you are using `getStatusCode()`, no immediate changes are necessary as it remains supported, but transitioning to `status()` is recommended for consistency with Laravel conventions.

---

### Singleton Guzzle Client

The Guzzle client is now managed using a **singleton pattern**, meaning only one instance of the client is reused across requests to reduce overhead.

#### Implications

- The same Guzzle client will be used across all `fetch()` and `fetch_async()` calls.
- If you need different Guzzle configurations for specific requests, you will need to pass a custom Guzzle client via the `options` parameter.

**Action Required**: If your application depends on multiple Guzzle client configurations, make sure to pass the client explicitly in the `options` when calling `fetch()` or `fetch_async()`.

---

### Deprecations

- **`fetchAsync()`** is deprecated and will be removed in future releases. Please use `fetch_async()` going forward.

**Action Required**: Update any usage of `fetchAsync()` to `fetch_async()`.

---

### Error Handling

Error handling has been centralized in the `Http` class, providing more robust and consistent behavior for both synchronous and asynchronous requests.

- Request exceptions are now consistently caught and handled, ensuring proper response formatting even in error scenarios.
