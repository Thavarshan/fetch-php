---
name: fetch-php-ci-triage
description: Diagnose Fetch PHP GitHub Actions failures — matrix failures across OS (Ubuntu/Windows/macOS) and PHP (8.3/8.4/8.5), prefer-lowest dependency failures, lint/PHPStan, coverage/Codecov, and the docs build. Use when CI is red and you need to find which job failed and why, then reproduce it safely.
---

# Fetch PHP CI triage

Read-only investigation with `gh`. Reproduce locally with the same versions
before changing anything. See `.github/workflows/` for the source of truth.

## When to use

A GitHub Actions run is failing and you need to localize the cause. For fixing
the underlying test/behavior use `fetch-php-debug-failure`.

## The workflows

- **CI** (`ci.yml`) — `lint` (Duster + PHPStan), `test` matrix
  (Ubuntu/Windows/macOS × PHP 8.3/8.4/8.5 + one Ubuntu/8.3 `prefer-lowest`),
  `coverage` (Xdebug → Codecov), `docs` (VitePress build). Jobs are gated by a
  `paths-filter`, so PHP jobs skip on docs-only changes and vice versa.
- **Security** (`security.yml`) — `composer audit` + PHPStan.
- **Package & Release** (`packages.yml`) — tag-triggered; see
  `fetch-php-release`.

## Workflow

1. **Find the failing job.**

   ```bash
   gh run list --repo Thavarshan/fetch-php --limit 10
   gh run view <run-id> --repo Thavarshan/fetch-php
   gh run view <run-id> --repo Thavarshan/fetch-php --log-failed
   ```

2. **Classify by matrix cell** — the job name is `PHP <ver> on <os> - <stability>`:
   - **One PHP version only** → language/feature difference (8.3 vs 8.5). Repro
     with that `php -v`.
   - **`prefer-lowest` only** → you rely on behavior newer than the minimum
     constraint. Reproduce: `composer update --prefer-lowest --prefer-dist` then
     `NO_NETWORK=1 composer test`. Fix by adjusting code or the version floor in
     `composer.json` (with justification), not by loosening the test.
   - **Windows only** → path/EOL, or `pcntl`/`posix` (Windows installs ignore
     those extensions). Guard platform-specific code.
   - **macOS only** → usually filesystem case-sensitivity or tmp path.
   - **All cells** → a genuine code/test bug (`fetch-php-debug-failure`).
   - **lint job** → `composer fix` then `composer lint`; PHPStan → `composer
     analyse`.
   - **coverage/Codecov** → Codecov upload uses `fail_ci_if_error: false`, so a
     Codecov hiccup shouldn't fail the run; a red coverage job means the tests
     themselves failed under Xdebug.
   - **docs** → reproduce with `npm ci && npm run build`. (`check-links` runs
     with `|| true`; it never fails the job.)

3. **Reproduce locally** with the matching PHP version and stability before
   proposing a fix. Use `NO_NETWORK=1` for test jobs.

## Verification

- You can reproduce the exact failure locally, or explain precisely why it is
  environment-specific.
- The fix targets the real cause; matrix stays green across cells you can run.

## Red flags — stop

- "Fixing" CI by deleting a matrix cell, adding `continue-on-error`, or skipping
  a test.
- Bumping/loosening a dependency to dodge `prefer-lowest` without justification
  (see `fetch-php-public-api-review` — the version floor is public API).
- Touching `packages.yml`/tags to chase a CI failure (use `fetch-php-release`).
