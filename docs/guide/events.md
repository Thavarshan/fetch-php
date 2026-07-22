---
title: Events & Hooks
description: Observe the full request/response lifecycle with prioritised event listeners and correlation IDs for logging, metrics, and tracing.
---

# Events & Hooks

The event system lets you observe the request/response lifecycle for logging, metrics, tracing, and alerting — without wrapping every call. Where [middleware](/guide/middleware) actively transform the request/response, events are for **observation**: listeners receive a rich event object and return nothing.

Every event for a single logical request shares a **correlation ID**, so a request and its response, retries, and errors can be tied together across your logs.

> Listeners are isolated: if a listener throws, the error is logged (when a logger is configured) and the remaining listeners still run. Observability code can never break a request.

## Listening to events

Register listeners on the client or handler:

```php
use Fetch\Events\RequestEvent;
use Fetch\Events\ResponseEvent;
use Fetch\Events\ErrorEvent;

fetch_client()
    ->onRequest(function (RequestEvent $event) use ($logger) {
        $logger->info('Request started', [
            'correlation_id' => $event->getCorrelationId(),
            'method' => $event->getRequest()->getMethod(),
            'uri' => (string) $event->getRequest()->getUri(),
        ]);
    })
    ->onResponse(function (ResponseEvent $event) use ($metrics) {
        $metrics->timing('http.duration_ms', $event->getLatency(), [
            'status' => $event->getResponse()->getStatusCode(),
        ]);
    })
    ->onError(function (ErrorEvent $event) use ($alerts) {
        $alerts->notify('HTTP request failed', [
            'correlation_id' => $event->getCorrelationId(),
            'error' => $event->getException()->getMessage(),
        ]);
    })
    ->get('https://api.example.com/users');
```

Listeners registered on the global client (`fetch_client()`) apply to the `fetch()`, `get()`, `post()`, … helpers too, since they share the same handler.

## Available events

| Method | Event name | Fired when | Notable accessors |
| ------ | ---------- | ---------- | ----------------- |
| `onRequest` | `request.sending` | Before a request is sent (after middleware) | `getRequest()`, `getCorrelationId()` |
| `onResponse` | `response.received` | A response is received (including cache/mock hits) | `getResponse()`, `getDuration()`, `getLatency()` |
| `onError` | `error.occurred` | The request fails with an exception | `getException()`, `getAttempt()`, `getResponse()` |
| `onRetry` | `request.retrying` | Before a retry attempt | `getAttempt()`, `getMaxAttempts()`, `getDelay()`, `getPreviousException()`, `isLastAttempt()` |
| `onTimeout` | `request.timeout` | The failure is a timeout (fired alongside `onError`) | `getTimeout()`, `getElapsed()` |
| `onRedirect` | `request.redirecting` | The transport follows a redirect | `getResponse()`, `getLocation()`, `getRedirectCount()` |

All events extend `Fetch\Events\FetchEvent` and expose `getRequest()`, `getCorrelationId()`, `getTimestamp()`, and `getContext()`.

## Registering many listeners at once

`hooks()` accepts an array keyed by event name or a friendly alias:

```php
fetch_client()->hooks([
    'before_send'    => fn ($event) => $tracer->start($event->getCorrelationId()),
    'after_response' => fn ($event) => $tracer->finish($event->getCorrelationId()),
    'on_error'       => fn ($event) => $tracer->fail($event->getCorrelationId()),
    'on_retry'       => fn ($event) => $stats->increment('http.retries'),
    'on_timeout'     => fn ($event) => $stats->increment('http.timeouts'),
    'on_redirect'    => fn ($event) => $logger->info('Redirected to ' . $event->getLocation()),
]);
```

Recognised aliases: `before_send`, `after_response`, `on_error`, `on_retry`, `on_timeout`, `on_redirect`. Any other key is treated as a raw event name.

## Priority

Listeners run highest-priority first; equal priorities run in registration order:

```php
fetch_client()
    ->onResponse($auditLogger, priority: 100) // runs first
    ->onResponse($metrics);                    // priority 0, runs later
```

## Correlation IDs

A correlation ID is generated for each request and shared by every event it produces. Use it to stitch together a request's lifecycle in your logs or a tracing system:

```php
fetch_client()
    ->onRequest(fn ($e) => $tracer->startSpan('http', $e->getCorrelationId()))
    ->onResponse(fn ($e) => $tracer->endSpan($e->getCorrelationId(), $e->getLatency()));
```

## Async

Events fire in asynchronous mode too. `response.received` and `error.occurred` are dispatched when the underlying promise settles, so listeners see the resolved response or the rejection.

```php
use function Matrix\Support\await;

fetch_client()->onResponse(fn ($e) => $metrics->timing('http', $e->getLatency()));

await(fetch_client()->getHandler()->async()->get('https://api.example.com/data'));
```

## Events vs. middleware

- **Events** observe. Listeners can't change the request or response; a throwing listener is isolated.
- **[Middleware](/guide/middleware)** participate. They can modify the request/response, short-circuit, or handle errors.

Reach for events for logging, metrics, and tracing; reach for middleware when you need to alter the request/response flow.
