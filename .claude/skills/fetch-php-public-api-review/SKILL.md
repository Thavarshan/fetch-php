---
name: fetch-php-public-api-review
description: Assess the semantic-versioning and backward-compatibility impact of a Fetch PHP change — public classes/interfaces/method signatures, global helpers, enum cases, exception behavior, PSR contracts, and default values. Use before changing any public surface or default, and to classify a release as patch/minor/major.
---

# Fetch PHP public-API & semver review

Read-only analysis. Output a BC classification (**patch / minor / major**) with
the exact `file:line` for each affected symbol and the migration cost.

## When to use

Before changing anything in the public surface, or when deciding the next
version. Public surface = everything a consumer can depend on:

- Public/protected members of non-`@internal` classes and all `Interfaces/`.
- Global helpers in `src/Fetch/Support/helpers.php` (`fetch`, `fetch_client`,
  `get`/`post`/`put`/`patch`/`delete`, `fetch_stream`, `fetch_sse`, async
  bridges).
- `Enum/` cases and their helper methods (`Method`, `ContentType`, `Status`).
- Exception types and their externally visible behavior (`ClientException`,
  `NetworkException`, `RequestException`, `HttpException`).
- Response/stream/SSE semantics and PSR-7/PSR-18/PSR-3 conformance.
- **Defaults**: retries (`RetryDefaults`), timeouts (`Defaults`), redirects,
  caching, option precedence in `RequestOptions`.
- Supported PHP versions and Composer constraints.

## Classification rules

- **MAJOR (breaking)** — remove/rename a public symbol; change a method
  signature incompatibly (params, types, return); remove/rename an enum case;
  change thrown exception type; change a default that alters observed behavior
  (e.g. retry count, timeout, redirect following, cache on/off); raise the
  minimum PHP version or tighten a dependency constraint; break a PSR contract.
- **MINOR** — add a new public method/class/helper/enum case; add an optional
  parameter with a safe default; widen accepted input; add a new opt-in option.
- **PATCH** — internal-only fix with no observable public change.

Guzzle's parameter-addition rule applies: prefer adding new **optional**
parameters (or an options array) over changing existing signatures.

## Workflow

1. Diff the public surface: `git diff main...HEAD -- src/Fetch`.
2. For each changed public symbol, decide added / changed / removed and map to
   the rule above. Signature/enum/exception/default changes are the usual traps.
3. If any change is MAJOR, **stop** — require explicit maintainer approval and a
   documented migration path; do not ship it autonomously.
4. Produce: classification, affected symbols (`file:line`), consumer impact,
   required migration notes, and whether tests/docs/CHANGELOG cover it.

## Verification

- Every affected public symbol is listed with its BC verdict.
- Deprecations (not removals) are used where a graceful path exists.
- CHANGELOG + docs describe any minor/major change; a migration note exists for
  major changes.

## Red flags — stop

- A signature/default/enum/exception change slipped in as a "fix" with no BC note.
- Minimum PHP or a dependency constraint changed without justification.
- A PSR-7/18/3 method's contract weakened.
