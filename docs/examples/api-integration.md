---
title: API Integration
description: Organize shared configuration with a Fetch client and handler.
---

# API Integration

Use `fetch_client()` for the process-wide helper client, or construct `Fetch\Http\Client` / `ClientHandler` when an application needs explicit dependency ownership. Keep endpoint-specific configuration in the request options and shared configuration on the handler.

```php
$client = fetch_client([
    'base_uri' => 'https://api.example.test/v1/',
    'headers' => ['Accept' => 'application/json'],
]);

$handler = $client->getHandler();
$response = $handler->get('users', ['query' => ['page' => 2]]);
```

Convenience methods can return a response or a promise depending on async mode. See [making requests](/guide/making-requests). The offline client configuration fixture is included below.

<<< @/examples/snippets/client-configuration.php
