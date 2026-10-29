---
title: Testing
description: Test Fetch requests deterministically with MockServer and Guzzle MockHandler.
---

# Testing

Tests must not make real network calls. Use `MockServer` for the normal buffered request pipeline and Guzzle's `MockHandler` when testing transport-level behavior such as cache or streaming.

## MockServer

Register fakes before making a request, then enable the stray-request guard. `fake()` resets MockServer state, so call the guard after it.

```php
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;

MockServer::fake([
    'GET https://api.example.test/v1/users' => MockResponse::json(['users' => []]),
]);
MockServer::preventStrayRequests();

$response = get('https://api.example.test/v1/users');
MockServer::assertSent('GET https://api.example.test/v1/users');
```

Patterns can include the method and URL; fakes also support wildcard patterns, callbacks, and `MockResponseSequence`. Check `src/Fetch/Testing/MockServer.php` for matching and assertion syntax. `record()` enables request/response recording, and `recorded()` returns the recorded interactions.

## MockResponse

`MockResponse::json($data, $status = 200, $headers = [])` creates JSON. `MockResponse::create($status, $body, $headers)` creates a custom response. Other convenience factories include `ok()`, `created()`, `noContent()`, `notFound()`, and `serviceUnavailable()`; check their method signatures before passing arguments.

## Injected Guzzle handler

`MockServer` fakes return in the Fetch request pipeline before `CacheManager` and are not used by streaming. Use an injected Guzzle `MockHandler` for tests that need to exercise those layers:

```php
use Fetch\Http\ClientHandler;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

$guzzle = new GuzzleClient([
    'handler' => HandlerStack::create(new MockHandler([
        new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
    ])),
]);
$handler = ClientHandler::createWithClient($guzzle);
$response = $handler->get('https://api.example.test/v1/health');
```

## Isolation

The global helper client and MockServer use shared state. Reset them between tests. Tests extending `Tests\TestCase` also reset `GlobalServices` in `tearDown()`.

The repository uses PHPUnit 11. Run `composer test`, `composer test:unit`, or `composer test:integration`. See [testing utilities](/api/testing).
