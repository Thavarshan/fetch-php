---
title: Upgrading to Fetch PHP 4
description: Migrate Matrix, async transports, cache keys, and custom handlers.
---

# Upgrading to Fetch PHP 4

Version 4 requires PHP `^8.3` and `jerome/matrix ^4.0`. PHP's minimum version
is unchanged. Resolve application dependencies and run your own test suite:

```sh
composer require jerome/fetch-php:^4.0 --with-all-dependencies
```

Applications should commit their own `composer.lock`; Fetch is a library and
does not commit one. Review the [changelog] for the complete release scope.

## Use the async transport

Use an async handler for nonblocking buffered requests:

```php
use Fetch\Http\ClientHandler;
use function Matrix\Support\await;

$handler = ClientHandler::create()->async();
$response = await($handler->get('https://api.example.test/users'));
```

The default transport requires `ext-curl`. Synchronous `fetch()` inside
Matrix's `async()` still blocks. PSR-18 `Client::sendRequest()` stays
synchronous. Do not start a blocking `await()` from an already running loop;
chain promises there instead.

For a custom Guzzle client, explicitly connect its cURL multi driver:

```php
use Fetch\Http\ClientHandler;
use Fetch\Http\GuzzleAsyncTransport;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\Handler\Proxy;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;

$driver = new CurlMultiHandler(['select_timeout' => 0.001]);
$client = new Client([
    'handler' => HandlerStack::create(
        Proxy::wrapStreaming($driver, new StreamHandler),
    ),
    'http_errors' => false,
]);
$handler = ClientHandler::createWithClient(
    $client,
    new GuzzleAsyncTransport($client, $driver),
)->async();
```

An unsupported transport rejects with `AsyncTransportException` before
network dispatch. Queue-only mode is for deterministic Guzzle fakes; it does
not make a blocking handler asynchronous. See [async requests] for details.

## Cancellation and return types

Cancellation rejects with `Matrix\Exceptions\CancellationException` and
propagates to the active transport and retry operation. Handle the rejected
promise even when cancelling it. Async middleware and mock short circuits
return promises consistently. Await or chain every async result.

Client convenience method return unions now include `PromiseInterface`.
Subclasses must keep compatible signatures. Existing named response status
helpers and `isStatus()` remain available.

## Custom handlers and traits

Request execution delegates to `RequestCoordinator`, `RequestBuilder`, and
separate executors. Overrides of protected execution internals should move
custom behavior to middleware or an `AsyncTransport`. `ManagesPromises`
remains a deprecated shim for direct trait consumers; use `PromiseManager`
for new composition. Logger forwarding still uses the existing handler
contract.

`buildFullUri()` remains deprecated in favor of `buildFullUriFromContext()`.
The handler mapper accepts 1 to 200 concurrent tasks and preserves input keys.
Retries count additional attempts and take delays in milliseconds.

## Caching and request options

Per-request options and base URIs no longer become shared defaults. Query
options replace an existing URI query as in Guzzle; the final query is built
once. Persist defaults explicitly on a handler when they should survive calls.

Clear persisted Fetch caches on upgrade. Cache keys now use the final request
headers and always partition Authorization and Cookie values, including custom
keys. Authentication hidden in a custom transport needs an explicit varying
header or key. A custom key must still distinguish unrelated resources.

## Validation and streaming

Validation is opt-in and uses optional adapters. Install them as described in
[validation]. Request policies apply after middleware; response policies apply
to middleware, mock, cache, and transport responses. Invalid responses are not
cached or retried. OpenAPI support is 3.0.x; remote references are disabled.

Streaming and SSE remain synchronous, bypass the buffered lifecycle, and are
not buffered for validation. A configured policy raises
`ValidationConfigurationException`; disable it before streaming.

The separate Fetch connection-pool and DNS-cache APIs remain configuration
facilities and do not select the outgoing transport. See [connection pooling].

[changelog]: https://github.com/Thavarshan/fetch-php/blob/main/CHANGELOG.md
[async requests]: /guide/async-requests
[validation]: /guide/validation
[connection pooling]: /guide/connection-pooling
