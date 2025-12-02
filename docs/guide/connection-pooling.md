---
title: Connection Pooling & HTTP/2
description: Reuse TCP connections, enable DNS caching, and opt into HTTP/2 for faster Fetch PHP requests.
---

# Connection Pooling & HTTP/2

Fetch PHP can reuse TCP connections across all handlers via a shared connection pool and DNS cache. You can also enable HTTP/2 with cURL-level validation. Pool state is stored statically so that separate `ClientHandler` instances benefit from the same sockets.

## Enable pooling

```php
use Fetch\Http\ClientHandler;

// Globally for helper functions
$handler = fetch_client()->getHandler()
    ->withConnectionPool(); // uses defaults

// Custom configuration
$handler = ClientHandler::create()
    ->withConnectionPool([
        'enabled' => true,
        'max_connections' => 200,   // total across hosts
        'max_per_host' => 10,
        'max_idle_per_host' => 5,
        'keep_alive_timeout' => 60, // seconds
        'connection_timeout' => 5,  // seconds
        'strategy' => 'least_connections',
        'connection_warmup' => false,
        'warmup_connections' => 0,
        'dns_cache_ttl' => 300,     // seconds
    ]);
```

Notes:

- Pooling is **global**—changing config affects all handlers in the process.
- Use `resetPool()` in tests to clear shared state, or `closeAllConnections()` to drop existing sockets.
- Pool stats are available via `getPoolStats()` and `getConnectionDebugStats()` (also captured in debug snapshots).

## DNS caching

DNS lookups are cached alongside the pool. The TTL follows `dns_cache_ttl` (default 300 seconds). You can inspect and clear entries:

```php
$handler = fetch_client()->getHandler()->withConnectionPool();

$handler->getDnsCacheStats();     // ['enabled' => true, 'entries' => ...]
$handler->clearDnsCache();        // Clear all
$handler->clearDnsCache('api.example.com'); // Clear a single host
```

## HTTP/2

Enable HTTP/2 support to allow multiplexed requests when the environment supports `CURL_HTTP_VERSION_2_0`.

```php
$handler = fetch_client()->getHandler()
    ->withConnectionPool() // pooling pairs well with HTTP/2
    ->withHttp2([
        'enabled' => true,
        'max_concurrent_streams' => 100,
        'window_size' => 65535,
        'header_table_size' => 4096,
        'enable_server_push' => false,
        'stream_prioritization' => false,
    ]);
```

What happens under the hood:

- The handler applies HTTP/2-specific cURL options while preserving any user-provided `curl` options.
- If you did not set an HTTP version explicitly, the handler sets `version` to `2.0`.
- An exception is thrown when HTTP/2 is requested but the cURL extension lacks HTTP/2 support—fail fast rather than silently downgrade.

## Operational tips

- Combine pooling + `withHttp2()` for the lowest connection overhead.
- Pair pooling with retries (`retry()`) to avoid reconnect storms on transient errors.
- Use `getConnectionDebugStats()` in a debug-enabled run to correlate latency spikes with pool exhaustion or DNS churn.
