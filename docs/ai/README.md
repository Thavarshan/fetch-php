# AI-assisted development in Fetch PHP

This repository ships a cross-agent configuration so AI coding assistants work
on Fetch PHP safely and consistently. It targets **Claude Code** and **OpenAI
Codex** first, and stays compatible with any tool that understands `AGENTS.md`
and the [Agent Skills](https://agentskills.io) standard.

Contributors remain fully responsible for AI-assisted changes — see the
AI-assisted contributions section in [CONTRIBUTING.md](../../CONTRIBUTING.md).

## Supported tools & what they read

- **Tool:** **Claude Code**
  - **Reads:** `CLAUDE.md`, `.claude/rules/`, `.claude/skills/`,
               `.claude/agents/`, `.claude/settings.json`, hooks
  - **Notes:** `CLAUDE.md` imports `AGENTS.md` via `@AGENTS.md`

- **Tool:** **OpenAI Codex**
  - **Reads:** `AGENTS.md` (root + nested)
  - **Notes:** Discovers skills through the list in `AGENTS.md`; optional native
               `$skill` discovery via `.agents/skills/` (see below)

- **Tool:** **Other AGENTS.md tools**
  - **Reads:** `AGENTS.md`
  - **Notes:** Vendor-neutral instructions apply

## File map

- **Path:** `AGENTS.md`
  - **Purpose:** **Canonical, vendor-neutral instructions** — the single source
                 of truth (architecture, commands, conventions, testing,
                 public-API rules, security, Git boundaries, definition of
                 done).

- **Path:** `CLAUDE.md`
  - **Purpose:** Claude-specific, additive guidance. Imports `AGENTS.md`; adds
                 plan-mode, skill routing, subagents, verification behavior.

- **Path:** `CODE_MAP.md`
  - **Purpose:** Concrete public-surface map; the contract docs and tests
                 follow.

- **Path:** `.claude/rules/*.md`
  - **Purpose:** Path-scoped reminders that auto-load when a matching file is
                 opened (`paths:` front matter).

- **Path:** `.claude/skills/<name>/SKILL.md`
  - **Purpose:** Task workflows (canonical copy).

- **Path:** `.claude/agents/*.md`
  - **Purpose:** Specialist subagents.

- **Path:** `.claude/settings.json`
  - **Purpose:** Shared, conservative permissions + the guard hook registration.

- **Path:** `.claude/hooks/guard`
  - **Purpose:** Blocks destructive Git / catastrophic `rm`.

- **Path:** `.claude/settings.local.json`
  - **Purpose:** **Personal, git-ignored** — your own permission grants.

- **Path:** `docs/ai/SOURCES.md`
  - **Purpose:** External sources & license attribution.

- **Path:** `tools/validate-ai-config.php`
  - **Purpose:** The validator (`composer ai:validate`).

## Skills (when they activate)

Skills live once under `.claude/skills/`. Claude Code loads their descriptions
at startup and activates one when the task matches (or on `/skill-name`); Codex
and other tools find them through the list in `AGENTS.md`.

- **Skill:** `fetch-php-implement-change`
  - **Use when:** Adding a feature or fixing a bug in `src/Fetch`.

- **Skill:** `fetch-php-test-development`
  - **Use when:** Writing or fixing PHPUnit tests.

- **Skill:** `fetch-php-debug-failure`
  - **Use when:** A test or behavior is failing.

- **Skill:** `fetch-php-code-review`
  - **Use when:** Reviewing a diff/branch/PR.

- **Skill:** `fetch-php-http-security-review`
  - **Use when:** URL/auth/redirect/TLS/logging/cache changes.

- **Skill:** `fetch-php-performance-review`
  - **Use when:** Pool/stream/retry/async/event changes.

- **Skill:** `fetch-php-public-api-review`
  - **Use when:** Any public-surface or default change.

- **Skill:** `fetch-php-ci-triage`
  - **Use when:** GitHub Actions is red.

- **Skill:** `fetch-php-documentation-sync`
  - **Use when:** Docs/README/examples need updating.

- **Skill:** `fetch-php-release`
  - **Use when:** Preparing/recovering a release (maintainer-approved only).

## Subagents

`php-library-maintainer` (implements changes), `phpunit-test-engineer` (tests),
and the read-only reviewers `http-security-reviewer`, `public-api-reviewer`,
`documentation-reviewer`, and `ci-investigator`. Reviewers have no Edit/Write
tools — they report findings for a human or the maintainer agent to act on.

## Permissions & safety

`.claude/settings.json` is deliberately conservative:

- **allow** — read-only and routine dev commands
  (`composer test/lint/analyse/ fix/check`, `phpunit`, `git status/diff/log`,
  `gh run/release view`, docs build).
- **ask** — anything outward-facing or state-changing (`git push`, `git tag`,
  `git commit`, `gh release`, `composer require/update/remove`, `curl`).
- **deny** — destructive Git (`reset --hard`, `clean -fd`, force push) and
  reading secret files (`.env`, keys, `~/.ssh`, `~/.aws`).

The `PreToolUse` hook (`.claude/hooks/guard`) is defense-in-depth
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

It checks (no network, deterministic): required files exist; `CLAUDE.md` imports
`@AGENTS.md`; every skill has valid, unique front matter with a name matching
its directory; skills are referenced in `AGENTS.md` (Claude↔Codex sync);
subagents/rules are well-formed; JSON parses; and no personal absolute paths or
secret-looking strings are committed.

## Adding a new skill

1. Create `.claude/skills/<kebab-name>/SKILL.md` with front matter:

    ```yaml
    ---
    name: <kebab-name> # must equal the directory name
    description: <what it does, and exactly when to use it> # ≤1024 chars
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
