---
title: Helper Functions
description:
    Reference for the global fetch, client, HTTP verb, streaming, and Matrix
    helpers.
---

# Helper Functions

Composer autoloads the helpers from `Fetch\Support\helpers.php`. They are global
functions; no namespace import is required for `fetch()` or the HTTP verb
helpers.

## Request helpers

- **Function:** `fetch()`
  - **Signature:** `fetch(string\|RequestInterface\|null $resource = null,
                   ?array $options = [])`
  - **Behavior:** Sends a request; with `null` returns the global handler; with
                  a PSR request sends through the PSR-18 client

- **Function:** `fetch_client()`
  - **Signature:** `fetch_client(?array $options = null, bool $reset = false):
                   Client`
  - **Behavior:** Gets or reconfigures the shared `Fetch\Http\Client`

- **Function:** `get()`
  - **Signature:** `get(string $url, ?array $query = null, ?array $options =
                   [])`
  - **Behavior:** GET; second argument is query parameters

- **Function:** `post()`, `put()`, `patch()`, `delete()`
  - **Signature:** `(string $url, mixed $data = null, ?array $options = [])`
  - **Behavior:** Array data becomes a JSON body; scalar data becomes a raw body

- **Function:** `fetch_stream()`
  - **Signature:** `fetch_stream(string $url, ?array $options = []):
                   StreamedResponse`
  - **Behavior:** Synchronous, unbuffered response; `method` may be set in
                  options

- **Function:** `fetch_sse()`
  - **Signature:** `fetch_sse(string $url, ?array $options = []): EventSource`
  - **Behavior:** Synchronous SSE reader; `method` may be set in options

The verb helpers accept request options as the final argument. For form or
multipart requests use `fetch()` with `form` or `multipart`, or use the
handler's `withFormParams()` and `withMultipart()` methods.

## Client lifetime and configuration

`fetch_client()` keeps one static `Client`. Calling `fetch_client($options)`
creates a new client with a cloned handler and those defaults; calling
`fetch_client(reset: true)` recreates it. Configuration is process-wide for
callers using the helper. Prefer injecting an explicit `Client` or handler in
long-lived applications and tests that need isolation.

`fetch_client()` returns the facade. To reach handler-specific methods, call
`fetch_client()->getHandler()`. See
[client creation and options](/guide/making-requests).

## Matrix helper names

Fetch conditionally defines global convenience wrappers for Matrix helpers when
Matrix 4 functions are available: `async()`, `await()`, `all()`, `any()`,
`race()`, `map()`, `batch()`, and `retry()`. They are global functions from the
Fetch helper file, while Matrix's canonical functions live in `Matrix\Support`.
Prefer explicit imports such as:

```php
use function Matrix\Support\async;
use function Matrix\Support\await;
```

Fetch global wrappers use array arguments. Matrix namespaced functions accept
the iterable signatures in Matrix's API. Their timing and retry units differ;
see [async requests](/guide/async-requests) and
[promise operations](/guide/promise-operations).

## Internal helper functions

`request_method()`, `process_request_options()`,
`extract_body_and_content_type()`, and `handle_request_with_base_uri()` are
global implementation helpers loaded with the package. They support the public
helpers; application code should normally use `fetch()`, the verb helpers,
`Client`, or `ClientHandler` instead.
