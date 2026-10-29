---
title: Streams and Server-Sent Events
description: Stream response bodies or parse server-sent events.
---

# Streams and Server-Sent Events

Global helpers:

```text
fetch_stream(string $url, ?array $options = []): Fetch\Interfaces\StreamedResponse
fetch_sse(string $url, ?array $options = []): Fetch\Http\EventSource
```

`ClientHandler::stream(string|Method $method = Method::GET, string $uri = '', array $options = [])` and `Client::stream(string $url, ?array $options = [], string|Method $method = Method::GET)` return a `StreamedResponse`. It exposes `stream(int $chunkSize = 8192)`, `lines()`, `sse()`, and `buffer()`. Reading consumes the underlying body.

`EventSource` implements `IteratorAggregate` and yields `ServerSentEvent` objects. It exposes `events()`, `response()`, `lastEventId()`, and `reconnectionTime()`. An event has readonly `data`, `type` (default `message`), `id`, and `retry` fields; `json()` decodes its data and `isDone()` detects the conventional `[DONE]` payload.

The parser does not reconnect automatically. Streaming I/O is synchronous and uses a separate direct Guzzle path. See the [streaming guide](/guide/streaming) for consumption and testing details.
