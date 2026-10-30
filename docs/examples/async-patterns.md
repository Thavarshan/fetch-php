---
title: Async Patterns
description:
    Compose Fetch promise-returning requests with Matrix promise helpers.
---

# Async Patterns

Fetch integrates with Matrix promises. An async handler returns request promises
that can progress concurrently through cURL multi on the ReactPHP event loop.

```php
use function Matrix\Support\all;
use function Matrix\Support\await;

$handler = \Fetch\Http\ClientHandler::create()->async();
$results = await(
    all([
        'first' => $handler->get('https://api.example.test/first'),
        'second' => $handler->get('https://api.example.test/second'),
    ]),
);
```

For the package integration with offline fakes, see the fixture below. Review
[async requests](/guide/async-requests) and
[promise operations](/guide/promise-operations) before using timeouts or
concurrency controls around network calls.

<<< @/examples/snippets/async.php
