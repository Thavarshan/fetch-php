# AI-assisted development in Fetch PHP

This repository ships a cross-agent configuration so AI coding assistants work
on Fetch PHP safely and consistently. It targets **Claude Code** and **OpenAI
Codex** first, and stays compatible with any tool that understands `AGENTS.md`
and the [Agent Skills](https://agentskills.io) standard.

Contributors remain fully responsible for AI-assisted changes — see the
AI-assisted contributions section in [CONTRIBUTING.md](../../CONTRIBUTING.md).

## Supported tools & what they read

| Tool | Reads | Notes |
| --- | --- | --- |
| **Claude Code** | `CLAUDE.md`, `.claude/rules/`, `.claude/skills/`, `.claude/agents/`, `.claude/settings.json`, hooks | `CLAUDE.md` imports `AGENTS.md` via `@AGENTS.md` |
| **OpenAI Codex** | `AGENTS.md` (root + nested) | Discovers skills through the list in `AGENTS.md`; optional native `$skill` discovery via `.agents/skills/` (see below) |
| **Other AGENTS.md tools** | `AGENTS.md` | Vendor-neutral instructions apply |

## File map

| Path | Purpose |
| --- | --- |
| `AGENTS.md` | **Canonical, vendor-neutral instructions** — the single source of truth (architecture, commands, conventions, testing, public-API rules, security, Git boundaries, definition of done). |
| `CLAUDE.md` | Claude-specific, additive guidance. Imports `AGENTS.md`; adds plan-mode, skill routing, subagents, verification behavior. |
| `CODE_MAP.md` | Concrete public-surface map; the contract docs and tests follow. |
| `.claude/rules/*.md` | Path-scoped reminders that auto-load when a matching file is opened (`paths:` front matter). |
| `.claude/skills/<name>/SKILL.md` | Task workflows (canonical copy). |
| `.claude/agents/*.md` | Specialist subagents. |
| `.claude/settings.json` | Shared, conservative permissions + the guard hook registration. |
| `.claude/hooks/guard-destructive.sh` | Blocks destructive Git / catastrophic `rm`. |
| `.claude/settings.local.json` | **Personal, git-ignored** — your own permission grants. |
| `docs/ai/SOURCES.md` | External sources & license attribution. |
| `tools/validate-ai-config.php` | The validator (`composer ai:validate`). |

## Skills (when they activate)

Skills live once under `.claude/skills/`. Claude Code loads their descriptions at
startup and activates one when the task matches (or on `/skill-name`); Codex and
other tools find them through the list in `AGENTS.md`.

| Skill | Use when |
| --- | --- |
| `fetch-php-implement-change` | Adding a feature or fixing a bug in `src/Fetch`. |
| `fetch-php-test-development` | Writing or fixing PHPUnit tests. |
| `fetch-php-debug-failure` | A test or behavior is failing. |
| `fetch-php-code-review` | Reviewing a diff/branch/PR. |
| `fetch-php-http-security-review` | URL/auth/redirect/TLS/logging/cache changes. |
| `fetch-php-performance-review` | Pool/stream/retry/async/event changes. |
| `fetch-php-public-api-review` | Any public-surface or default change. |
| `fetch-php-ci-triage` | GitHub Actions is red. |
| `fetch-php-documentation-sync` | Docs/README/examples need updating. |
| `fetch-php-release` | Preparing/recovering a release (maintainer-approved only). |

## Subagents

`php-library-maintainer` (implements changes), `phpunit-test-engineer` (tests),
and the read-only reviewers `http-security-reviewer`, `public-api-reviewer`,
`documentation-reviewer`, and `ci-investigator`. Reviewers have no Edit/Write
tools — they report findings for a human or the maintainer agent to act on.

## Permissions & safety

`.claude/settings.json` is deliberately conservative:

- **allow** — read-only and routine dev commands (`composer test/lint/analyse/
  fix/check`, `phpunit`, `git status/diff/log`, `gh run/release view`, docs
  build).
- **ask** — anything outward-facing or state-changing (`git push`, `git tag`,
  `git commit`, `gh release`, `composer require/update/remove`, `curl`).
- **deny** — destructive Git (`reset --hard`, `clean -fd`, force push) and
  reading secret files (`.env`, keys, `~/.ssh`, `~/.aws`).

The `PreToolUse` hook (`.claude/hooks/guard-destructive.sh`) is defense-in-depth
for the destructive-Git and catastrophic-`rm` cases. It is pure POSIX `sh` +
`grep` (no `jq`/PHP/Node dependency), runs locally, makes no network calls, and
transmits nothing.

**Platform note:** the hook needs a POSIX shell. On Windows, Claude Code runs
hooks through its bundled Git Bash, so it works there; the declarative
permission rules apply on every platform regardless.

Your personal grants belong in `.claude/settings.local.json` (git-ignored), not
in the committed settings file. Never commit machine-specific paths.

## Codex native skill discovery (optional)

Codex reads `AGENTS.md` and will find the skills listed there. If you also want
native `$skill-name` discovery in Codex, expose the canonical skills under
`.agents/skills/` locally:

```bash
# macOS/Linux — symlink so there is still only one real copy
ln -s ../.claude/skills .agents/skills
```

`.agents/skills` is git-ignored to prevent a committed duplicate. On Windows,
symlinks need Developer Mode/Administrator, so either enable that or keep using
the `AGENTS.md`-driven discovery (recommended default). Do not commit a second
copy of any skill.

## Validate the configuration

```bash
composer ai:validate      # or: php tools/validate-ai-config.php
```

It checks (no network, deterministic): required files exist; `CLAUDE.md`
imports `@AGENTS.md`; every skill has valid, unique front matter with a name
matching its directory; skills are referenced in `AGENTS.md` (Claude↔Codex
sync); subagents/rules are well-formed; JSON parses; and no personal absolute
paths or secret-looking strings are committed.

## Adding a new skill

1. Create `.claude/skills/<kebab-name>/SKILL.md` with front matter:

   ```yaml
   ---
   name: <kebab-name>            # must equal the directory name
   description: <what it does, and exactly when to use it>   # ≤1024 chars
   ---
   ```

2. Write a concrete, executable workflow with verification steps and red-flag
   stop conditions. Reference repository commands from `AGENTS.md`; don't invent
   new ones or duplicate whole `AGENTS.md` sections.
3. Add the skill to the list in `AGENTS.md` (required for Codex discovery and
   for `composer ai:validate` to pass) and to the tables in `CLAUDE.md` and this
   file.
4. Run `composer ai:validate`.

## Avoiding instruction drift

- `AGENTS.md` is the single source of truth; `CLAUDE.md` and rules stay
  additive. If they disagree with the code, fix them in the same change.
- Update `CODE_MAP.md` when the public surface changes.
- Record any adapted external material in `docs/ai/SOURCES.md`.
- Run `composer ai:validate` before committing AI-config changes.
