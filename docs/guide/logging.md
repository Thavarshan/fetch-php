---
title: Logging
description: Add PSR-3 logging middleware and redact request data.
---

# Logging

Register `Fetch\Middleware\LoggingMiddleware` with a PSR-3 logger to log request
activity:

```php
use Fetch\Middleware\LoggingMiddleware;

$handler = fetch_client()->getHandler();
$handler->addMiddleware(new LoggingMiddleware($logger));
```

Logging middleware participates in the middleware pipeline. It does not make
request execution asynchronous. Review the logger configuration and any custom
middleware for redaction of authorization headers, cookies, credentials,
request/response bodies, and sensitive query parameters. Never log
secret-bearing values from example applications.
