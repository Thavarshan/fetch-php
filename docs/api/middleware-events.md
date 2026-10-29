---
title: Middleware and Events
description: Middleware contracts and lifecycle event registration.
---

# Middleware and Events

Middleware implements `Fetch\Interfaces\Middleware::handle(RequestInterface $request, callable $next): ResponseInterface|PromiseInterface`; callables with that shape are accepted too. Add with `addMiddleware()` and remove/replace through the handler's middleware methods. Middleware participates in the buffered request pipeline and may receive a promise when async mode is active.

Event-aware clients and handlers provide `on()` and convenience registrations for request, response, error, retry, timeout, and redirect events. Event classes live in `Fetch\Events`. Middleware can change execution; events are lifecycle notifications. Exact signatures are declared in `Fetch\Interfaces\MiddlewareAware` and `EventAware`.
