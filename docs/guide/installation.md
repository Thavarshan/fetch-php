---
title: Installation
description:
    Install Fetch PHP with Composer and check the platform requirements.
---

# Installation

Install the package with Composer:

```sh
composer require jerome/fetch-php
```

Composer autoloads Fetch PHP classes under `Fetch\` and includes the global
helpers from `Fetch\Support\helpers.php`. In a standalone script:

```php
<?php

require __DIR__ . '/vendor/autoload.php';
```

## Requirements

- PHP `^8.3` (PHP 8.3 and later versions below 9.0).
- Composer.
- Runtime dependencies listed in `composer.json`: Guzzle HTTP `^7.9`, Guzzle
  PSR-7 `^2.7`, Matrix `^4.0`, ReactPHP event-loop `^1.5` and promise `^3.2`,
  PSR HTTP Message `1 or `2`, and PSR Log `1, `2, or `3.
- Composer metadata requires `ext-pcntl`. The CI matrix runs Windows with the
  pcntl and posix platform requirements ignored; a default Composer install on
  Windows may report the missing pcntl extension.

The cURL extension is not declared as a package requirement. It is required for
the HTTP/2 cURL configuration path; HTTP/2 also depends on the local cURL build
supporting it. Other optional Guzzle features may require extensions of their
own.

## Frameworks and dependency injection

Fetch PHP is framework-independent. Use the global helper API, instantiate
`Fetch\Http\Client`, or inject the PSR-18 client into your own services. The
`Client` accepts a `ClientHandlerInterface`, options, and an optional PSR-3
logger. A handler can use a custom `GuzzleHttp\ClientInterface` through
`ClientHandler::createWithClient()` or `setHttpClient()`.

## Common installation checks

- Run `composer check-platform-reqs` if Composer reports a missing PHP
  extension.
- Confirm the PHP executable used by Composer matches the one used by the
  application.
- Use the package's declared PHP and dependency constraints when resolving
  conflicts; this is a library and does not commit `composer.lock`.
- For HTTP/2, check that the cURL extension is loaded and advertises HTTP/2
  support.
- If you install on Windows, account for the package's declared `ext-pcntl`
  requirement; CI's platform override is for its test matrix.

Continue with the [quickstart](/guide/quickstart) or see
[custom clients and transports](/guide/custom-clients).
