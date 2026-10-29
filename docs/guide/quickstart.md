---
title: Quickstart
description: Install Fetch PHP and run an offline request example.
---

# Quickstart

Install the package and make sure Composer's autoloader is loaded:

```sh
composer require jerome/fetch-php
```

The example uses `MockServer`, so it executes offline. The included file's autoloader path is relative to this repository; in an application, load your Composer autoloader in the normal way.

<<< @/examples/snippets/quickstart.php

A request returns a `Fetch\Http\Response` synchronously by default. HTTP status codes such as 404 remain responses; inspect `isStatus()` or `successful()` before handling the body.

Next: [make requests with helpers, clients, and handlers](/guide/making-requests).
