---
title: Authentication
---

description: Configure bearer tokens, basic authentication, and request headers.
---

# Authentication

Authentication can be applied to one request or configured on a handler.
`withToken()` sets a bearer `Authorization` header; `withAuth()` configures
Guzzle basic authentication.

## Bearer token

```php
$handler = fetch_client()->getHandler();
$response = $handler
    ->withToken($token)
    ->get('https://api.example.test/v1/profile');
```

For a single call, pass a `token` request option to `fetch()` or a verb helper.
Helper options are per-request.

```php
$response = fetch('https://api.example.test/v1/profile', ['token' => $token]);
```

For an API key or custom scheme, set a request header:

```php
$response = get('https://api.example.test/v1/items', null, [
    'headers' => ['X-API-Key' => $apiKey],
]);
```

## Basic authentication

```php
$response = fetch('https://api.example.test/v1/private', [
    'auth' => [$username, $password],
]);
```

Use HTTPS and obtain credentials from an appropriate secret store. Do not write
tokens or passwords to logs, fixtures, or committed examples. The offline
fixture is checked by `composer docs:examples`.

<<< @/examples/snippets/authentication.php
