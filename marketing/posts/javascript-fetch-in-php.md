# JavaScript's fetch() experience, brought to PHP (with important differences)

*Publication draft — verify the current Packagist release and run all examples
before publication. Suggested audience: DEV Community / personal technical
blog. Approximate reading time: 4 minutes.*

JavaScript developers use `fetch()` every day. PHP developers have excellent
HTTP clients too — Guzzle, Symfony HttpClient, and Laravel's HTTP client are
established options. But switching between JavaScript and PHP often means
switching request styles.

I built [Fetch PHP](https://github.com/Thavarshan/fetch-php) , an MIT-licensed,
Guzzle-powered client with a JavaScript-inspired global `fetch()` helper. The
goal is a familiar first request without giving up PHP-native interfaces.

## Start with the familiar shape

In JavaScript:

```js
const response = await fetch('https://jsonplaceholder.typicode.com/users/1');
if (!response.ok) throw new Error('HTTP request failed');
const user = await response.json();
console.log(user.name);
```

In PHP (synchronous by default):

```php
<?php

require __DIR__.'/vendor/autoload.php';

$response = fetch('https://jsonplaceholder.typicode.com/users/1');

if (!$response->successful()) {
    throw new RuntimeException('HTTP request failed: '.$response->status());
}

$user = $response->json();
echo $user['name'], PHP_EOL;
```

Install from Packagist **after checking which release Composer currently
resolves**:

```sh
composer require jerome/fetch-php
```

These examples contact a public demo service and require internet access. In
production, validate returned data and constrain untrusted outgoing URLs.

## What is familiar — and what is different?

Fetch PHP takes inspiration from browser Fetch; it is not a JavaScript runtime
or polyfill.

- `fetch($url, $options)` offers a concise request entry point.
- PHP responses have `json()`, `text()`, `status()` and `successful()` helpers.
- 4xx/5xx HTTP responses do not automatically throw exceptions; handle
  unsuccessful status explicitly.
- Requests use PHP and Guzzle option conventions, **not** the browser's Fetch
  security model.
- The normal `fetch()` path is synchronous. Genuine nonblocking operations
  require the documented async transport and event loop configuration; wrapping
  a blocking request in a promise does not make it nonblocking.

That honesty matters more than a superficially identical function name.

## Keep real HTTP work testable

An HTTP example is more useful when it can be exercised without a network or
credentials. Fetch PHP includes a built-in test fake:

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;

MockServer::fake([
    'GET https://api.example.test/v1/users/42' => MockResponse::json([
        'id' => 42,
        'name' => 'Ada Lovelace',
    ]),
]);
MockServer::preventStrayRequests();

$response = fetch('https://api.example.test/v1/users/42');
echo $response->json()['name'], PHP_EOL;
```

This example is adapted from the repository's existing offline README
quickstart. Tests should also verify error statuses and unexpected requests,
not just the happy path.

## When should you use it?

Fetch PHP may appeal when you want Fetch-style ergonomics in a plain PHP
service, a small integration script or an application that already understands
PSR interfaces. You can also access an object-oriented client and the
underlying Guzzle ecosystem.

**When not to choose it:** if you're already deeply invested in Laravel's HTTP
facade, Symfony HttpClient, Guzzle directly, or a structured API SDK such as
Saloon, switching libraries solely for a different request spelling may not be
worth it. Check package requirements: currently PHP 8.3+ with a declared
`ext-pcntl` requirement that affects some environments, especially Windows.

## I'd value a technical review

The [Fetch PHP source](https://github.com/Thavarshan/fetch-php) and
[documentation](https://fetch-php.thavarshan.com/) are public. If you work with
HTTP in PHP, which part of the API would you simplify, and which integration
would you want an executable example for?

*Disclosure: I maintain Fetch PHP.*
