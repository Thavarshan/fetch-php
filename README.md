# Fetch PHP

Fetch PHP is a Guzzle-based HTTP client for PHP with a small global `fetch()`
helper, an object-oriented client, PSR-7 and PSR-18 support, streaming,
middleware, retries, and optional caching.

The API takes inspiration from JavaScript Fetch. It uses PHP values and Guzzle
request options, and it does not implement the browser Fetch API or its security
model.

## Requirements and installation

- PHP 8.3 or newer
- Composer
- Runtime packages: Guzzle 7.9+, PSR-7 1.x/2.x, PSR-3 1.x/2.x/3.x, ReactPHP
  event-loop and promise packages, and `jerome/matrix ^4.0`
- The package metadata requires `ext-pcntl`. CI ignores that platform
  requirement on Windows.

```sh
composer require jerome/fetch-php
```

Composer autoloads the global helpers. In a standalone script, load
`vendor/autoload.php`.

## Quick start

This complete example uses the built-in fake server, so it runs without network
access. Save it as a PHP file in the project root after installing the package.

<!-- docs-example: quickstart -->

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Fetch\Interfaces\Response as FetchResponse;
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

if (!($response instanceof FetchResponse)) {
    throw new RuntimeException('Expected a synchronous HTTP response.');
}

$user = $response->json();
echo $user['name'], PHP_EOL;
```

<!-- /docs-example: quickstart -->

Replace the fake with your service URL and remove the mock setup to make a real
request. Keep URLs derived from untrusted input subject to your application's
SSRF controls.

## Fetch-style and PHP-style entry points

`fetch($url, $options)` makes one request. Passing no URL to `fetch()` returns
the `ClientHandler` for fluent configuration. `fetch_client()` returns the
shared `Fetch\Http\Client` facade; call `getHandler()` when you need
handler-only configuration.

```php
$response = get('https://api.example.test/users', ['page' => 2]);
$created = post('https://api.example.test/users', ['name' => 'Grace']);
$updated = put('https://api.example.test/users/42', ['name' => 'Grace Hopper']);
$changed = patch('https://api.example.test/users/42', ['active' => false]);
$deleted = delete('https://api.example.test/users/42');
```

The `get()` helper treats its second argument as query parameters. Array data
passed to `post()`, `put()`, `patch()`, or `delete()` is JSON by default. For
form or multipart data, pass `form` or `multipart` in `fetch()` options. See
[making requests](https://fetch-php.thavarshan.com/guide/making-requests) for
body, header, timeout, redirect, TLS, cookie, and proxy options.

| PHP entry point            | Purpose                                    |
| -------------------------- | ------------------------------------------ |
| `fetch($url, $options)`    | One request using an options array         |
| `fetch_client()`           | Shared object-oriented `Client` facade     |
| `Fetch\Http\Client`        | PSR-18 client plus convenience methods     |
| `Fetch\Http\ClientHandler` | Fluent request configuration and execution |

## Responses

Responses are PSR-7 responses extended by `Fetch\Http\Response`. Use `status()`,
`isStatus()`, `successful()`, and `failed()` for status checks; a 4xx or 5xx
response is not automatically an exception. Parse JSON with `json()`, read plain
text with `text()`, or inspect headers with `header()`. See the
[response guide](https://fetch-php.thavarshan.com/guide/working-with-responses).

## Async and Matrix

Fetch PHP integrates with Matrix 4 and ReactPHP promises. The API can return
promises and Matrix supplies helpers such as `async()`, `await()`, and `all()`.
In this release, Fetch wraps synchronous Guzzle request execution in a promise:
network I/O blocks the event loop, so these APIs do not make HTTP calls
nonblocking or run them concurrently. Do not call blocking `await()` while the
Matrix event loop is already running. See the
[async guide](https://fetch-php.thavarshan.com/guide/async-requests) for these
limits, cancellation, and timeout behavior.

## More features

The documentation site covers
[retries](https://fetch-php.thavarshan.com/guide/retry-handling),
[HTTP caching](https://fetch-php.thavarshan.com/guide/http-caching),
[middleware](https://fetch-php.thavarshan.com/guide/middleware),
[lifecycle events](https://fetch-php.thavarshan.com/guide/events),
[streaming and SSE](https://fetch-php.thavarshan.com/guide/streaming),
[HTTP/2 configuration and pool limits][pool],
[logging and profiling](https://fetch-php.thavarshan.com/guide/logging), and
[offline testing](https://fetch-php.thavarshan.com/guide/testing).

The package implements PSR-7 HTTP messages, PSR-18 HTTP client behavior, and
PSR-3 logger awareness. It does not include JSON Schema or OpenAPI contract
validation.

[pool]: https://fetch-php.thavarshan.com/guide/connection-pooling

## Development

Install dependencies and run the repository checks:

```sh
composer install
composer check
composer docs:examples
npm ci
npm run build
npm run check-links
```

`composer check` runs PHPStan, Duster, and PHPUnit. Documentation PHP examples
are syntax-checked and the runnable fixtures execute against local fakes. See
[CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidance and
[the documentation site](https://fetch-php.thavarshan.com/) for the full guide
and API reference.

## Support

Report bugs and request features on
[GitHub Issues](https://github.com/Thavarshan/fetch-php/issues). For security
reports, follow the private contact instructions in [SECURITY.md](SECURITY.md).

## License

Fetch PHP is available under the [MIT License](LICENSE).
