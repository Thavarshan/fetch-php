---
title: Troubleshooting
description:
    Resolve common installation, request, response, and event-loop issues.
---

# Troubleshooting

## Composer reports missing `ext-pcntl`

The package metadata requires `ext-pcntl`. Confirm Composer uses the same PHP
installation as your application and enable the extension where available.
Windows CI ignores this requirement for its test matrix; that does not remove
the package constraint from a normal Composer install.

## A 404 or 500 did not throw

HTTP status errors are returned as responses by default. Inspect `status()`,
`failed()`, or a named status method. Transport and request failures use
exceptions. See [error handling](/guide/error-handling).

## A relative URI is rejected or resolves unexpectedly

Relative paths need a valid absolute `base_uri` configured on the client/handler
or passed to `fetch()`. URI format validation does not block private or internal
hosts; apply SSRF controls when URLs include untrusted input.

## `fetch_client()->withCache()` is undefined

`fetch_client()` returns `Fetch\Http\Client`. Handler-specific features are
available from `$client->getHandler()`, for example
`$client->getHandler()->withCache(...)`.

## Async work blocks the event loop

Use `ClientHandler::create()->async()` for nonblocking requests. Wrapping a
synchronous request in Matrix `async()` still blocks. A custom Guzzle client
needs an explicit `AsyncTransport`; missing drivers reject before dispatch. See
[async requests](/guide/async-requests).

## A streaming or cached test bypasses `MockServer`

`MockServer` covers the regular buffered request path. Streaming and cache-hit
behavior should use an injected Guzzle `MockHandler`. See
[testing](/guide/testing).
