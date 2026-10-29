---
title: Lifecycle Events
description: Subscribe to Fetch request, response, retry, timeout, redirect, and error events.
---

# Lifecycle Events

`Client` and `ClientHandler` expose `on()` and convenience methods such as `onRequest()`, `onResponse()`, `onError()`, `onRetry()`, `onTimeout()`, and `onRedirect()`. Listeners receive event objects; they are intended for observation, while middleware changes request flow.

```php
use Fetch\Events\ResponseEvent;

fetch_client()->onResponse(static function (ResponseEvent $event): void {
    error_log(sprintf('HTTP status %d', $event->getResponse()->getStatusCode()));
});
```

Event names and accessors are defined by the classes in `Fetch\Events`. Listeners must not expose authorization values, cookies, request bodies, or sensitive URL data. Listener exception handling and which events fire depend on the execution path; test the operation you instrument.

Listeners added to the shared client remain attached to that process-wide helper client until it is reset. Clear or reset shared state between tests.
