---
title: ClientHandler
description: Fluent Fetch request execution and configuration API.
---

# `Fetch\Http\ClientHandler`

`ClientHandler` implements `Fetch\Interfaces\ClientHandler`, combining
configuration, execution, promise, retry, cache, debugging, middleware, event,
pool, and Guzzle-client contracts.

Create a handler with `create()`, `createWithBaseUri()`, or
`createWithClient()`.

## Request execution

The fluent request methods return a Fetch response or a React promise when async
mode is enabled.

```text
request(
    string|Method $method,
    string $uri,
    mixed $body = null,
    string|ContentType $contentType = ContentType::JSON,
    array $options = [],
): Response|PromiseInterface

sendRequest(
    string|Method $method,
    string $uri,
    array $options = [],
): Response|PromiseInterface

get(string $uri, array $queryParams = []): Response|PromiseInterface
```

`post()`, `put()`, `patch()`, and `delete()` accept the URI, optional body,
and content type. `head()` and `options()` accept the URI.

Streaming uses separate methods:

```text
stream(
    string|Method $method = Method::GET,
    string $uri = '',
    array $options = [],
): StreamedResponse

sse(
    string|Method $method = Method::GET,
    string $uri = '',
    array $options = [],
): EventSource
```

## Configuration

`RequestConfigurator` provides `withToken()`, `withAuth()`, `withHeaders()`,
`withHeader()`, `withBody()`, `withJson()`, `withFormParams()`,
`withMultipart()`, `withQueryParameters()`, `withQueryParameter()`,
`timeout()`, `withOptions()`, `withOption()`, `baseUri()`,
`withClonedOptions()`, `reset()`, and option/header inspection methods.
Other methods configure proxy, cookies, redirects, TLS, and streaming.

`RetryableHandler` provides `retry()`, `retryStatusCodes()`,
`retryExceptions()`, and getters. Retry delay is measured in milliseconds;
retry counts are additional attempts after the first request.

`CacheableRequestHandler` provides `withCache()`, `withoutCache()`,
`getCache()`, and `isCacheEnabled()`. Caching requires an attached cache
manager.

`DebuggableHandler` provides `withLogLevel()`, `debug()`, `setLogger()`,
`withProfiler()`, and `withDebug()`.

`PromiseHandler` provides `async()`, `isAsync()`, `wrapAsync()`,
`awaitPromise()`, `all()`, `race()`, `any()`, `sequence()`, `resolve()`,
`reject()`, `map()`, `then()`, `catch()`, and `finally()`.

Async requests use a ReactPHP-compatible `AsyncTransport`. The default client
uses cURL multi; custom Guzzle clients must supply their transport explicitly.
`setAsyncTransport(AsyncTransport $transport)` replaces that bridge.

The concrete handler also provides `withRequestSchema(mixed $schema)`,
`withResponseSchema(mixed $schema)`, `withOpenApiSpec(string $path)`, and
`validateContracts(bool $validate = true)`. These are opt-in; see
[validation](/guide/validation) for optional adapters and per-request options.

See [middleware and events](/api/middleware-events). Pool and DNS configuration
is exposed by `PoolAwareHandler`, but those services are not integrated into
request execution; see [connection pooling](/guide/connection-pooling).
