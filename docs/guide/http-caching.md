---
title: HTTP Caching
description: Configure RFC 7234 compliant caching with stale-while-revalidate and stale-if-error support for synchronous requests.
---

# HTTP Caching

Fetch PHP includes a first-class caching layer that follows RFC 7234 semantics. It supports ETag/Last-Modified revalidation, `stale-while-revalidate`, `stale-if-error`, and cache key variation based on headers. Caching is intentionally **synchronous-only**—async requests bypass the cache to avoid mixing promise lifecycles with storage writes.

## Enabling the cache

Enable caching on the fluent handler or the global client. A lightweight in-memory cache is used by default; provide a `CacheInterface` (e.g., `FileCache`) for persistence.

```php
use Fetch\Http\ClientHandler;
use Fetch\Cache\FileCache;

// Global client (affects fetch(), get(), etc.)
fetch_client()->getHandler()->withCache();

// Custom handler with a file-backed cache
$handler = ClientHandler::create()
    ->withCache(new FileCache(__DIR__ . '/storage/fetch-cache', defaultTtl: 1800))
    ->baseUri('https://api.example.com');

$response = $handler->get('/users');
```

> Caching only wraps synchronous calls. When you opt into async (`->async()` or `then()/catch()`), requests skip cache lookups and storage.

## Cache options (defaults)

```php
$handler->withCache(options: [
    'respect_cache_headers' => true,              // Honor Cache-Control/Expires
    'default_ttl' => 3600,                        // Fallback TTL in seconds
    'stale_while_revalidate' => 0,                // Seconds to serve stale before refresh
    'stale_if_error' => 0,                        // Seconds to serve stale on error
    'cache_methods' => ['GET', 'HEAD'],           // Methods eligible for caching
    'cache_status_codes' => [200,203,204,206,300,301,404,410],
    'vary_headers' => ['Accept','Accept-Encoding','Accept-Language'],
    'is_shared_cache' => false,                   // Public/proxy-style caching rules
]);
```

Common overrides:

- `default_ttl`: Fallback when response headers provide no directives.
- `respect_cache_headers`: Disable to treat every cacheable response as storable with `default_ttl`.
- `stale_while_revalidate`: Serve stale responses for a grace window; the library does not perform a background refresh—callers remain in control of when to re-fetch.
- `stale_if_error`: Serve stale content when the live request fails within this window.
- `vary_headers`: Adjust cache key variation for content negotiation.

## Per-request cache control

Pass a `cache` array in request options to fine-tune behavior without changing the handler defaults.

```php
$response = fetch('https://api.example.com/users', [
    'cache' => [
        'enabled' => true,
        'ttl' => 600,                  // Override TTL for this call
        'respect_headers' => true,     // Honor Cache-Control on this response
        'is_shared_cache' => false,    // Toggle shared vs private semantics
        'force_refresh' => false,      // Skip cache and fetch fresh
        'key' => 'users:list:v1',      // Custom cache key
        'cache_body' => false,         // Allow body-based keys for non-GET when true
    ],
]);
```

Useful patterns:

- **Force refresh**: set `force_refresh => true` to ignore stored entries.
- **Cache POST/PUT**: add `cache_body => true` and include a body to hash into the key.
- **Static assets**: pin a custom `key` for predictable lookups regardless of URL params.

## What gets cached

- Methods listed in `cache_methods` (default: `GET`, `HEAD`).
- Status codes listed in `cache_status_codes`.
- Responses that pass `Cache-Control` validation when `respect_cache_headers` is enabled.
- Conditional requests reuse cached bodies on `304 Not Modified`.

The library adds an `X-Cache-Status` header when serving cached data to aid debugging (`HIT`, `STALE`, `REVALIDATED`, `STALE-IF-ERROR`, `MISS`, `BYPASS`).

## Using file-based storage

```php
use Fetch\Cache\FileCache;

$handler = fetch_client()->getHandler();
$handler->withCache(
    cache: new FileCache(
        directory: sys_get_temp_dir() . '/fetch-cache',
        defaultTtl: 900,
        maxSize: 50 * 1024 * 1024, // 50MB
    ),
    options: ['stale_if_error' => 60]
);

$handler->get('https://api.example.com/config');
```

`FileCache` prunes invalid/expired entries and supports size limits. Implement `CacheInterface` to plug in Redis, APCu, or your framework's cache.

## Conditional revalidation

When a cached entry contains `ETag` or `Last-Modified`, the client automatically attaches `If-None-Match` / `If-Modified-Since` headers. A `304` response merges fresh headers with the cached body and sets `X-Cache-Status: REVALIDATED`.

```php
$handler = fetch_client()->getHandler()->withCache();

// First request stores the representation
$first = $handler->get('https://api.example.com/profile');

// Later call revalidates with ETag/Last-Modified
$second = $handler->get('https://api.example.com/profile');
// -> may short-circuit with HIT or reuse the cached body on 304
```

## Stale-if-error fallback

If a live request fails (network error or exception) and a stale entry is still within `stale_if_error`, the cached body is served instead of bubbling the failure.

```php
$handler = fetch_client()->getHandler()->withCache(['stale_if_error' => 120]);

try {
    $response = $handler->get('https://api.example.com/feature-flags');
} catch (\Throwable $e) {
    // If a stale entry exists, you will get a response with X-Cache-Status: STALE-IF-ERROR
    // Otherwise, the exception propagates
}
```

## Cache key strategy

Keys include:

- Method + normalized URI (scheme/host/path/query)
- Vary headers (`Accept`, `Accept-Encoding`, `Accept-Language` by default)
- Query params normalized for ordering
- Optional body hash when `cache_body` is true on non-GET/HEAD

Use `key` to pin a specific identifier when determinism matters more than request shape.
