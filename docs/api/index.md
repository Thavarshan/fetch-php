---
title: API Reference
description: Public helper functions, clients, messages, enums, and testing utilities.
---

# API Reference

This reference documents the public surface currently implemented in `src/Fetch`. For a conceptual introduction, start with the [guide](/guide/).

## Functions

- [`fetch()`](/api/fetch), [`fetch_client()`](/api/fetch-client)
- [HTTP helpers](/api/http-method-helpers): `get`, `post`, `put`, `patch`, `delete`
- Streaming helpers: `fetch_stream`, `fetch_sse` (see [streams and events](/api/streams))

## Classes and contracts

- [`Client`](/api/client) and [`ClientHandler`](/api/client-handler)
- [`Request`](/api/request) and [`Response`](/api/response)
- [Streams and SSE](/api/streams), [middleware and events](/api/middleware-events), [exceptions](/api/exceptions)
- [Testing utilities](/api/testing)

## Enums

- [`Method`](/api/method-enum)
- [`ContentType`](/api/content-type-enum)
- [`Status`](/api/status-enum)

Return types of convenience methods depend on whether asynchronous mode is enabled. PSR-18 `Client::sendRequest()` remains synchronous.
