---
title: Error Handling Example
description: Check response status codes and handle rejected requests.
---

# Error Handling

HTTP error statuses are responses by default. Check `failed()`, `status()`, or exact status helpers after confirming the result is a Fetch response. Catch transport/request exceptions separately. In async workflows, attach a rejection callback or call `catch()`; `await()` rethrows rejection reasons.

```php
try {
    $response = fetch($url);
    if ($response instanceof \Fetch\Interfaces\Response && $response->failed()) {
        // Apply application policy to the HTTP error response.
    }
} catch (\Fetch\Exceptions\ClientException $exception) {
    // Handle the transport or client failure.
}
```

See the [error handling guide](/guide/error-handling) for the distinction between HTTP statuses and thrown exceptions.
