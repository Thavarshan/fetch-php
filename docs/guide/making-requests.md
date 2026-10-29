---
title: Making Requests
description: Choose a request entry point and configure methods, URLs, bodies, and Guzzle options.
---

# Making Requests

Fetch PHP offers a one-request helper, a shared client facade, and a configurable handler.

## Choose an entry point

- `fetch($url, $options)` makes one request. `fetch()` with no URL returns the global handler for fluent configuration.
- `fetch_client()` returns the shared `Fetch\Http\Client` facade. It implements PSR-18 and also has convenience methods such as `get()` and `post()`.
- `$client->getHandler()` returns the `Fetch\Interfaces\ClientHandler` contract for handler-only configuration such as `withCache()`, `retry()`, and `withConnectionPool()`.
- `Fetch\Http\ClientHandler::create()` creates an independent handler; `createWithBaseUri()` and `createWithClient()` are factories.
- `Fetch\Http\Client::createWithBaseUri()` creates a client facade with a configured base URI.

Do not call handler-only methods directly on `fetch_client()`: it returns `Client`. Use `fetch_client()->getHandler()->withCache(...)` or instantiate a handler.

## Common methods

The global helpers are `get()`, `post()`, `put()`, `patch()`, and `delete()`. The client and handler also support `head()` and `options()`. Use `fetch($url, ['method' => 'TRACE'])` or `sendRequest()` for the additional `TRACE` and `CONNECT` methods.

```http
GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS, TRACE, CONNECT
```

`get($url, $query)` treats its second argument as query parameters. For the other global verb helpers, an array body is JSON by default. For an explicit method, use `fetch()` options.

## Request configuration

Request options follow Guzzle's option names where applicable. Fetch-specific keys include `base_uri`, `retries`, `retry_delay`, `retry_status_codes`, `retry_exceptions`, `cache`, `async`, `content_type`, and `token`.

Common request options:

| Option | Meaning |
| --- | --- |
| `method` | HTTP method string or `Method` enum |
| `headers` | Header name/value map |
| `query` | Query parameter map, encoded by Guzzle |
| `json` | JSON body; takes precedence over other body options |
| `form` / `form_params` | URL-encoded form fields |
| `multipart` | Multipart parts; Guzzle supplies the boundary |
| `body` | String, stream, or other Guzzle-supported body |
| `timeout` | Total request timeout, seconds; default 30 |
| `connect_timeout` | Connection timeout, seconds |
| `allow_redirects` | Guzzle redirect policy |
| `auth` / `token` | Basic auth credentials or bearer token |
| `proxy`, `cookies`, `cert`, `ssl_key`, `verify` | Guzzle transport options; availability depends on entry point |

For global `fetch()`, `process_request_options()` forwards the common options it explicitly normalizes. For lower-level Guzzle options such as `verify`, `version`, `curl`, `sink`, or `decode_content`, configure a `ClientHandler` or pass them through the `Client` facade.

Body option precedence is `json > form/form_params > multipart > body`. Query and headers merge with handler defaults. `Client::fetch()` and the global base-URI helper pass request overrides through the immutable request context, so they do not persist into later requests or change the shared handler defaults.

<<< @/examples/snippets/request-options.php

## Base URI and paths

A base URI must be an absolute valid URL. Relative request paths are resolved using Guzzle URI rules. To configure a reusable client:

<<< @/examples/snippets/client-configuration.php

## Request objects

`Fetch\Http\Request` is an immutable PSR-7 request with factories such as `Request::json()`, `Request::form()`, `Request::multipart()` and verb factories. Send a PSR request through `ClientInterface::sendRequest()` when integrating with PSR-18. See the [Request API](/api/request).

## Redirects, TLS, and proxies

Guzzle options pass through the Client or ClientHandler request path. Keep TLS verification enabled; provide `verify`, `cert`, and `ssl_key` only for explicit transport configuration. URI validation checks formatting, not whether a destination is a private or internal host. Apply SSRF controls in the application when URLs can come from untrusted input.

See [authentication](/guide/authentication), [file uploads](/guide/file-uploads), and the [ClientHandler reference](/api/client-handler).
