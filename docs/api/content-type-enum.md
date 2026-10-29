---
title: ContentType
description: ContentType MIME constants and helpers.
---

# `Fetch\Enum\ContentType`

`ContentType` is a backed string enum covering JSON variants, forms, multipart,
text, HTML, XML, binary, common files, event streams, and NDJSON. Convert
exactly with `fromString()` or safely with `tryFromString()`.
`normalizeContentType()` returns a matching enum when known and leaves an
unknown string unchanged.

Category methods include `isJson()`, `isForm()`, `isMultipart()`, `isText()`,
`isEventStream()`, and `isStreamable()`. Matching uses the enum's declared MIME
strings; response content types may include parameters, so use a response's
`hasJsonContent()` helper for response inspection.
