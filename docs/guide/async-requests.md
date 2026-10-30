---
title: Asynchronous Requests
description: Make nonblocking HTTP requests and compose promises with Matrix 4
---

# Asynchronous Requests

Fetch PHP uses [jerome/matrix](https://packagist.org/packages/jerome/matrix) 4
for promise composition and ReactPHP integration. For nonblocking HTTP I/O, make
requests through an async `ClientHandler`. Wrapping a synchronous `fetch()` call
in Matrix's `async()` helper does not make the underlying network request
nonblocking.

## Make an asynchronous request

Call `async()` on a handler before making the request. The request method
returns a ReactPHP promise, which Matrix can await:

```php
use Fetch\Http\ClientHandler;
use function Matrix\Support\await;

$handler = ClientHandler::create()->async();
$response = await($handler->get("https://api.example.com/users"));
$users = $response->json();
```

## Run requests concurrently

Pass the request promises to Matrix's `all()` helper. Associative input keys are
preserved in the result:

```php
use Fetch\Http\ClientHandler;
use function Matrix\Support\all;
use function Matrix\Support\await;

$handler = ClientHandler::create()->async();
$responses = await(
    all([
        "users" => $handler->get("https://api.example.com/users"),
        "posts" => $handler->get("https://api.example.com/posts"),
        "comments" => $handler->get("https://api.example.com/comments"),
    ]),
);

$users = $responses["users"]->json();
$posts = $responses["posts"]->json();
```

You can also chain work on a request promise:

```php
$handler
    ->get("https://api.example.com/users")
    ->then(static fn($response): array => $response->json())
    ->then(static fn(array $users): array => array_column($users, "name"))
    ->catch(static function (\Throwable $error): array {
        // Handle transport and validation failures.
        return [];
    });
```

## Limit concurrency

`ClientHandler::map()` accepts a callback that returns a promise for each item.
Its callback receives the item and its original key; output keys remain
associated with their values. A concurrency value above 200 is rejected:

```php
use Fetch\Http\ClientHandler;
use function Matrix\Support\await;

$handler = ClientHandler::create()->async();
$userIds = [10, 20, 30, 40];

$responses = await(
    $handler->map(
        $userIds,
        fn(int $id, int $key) => $handler->get(
            "https://api.example.com/users/{$id}",
        ),
        concurrency: 3,
    ),
);

foreach ($responses as $key => $response) {
    echo "User at input index {$key}: ", $response->json()["name"], PHP_EOL;
}
```

## Errors, retries, and timeouts

Attach `catch()` to handle a rejected promise. Cancellation is surfaced as
`Matrix\Exceptions\CancellationException` and is not retried:

```php
$handler
    ->get("https://api.example.com/users/999")
    ->then(static fn($response) => $response->json())
    ->catch(static function (\Throwable $error): array {
        error_log("Request failed: " . $error::class);

        return [];
    });
```

Configure retries on the handler. Fetch's retry count means additional attempts
after the first; its delay is in milliseconds:

```php
use Fetch\Http\ClientHandler;
use function Matrix\Support\await;

$handler = ClientHandler::create()->retry(3, 100)->async();
$response = await($handler->get("https://api.example.com/unstable-endpoint"));
```

You can set a per-request timeout with Fetch options or bound how long you await
a promise with Matrix:

```php
use Fetch\Http\ClientHandler;
use function Matrix\Support\await;
use function Matrix\Support\timeout;

$handler = ClientHandler::create()->async();
$response = await(
    timeout($handler->get("https://api.example.com/slow-endpoint"), 5.0),
);
```

## Custom Guzzle clients and event loops

The default Guzzle client uses a cURL multi handler when the cURL extension is
available. Fetch advances its pending transfers from ReactPHP's event loop. If
you supply a custom Guzzle client, inject an `AsyncTransport` with
`setAsyncTransport()` before making asynchronous requests. A
`GuzzleAsyncTransport` must receive the `CurlMultiHandler` that drives that
client.

Without cURL multi or another loop-compatible transport, Fetch rejects async
requests with `AsyncTransportException`. It does not run Guzzle's blocking
stream handler on the event loop. Queue-only Guzzle handlers such as
`MockHandler` may be enabled explicitly for deterministic tests with
`GuzzleAsyncTransport(..., supportsQueueOnlyHandlers: true)`; this option is
intended for queue-backed fakes and does not make blocking network I/O
asynchronous.

## Streaming

`stream()` and `sse()` remain synchronous streaming APIs. They expose response
data incrementally and do not buffer the whole body. When cURL is available, the
default client routes buffered requests through cURL multi and `stream => true`
requests through Guzzle's streaming handler. Asynchronous streaming is not
currently provided.
