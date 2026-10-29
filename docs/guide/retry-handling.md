---
title: Retry Handling
description: Configure HTTP retries, retryable responses, exceptions, and timing.
---

# Retry handling

Fetch's HTTP retry policy is implemented by `RetryStrategy` and applies to requests made through the handler pipeline. The default is one retry (two total request attempts) with a 100 millisecond base delay.

## Configure a handler

```php
$handler = fetch_client()->getHandler();
$handler
    ->retry(3, 200)
    ->retryStatusCodes([429, 503]);
```

`retry($retries, $delay)` takes a retry count excluding the initial request and a base delay in milliseconds. The strategy applies exponential backoff with jitter and caps the delay at 30 seconds. Use `retryStatusCodes(array $codes)` and `retryExceptions(array $classes)` to replace the handler's retry criteria.

The default status list is 408, 429, 500, 502, 503, 504, 507, 509, 520, 521, 522, 523, 525, 527, and 530. The default retryable exception is Guzzle's `ConnectException`.

## Per-request options

`fetch()` and handler request options accept `retries` and `retry_delay`. Delay values are milliseconds. The legacy alias `max_retries` is normalized to `retries`; if both are present, `retries` wins. Optional `retry_status_codes` and `retry_exceptions` values configure request-level criteria.

Retries do not make unsafe operations idempotent. Use them for operations that can safely be repeated or that have an idempotency key supported by the remote service.

## Responses and exceptions

Retryable status codes are retried before the final response is returned. HTTP status responses are still responses; after attempts are exhausted, Fetch returns the last response. Retryable transport exceptions may be retried and then surface as a Fetch request or network exception, preserving the previous exception.

Streaming requests use a separate synchronous path and do not use the normal retry pipeline.

## Matrix retry is different

The global promise helper `retry()` is Matrix-style operation retry, not Fetch's HTTP retry policy. It counts total attempts and takes delay in milliseconds through Fetch's global wrapper. `Matrix\Support\retry()` takes total attempts and backoff seconds. It does not inspect HTTP status codes or Fetch retry settings.

See [error handling](/guide/error-handling) and the [ClientHandler API](/api/client-handler).
