---
title: Runnable Snippets
description: Offline PHP examples checked by the documentation example runner.
---

# Runnable Snippets

These PHP files run offline through `composer docs:examples`. Each one loads the
repository's Composer autoloader and uses a fake or injected Guzzle
`MockHandler` response instead of making a real network request.

- `quickstart.php` — a basic fake request
- `request-options.php` — per-request parameters and a JSON body
- `client-configuration.php` — shared client defaults
- `authentication.php` — bearer token header
- `middleware.php` — request header middleware
- `cache.php` — a cached response using an injected Guzzle handler
- `async.php` and `cancellation.php` — Matrix promise behavior
- `streaming.php` — line-by-line stream consumption

- `validation.php` — optional JSON Schema validation of a fake response


## Homepage examples

The homepage displays regions from these same checked files. Autoloading and
offline mock setup are omitted from the display; no request is sent by the UI.

- `homepage-request.php` — the hero request and its expected JSON response
- `homepage-client.php` — shared client defaults
- `homepage-async.php` — explicit async transport with complete request URLs

The example response comes from `homepage-response.json`, shared between the
PHP fixture and the website data loader. Browse the
[fixture source on GitHub][fixtures].

[fixtures]: https://github.com/Thavarshan/fetch-php/tree/main/docs/examples
