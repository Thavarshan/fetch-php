---
title: HTTP Caching
description:
    Attach an RFC 7234 cache manager to a handler and control request caching.
---

# HTTP Caching

Caching is opt-in: attach a `CacheManager` to a handler with `withCache()`. A
request's `cache` option controls a manager that is already attached; it does
not create one by itself.

```php
use Fetch\Cache\FileCache;

$handler = fetch_client()
    ->getHandler()
    ->withCache(new FileCache('/tmp/fetch-cache'));
$response = $handler->get('https://api.example.test/v1/catalog');
```

The default cache policy targets GET and HEAD and a defined set of cacheable
statuses, with a default TTL of 3600 seconds. HTTP `Cache-Control`, validators,
and selected `Vary` request headers influence storage and revalidation. Private
caching is disabled by default. Review `CacheManager` options before persisting
data that may contain credentials or user-specific responses.

Cache lookup and storage use the final middleware-transformed request options.
Authorization and Cookie headers always partition cache entries, including
custom cache keys. Basic-auth options and URL-matched cookie jars are converted
to those headers before key generation. Credential values are hashed rather
than written into generated keys. A custom key still controls URL/resource
variation, so never reuse one key for unrelated resources. Application identity
carried outside these headers needs an explicit varying header or custom key.

The async and streaming request paths do not currently go through the cache
manager. `MockServer` returns fakes outside the cache pipeline; use an injected
Guzzle `MockHandler` when testing cache hits and revalidation.

<<< @/examples/snippets/cache.php
