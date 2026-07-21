---
title: Streaming & Server-Sent Events
description: Consume response bodies incrementally and parse text/event-stream responses for streaming LLM APIs and live feeds.
---

# Streaming & Server-Sent Events

Most responses in Fetch PHP are fully buffered—the body is read into memory and exposed through helpers like `json()` and `text()`. That is the right default for typical API calls, but it breaks down for large downloads and for endpoints that emit data over time, such as streaming LLM completions.

For those cases Fetch PHP provides an unbuffered path that mirrors JavaScript's `response.body`: a [`StreamedResponse`](#streamedresponse) you pull chunks or lines from as they arrive, plus a first-class [Server-Sent Events](#server-sent-events) consumer.

> Streaming is **synchronous** and **bypasses the response cache**. The body is never read into memory up front, so a streamed response can only be traversed once—use `buffer()` if you need random access.

## Streaming a response body

Use the `fetch_stream()` helper, or `->stream()` on a client/handler. Nothing is read until you iterate.

```php
use function fetch_stream;

$response = fetch_stream('https://example.com/large-export.csv');

// Pull raw chunks (default 8 KB) as they arrive.
foreach ($response->stream() as $chunk) {
    echo $chunk;
}
```

Prefer `lines()` for text protocols—it handles `\n`/`\r\n` and reassembles lines split across chunk boundaries:

```php
foreach (fetch_stream('https://example.com/events.log')->lines() as $line) {
    process($line);
}
```

The fluent API works too, so you can attach headers, auth, or a base URI first:

```php
use Fetch\Enum\Method;

$response = fetch_client()
    ->getHandler()
    ->withToken($apiKey)
    ->stream(Method::GET, 'https://example.com/feed');

if ($response->ok()) {
    foreach ($response->lines() as $line) {
        // ...
    }
}
```

### Falling back to a buffered response

Call `buffer()` to drain the remaining stream into a regular `Fetch\Http\Response`:

```php
$response = fetch_stream('https://api.example.com/report');

$data = $response->buffer()->json();
```

## Server-Sent Events

`fetch_sse()` (or `->sse()`) returns an `EventSource` that lazily yields `ServerSentEvent` objects parsed according to the WHATWG Server-Sent Events specification. An `Accept: text/event-stream` header is added automatically.

```php
use function fetch_sse;

$events = fetch_sse('https://api.example.com/v1/stream');

foreach ($events as $event) {
    if ($event->isDone()) {   // detects the "[DONE]" sentinel many APIs send
        break;
    }

    $payload = $event->json(); // decode the data: field as JSON
    echo $payload['delta'] ?? '';
}
```

### Streaming an LLM completion

SSE endpoints are frequently `POST` requests. Pass a method and body through the options array:

```php
$events = fetch_sse('https://api.example.com/v1/chat/completions', [
    'method' => 'POST',
    'headers' => ['Authorization' => "Bearer {$apiKey}"],
    'json' => [
        'model' => 'example-model',
        'stream' => true,
        'messages' => [
            ['role' => 'user', 'content' => 'Write a haiku about PHP.'],
        ],
    ],
]);

foreach ($events as $event) {
    if ($event->isDone()) {
        break;
    }

    $delta = $event->json()['choices'][0]['delta']['content'] ?? '';
    echo $delta;
}
```

### The `ServerSentEvent` object

Each dispatched event exposes the parsed fields:

| Property / method | Description |
| ----------------- | ----------- |
| `data`            | The event payload (multiple `data:` lines are joined with `\n`). |
| `type`            | The event type from the `event:` field (defaults to `"message"`). |
| `id`              | The last event ID from the `id:` field, if any. |
| `retry`           | The reconnection time in milliseconds from the `retry:` field, if any. |
| `json()`          | Decode `data` as JSON. |
| `isDone()`        | Whether `data` is the conventional `[DONE]` termination sentinel. |

The `EventSource` also tracks stream-level state via `lastEventId()` and `reconnectionTime()`.

## When to stream

- **Stream** for large downloads, long-lived feeds, and any `text/event-stream` endpoint (streaming AI/LLM APIs, live dashboards, log tails).
- **Buffer** (the default `fetch()`/`get()`/`post()` helpers) for ordinary JSON APIs where you want the whole response at once and features like caching and retries.
