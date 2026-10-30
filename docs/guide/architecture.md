---
title: Architecture
description:
    How Fetch's helpers, client, handler, request pipeline, and support services
    fit together.
---

# Architecture

Fetch exposes several layers for different use cases:

1. Global functions in `Fetch\Support\helpers.php` provide `fetch()`,
   `fetch_client()`, verb helpers, and streaming helpers.
2. `Fetch\Http\Client` implements PSR-18 and delegates to a `ClientHandler`.
3. `Fetch\Http\ClientHandler` owns Fetch-specific configuration and orchestrates
   request execution.
4. `Request`, `Response`, and `StreamedResponse` provide PSR-7-compatible
   message objects and convenience methods.
5. Concerns, cache, middleware, events, retry, testing, and pool classes provide
   supporting features.

`RequestCoordinator` owns the buffered lifecycle. `RequestBuilder` constructs
the middleware request; optional validation checks the transformed request
before mock, cache, or transport selection. Responses are validated before
returning or caching them. `RequestExecutor` handles synchronous transport;
`AsyncRequestExecutor` uses an injected `AsyncTransport`. Both use the same
`RetryStrategy`, with Matrix timers providing asynchronous retry delays.

The default `GuzzleAsyncTransport` pumps cURL multi and Guzzle's promise queue
from ReactPHP. `PromiseManager` composes Matrix operations, while
`ConnectionPoolManager` holds handler configuration for shared pool services.
The separate Fetch pool and DNS cache are not wired into transport selection.
Guzzle manages connections in the active transport.

`StreamingRequestExecutor` uses Guzzle's incremental stream handler. Streaming
is synchronous and bypasses middleware, validation, mocks, retries, and caching;
a configured validation policy raises an explicit configuration exception.

For the source-level map, see
[`CODE_MAP.md`](https://github.com/Thavarshan/fetch-php/blob/main/CODE_MAP.md).
Verify behavior against source and tests when working on implementation details.
