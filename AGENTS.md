# Repository Guidelines

## Project Structure & Module Organization

- `src/Fetch/` — library source (PSR-4: `Fetch\`), organized by `Enum/`, `Http/`, `Interfaces/`, `Traits/`, `Support/`, etc.
- `tests/` — PHPUnit tests (`Unit/`, `Integration/`, `Mocks/`). Files end with `*Test.php`.
- `bin/` — helper scripts: `lint.sh`, `fix.sh`, `test.sh` (invoked via Composer scripts).
- `docs/` — VitePress documentation site (see `package.json` scripts).
- Key configs: `composer.json`, `phpunit.xml.dist`, `phpstan*.neon`, `pint.json`.

## Build, Test, and Development Commands

- `composer install` — install PHP dependencies.
- `composer test` — run PHPUnit. Examples: `composer test -- --coverage`, `composer test -- --filter=ResponseTest`.
- `composer lint` — static analysis + style checks (Duster/Pint, PHPStan, syntax).
- `composer fix` — auto-fix coding style issues.
- Docs: `npm run dev` (live docs), `npm run build` (static site), `npm run preview`.

## Coding Style & Naming Conventions

- PHP ≥ 8.2. Declare `strict_types=1` in new files.
- PSR-12 aligned; 4-space indentation; single quotes for strings; ordered imports.
- Namespaces follow PSR-4 under `Fetch\...`; file name matches class.
- PHPUnit method casing: snake_case (see `pint.json`).
- Before committing, run `composer fix` and ensure `composer lint` passes.

## Testing Guidelines

- Framework: PHPUnit 11; mocking via Mockery (see `tests/Mocks/`).
- Place unit tests in `tests/Unit/`, integration tests in `tests/Integration/`.
- Naming: `ClassOrFeatureTest.php`; each test method asserts one behavior.
- Coverage: include tests for new code paths and regressions; use `--coverage` locally if Xdebug is available.

## Commit & Pull Request Guidelines

- Commits: concise, imperative mood. Example: `Add retry backoff jitter`.
- PRs: describe Purpose and Approach, link issues, checklists per `.github/PULL_REQUEST_TEMPLATE.md`.
- Requirements: green CI (tests, lint), updated docs/snippets when API changes, add tests for fixes/features.

## Security & Configuration Tips

- Do not commit secrets or tokens. Prefer environment-driven config in tests.
- Network-dependent tests should use mocks/fakes; avoid external calls in CI.
- Targeted PHP versions are enforced in `.phpvmrc` and CI; verify locally.

## CI/No-Network Environments

- Skip the network-dependent unit test by setting `NO_NETWORK=1`.
- Example: `NO_NETWORK=1 composer test`.
- Keep tests deterministic: mock HTTP instead of real network I/O where possible.
