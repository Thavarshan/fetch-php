---
name: fetch-php-performance-review
description: Review Fetch PHP for resource and performance safety — connection reuse/pooling, buffering vs streaming, memory use, retry amplification, promise lifecycle, listener/stream/connection leaks, and event-loop blocking. Use when changing Pool, streaming, retries, async/promises, events, or caching.
---

# Fetch PHP performance & resource review

Read-only review focused on resource lifetimes and throughput, not micro-opts.

## When to use

Changes to `Pool/` (`ConnectionPool`, `HostConnectionPool`, `Connection`,
`DnsCache`, `Http2Configuration`), streaming (`StreamedResponse`, `EventSource`),
`ManagesRetries`/`RetryStrategy`, `ManagesPromises`, `ManagesEvents`, or caching.

## What to check

1. **Connection reuse & pooling.** Connections are returned to the pool
   (`releaseConnection`) on both success and error paths; `isReusable()` /
   keep-alive respected; per-host and idle limits enforced; no unbounded pool
   growth. Warmup counts don't leak eager connections.
2. **Streaming vs buffering.** `StreamedResponse` bodies stay unbuffered —
   consumed via `stream()`/`lines()`, never fully read into memory unless
   `buffer()` is explicitly called. Flag any change that reads an entire
   attacker- or size-unbounded body into a string.
3. **Memory.** No accumulation of full response bodies, recorded requests, or
   debug snapshots across many requests; bounded caches (`MemoryCache` max
   items, `FileCache` size pruning) stay bounded.
4. **Retry amplification.** Retries are capped (`RetryDefaults`), delays are
   bounded with jitter (`MAX_DELAY_MS`), and only idempotent/retryable statuses
   trigger retries. Watch for retry-on-every-error or nested retry layers
   (handler + middleware) multiplying attempts (retry storm).
5. **Promise lifecycle.** Every async path resolves or rejects exactly once;
   rejections are handled; no dangling promises or timeouts that leak. Timeouts
   surface as the documented exception, not a silent hang.
6. **Listeners & leaks.** Event listeners and `on_redirect`/hook callbacks
   aren't registered per-request without cleanup (listener accumulation);
   streams and file handles (`FileCache`, recordings) are closed.
7. **Event-loop blocking.** No synchronous/blocking calls (`sleep`, blocking
   I/O, `gethostbyname` on the hot path without cache) inside async execution
   that would stall the ReactPHP loop.

## Verification

- For pooling/stream/promise changes, point to where the resource is released/
  closed on *every* path (success, exception, timeout, cancellation).
- Add or identify a test proving bounded behavior (e.g. capped retries, released
  connections) where feasible; keep it deterministic.

## Red flags — stop

- A body read fully into memory on the streaming path.
- Retries/delays without a cap, or retrying non-idempotent requests by default.
- A promise path that can neither resolve nor reject (hang), or double-resolve.
- Connections/handles/listeners created without a matching release/close.
