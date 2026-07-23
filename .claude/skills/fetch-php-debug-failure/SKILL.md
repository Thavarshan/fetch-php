---
name: fetch-php-debug-failure
description: Diagnose a failing Fetch PHP test or unexpected behavior — reproduce it, classify the cause (environment, network, async/timing, platform, dependency, or implementation), minimize the failing case, fix the root cause, and add regression coverage. Use when a test fails locally or in CI.
---

# Debug a Fetch PHP failure

Follow [AGENTS.md](../../../AGENTS.md). For CI-matrix-specific failures use
`fetch-php-ci-triage`; for writing the regression test use
`fetch-php-test-development`.

## When to use

A PHPUnit test fails, PHPStan reports an error, or runtime behavior is wrong and
you need to find and fix the root cause.

## Workflow

1. **Reproduce narrowly.**

   ```bash
   ./vendor/bin/phpunit --filter=<method>          # single test
   ./vendor/bin/phpunit tests/Unit/<Some>Test.php  # single file
   NO_NETWORK=1 composer test                      # full offline suite
   ```

2. **Classify the cause** before fixing:
   - **Environment** — PHP version (`php -v`; CI runs 8.3/8.4/8.5), missing ext
     (`pcntl`), locale/timezone (`phpunit.xml.dist` pins UTC / `C.UTF-8`).
   - **Network** — a test reaching real hosts; convert to `MockServer`/Mockery.
   - **Async/timing** — dependence on wall-clock, event-loop ordering, or
     `sleep()`; make it deterministic.
   - **Platform** — Windows/macOS path, EOL, or `pcntl`/`posix` differences
     (Windows ignores those extensions).
   - **Dependency** — behavior differing under `prefer-lowest`; check version
     constraints in `composer.json`.
   - **Test isolation** — leaked global state (`GlobalServices`, `MockServer`,
     `fetch_client`); confirm by running the test alone vs in suite.
   - **Implementation** — a genuine bug in `src/Fetch`.
3. **Minimize** to the smallest input/state that still fails; read the owning
   class/trait (via `CODE_MAP.md`).
4. **Fix the root cause**, not the symptom. Don't loosen assertions or add
   PHPStan ignores to make red go green.
5. **Add a regression test** that fails without the fix.
6. **Re-run** the focused test, then `NO_NETWORK=1 composer check`.

## Verification

- The regression test fails on the unpatched code and passes after the fix.
- Full offline gate green; no new PHPStan ignores; no widened tolerances.

## Red flags — stop

- "Fix" is deleting/skipping the assertion, adding `@group`, or an ignore entry.
- Root cause is unknown but the test now passes (likely masked flake).
- The failure is a real BC break surfaced by a test — escalate via
  `fetch-php-public-api-review` before changing the test's expectation.
