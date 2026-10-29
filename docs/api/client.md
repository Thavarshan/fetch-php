---
title: Client
description: PSR-18 client facade and convenience request methods.
---

# `Fetch\Http\Client`

`Client` implements PSR-18 `ClientInterface` and PSR-3
`LoggerAwareInterface`.

Create a client with optional handler, default options, and logger:

```text
__construct(
    ?ClientHandlerInterface $handler = null,
    array $options = [],
    ?LoggerInterface $logger = null,
)
```

`createWithBaseUri(string $baseUri, array $options = []): static` creates a
client with a handler configured for that base URI.

## Request methods

- `sendRequest(RequestInterface $request): PsrResponseInterface`
  - Sends a synchronous PSR-18 request.
- `fetch(?string $url = null, ?array $options = []): ResponseInterface
  | PromiseInterface | ClientHandlerInterface`
  - Sends a request, or returns the handler when `$url` is null.
- `get(string $url, ?array $queryParams = null, ?array $options = []):
  ResponseInterface | PromiseInterface`
  - Uses `$queryParams` as the query string.
- `post()`, `put()`, `patch()`, and `delete()` accept a URL, optional body,
  content type, and options. Their second argument is the body.
- `head()` and `options()` accept a URL and request options.
- `stream()` returns an unbuffered response.
- `sse()` returns an `EventSource`.

Fetch-specific fluent configuration is exposed through
`getHandler(): ClientHandlerInterface`. Use `getHttpClient()` to access the
underlying Guzzle client.

Convenience methods can return promises when async mode is enabled.
`sendRequest()` remains synchronous.

See [custom clients](/guide/custom-clients) and
[request options](/guide/making-requests).
