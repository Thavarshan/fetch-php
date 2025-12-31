---
layout: home

# SEO-optimized title and description
title: Fetch PHP - Modern JavaScript-like HTTP Client for PHP
description: A modern HTTP client library that brings JavaScript's fetch API experience to PHP with async/await patterns, promise-based API, and powerful retry mechanics.

# SEO-optimized meta tags (these extend what's in config.js)
head:
  - - meta
    - name: keywords
      content: fetch php, javascript fetch in php, async await php, http client php, promise based http, php http, guzzle alternative
  - - meta
    - property: og:title
      content: Fetch PHP - JavaScript's fetch API for PHP
  - - meta
    - property: og:description
      content: Write HTTP requests just like you would in JavaScript with PHP's Fetch API. Modern, simple HTTP client with a familiar API.
  - - link
    - rel: canonical
      href: https://fetch-php.thavarshan.com/

# Your existing hero section (unchanged)
hero:
  name: Fetch PHP
  text: Write fetch() in PHP
  tagline: The JavaScript fetch API experience, built for PHP 8.3+
  image:
    src: /logo.png
    alt: Fetch PHP
  actions:
    - theme: brand
      text: Get Started
      link: /guide/quickstart
    - theme: alt
      text: View on GitHub
      link: https://github.com/jerome/fetch-php

# Your existing features section (unchanged)
features:
  - title: Familiar API
    details: Write requests in PHP the same way you do in JavaScript with fetch(), async, and await.
    icon: 🚀
  - title: Promise-Based Async
    details: Promise-first API with then(), catch(), finally(), and helpers for concurrency.
    icon: ⚡
  - title: Fluent Interface
    details: Chain config and requests for readable, composable HTTP code.
    icon: 🔗
  - title: Helper Functions
    details: Global helpers like fetch(), get(), post() for quick calls.
    icon: 🧰
  - title: PSR Compatible
    details: Implements PSR-7 (HTTP Messages), PSR-18 (HTTP Client), and PSR-3 (Logging) standards.
    icon: 🔄
  - title: Powerful Responses
    details: Rich response helpers for json(), text(), array(), object(), and status checks.
    icon: 📦
  - title: RFC 7234 Caching
    details: Sync-only cache with ETag/Last-Modified revalidation, stale-while-revalidate, and stale-if-error support.
    icon: 🧠
  - title: Pooling & HTTP/2
    details: Shared connection pooling, DNS cache, and optional HTTP/2 with validation and stats for debugging.
    icon: 🌐
  - title: Debug & Profiling
    details: Unified debug snapshots and optional profiler with timing, memory, and connection stats; configurable log level.
    icon: 🧭
---

<!-- The rest of your existing content starts here, properly formatted with Markdown -->

## The Fetch Experience, Now in PHP

If you can write this in JavaScript:

```js
const response = await fetch('https://api.example.com/users');
const users = await response.json();
```

You can write this in PHP:

```php
use function Matrix\Support\async;
use function Matrix\Support\await;

$response = await(async(fn() => fetch('https://api.example.com/users')));
$users = $response->json();
```

Same mental model. Same simple flow. PHP-native results.

### Promise Chaining That Feels Like JavaScript

```php
use function Matrix\Support\async;

async(fn() => fetch('https://api.example.com/users'))
    ->then(fn ($response) => $response->json())
    ->catch(fn ($error) => $error->getMessage())
    ->finally(fn () => echo 'Request completed.');
```

### Fluent, Readable Requests

```php
$response = fetch_client()
    ->baseUri('https://api.example.com')
    ->withHeaders(['Accept' => 'application/json'])
    ->withToken('your-auth-token')
    ->withQueryParameters(['page' => 1, 'limit' => 10])
    ->get('/users');
```

### Quick Helpers When You Want Less Syntax

```php
$users = get('https://api.example.com/users')->json();
$user = post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com',
])->json();
```

### Async Concurrency, Not Boilerplate

```php
use function Matrix\Support\async;
use function Matrix\Support\await;
use function Matrix\Support\all;

await(async(function() {
    $results = await(all([
        'users' => async(fn() => fetch('https://api.example.com/users')),
        'posts' => async(fn() => fetch('https://api.example.com/posts')),
    ]));

    foreach ($results['users']->json() as $user) {
        echo $user['name'] . "\n";
    }
}));
```

> Note: Async helpers like `async()`, `await()`, `all()`, `race()`, `map()`, and `batch()` come from the Matrix library dependency and are available out of the box.

## Why Fetch PHP?

Fetch PHP brings the simplicity of JavaScript's fetch API to PHP and layers in modern PHP features: retries with exponential backoff, async helpers, fluent configuration, type-safe enums, and first-class response utilities. It feels familiar to full-stack developers and stays powerful enough for real production workloads.

<div class="custom-block tip">
  <p><strong>Key Benefits:</strong></p>
  <ul>
    <li>JavaScript-like syntax that's familiar to full-stack developers</li>
    <li>Promise-based API with .then(), .catch(), and .finally()</li>
    <li>Built on Guzzle for performance with a more elegant API</li>
    <li>Type-safe enums for HTTP methods, content types, and status codes</li>
    <li>Automatic retry mechanics with exponential backoff</li>
    <li>RFC 7234 caching (sync-only) with stale-while-revalidate and stale-if-error</li>
    <li>Shared connection pooling, DNS cache, and optional HTTP/2</li>
    <li>Unified debug/profiler with sanitized logging and configurable log levels</li>
  </ul>
</div>

## Getting Started

```bash
composer require jerome/fetch-php
```

Read the [quick start guide](/guide/quickstart) to begin working with Fetch PHP.

<!-- Add FAQ section for long-tail SEO keywords -->
## Frequently Asked Questions

### How does Fetch PHP compare to Guzzle?

While Guzzle is a powerful HTTP client, Fetch PHP enhances the experience by providing a JavaScript-like API, global client management, simplified requests, enhanced error handling, and modern PHP 8.3+ enums.

### Can I use Fetch PHP with Laravel or Symfony?

Yes! Fetch PHP works seamlessly with all PHP frameworks including Laravel, Symfony, CodeIgniter, and others. It requires PHP 8.3 or higher.

### Does Fetch PHP support retries and caching?

Yes. Fetch PHP includes retry mechanics with exponential backoff, and RFC 7234 caching for sync requests.

### Is Fetch PHP suitable for production use?

Yes. Fetch PHP is built on top of Guzzle, one of the most battle-tested HTTP clients in the PHP ecosystem, while providing a more modern developer experience.

<div class="custom-block warning">
  <p>Having trouble? <a href="https://github.com/jerome/fetch-php/issues">Open an issue</a> on our GitHub repository.</p>
</div>
