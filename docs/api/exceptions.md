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

Handler and convenience requests preserve the validation exception types in
synchronous calls and async rejections, including mock and middleware short
circuits. See
[validation](/guide/validation) for the configured lifecycle boundaries.

## HTTP client exceptions

`Fetch\Exceptions\ClientException` is the base Fetch client exception and
implements the PSR client exception contract. `NetworkException` implements PSR
`NetworkExceptionInterface`; `RequestException` includes the relevant
request/response context; `HttpException` is a legacy runtime exception that can
carry a response.

PSR-18 `Client::sendRequest()` maps validation failures to client exceptions,
retaining the original failure as the previous exception.

HTTP status codes are normally returned as responses rather than thrown. Catch
client exceptions for transport/request failures, and inspect response status
helpers for HTTP error statuses. See [error handling](/guide/error-handling).
