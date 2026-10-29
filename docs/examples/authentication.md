---
title: Authentication Example
description: Add bearer or API-key credentials to requests without committing secrets.
---

# Authentication

Pass credentials from your application's secret provider at runtime. For bearer authentication, use the request `token` option or configure the handler with `withToken()`.

```php
$response = fetch($url, ['token' => $token]);

$handler = fetch_client()->getHandler()->withToken($token);
$response = $handler->get($url);
```

For API keys, use a header option. Use HTTPS and redact authorization data from logs. The offline behavior is covered by the `authentication.php` fixture in `composer docs:examples`.
