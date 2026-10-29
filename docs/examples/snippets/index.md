---
title: Runnable Snippets
description: Offline PHP examples checked by the documentation example runner.
---

# Runnable Snippets

These PHP files run offline through `composer docs:examples`. Each one loads the repository's Composer autoloader and uses a fake or injected Guzzle `MockHandler` response instead of making a real network request.

- `quickstart.php` — a basic fake request
- `request-options.php` — per-request parameters and a JSON body
- `client-configuration.php` — shared client defaults
- `authentication.php` — bearer token header
- `middleware.php` — request header middleware
- `cache.php` — a cached response using an injected Guzzle handler
- `async.php` and `cancellation.php` — Matrix promise behavior
- `streaming.php` — line-by-line stream consumption
