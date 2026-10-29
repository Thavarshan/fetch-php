---
title: Streaming Responses and SSE
description: Read streamed response bodies incrementally and parse Server-Sent Events.
---

# Streaming Responses and SSE

Use `fetch_stream()` or the handler's `stream()` method to receive a `StreamedResponse`. It exposes `stream()` for chunks, `lines()` for newline-delimited text, `sse()` for event parsing, and `buffer()` to consume the remaining body into a regular response.

```php
$stream = fetch_stream('https://api.example.test/v1/log');

foreach ($stream->lines() as $line) {
    // Process each line before reading the next one.
}
```

The underlying stream is consumed as it is read; it is generally a one-pass resource. SSE support parses events but does not automatically reconnect or implement application retry policy.

Streaming uses a separate direct Guzzle request path. It does not pass through the usual MockServer, middleware, retry, or cache pipeline. Use a Guzzle `MockHandler` for deterministic streaming tests. Streaming I/O is synchronous.

<<< @/examples/snippets/streaming.php
