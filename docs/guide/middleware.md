---
title: Middleware & Interceptors
description: Wrap the request/response lifecycle with a PSR-7 middleware pipeline for auth, logging, versioning, and other cross-cutting concerns.
---

# Middleware & Interceptors

Middleware let you wrap the request/response lifecycle without touching every call site — the PHP equivalent of JavaScript fetch interceptors or Laravel's HTTP middleware. Each middleware receives the outgoing PSR-7 request and a `$next` callable representing the rest of the pipeline, so it can:

- modify the request before calling `$next($request)`;
- short-circuit by returning a response without calling `$next`;
- inspect or modify the response that comes back;
- catch and handle errors thrown further down the pipeline.

Middleware sit **outside** the built-in mocking, caching, and retry logic, so they run first and can short-circuit before any of that work happens.

## Writing middleware

Implement `Fetch\Interfaces\Middleware`:

```php
use Fetch\Interfaces\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;

final class AuthMiddleware implements Middleware
{
    public function __construct(private TokenManager $tokens) {}

    public function handle(RequestInterface $request, callable $next): ResponseInterface|PromiseInterface
    {
        $request = $request->withHeader('Authorization', 'Bearer ' . $this->tokens->get());

        return $next($request);
    }
}
```

Trivial middleware can be plain callables with the same signature — no class required:

```php
$client->addMiddleware(fn ($request, $next) => $next($request->withHeader('X-Trace-ID', uniqid())));
```

## Registering middleware

```php
use Fetch\Middleware\AddHeadersMiddleware;
use Fetch\Middleware\LoggingMiddleware;

fetch_client()
    ->addMiddleware(new AuthMiddleware($tokens))
    ->addMiddleware(new LoggingMiddleware($logger))
    ->get('https://api.example.com/users');
```

Replace the whole stack with `middleware()`, and remove middleware with `withoutMiddleware()`:

```php
$client->middleware([
    new AuthMiddleware($tokens),
    [new LoggingMiddleware($logger), 100], // [middleware, priority]
]);

$client->withoutMiddleware(LoggingMiddleware::class); // remove by class
$client->withoutMiddleware();                          // clear all
```

Middleware registered on the global client (`fetch_client()`) also apply to the `fetch()`, `get()`, `post()`, … helpers, since they share the same handler.

## Ordering with priority

Higher priority runs first (outermost). Middleware with equal priority run in the order they were added.

```php
$client
    ->addMiddleware($rateLimiter, priority: 100) // runs first
    ->addMiddleware($auth, priority: 50)
    ->addMiddleware($logger);                    // priority 0, runs last
```

## Conditional registration

`when()` and `unless()` apply configuration only when a condition holds. The condition may be a boolean or a callable resolved with the handler:

```php
$client
    ->when($isProduction, fn ($c) => $c->addMiddleware(new SecurityMiddleware()))
    ->unless($isLocal, fn ($c) => $c->addMiddleware(new MetricsMiddleware()));
```

## Short-circuiting

Return a response without calling `$next` to skip the actual request — useful for caching layers, circuit breakers, or canned responses:

```php
$client->addMiddleware(function ($request, $next) use ($cache) {
    if ($hit = $cache->get((string) $request->getUri())) {
        return $hit; // any PSR-7 response; wrapped into a Fetch response automatically
    }

    return $next($request);
});
```

## Modifying the response

```php
$client->addMiddleware(function ($request, $next) {
    $response = $next($request);

    return $response->withHeader('X-Processed-By', 'fetch-php');
});
```

## Async middleware

In asynchronous mode (`->async()`), `$next($request)` returns a `React\Promise\PromiseInterface` rather than a response. Middleware that need the response must chain off the promise instead of inspecting it directly:

```php
final class AsyncCacheMiddleware implements Middleware
{
    public function handle(RequestInterface $request, callable $next): ResponseInterface|PromiseInterface
    {
        return $next($request)->then(function ($response) {
            // inspect/annotate the resolved response
            return $response;
        });
    }
}
```

The built-in `LoggingMiddleware` handles both modes transparently.

## Built-in middleware

| Middleware | Purpose |
| ---------- | ------- |
| `Fetch\Middleware\AddHeadersMiddleware` | Add or override a fixed set of headers (API versioning, tenant IDs, …). Pass `overwrite: false` to only fill in absent headers. |
| `Fetch\Middleware\LoggingMiddleware` | Log the request/response lifecycle to a PSR-3 logger with a per-request correlation ID; works synchronously and asynchronously. |
