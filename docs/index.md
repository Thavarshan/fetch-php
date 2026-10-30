---
layout: home
markdownStyles: false
title: HTTP requests, the PHP way.
description: A Guzzle-based HTTP client with a familiar fetch API for PHP 8.3+.
hero:
  text: HTTP requests, the PHP way.
  tagline: >-
    A familiar fetch-style API, built on Guzzle and designed for modern PHP.
  actions:
    - theme: brand
      text: Get started
      link: /guide/quickstart
    - theme: alt
      text: GitHub
      link: https://github.com/Thavarshan/fetch-php
---

## A familiar request API

Make a request with the global helper, then work with a typed response. Requests
are synchronous by default; async execution is available when you need it.

```php
$response = fetch('https://api.example.test/users');

if ($response->successful()) {
    $users = $response->json();
}
```

## Install with Composer

```sh
composer require jerome/fetch-php
```

Use verb helpers or a PSR-18 client, configure requests fluently, and add
retries, RFC 7234 caching, middleware, or opt-in schema validation as your
application needs them. Buffered async requests use Matrix with the ReactPHP
event loop and cURL multi transport.

Start with the [quickstart](/guide/quickstart), browse the [guides](/guide/),
or go directly to the [API reference](/api/). Read the
[changelog](https://github.com/Thavarshan/fetch-php/blob/main/CHANGELOG.md)
for release notes.
