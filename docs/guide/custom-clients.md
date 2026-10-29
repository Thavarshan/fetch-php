---
title: Clients and Handlers
description: Choose the PSR-18 client facade or the fluent ClientHandler API.
---

# Clients and Handlers

Use `Fetch\Http\Client` when code needs a PSR-18 client. Use `ClientHandler` for
Fetch-specific fluent configuration such as headers, retries, middleware,
events, streaming, and cache attachment.

`fetch_client()` returns the shared `Client`. Its `getHandler()` method exposes
the handler used by that client. A standalone handler can be created with
`ClientHandler::create()` or with a supplied Guzzle client:

```php
use Fetch\Http\ClientHandler;
use GuzzleHttp\Client as GuzzleClient;

$handler = ClientHandler::createWithClient(
    new GuzzleClient([
        'timeout' => 5,
    ]),
);
$response = $handler->get('https://api.example.test/health');
```

The client facade implements PSR-18 `sendRequest()` and adds convenience request
methods. Handler and client convenience methods may return a React promise when
asynchronous mode is enabled; PSR-18 `sendRequest()` is synchronous.

`fetch_client($options, reset: true)` replaces the process-wide helper client.
Reset shared state in tests. Per-request options passed to `Client` methods are
sent through an immutable request context and do not become defaults for later
requests.
