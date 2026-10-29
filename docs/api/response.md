---
title: Response
description: Fetch response status, header, content-type, and body helpers.
---

# `Fetch\Http\Response`

`Response` extends Guzzle's PSR-7 response and implements `Fetch\Interfaces\Response` and `ArrayAccess`. The interface is the stable type to use for type declarations.

Body readers: `json()`, `object()`, `array()`, `text()`, `body()`, `blob()`, `arrayBuffer()`, and `xml()`. Status readers include `status()`, `statusText()`, `statusEnum()`, `isStatus()`, exact named helpers such as `isNotFound()`, and groups such as `successful()`, `isRedirection()`, `isClientError()`, `isServerError()`, and `failed()`. Note `ok()` means any 2xx code, while `isOk()` means exactly 200.

Content helpers: `contentType()`, `contentTypeEnum()`, `hasJsonContent()`, `hasHtmlContent()`, and `hasTextContent()`. Header methods include PSR-7 methods plus `headers()` and `header(string $name): ?string`. `get(string $key, mixed $default = null)` reads a decoded JSON field. Debug metadata is available with `getDebugInfo()` when attached.

There is no `isSuccess()` or `isJson()` response method. Use `successful()` and `hasJsonContent()` respectively.
