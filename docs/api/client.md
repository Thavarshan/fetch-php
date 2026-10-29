---
title: Client
description: PSR-18 client facade and convenience request methods.
---

# `Fetch\Http\Client`

`Client` implements PSR-18 `Psr\Http\Client\ClientInterface` and PSR-3 `LoggerAwareInterface`. Its constructor is `__construct(?ClientHandlerInterface $handler = null, array $options = [], ?LoggerInterface $logger = null)`. `createWithBaseUri(string $baseUri, array $options = []): static` creates a client with a configured handler.

| Method | Signature / behavior |
| --- | --- |
| `sendRequest` | `sendRequest(RequestInterface $request): PsrResponseInterface`; synchronous PSR-18 entry point. |
| `fetch` | `fetch(?string $url = null, ?array $options = []): ResponseInterface|PromiseInterface|ClientHandlerInterface`; without a URL returns the handler. |
| `get` | `get(string $url, ?array $queryParams = null, ?array $options = []): ResponseInterface|PromiseInterface`. |
| `post`, `put`, `patch`, `delete` | `(string $url, mixed $body = null, string|ContentType $contentType = ContentType::JSON, ?array $options = []): ResponseInterface|PromiseInterface`. |
| `head`, `options` | `(string $url, ?array $options = []): ResponseInterface|PromiseInterface`. |
| `stream` | `(string $url, ?array $options = [], string|Method $method = Method::GET): StreamedResponseInterface`. |
| `sse` | Same arguments as `stream`; returns `EventSource`. |

`getHandler(): ClientHandlerInterface` exposes Fetch-specific configuration. `getHttpClient(): GuzzleHttp\ClientInterface` exposes the underlying transport client. The facade delegates configuration for middleware and events. Convenience methods may return promises when async mode is enabled; `sendRequest()` remains synchronous.
