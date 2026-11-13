# Testing PHP HTTP requests without relying on a live API

*Publication draft — verify Composer version and run the snippet before
publishing. Audience: PHP developers using PHPUnit or Pest.*

Tests that call a live REST API can fail because of unrelated network errors,
rate limits or service changes. Most HTTP client tests should instead exercise
the request logic with deterministic responses.

Fetch PHP has a built-in `MockServer` and `MockResponse` API, so a developer
can keep an HTTP integration example self-contained.

## A complete, offline quickstart

Install the compatible released package first:

```sh
composer require jerome/fetch-php
```

Put this in `example.php`:

```php
<?php

declare(strict_types=1);

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

if (!$response->successful()) {
    throw new RuntimeException('Unexpected HTTP status: '.$response->status());
}

assert($response->json()['name'] === 'Ada Lovelace');
echo $response->json()['name'], PHP_EOL;
```

Then run `php example.php`. The fake handles the request locally; it does not
call `api.example.test`.

This is based on the tested example in the Fetch PHP README. In a test suite,
also verify unexpected requests are rejected, and reset shared mocks between
tests according to your suite's isolation rules. `assert()` can be disabled in
PHP runtime settings, so for production test suites use explicit PHPUnit/Pest
assertions.

## Why strict fakes help

A permissive fake may disguise accidental network access. Rejecting stray
requests makes missing expectations visible. Useful cases include:

1. Successful JSON responses.
2. Client/server HTTP statuses; Fetch PHP does not throw automatically for
   4xx/5xx.
3. Retry behavior using deterministic simulated failures.
4. Malformed response bodies and missing fields.

## The tradeoff

Mocking the client's boundary makes application tests faster and more
deterministic. It does **not** prove the real external API still behaves the
way your fixture assumes. Keep a small number of separate integration or
contract checks where appropriate.

See the [testing guide](https://fetch-php.thavarshan.com/guide/testing) and
[source repository](https://github.com/Thavarshan/fetch-php). What HTTP
integration test has been the hardest for you to keep reliable?

*Disclosure: I maintain Fetch PHP.*
