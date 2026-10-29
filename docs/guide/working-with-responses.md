---
title: Working with Responses
description: Read response status, headers, JSON, text, XML, and debug metadata.
---

# Working with Responses

Buffered requests return `Fetch\Http\Response`, which extends Guzzle's PSR-7 response and implements the Fetch response contract and `ArrayAccess`. It does not throw just because the server returned 4xx or 5xx; transport failures can throw Fetch exceptions.

## Status

Use `status(): int` for the numeric code, `statusText(): string` for its reason phrase, and `statusEnum(): ?Status` when a known `Fetch\Enum\Status` case is useful.

- `ok()` and `successful()` mean any 2xx status; `isOk()` means exactly 200.
- `successful()` means any 2xx status.
- `failed()` means 4xx or 5xx.
- `isRedirection()`, `isClientError()`, `isServerError()`, and `isInformational()` are category checks.
- `isStatus(Status|int $status)` is the canonical exact-code check.
- Named exact-code methods include `isCreated()`, `isAccepted()`, `isNoContent()`, `isMovedPermanently()`, `isFound()`, `isBadRequest()`, `isUnauthorized()`, `isForbidden()`, `isNotFound()`, `isConflict()`, `isUnprocessableEntity()`, `isTooManyRequests()`, `isInternalServerError()`, and `isServiceUnavailable()`.

There is no `isSuccess()` method on Response; that predicate exists on the `Status` enum. There is no `isJson()` method on Response; use `hasJsonContent()`.

## Body access

| Method | Result |
| --- | --- |
| `json(bool $assoc = true, bool $throwOnError = true, int $depth = 512, int $options = 0): mixed` | Decodes JSON. On decode error it throws `RuntimeException` by default; with errors disabled it returns an empty array or object. |
| `array(bool $throwOnError = true): array` | Decodes as an array; returns an empty array for non-array JSON when errors are not thrown. |
| `object(bool $throwOnError = true): object` | Decodes as an object. |
| `text(): string` / `body(): string` | Returns the buffered body as a string. |
| `arrayBuffer(): string` | Returns raw body bytes as a string. |
| `blob()` | Copies the body to a rewound in-memory PHP stream resource. |
| `xml(int $options = 0, bool $throwOnError = true): ?SimpleXMLElement` | Parses XML; requires SimpleXML. Returns null on parse errors when throwing is disabled. |

`Response` buffers the body, so calling these helpers materializes the response contents in memory. Use `fetch_stream()` for large or incrementally produced bodies.

Array access (`$response['key']`) reads the decoded JSON payload. Use `json()` when you need to control decoding or handle the top-level type explicitly.

## Headers and content type

- `headers(): array` returns PSR-7 header arrays.
- `header(string $name): ?string` returns a comma-joined header line or null when absent.
- `contentType(): ?string` returns the media type without parameters such as charset.
- `contentTypeEnum(): ?ContentType` maps recognized types to the enum.
- `hasJsonContent()`, `hasHtmlContent()`, and `hasTextContent()` are content-type checks. They do not decode or validate the body.

## Metadata and immutability

Responses retain PSR-7 immutable methods such as `withHeader()` and `withStatus()`. Fetch-specific `withDebugInfo()` attaches a `DebugInfo` snapshot, and `getDebugInfo()` / `hasDebugInfo()` read it. The response `get(string $key, mixed $default = null)` helper reads a JSON object/array key with a default.

For raw PSR-7 methods, consult the PSR-7 response contract. See the [Response API](/api/response) and [streaming guide](/guide/streaming).
