---
title: Promise Operations
description: Use Matrix 4 promise aggregation and scheduling helpers with Fetch.
---

# Promise operations

Matrix 4 provides promise aggregation and task scheduling helpers. Import the
canonical functions from `Matrix\Support`:

```php
use function Matrix\Support\all;
use function Matrix\Support\any;
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\batch;
use function Matrix\Support\map;
use function Matrix\Support\race;
```

Fetch also exposes conditional global wrapper functions for `all()`, `any()`,
`async()`, `await()`, `batch()`, `map()`, `race()`, and `retry()`. The wrappers
accept arrays and mostly delegate to Matrix; `retry()` adapts its delay argument
from milliseconds to Matrix seconds.

## Aggregation

- `all(iterable $promises)` fulfills with keyed results when every input
  fulfills; it rejects on the first rejection.
- `race(iterable $promises)` settles with the first input that fulfills or
  rejects.
- `any(iterable $promises)` fulfills with the first successful input and rejects
  when all inputs reject.
- `await(PromiseInterface $promise, ?float $timeout = null)` drives the loop
  from a non-running context. The timeout is seconds.

These operations can be useful for promises that do nonblocking work. Wrapping
Fetch calls does not make Guzzle's synchronous HTTP transport concurrent.

## Map and batch limits

`Matrix\Support\map($items, $callback, $concurrency = 0)` lazily schedules items
and preserves their input keys. In Matrix, `0` means no explicit concurrency
limit. Fetch's global `map()` wrapper accepts an array and defaults to
concurrency `5`.

`Matrix\Support\batch($items, $callback, $batchSize = 10, $concurrency = 1)`
calls the callback with each batch, then flattens each callback's iterable
result. Batch size and concurrency are counts, not durations. Fetch's global
wrapper defaults the batch concurrency to `5`.

A concurrency limit bounds scheduled promises, but it cannot make synchronous
Fetch requests concurrent. Use a genuinely asynchronous PSR-18 transport or a
different nonblocking HTTP client if concurrent network I/O is required.

## Retry timing

Matrix's namespaced `retry($factory, $maxAttempts, $backoffStrategy)` uses total
attempts; its backoff callback returns seconds. The global Fetch wrapper
`retry($callable, $attempts, $delay)` also uses a total attempt count, but
integer or callback delays are milliseconds. Both are general promise-operation
retry helpers. For HTTP status-aware retries, use Fetch's handler `retry()`
configuration instead.

## Errors and cancellation

Aggregation preserves promise rejection. Matrix 4 cancels queued scheduler tasks
when a mapped task fails and propagates cancellation to active promises. A
wrapped Fetch request already executing synchronous Guzzle I/O cannot be
interrupted by that cancellation. Matrix cancellation rejects with
`Matrix\Exceptions\CancellationException`.

See [Async requests and Matrix](/guide/async-requests) for loop ownership and
transport behavior.
