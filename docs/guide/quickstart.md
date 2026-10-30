---
title: Quickstart
description: Install FetchPHP and make your first application request.
---

# Quickstart

## Install FetchPHP

From your application's root directory, install the package with Composer:

```sh
composer require jerome/fetch-php
```

## Make a request

Save this as `example.php` beside your `composer.json`, then run
`php example.php`. It sends a GET request to the public JSONPlaceholder API;
replace the URL with your own API endpoint when you are ready.

<<< @/examples/app/quickstart.php

Requests are synchronous by default. HTTP status codes such as 404 remain
responses: use `successful()` or `isStatus()` before handling the body. A
transport failure raises a `ClientException`; the example handles that
separately from HTTP errors.

## Run the offline demonstration

The repository's test fixture below uses `MockServer`, prevents stray requests,
and checks the result. Its autoloader path is specific to this repository.
This is validation scaffolding for contributors, rather than application setup.

::: details Complete offline fixture
<<< @/examples/snippets/quickstart.php
:::

The documentation runner also executes the exact application example above in
a temporary Composer-style directory with a fake response. It never calls the
public API during validation.

Next:
[make requests with helpers, clients, and handlers](/guide/making-requests).
