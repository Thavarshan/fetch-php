# CLAUDE.md

Guidance for Claude Code in this repository.

The canonical, vendor-neutral instructions live in **AGENTS.md** — architecture,
commands, conventions, testing, public-API rules, security boundaries, and the
definition of done. Read them first; they are imported here:

@AGENTS.md

Everything below is **additive** Claude-specific guidance — it does not restate
AGENTS.md. If the two ever disagree, AGENTS.md wins; fix the drift.

## Plan first for risky work

Enter **plan mode** before you edit when a task is likely to touch the public
API or defaults (`src/Fetch/Interfaces/`, `Http/Client.php`,
`Http/Response.php`, `Enum/`, `Support/helpers.php`,
`Support/RequestOptions.php`, `Support/Defaults.php`,
`Support/RetryDefaults.php`), spans multiple subsystems, or changes async,
caching, streaming, pooling, retry, or security behavior. Trivial, localized
fixes don't need a plan.

## Skills — invoke the right one

- Feature or bug fix in `src/Fetch`: `fetch-php-implement-change`.
- PHPUnit tests: `fetch-php-test-development`.
- Diagnose a failing test or behavior: `fetch-php-debug-failure`.
- Review a diff, branch, or PR: `fetch-php-code-review`.
- URL, auth, redirects, TLS, logging, or cache:
  `fetch-php-http-security-review`.
- Pool, streaming, retries, async, or events:
  `fetch-php-performance-review`.
- Public API or default changes: `fetch-php-public-api-review`.
- GitHub Actions failures: `fetch-php-ci-triage`.
- Docs, README, or examples: `fetch-php-documentation-sync`.
- Maintainer-approved release work: `fetch-php-release`.
Path-scoped `.claude/rules/` load automatically when you open matching files
(PHP source, tests, docs, workflows, public-API files) — you don't invoke them.

## Subagents

Delegate to a specialist (`.claude/agents/`) to keep review evidence-based and
read-only where appropriate:

- `php-library-maintainer` — implement a scoped change end to end.
- `phpunit-test-engineer` — author/repair tests.
- `http-security-reviewer` — adversarial HTTP/security review (read-only).
- `public-api-reviewer` — semver/BC impact (read-only).
- `documentation-reviewer` — docs-vs-code drift (read-only).
- `ci-investigator` — triage failing GitHub Actions (read-only + `gh`).

## Large or multi-part changes

Split into reviewable units and confirm the approach before a wide edit. Never
bundle a behavior change with a broad reformat. Run the narrowest check while
iterating (`./vendor/bin/phpunit --filter=...`) and the full gate before saying
done (`NO_NETWORK=1 composer check`).

## Verification behavior

- Don't claim a command passed unless you actually ran it; paste real failures.
- After edits, run `composer fix`, then `composer analyse`, then the relevant
  tests. `composer check` runs all three (analyse → lint → test).
- Respect the Git/PR/release boundaries in AGENTS.md — no destructive Git, no
  pushing/PRs/tags/releases without explicit maintainer instruction.
