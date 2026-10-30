---
layout: home
markdownStyles: false
fetchHome: true
title: HTTP, the PHP way.
description:
  A familiar fetch-style API for modern PHP, built on Guzzle and ReactPHP.
---

::: landing-tabs
=== landing-tab-request

```php
$response = fetch(
    'https://api.example.test/v1/users/42',
);

if ($response->successful()) {
    $user = $response->json();
}
```

===
=== landing-tab-response
<ResponsePreview />
===
:::

::: landing-strip
PHP 8.3+ <span>·</span> Guzzle 7.9+ <span>·</span> Matrix 4
<span>·</span> PSR-7 <span>·</span> PSR-18
:::

::: landing-section

=== landing-section-copy
<p class="landing-overline">01 &nbsp; / &nbsp; A familiar API</p>

## Start with a request. Grow into a client.

Use a helper for a single call, or configure a shared client when your
application needs defaults. The response stays a PSR-7 message, with JSON and
status helpers close at hand.

[Read the quickstart →](/guide/quickstart)
===

=== landing-section-code
```php
$client = fetch_client([
    'base_uri' => 'https://api.example.test/v1/',
    'headers' => ['Accept' => 'application/json'],
    'timeout' => 5,
]);

$response = $client->get('users');
$users = $response->json();
```
===

:::

::: landing-feature-list

<p class="landing-overline">The tools you need, when you need them</p>

- **Retries** — handle transient failures with a configurable policy.
- **HTTP caching** — opt in to RFC 7234 caching on a handler.
- **Middleware** — adapt PSR-7 requests and responses in your own pipeline.
- **Validation** — check JSON Schema or OpenAPI contracts when you need to.

[Explore the guides →](/guide/)
:::

::: landing-async

=== landing-async-copy
<p class="landing-overline">02 &nbsp; / &nbsp; Async with Matrix</p>

## Concurrent work, with an explicit boundary.

Enable async transport on a handler, then compose its promises with Matrix.
Synchronous helpers remain synchronous; the async path is clear at the call
site.

[Understand async requests →](/guide/async-requests)
===

=== landing-async-code
```php
use Fetch\Http\ClientHandler;
use function Matrix\Support\all;
use function Matrix\Support\await;

$handler = ClientHandler::create()->async();
$responses = await(all([
    'users' => $handler->get($usersUrl),
    'posts' => $handler->get($postsUrl),
]));
```
===

:::

::: landing-close

=== landing-close-copy
<p class="landing-overline">Ready when you are</p>

## Make your next request a FetchPHP request.

Start with the quickstart, then follow the guide from a single request to the
parts of the client your application needs.
===

=== landing-close-actions
[Get started →](/guide/quickstart)
[Browse the API →](/api/)
===

:::
