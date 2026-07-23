---
paths:
  - "src/Fetch/Interfaces/**/*.php"
  - "src/Fetch/Enum/**/*.php"
  - "src/Fetch/Http/Client.php"
  - "src/Fetch/Http/Response.php"
  - "src/Fetch/Http/Request.php"
  - "src/Fetch/Exceptions/**/*.php"
  - "src/Fetch/Support/helpers.php"
  - "src/Fetch/Support/RequestOptions.php"
  - "src/Fetch/Support/Defaults.php"
  - "src/Fetch/Support/RetryDefaults.php"
---

# Public API & semver rules

You are editing the public surface. These files are what consumers depend on.

- Prefer additive changes: new **optional** parameters (or an options array),
  new methods/helpers/enum cases. Avoid changing existing signatures.
- Removing/renaming a symbol, changing a signature/return/exception type, or
  changing a **default** (retry, timeout, redirect, cache, option precedence)
  is a breaking change → run `fetch-php-public-api-review`, classify the semver
  impact, and get explicit maintainer approval. Do not ship breaks autonomously.
- Preserve PSR-7 / PSR-18 / PSR-3 contracts.
- Deprecate rather than remove when a graceful path exists.
- Every minor/major change needs tests, docs, and a `CHANGELOG.md` entry.
