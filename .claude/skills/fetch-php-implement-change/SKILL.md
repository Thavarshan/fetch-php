---
name: fetch-php-implement-change
description: Implement a feature or bug fix in Fetch PHP safely and incrementally — locate related code, define expected behavior, change the smallest surface, add PHPUnit tests, update docs, and run the local gates. Use when asked to add functionality, change behavior, or fix a defect in src/Fetch.
---

# Implement a change in Fetch PHP

Follow [AGENTS.md](../../../AGENTS.md) for commands, conventions, and boundaries.
Use [CODE_MAP.md](../../../CODE_MAP.md) to find the real public surface.

## When to use

Any code change under `src/Fetch/` — new option, new helper, new middleware/event,
bug fix, refactor. For pure test work use `fetch-php-test-development`; for
diagnosing a failing test/CI use `fetch-php-debug-failure`.

## Workflow

1. **Locate the seam.** Grep `CODE_MAP.md` and `src/Fetch/` for the feature.
   Most request behavior flows through `ClientHandler` + its `Concerns/` traits
   and `Support/RequestOptions` / `RequestContext`. Identify which trait or class
   owns the behavior before touching anything.
2. **Define expected behavior** in one or two sentences, including error and edge
   cases (empty/invalid input, async vs sync, streaming, cache/no-cache).
3. **Check public-API impact.** If the change touches a public class, interface,
   method signature, helper in `helpers.php`, enum case, exception type, or a
   default (retry/timeout/redirect/cache), run `fetch-php-public-api-review`
   first and get maintainer sign-off before a breaking change.
4. **Implement the smallest change** that satisfies the behavior. New files get
   `declare(strict_types=1);` and match the surrounding style. Reuse existing
   option normalization (`RequestOptions`) and defaults (`Defaults`,
   `RetryDefaults`) rather than re-deriving them.
5. **Add/extend tests** (`fetch-php-test-development`): a failing-first test for
   bugs, positive + negative paths for features. Keep them offline and
   deterministic.
6. **Update docs** when behavior or the API changed (`fetch-php-documentation-sync`)
   and add a `CHANGELOG.md` entry.
7. **Run the gates**, narrowest first:

   ```bash
   ./vendor/bin/phpunit --filter=<yourTest>
   composer fix
   NO_NETWORK=1 composer check
   ```

## Verification

- `composer check` passes (PHPStan level 6, Duster, full PHPUnit).
- New/changed behavior is covered by a test that fails without your change.
- No unrelated dependency, config, or formatting churn in the diff.
- Docs/CHANGELOG updated if the public API or behavior changed.

## Red flags — stop and reconsider

- You added a `phpstan.neon` `ignoreErrors` entry to silence a real type problem.
- The change alters a default (retry/timeout/redirect/cache) or a public
  signature without BC analysis and maintainer approval.
- A test needs the network, `sleep()`, or wall-clock timing to pass.
- The fix grows to touch many files/subsystems — split it into reviewable units
  and confirm the approach first.
