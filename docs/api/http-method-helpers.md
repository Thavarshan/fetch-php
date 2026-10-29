---
title: HTTP Method Helpers
description: Reference for the global get, post, put, patch, and delete helpers.
---

# HTTP Method Helpers

```text
get(
    string $url,
    ?array $query = null,
    ?array $options = [],
): ResponseInterface|PromiseInterface

post(
    string $url,
    mixed $data = null,
    ?array $options = [],
): ResponseInterface|PromiseInterface

put(
    string $url,
    mixed $data = null,
    ?array $options = [],
): ResponseInterface|PromiseInterface

patch(
    string $url,
    mixed $data = null,
    ?array $options = [],
): ResponseInterface|PromiseInterface

delete(
    string $url,
    mixed $data = null,
    ?array $options = [],
): ResponseInterface|PromiseInterface
```

`get()` treats argument two as query parameters. For `post()`, `put()`,
`patch()`, and `delete()`, an array in argument two is JSON-encoded; use request
options when sending form or multipart data. All accept additional request
options as the last argument.

```php
$response = get('https://api.example.test/v1/users', ['active' => true]);
$created = post('https://api.example.test/v1/users', ['name' => 'Ada']);
```

These global helpers use the shared client. For `HEAD`, `OPTIONS`, `TRACE`, or
`CONNECT`, use `fetch()` with the method option or the client/handler API. See
[making requests](/guide/making-requests).
