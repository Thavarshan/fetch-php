---
title: Method
description: HTTP method enum values and request body capability helper.
---

# `Fetch\Enum\Method`

Cases: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`, `TRACE`, and
`CONNECT`. Values are uppercase HTTP method strings. `fromString()` normalizes
input to uppercase and throws `ValueError` if unknown; `tryFromString()` returns
the supplied default (null by default) for unknown values.

`supportsRequestBody()` returns true for POST, PUT, PATCH, and DELETE. It
returns false for GET, HEAD, OPTIONS, TRACE, and CONNECT.
