---
title: Async Patterns
description: Compose Fetch promise-returning requests with Matrix promise helpers.
---

# Async Patterns

Fetch integrates with Matrix promises and exports Matrix helper functions where available. This lets applications compose promise values, but Fetch currently performs synchronous Guzzle HTTP I/O inside the async callback. Multiple Fetch requests therefore do not provide concurrent nonblocking network I/O.

```php
use function Matrix\Support\all;
use function Matrix\Support\async;
use function Matrix\Support\await;

$results = await(all([
    'first' => async(fn () => loadFirstValue()),
    'second' => async(fn () => loadSecondValue()),
]));
```

For the package integration with offline fakes, see the fixture below. Review [async requests](/guide/async-requests) and [promise operations](/guide/promise-operations) before using timeouts or concurrency controls around network calls.

<<< @/examples/snippets/async.php
