---
title: Upgrading and Compatibility
description:
    Check PHP and dependency constraints and review release changes before
    upgrading.
---

# Upgrading and Compatibility

Fetch PHP uses semantic versioning. Check the
[CHANGELOG](https://github.com/Thavarshan/fetch-php/blob/main/CHANGELOG.md) and
compare the documented package constraints before updating an application.

The current Composer constraints require PHP `^8.3` and `jerome/matrix ^4.0`. A
project still constrained to Matrix 3 cannot install this package version until
its own dependency constraints are updated. Guzzle, PSR-7, PSR-3, and ReactPHP
constraints are listed in `composer.json` and summarized in
[Installation](/guide/installation).

For an application, run Composer's dependency solver and its own test suite
after updating:

```sh
composer update jerome/fetch-php --with-dependencies
```

Resolve incompatible package constraints in the consuming application rather
than editing the vendor package. Fetch PHP is a library and does not commit a
lock file; applications should commit their own `composer.lock`.

Promise cancellation follows Matrix 4 behavior: cancelled operations reject with
`Matrix\Exceptions\CancellationException`. Fetch's async HTTP request currently
performs blocking Guzzle I/O, so cancellation cannot interrupt a request already
in flight. See [async requests](/guide/async-requests).
