---
title: fetch()
description: Reference for the global fetch() helper.
---

# `fetch()`

```text
fetch(
    string|RequestInterface|null $resource = null,
    ?array $options = [],
): ResponseInterface|PromiseInterface|ClientHandlerInterface|Client
```

- Pass a URL string to send a request. The `method` option defaults to `GET`.
- Pass a PSR-7 request to use the shared client's PSR-18 `sendRequest()`;
  additional options are not merged into that request.
- Pass `null` (or omit the argument) to retrieve the shared client's handler for
  fluent configuration.

The URL form accepts high-level options for method, headers, body (`json`,
`form_params`, `multipart`, or `body`), query, timeouts, retry, auth/token,
proxy/cookies, redirects, TLS, streaming, progress, and debug. Body precedence
is JSON, form, multipart, then raw body. The helper explicitly forwards a subset
of Guzzle options; use `ClientHandler` or an injected Guzzle client when you
need other Guzzle-specific options.

```php
$response = fetch('https://api.example.test/v1/users', [
    'method' => 'POST',
    'json' => ['name' => 'Ada'],
    'timeout' => 5,
]);
```

The result can be a Fetch response or a React promise when async mode is active.
See [making requests](/guide/making-requests).
