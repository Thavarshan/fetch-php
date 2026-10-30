---
title: Debugging and Profiling
description:
    Enable Fetch request debugging and inspect response debug metadata safely.
---

# Debugging and Profiling

`withDebug()` enables request diagnostics on a handler. A boolean enables the
default debug configuration; an options array configures available diagnostics.
Responses can expose request-specific debug information through
`getDebugInfo()`.

```php
$handler = fetch_client()->getHandler()->withDebug();
$response = $handler->get('https://api.example.test/health');
$debug = $response->getDebugInfo();
```

Debug details depend on the request path and configured options. Streaming and
asynchronous execution follow different paths from the buffered synchronous
request pipeline, so verify what metadata is populated for the operation being
inspected.

Treat diagnostics as sensitive. Avoid logging authorization headers, cookies,
credentials, request bodies, or full user-supplied URLs. Redact data before
forwarding it to logs or tracing systems.
