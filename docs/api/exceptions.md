---
title: Exceptions
description: Fetch exception hierarchy and PSR client error contracts.
---

# Exceptions

## Validation exceptions

Optional validation uses exceptions under `Fetch\Validation`:

- `RequestValidationException` and `ResponseValidationException` extend
  `ValidationException`; `result()` exposes structured validation errors.
- `ValidationDependencyException` identifies a missing optional adapter.
- `ValidationConfigurationException` identifies an invalid schema or policy.

The validation exception types are preserved in synchronous requests and async
rejections, including mock and middleware short circuits. See
[validation](/guide/validation) for the configured lifecycle boundaries.

## HTTP client exceptions

`Fetch\Exceptions\ClientException` is the base Fetch client exception and
implements the PSR client exception contract. `NetworkException` implements PSR
`NetworkExceptionInterface`; `RequestException` includes the relevant
request/response context; `HttpException` is a legacy runtime exception that can
carry a response.

HTTP status codes are normally returned as responses rather than thrown. Catch
client exceptions for transport/request failures, and inspect response status
helpers for HTTP error statuses. See [error handling](/guide/error-handling).
