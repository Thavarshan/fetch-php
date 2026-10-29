---
title: Request
description: Fetch request model and body/header/query helpers.
---

# `Fetch\Http\Request`

`Request` extends Guzzle's PSR-7 request and retains PSR-7 immutability. Its constructor accepts `string|Method $method`, `string|UriInterface $uri`, headers, body, protocol version, and optional request target.

Factories:

- `Request::json($method, $uri, array $data, array $headers = [])`
- `Request::form($method, $uri, array $formParams, array $headers = [])`
- `Request::multipart($method, $uri, array $parts, array $headers = [])`
- Method factories: `get`, `post`, `put`, `patch`, `delete`, `head`, and `options`; `createFromBase(RequestInterface $request)` copies a PSR request.

Body and inspection helpers include `withJsonBody()`, `withFormBody()`, `withBody()`, `withContentType()`, `getBodyAsString()`, `getBodyAsJson()`, `getBodyAsFormParams()`, `hasJsonContent()`, `hasFormContent()`, `hasMultipartContent()`, and `hasTextContent()`. Query helpers are `withQueryParam()` and `withQueryParams()`. Authentication helpers are `withBearerToken()` and `withBasicAuth()`.

Every `with*` method returns a new immutable request. Keep the returned value. Send it through `Client::sendRequest()` or the global `fetch($request)` helper.
