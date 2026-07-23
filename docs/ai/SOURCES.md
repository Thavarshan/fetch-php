# AI configuration sources & attribution

This file records every external source consulted while building Fetch PHP's
AI-agent configuration, its license, and how it was used. The rule we followed:
**adapt concepts in our own words; never vendor files verbatim; never copy
source-available or proprietary content.** All Fetch PHP AI config in this repo
is original prose written for this project.

Research date: 2026-07-23.

## Official standards (source of truth for syntax)

| What | Source | Used for |
| --- | --- | --- |
| Claude Code memory & `@import` | `code.claude.com/docs/en/memory` | `CLAUDE.md` importing `@AGENTS.md`; `.claude/rules/` with `paths:` front matter |
| Claude Code skills | `code.claude.com/docs/en/skills` | `.claude/skills/<name>/SKILL.md` structure & `name`/`description` rules |
| Claude Code subagents | `code.claude.com/docs/en/sub-agents` | `.claude/agents/*.md` `name`/`description`/`tools`/`model` |
| Claude Code settings & permissions | `code.claude.com/docs/en/settings`, `/permissions` | `.claude/settings.json` allow/ask/deny matchers |
| Claude Code hooks | `code.claude.com/docs/en/hooks` | `PreToolUse` Bash guard hook contract |
| Codex `AGENTS.md` | `learn.chatgpt.com/docs/agent-configuration/agents-md` | AGENTS.md as canonical, 32 KiB budget, nested discovery |
| Codex skills | `learn.chatgpt.com/docs/build-skills` | Codex reads `.agents/skills/` (not `.claude/skills/`); `agents/openai.yaml` |
| Agent Skills open standard | `agentskills.io/specification` | SKILL.md frontmatter (`name` must match dir; `description` ≤1024) |

Key design consequence: Claude Code reads `.claude/skills/`, Codex reads
`.agents/skills/`. To avoid divergent copies **and** fragile Windows symlinks, we
keep one canonical skill set in `.claude/skills/` and reference it from
`AGENTS.md` (which Codex and other AGENTS.md-aware tools read). `composer
ai:validate` enforces that the two stay in sync.

## Open-source repositories evaluated

Licenses below were verified by inspecting each repo's `LICENSE`/`LICENSE.txt`
or the GitHub license field on 2026-07-23.

### Concepts adopted (adapted, not copied)

| Repo | Inspected | License | Concepts adapted into | 
| --- | --- | --- | --- |
| [guzzle/guzzle](https://github.com/guzzle/guzzle) — the library Fetch PHP wraps | `AGENTS.md` (HEAD `604492e`, 2026-07-20) | MIT | `#[\SensitiveParameter]` on secret-bearing params; fail-closed validation; locale-independent header comparison; prefer additive optional params for BC → informed `http-security.md`, `php-style.md`, `public-api-and-semver.md`, `fetch-php-http-security-review`, `fetch-php-public-api-review` |
| [trailofbits/skills](https://github.com/trailofbits/skills) | HEAD `cfe5d7b` | **CC-BY-SA-4.0 (copyleft)** — concepts only, no verbatim reuse | "fail-open defaults" detection; the "when to use / rationalizations to reject" skill shape → informed the *Red flags — stop* sections and `fetch-php-http-security-review` |
| [anthropics/skills](https://github.com/anthropics/skills) | HEAD `1f630fd` | Per-skill (Apache-2.0 for `skill-creator`, `mcp-builder`, `claude-api`); **`docx`/`pdf`/`pptx`/`xlsx` are source-available — NOT used** | SKILL.md spec/structure & progressive-disclosure model → all skills |
| [openai/skills](https://github.com/openai/skills) | 2026-07-14 | Per-skill Apache-2.0 (`security-best-practices`, `security-threat-model`) | threat-model skill structure → `fetch-php-http-security-review` |
| [addyosmani/agent-skills](https://github.com/addyosmani/agent-skills) | HEAD `fefc407` | MIT | individual review/testing concepts → `fetch-php-code-review`, `fetch-php-test-development` (cherry-picked, not vendored) |
| [algolia/algoliasearch-client-php](https://github.com/algolia/algoliasearch-client-php), [neuron-core/neuron-ai](https://github.com/neuron-core/neuron-ai) | 2026-07 | MIT | real-world examples of describing a Guzzle-based HTTP client to an agent → structure of `AGENTS.md` architecture section |

### Referenced only (not sourced/copied)

| Repo | License | Why not copied |
| --- | --- | --- |
| [openai/codex](https://github.com/openai/codex) | Apache-2.0 | Runtime, not config — used only to confirm the AGENTS.md convention |
| [anthropics/claude-code](https://github.com/anthropics/claude-code) | Proprietary / source-available (no OSS license) | Product repo; conventions referenced via official docs, no material copied |
| `anthropics/skills/{docx,pdf,pptx,xlsx}` | Source-available proprietary | Explicitly excluded — must never be copied or vendored |
| [symfony/http-client](https://github.com/symfony/http-client) | MIT | Inspected — has no `AGENTS.md`/`CLAUDE.md`; nothing to source |

## Keeping this current

When you adapt anything new from an external source, add a row here with the
repo, the commit/version inspected, its license, the concept adopted, whether it
was copied/adapted/inspiration, and the local files it influenced. Copyleft
(e.g. CC-BY-SA) and source-available/proprietary material must never be pasted
verbatim.
