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

The default buffered request path handles mock responses, middleware, cache,
retry, and Guzzle transport. Streaming uses a separate direct Guzzle path. Async
mode adapts callbacks to Matrix/React promises, but the current Guzzle request
inside the callback is synchronous and blocks the event loop. The connection
pool and DNS cache APIs are not integrated with the request transport.

For the source-level map, see
[`CODE_MAP.md`](https://github.com/Thavarshan/fetch-php/blob/main/CODE_MAP.md).
Verify behavior against source and tests when working on implementation details.
