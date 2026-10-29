---
title: ClientHandler
description: Fluent Fetch request execution and configuration API.
---

# `Fetch\Http\ClientHandler`

`ClientHandler` implements `Fetch\Interfaces\ClientHandler`, combining the configuration, execution, promise, retry, cache, debugging, middleware, event, pool, and Guzzle-client contracts. Create one with `create()`, `createWithBaseUri(string $baseUri)`, or `createWithClient(GuzzleHttp\ClientInterface $client)`.

## Request execution

| Method | Signature |
| --- | --- |
| `request` | `request(string|Method $method, string $uri, mixed $body = null, string|ContentType $contentType = ContentType::JSON, array $options = []): Response|PromiseInterface`. |
| `sendRequest` | `sendRequest(string|Method $method, string $uri, array $options = []): Response|PromiseInterface`. |
| `get` | `get(string $uri, array $queryParams = []): Response|PromiseInterface`. |
| `post`, `put`, `patch`, `delete` | `(string $uri, mixed $body = null, string|ContentType $contentType = ContentType::JSON): Response|PromiseInterface`. |
| `head`, `options` | `(string $uri): Response|PromiseInterface`. |
| `stream` | `(string|Method $method = Method::GET, string $uri = '', array $options = []): StreamedResponse`. |
| `sse` | Same arguments as `stream`; returns `EventSource`. |

## Configuration methods

`RequestConfigurator` provides `withToken()`, `withAuth()`, `withHeaders()`, `withHeader()`, `withBody()`, `withJson()`, `withFormParams()`, `withMultipart()`, `withQueryParameters()`, `withQueryParameter()`, `timeout()`, proxy/cookie/redirect/TLS/stream setters, `withOptions()`, `withOption()`, `baseUri()`, `withClonedOptions()`, `reset()`, and option/header inspection methods.

`RetryableHandler` provides `retry(int $retries, int $delay = 100)`, `retryStatusCodes(array)`, `retryExceptions(array)`, and getters. Delay is milliseconds; retries are additional attempts after the initial request.

`CacheableRequestHandler` provides `withCache(?CacheInterface $cache = null, array $options = [])`, `withoutCache()`, `getCache()`, and `isCacheEnabled()`. `DebuggableHandler` provides `withLogLevel()`, `debug()`, `setLogger()`, `withProfiler()`, and `withDebug()`.

`PromiseHandler` provides `async()`, `isAsync()`, `wrapAsync()`, `awaitPromise()`, `all()`, `race()`, `any()`, `sequence()`, `resolve()`, `reject()`, `map()`, `then()`, `catch()`, and `finally()`. The current HTTP callback performs synchronous Guzzle I/O inside the Matrix task; this promise API does not make Fetch HTTP I/O nonblocking.

Middleware and event methods are summarized in [Middleware and Events](/api/middleware-events). Pool and DNS configuration is exposed by `PoolAwareHandler`, but the pool/DNS services are not integrated into outgoing request execution; see [the limitation and issue #128](/guide/connection-pooling).
