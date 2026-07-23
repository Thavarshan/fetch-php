---
paths:
  - "src/Fetch/Concerns/ManagesPromises.php"
  - "src/Fetch/Concerns/ManagesRetries.php"
  - "src/Fetch/Concerns/ManagesConnectionPool.php"
  - "src/Fetch/Concerns/ManagesEvents.php"
  - "src/Fetch/Pool/**/*.php"
  - "src/Fetch/Http/StreamedResponse.php"
  - "src/Fetch/Http/EventSource.php"
  - "src/Fetch/Support/RetryStrategy.php"
---

# Async & resource-safety rules

- Every async path resolves or rejects exactly once; handle rejections; no
  dangling promises or silent hangs. Timeouts surface as the documented
  exception.
- Don't block the ReactPHP event loop (`sleep`, blocking I/O, uncached DNS on
  the hot path).
- Retries stay capped with bounded, jittered delays and only fire on
  retryable/idempotent cases — avoid retry storms (handler + middleware layering).
- Release/close resources on every path (success, error, timeout, cancellation):
  pool connections (`releaseConnection`), streams, file handles, recordings.
- Don't accumulate listeners per request; keep caches bounded; keep streaming
  bodies unbuffered unless `buffer()` is called.
- Deeper review: `fetch-php-performance-review`.
