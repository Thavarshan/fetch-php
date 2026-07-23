# AGENTS.md

Canonical, vendor-neutral instructions for AI coding agents working in the
**Fetch PHP** repository. Keep this file the single source of truth: other agent
configs (Claude Code, Codex, etc.) should reference it rather than restate it.

If anything here conflicts with the code, the code wins — fix the drift here in
the same change.

## Project

Fetch PHP (`jerome/fetch-php`) is a modern PHP HTTP client that brings the
JavaScript `fetch` experience to PHP, built on top of Guzzle. It provides a
`fetch()`-style API plus verb helpers, async/await via ReactPHP + `jerome/matrix`,
streaming and Server-Sent Events, RFC 7234 HTTP caching, connection pooling,
middleware, lifecycle events, retries, and full PSR-7/PSR-18/PSR-3 compliance.

The public GitHub repository is `Thavarshan/fetch-php`; the Packagist package is
`jerome/fetch-php`.

## Architecture

Read [CODE_MAP.md](CODE_MAP.md) for the concrete public surface before making
non-trivial changes. High-level layout under `src/Fetch/` (PSR-4 root `Fetch\`):

- `Http/` — `Client` (PSR-18), `ClientHandler` (fluent engine), `Request`,
  `Response`, `StreamedResponse`, `EventSource`, `ServerSentEvent`,
  `MiddlewarePipeline`.
- `Concerns/` — traits composed into `ClientHandler`: `ConfiguresRequests`,
  `HandlesUris`, `PerformsHttpRequests`, `ManagesPromises`, `ManagesRetries`,
  `ManagesMiddleware`, `ManagesEvents`, `ManagesConnectionPool`,
  `ManagesDebugAndProfiling`, `HandlesMocking`.
- `Enum/` — `Method`, `ContentType`, `Status`.
- `Cache/` — RFC 7234 caching: `CacheManager`, `CacheControl`,
  `CacheKeyGenerator`, `CachedResponse`, `MemoryCache`, `FileCache`.
- `Pool/` — `ConnectionPool`, `HostConnectionPool`, `Connection`, `DnsCache`,
  `Http2Configuration`, `PoolConfiguration`.
- `Events/` — `EventDispatcher` and lifecycle events.
- `Middleware/` — built-in `AddHeadersMiddleware`, `LoggingMiddleware`.
- `Exceptions/` — `ClientException`, `NetworkException`, `RequestException`,
  `HttpException`.
- `Support/` — `RequestOptions`, `RequestContext`, `Defaults`, `RetryDefaults`,
  `RetryStrategy`, `GlobalServices`, profiling/debug helpers, and
  `helpers.php` (global functions).
- `Testing/` — `MockServer`, `MockResponse`, `MockResponseSequence`, `Recorder`.
- `Interfaces/` — contracts; `Traits/` — immutability traits.

## Environment

- **PHP `^8.3`.** CI runs **8.3, 8.4, and 8.5** on Ubuntu, Windows, and macOS,
  plus one `prefer-lowest` job (Ubuntu, 8.3). Local dev pins 8.4 (`.phpvmrc`).
- Requires `ext-pcntl` (ignored on Windows, along with `ext-posix`).
- Runtime deps: `guzzlehttp/guzzle ^7.9`, `guzzlehttp/psr7 ^2.7`,
  `jerome/matrix ^3.4`, `react/event-loop ^1.5`, `react/promise ^3.2`,
  `psr/http-message ^1|^2`, `psr/log ^1|^2|^3`.
- `composer.json` has **no `version` field** — versions come from Git tags.
- `composer.lock` is not committed (this is a library).

## Commands (authoritative — from `composer.json` / `package.json`)

| Task | Command | Actually runs |
| --- | --- | --- |
| Install | `composer install` | — |
| Style check | `composer lint` | `duster lint src` |
| Auto-fix style | `composer fix` | `duster fix src` |
| Static analysis | `composer analyse` | `phpstan analyse` (`phpstan.neon`, level 6) |
| All tests | `composer test` | `phpunit --no-coverage` |
| Unit only | `composer test:unit` | `phpunit --no-coverage tests/Unit` |
| Integration only | `composer test:integration` | `phpunit --no-coverage tests/Integration` |
| Coverage | `composer test:coverage` | `phpunit` with Xdebug (needs `ext-xdebug`) |
| Full gate | `composer check` | `analyse`, then `lint`, then `test` |
| Docs (dev) | `npm run dev` | `vitepress dev docs` |
| Docs (build) | `npm run build` | `vitepress build docs` |

- Focused runs: `./vendor/bin/phpunit --filter=some_method` or
  `./vendor/bin/phpunit tests/Unit/SomeTest.php`. Passing args through Composer
  also works: `composer test -- --filter=some_method`.
- There is **no `bin/` script directory**; invoke the Composer/npm scripts above.
- `composer lint` does **not** run PHPStan — run `composer analyse` for that.

## Coding conventions

- Declare `declare(strict_types=1);` in every new PHP file.
- PSR-12 aligned, 4-space indent, single quotes, ordered imports (`const`,
  `class`, `function`), enforced by **Duster** (Pint + PHP CS Fixer +
  PHP_CodeSniffer) via `pint.json`. Run `composer fix` before committing.
- PSR-4 namespaces under `Fetch\...`; one class per file, filename matches class.
- Keep PHPStan level 6 clean (`composer analyse`); prefer real type fixes over
  new entries in `phpstan.neon` `ignoreErrors`.
- Match the style, naming, and comment density of the surrounding file.

## Testing

- **PHPUnit 11**; mocks via **Mockery**. Test namespace `Tests\`.
- Unit tests → `tests/Unit/`, integration tests → `tests/Integration/`,
  shared fakes → `tests/Mocks/` (excluded from the suite and PHPStan).
- Files end in `*Test.php`; test **methods are `snake_case`** (e.g.
  `test_it_builds_the_uri`) — enforced by `pint.json`.
- Tests may extend `Tests\TestCase` (resets `GlobalServices` in `tearDown`) to
  stay isolated; reset shared singletons (`GlobalServices`, `MockServer`,
  `fetch_client(reset: true)`) when you touch global state.
- **Tests must not make real network calls.** Use `Fetch\Testing\MockServer`,
  Mockery, or a Guzzle `MockHandler`. CI exports `NO_NETWORK=1` as a guard; no
  test currently branches on it, so do not rely on it to gate live I/O — just
  keep every test offline and deterministic.
- Avoid `sleep()`/wall-clock assertions; test timing, retry, cancellation, and
  error paths with fakes and injected clocks/strategies.
- Add a regression test with every bug fix. Coverage excludes
  `src/Fetch/Support`.

## Public API & backward compatibility

This is a widely used library on semantic versioning. Treat as
**backward-compatibility-sensitive**: public classes/interfaces, public method
signatures, global helpers in `helpers.php`, enum cases, exception types and
their externally visible behavior, response/stream semantics, and the defaults
for retries, timeouts, redirects, caching, and PSR-7/PSR-18 behavior.

Changing any of these requires: impact analysis, a BC classification
(patch / minor / major), tests, docs, and a CHANGELOG entry. Breaking or
release-sensitive changes need explicit maintainer approval — never ship them
autonomously.

## Dependencies

Do not add or bump dependencies for convenience. A dependency change needs
justification, a license/maintenance/security check, a supported-PHP-version
check, `prefer-lowest` consideration, and CHANGELOG/docs notes. Never add
dependencies for AI tooling.

## Documentation

When behavior or the public API changes, update the VitePress docs under
`docs/` (and `README.md` where relevant), verify examples against the real API,
and run `npm run build`. Remove any generated `docs/.vitepress/.temp` before
committing.

## Security boundaries

- Never commit or print secrets, tokens, or credentials — not in code, tests,
  fixtures, logs, docs examples, or error output.
- Treat as sensitive: user-supplied URLs, redirects, proxies, auth headers,
  cookies, TLS options, cache keys, DNS caching, connection pooling, request/
  response bodies, multipart uploads, streaming, retries, middleware, and
  events. Keep auth/`Authorization`/`Cookie` values redacted in logging,
  profiling, and debug snapshots.
- `HandlesUris` validates URI *format* only — it does **not** block private/
  internal hosts. SSRF hardening is the caller's responsibility; don't imply
  otherwise in docs, and flag anywhere user input reaches a request URL.

## Async & resource safety

Watch for leaked/unresolved promises, unhandled rejections, blocked event
loops, unbounded concurrency, retry storms, duplicate callbacks, listener
accumulation, and stream/connection leaks. Respect cancellation and timeout
behavior; ensure exceptions crossing async boundaries are handled.

## Git & pull-request boundaries

- Work on a branch (`feature/*`, `fix/*`, `refactor/*`); base is `main`
  (`develop` also exists). Conventional Commits (`feat:`, `fix:`, `docs:`, …).
- **Never** run `git reset --hard`, `git clean -fd`, `git push --force`, or
  `git push --force-with-lease`. Do not discard unrelated uncommitted work.
- Do not push, open PRs, create/move tags, publish packages, cut GitHub
  releases, or trigger Packagist without explicit maintainer instruction.
  Release procedure lives in the `fetch-php-release` skill.

## Agent skills

Task workflows live once in `.claude/skills/<name>/SKILL.md` (the canonical
source). Claude Code discovers them natively; Codex and other tools read this
list and open the referenced file on demand — there is no second copy to drift.
Use the skill that matches the task:

- `fetch-php-implement-change` — implement a feature/bug fix under `src/Fetch`.
- `fetch-php-test-development` — write/fix PHPUnit tests.
- `fetch-php-debug-failure` — diagnose a failing test or behavior.
- `fetch-php-code-review` — review a diff/branch/PR.
- `fetch-php-http-security-review` — SSRF, leakage, redirects, TLS, cache.
- `fetch-php-performance-review` — pooling, streaming, retries, async, leaks.
- `fetch-php-public-api-review` — semver/BC impact of a public-surface change.
- `fetch-php-ci-triage` — diagnose failing GitHub Actions.
- `fetch-php-documentation-sync` — keep docs/README/CODE_MAP aligned with code.
- `fetch-php-release` — prepare/recover a release (maintainer-approved only).

Claude Code also ships path-scoped rules (`.claude/rules/`), specialist
subagents (`.claude/agents/`), conservative permissions, and a destructive-command
guard hook. See [docs/ai/README.md](docs/ai/README.md) for the full map and how
to validate the setup (`composer ai:validate`).

## Definition of done

1. `composer fix` applied; `composer lint` clean.
2. `composer analyse` clean (PHPStan level 6).
3. Relevant tests added/updated; `composer test` green (`composer check` for the
   full gate).
4. Docs/CHANGELOG updated when behavior or the public API changed.
5. No secrets, no unrelated dependency or config churn, no destructive Git.
6. Diff reviewed and understood.
