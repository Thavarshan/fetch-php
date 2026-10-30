---
title: fetch_client()
description: Reference for the shared global Fetch client factory.
---

# `fetch_client()`

```text
fetch_client(?array $options = null, bool $reset = false): Fetch\Http\Client
```

Returns the process-wide helper client. With options, creates a client if absent
or returns a new client using a cloned handler with those options. With
`reset: true`, replaces the shared instance. The returned object exposes
`getHandler()` for Fetch-specific fluent configuration and implements PSR-18.

```php
$client = fetch_client([
    'base_uri' => 'https://api.example.test/v1/',
    'timeout' => 5,
]);
$response = $client->getHandler()->get('users');
```

Reset shared client state in tests when global helpers, options, middleware,
events, or mocking are involved. See
[clients and handlers](/guide/custom-clients).
