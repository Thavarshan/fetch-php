---
title: Guides
description:
    Installation, request, response, configuration, and advanced usage guides.
---

# Guides

<Badge type="info" text="PHP 8.3+" />

Use the guides to learn the library from installation through advanced
integrations. The public API is described in the [API reference](/api/).

## Getting started

- [Installation and requirements](/guide/installation)
- [Quickstart](/guide/quickstart)

## Core usage

- [Making requests](/guide/making-requests)
- [Global helper functions](/guide/helper-functions)
- [Working with responses](/guide/working-with-responses)
- [HTTP enums](/guide/working-with-enums)
- [Authentication](/guide/authentication)
- [Error handling](/guide/error-handling)

## Advanced features

- [Async promises and Matrix 4](/guide/async-requests)
- [Promise helpers](/guide/promise-operations)
- [Retries](/guide/retry-handling)
- [HTTP caching](/guide/http-caching)
- [Request and response validation](/guide/validation)
- [Middleware](/guide/middleware)
- [Lifecycle events](/guide/events)
- [Streaming and SSE](/guide/streaming)
- [File uploads](/guide/file-uploads)
- [HTTP/2 and legacy pool APIs](/guide/connection-pooling)
- [Logging](/guide/logging)
- [Debugging and profiling](/guide/debugging-and-profiling)
- [Custom clients and transports](/guide/custom-clients)
- [Testing with fakes](/guide/testing)
- [Architecture](/guide/architecture)
- [Troubleshooting](/guide/troubleshooting)
- [Upgrading and compatibility](/guide/upgrading)

## Limitations

- Async I/O requires cURL multi or an explicitly injected loop-compatible
  transport. A synchronous request wrapped in Matrix `async()` still blocks.
- Streaming is synchronous, consumes the underlying stream, and bypasses the
  cache and middleware pipeline.
- Validation requires optional adapters; OpenAPI support is limited to 3.0.x.
