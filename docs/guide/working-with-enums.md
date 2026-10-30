---
title: HTTP Enums
description: Use the Method, ContentType, and Status backed enums.
---

# HTTP Enums

Fetch PHP provides backed enums under `Fetch\Enum`.

## Method

`Method` contains `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`,
`TRACE`, and `CONNECT`. Use `fromString()` to convert a method or
`tryFromString()` to return a fallback. `supportsRequestBody()` returns true for
POST, PUT, PATCH, and DELETE.

## ContentType

`ContentType` includes JSON, URL-encoded form, multipart, text, HTML, XML,
binary, PDF, CSV, ZIP, JavaScript, CSS, event stream, NDJSON, and common
JSON-based media types (JSON:API, JSON-LD, GeoJSON, and Problem Details).

`isJson()` recognizes the JSON media type and those JSON-based types.
`normalizeContentType()` returns an enum for a recognized value or preserves an
unknown media-type string. Content type parsing is case-insensitive but expects
the media type value without parameters.

## Status

`Status` is an integer-backed enum with the HTTP status cases present in the
source. Use `fromInt()` or `tryFromInt()` and `phrase()`. Category methods
include `isInformational()`, `isSuccess()`, `isRedirection()`,
`isClientError()`, `isServerError()`, `isError()`, `isCacheable()`,
`isNotModified()`, and `isEmpty()`.

For exact response checks, use `$response->isStatus(Status::NOT_FOUND)`. For
category checks use `$response->successful()` or check
`$response->statusEnum()?->isSuccess()`.

See the [enum reference pages](/api/method-enum),
[ContentType](/api/content-type-enum), and [Status](/api/status-enum).
