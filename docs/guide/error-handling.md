---
title: Error Handling
description:
    Distinguish HTTP error responses from transport and request exceptions.
---

# Error Handling

Fetch returns HTTP responses for status codes such as 404 and 500 by default.
Check status or response helpers; a non-2xx response is not automatically a
thrown exception.

```php
use Fetch\Interfaces\Response as FetchResponse;

$response = fetch('https://api.example.test/v1/item');

if (!($response instanceof FetchResponse)) {
    throw new RuntimeException('Expected a completed response.');
}

if ($response->failed()) {
    // Inspect status(), headers, and body() before deciding how to recover.
}
```

Transport or request failures may throw Fetch exceptions such as
`NetworkException`, `RequestException`, or `HttpException`, depending on the
failure and calling API. Catch the narrowest relevant type and preserve the
previous exception when wrapping failures.

In Matrix promise workflows, handle rejections with `then()`'s second callback
or `catch()`. `await()` rethrows a rejected operation. A synchronous Guzzle
request inside Fetch's async callback can block the event loop; see
[async requests](/guide/async-requests).
