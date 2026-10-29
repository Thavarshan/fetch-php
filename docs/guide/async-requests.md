---
title: Async Requests and Matrix
description:
    Understand the promise API, ReactPHP loop ownership, and the synchronous
    transport limitation.
---

# Async requests and Matrix 4

Fetch PHP requires `jerome/matrix ^4.0` and returns ReactPHP `PromiseInterface`
values when async mode is requested. Matrix 4 uses the ReactPHP event loop and
supplies the coroutine and promise helpers.

## The transport limitation

The current Fetch implementation schedules the request through Matrix, then
performs the Guzzle request synchronously inside the scheduled callback.
Returning a promise does not make the HTTP I/O nonblocking: a slow Guzzle
request blocks the event loop, and several Fetch requests wrapped in Matrix
helpers do not make network progress concurrently. See
[issue #129](https://github.com/Thavarshan/fetch-php/issues/129) for nonblocking
transport work.

Use these APIs when you need a promise-shaped interface around Fetch's
synchronous request pipeline. Do not use them for high-throughput concurrent
HTTP work or inside a server loop where blocking is unacceptable.

## Create and await a request promise

The following offline example starts two promise tasks and gathers their
responses. It verifies API behavior, not concurrent network execution.

<<< @/examples/snippets/async.php

`Matrix\Support\await($promise, $timeout)` drives Matrix's loop and returns the
fulfillment value. The optional timeout is in seconds. Matrix throws if
`await()` is called while its event loop is already running. In an application
that owns a running ReactPHP loop, compose the promise with `then()` /
`otherwise()` instead of calling `await()`.

## Promise resolution and failures

An HTTP error status such as 404 is a fulfilled response; check `successful()`
or `isStatus()`. A connection failure, invalid request, or other transport error
rejects the promise. Fetch adds request context and wraps asynchronous failures
in Matrix's `AsyncException`; inspect the exception chain for the originating
Fetch or Guzzle exception.

`all()` rejects when an input promise rejects. `race()` settles with the first
fulfilled or rejected promise. `any()` fulfills with the first successful
promise and rejects if all inputs reject. See
[promise operations](/guide/promise-operations) for mapping, batching, and
retries.

## Cancellation

Matrix can cancel queued promise work and its own timers. Fetch does not have a
separate cancellation-token API. A Fetch HTTP call that has started inside
Guzzle is synchronous, so cancelling its promise cannot interrupt the in-flight
socket operation. Cancellation before the scheduled callback runs can prevent
that callback from starting:

<<< @/examples/snippets/cancellation.php

A cancelled Matrix operation rejects with
`Matrix\Exceptions\CancellationException`. Catch that exception when
cancellation is part of the calling code.

## Timeouts

Fetch's Guzzle `timeout` and `connect_timeout` options are seconds and control
the synchronous HTTP operation. Matrix's `await($promise, $timeout)` also
accepts seconds, but its timer cannot fire while a synchronous Fetch request
blocks the same loop. Use the Guzzle timeout options to bound the HTTP request
itself.

## Matrix loop, events, and metrics

Matrix uses the shared ReactPHP loop returned by `Matrix\Support\getLoop()`.
Matrix also exposes promise lifecycle listeners and metrics through
`Matrix\Support\listen()` and `Matrix\Support\getMetrics()`. These are
Matrix-level signals; they are separate from Fetch's request lifecycle callbacks
documented in [Events and hooks](/guide/events).

## Retry mechanisms are separate

Fetch's `retry()` is configured in milliseconds and retries an HTTP request
according to status and exception policies. Matrix's `Matrix\Support\retry()`
counts total operation attempts and its backoff callback returns seconds.
Fetch's global `retry()` wrapper accepts milliseconds. Read
[retry handling](/guide/retry-handling) before combining them.
