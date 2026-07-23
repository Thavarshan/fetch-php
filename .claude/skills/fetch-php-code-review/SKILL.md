---
name: fetch-php-code-review
description: Review a Fetch PHP change for correctness, regression risk, public-API/PSR compatibility, static-analysis correctness, test quality, and documentation impact. Use when reviewing a diff, branch, or PR touching src/Fetch, or before opening a PR.
---

# Fetch PHP code review

General-purpose review lens. For deep security, performance, or API/semver
review, delegate to `fetch-php-http-security-review`,
`fetch-php-performance-review`, or `fetch-php-public-api-review`.

## When to use

Reviewing a working diff, a branch, or a PR under `src/Fetch/` or `tests/`.

## Scope the diff first

```bash
git diff --stat main...HEAD
git diff main...HEAD
```

Identify which subsystems changed (Http, Cache, Pool, Events, Middleware,
async) and pull the matching specialist skill for those.

## Review checklist

1. **Correctness** — logic matches intended behavior; edge cases (empty/invalid
   input, async vs sync, streaming, cache hit/miss, redirect, error) handled.
   Option precedence goes through `RequestOptions`/`RequestContext`, not ad hoc.
2. **Regression risk** — does it change a shared trait, default, or the
   `ClientHandler` pipeline in a way that affects unrelated call paths?
3. **Public API / PSR compat** — any change to a public class, interface,
   signature, helper, enum case, exception, or default triggers
   `fetch-php-public-api-review`. Confirm PSR-7/PSR-18/PSR-3 contracts still hold.
4. **Static analysis** — PHPStan level 6 clean; no new `ignoreErrors` masking a
   real type issue; `declare(strict_types=1)` present in new files.
5. **Test quality** — new behavior covered; bug fixes have a regression test;
   tests are offline, deterministic, and `snake_case`; failure/cancellation
   paths exercised, not just the happy path.
6. **Security & secrets** — no credentials/tokens in code, tests, fixtures, or
   logs; auth/cookie values stay redacted (see `fetch-php-http-security-review`).
7. **Docs & changelog** — behavior/API changes reflected in `docs/`, `README`,
   and `CHANGELOG.md`; examples match the real API.
8. **Scope hygiene** — no unrelated dependency bumps, reformatting, or config
   churn; the diff is minimal and reviewable.

## Verification

- Re-run the gate the author should have run: `NO_NETWORK=1 composer check`.
- Every claim in the review points to a `file:line`.

## Red flags

- Green tests achieved by weakening assertions or adding PHPStan ignores.
- A silent behavior/default change with no test, doc, or changelog note.
- New global mutable state or singletons without reset hooks (test isolation).
- Broad refactors bundled with a behavior change — ask to split.
