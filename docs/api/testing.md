---
title: Testing Utilities
description: Fake HTTP responses and inspect requests without real network access.
---

# Testing Utilities

`MockServer` provides static `fake()`, `preventStrayRequests()`, `record()`, `recorded()`, `assertSent()`, and request-count helpers. Call `fake()` before `preventStrayRequests()` because `fake()` resets mock state. Fakes are evaluated before actual HTTP execution on the normal buffered path.

`MockResponse` builds canned responses; `MockResponseSequence` provides successive responses; `Recorder` stores interactions. For transport features that bypass `MockServer`—including streaming and the cache pipeline—inject a Guzzle `MockHandler` into `ClientHandler::createWithClient()`.

Tests must not make real network requests. Reset shared global client and mock state between tests. See the [testing guide](/guide/testing).
