---
title: Making Requests
description:
    Choose a request entry point and configure methods, URLs, bodies, and Guzzle
    options.
---

# Making Requests

Fetch PHP offers a one-request helper, a shared client facade, and a
configurable handler.

## Choose an entry point

- `fetch($url, $options)` makes one request. `fetch()` with no URL returns the
  global handler for fluent configuration.
- `fetch_client()` returns the shared `Fetch\Http\Client` facade. It implements
  PSR-18 and also has convenience methods such as `get()` and `post()`.
- `$client->getHandler()` returns the `Fetch\Interfaces\ClientHandler` contract
  for handler-only configuration such as `withCache()` and `retry()`.
- Pool and DNS configuration is deprecated since 4.1.0 and does not affect
  outgoing request transport. See
  [HTTP/2 and legacy pool APIs](/guide/connection-pooling).
- `Fetch\Http\ClientHandler::create()` creates an independent handler;
  `createWithBaseUri()` and `createWithClient()` are factories.
- `Fetch\Http\Client::createWithBaseUri()` creates a client facade with a
  configured base URI.

Do not call handler-only methods directly on `fetch_client()`: it returns
`Client`. Use `fetch_client()->getHandler()->withCache(...)` or instantiate a
handler.

## Common methods

The global helpers are `get()`, `post()`, `put()`, `patch()`, and `delete()`.
The client and handler also support `head()` and `options()`. Use
`fetch($url, ['method' => 'TRACE'])` or `sendRequest()` for the additional
`TRACE` and `CONNECT` methods.

```http
GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS, TRACE, CONNECT
```

`get($url, $query)` treats its second argument as query parameters. For the
other global verb helpers, an array body is JSON by default. For an explicit
method, use `fetch()` options.

## Request configuration

Request options follow Guzzle's option names where applicable. Fetch-specific
keys include `base_uri`, `retries`, `retry_delay`, `retry_status_codes`,
`retry_exceptions`, `cache`, `async`, `content_type`, and `token`.

Common request options:

- **Option:** `method`
  - **Meaning:** HTTP method string or `Method` enum

- **Option:** `headers`
  - **Meaning:** Header name/value map

- **Option:** `query`
  - **Meaning:** Query parameter map, encoded by Guzzle

- **Option:** `json`
  - **Meaning:** JSON body; takes precedence over other body options

- **Option:** `form` / `form_params`
  - **Meaning:** URL-encoded form fields

- **Option:** `multipart`
  - **Meaning:** Multipart parts; Guzzle supplies the boundary

- **Option:** `body`
  - **Meaning:** String, stream, or other Guzzle-supported body

- **Option:** `timeout`
  - **Meaning:** Total request timeout, seconds; default 30

- **Option:** `connect_timeout`
  - **Meaning:** Connection timeout, seconds

- **Option:** `allow_redirects`
  - **Meaning:** Guzzle redirect policy

- **Option:** `auth` / `token`
  - **Meaning:** Basic auth credentials or bearer token

- **Option:** `proxy`, `cookies`, `cert`, `ssl_key`, `verify`
  - **Meaning:** Guzzle transport options; availability depends on entry point

For global `fetch()`, `process_request_options()` forwards the common options it
explicitly normalizes. For lower-level Guzzle options such as `verify`,
`version`, `curl`, `sink`, or `decode_content`, configure a `ClientHandler` or
pass them through the `Client` facade.

Within one option set, body precedence is
`json > form/form_params > multipart > body`. A body option from a later
configuration scope replaces the earlier scope's body. The explicit
`content_type` option overrides inferred content types; otherwise an explicit
Content-Type header is preserved case-insensitively. Named `json` and
`form`/`form_params` options determine serialization even when a custom
Content-Type header is supplied; the header is sent as provided. The
`request()`, `post()`, `put()`, `patch()`, and `delete()` body-format argument
also selects serialization independently of an explicit Content-Type header.
A request-scope `content_type` option takes precedence over that argument. A
generic array `body` passed to `sendRequest()` uses `content_type` or
Content-Type to select its serialization. Multipart requests drop the caller's
Content-Type so Guzzle can provide the boundary. Query parameters and other
headers merge with handler defaults.
`Client::fetch()` and the global base-URI helper pass request overrides through
the immutable request context, so they do not persist into later requests or
change the shared handler defaults.

<<< @/examples/snippets/request-options.php

## Base URI and paths

A base URI must be an absolute valid URL. Relative request paths are resolved
using Guzzle URI rules. To configure a reusable client:

<<< @/examples/snippets/client-configuration.php

## Request objects

`Fetch\Http\Request` is an immutable PSR-7 request with factories such as
`Request::json()`, `Request::form()`, `Request::multipart()` and verb factories.
Send a PSR request through `ClientInterface::sendRequest()` when integrating
with PSR-18. See the [Request API](/api/request).

## Redirects, TLS, and proxies

Guzzle options pass through the Client or ClientHandler request path. Keep TLS
verification enabled; provide `verify`, `cert`, and `ssl_key` only for explicit
transport configuration. URI validation checks formatting, not whether a
destination is a private or internal host. Apply SSRF controls in the
application when URLs can come from untrusted input.

See [authentication](/guide/authentication),
[file uploads](/guide/file-uploads), and the
[ClientHandler reference](/api/client-handler).
