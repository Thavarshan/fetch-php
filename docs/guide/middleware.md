---
title: Middleware
description: Register PSR-7 request middleware on a Fetch handler.
---

# Middleware

`Client` and `ClientHandler` accept middleware implementing
`Fetch\Interfaces\Middleware` or a callable. A callable receives the outgoing
PSR-7 request and a `$next` function. Middleware may modify the request and pass
it onward.

```php
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;

$handler = fetch_client()->getHandler();
$handler->addMiddleware(static function (
    RequestInterface $request,
    callable $next,
): ResponseInterface|PromiseInterface {
    return $next($request->withHeader('X-Client-Name', 'example'));
});
```

Use a class implementing `Middleware` when behavior needs dependencies or reuse.
Middleware registered on the shared helper client persists until it is replaced
or reset.

The pipeline applies to the regular buffered request path. Streaming requests
use a separate Guzzle path and bypass this middleware pipeline. A middleware may
see a promise in async mode, so do not treat it as a PSR response until it
resolves.

<<< @/examples/snippets/middleware.php
