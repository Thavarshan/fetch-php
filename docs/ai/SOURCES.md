# AI configuration sources & attribution

This file records every external source consulted while building Fetch PHP's
AI-agent configuration, its license, and how it was used. The rule we followed:
**adapt concepts in our own words; never vendor files verbatim; never copy
source-available or proprietary content.** All Fetch PHP AI config in this repo
is original prose written for this project.

Research date: 2026-07-23.

## Official standards (source of truth for syntax)

- **What:** Claude Code memory & `@import`
  - **Source:** `code.claude.com/docs/en/memory`
  - **Used for:** `CLAUDE.md` importing `@AGENTS.md`; `.claude/rules/` with
                  `paths:` front matter

- **What:** Claude Code skills
  - **Source:** `code.claude.com/docs/en/skills`
  - **Used for:** `.claude/skills/<name>/SKILL.md` structure &
                  `name`/`description` rules

- **What:** Claude Code subagents
  - **Source:** `code.claude.com/docs/en/sub-agents`
  - **Used for:** `.claude/agents/*.md` `name`/`description`/`tools`/`model`

- **What:** Claude Code settings & permissions
  - **Source:** `code.claude.com/docs/en/settings`, `/permissions`
  - **Used for:** `.claude/settings.json` allow/ask/deny matchers

- **What:** Claude Code hooks
  - **Source:** `code.claude.com/docs/en/hooks`
  - **Used for:** `PreToolUse` Bash guard hook contract

- **What:** Codex `AGENTS.md`
  - **Source:** `learn.chatgpt.com/docs/agent-configuration/agents-md`
  - **Used for:** AGENTS.md as canonical, 32 KiB budget, nested discovery

- **What:** Codex skills
  - **Source:** `learn.chatgpt.com/docs/build-skills`
  - **Used for:** Codex reads `.agents/skills/` (not `.claude/skills/`);
                  `agents/openai.yaml`

- **What:** Agent Skills open standard
  - **Source:** `agentskills.io/specification`
  - **Used for:** SKILL.md frontmatter (`name` must match dir; `description`
                  ≤1024)

Key design consequence: Claude Code reads `.claude/skills/`, Codex reads
`.agents/skills/`. To avoid divergent copies **and** fragile Windows symlinks,
we keep one canonical skill set in `.claude/skills/` and reference it from
`AGENTS.md` (which Codex and other AGENTS.md-aware tools read).
`composer ai:validate` enforces that the two stay in sync.

## Open-source repositories evaluated

Licenses below were verified by inspecting each repo's `LICENSE`/`LICENSE.txt`
or the GitHub license field on 2026-07-23.

### Concepts adopted (adapted, not copied)

- **Repo:** [guzzle/guzzle](https://github.com/guzzle/guzzle) — the library
            Fetch PHP wraps
  - **Inspected:** `AGENTS.md` (HEAD `604492e`, 2026-07-20)
  - **License:** MIT
  - **Concepts adapted into:** `#[\SensitiveParameter]` on secret-bearing
                               params; fail-closed validation;
                               locale-independent header comparison; prefer
                               additive optional params for BC → informed
                               `http-security.md`, `php-style.md`,
                               `public-api-and-semver.md`,
                               `fetch-php-http-security-review`,
                               `fetch-php-public-api-review`

- **Repo:** [trailofbits/skills](https://github.com/trailofbits/skills)
  - **Inspected:** HEAD `cfe5d7b`
  - **License:** **CC-BY-SA-4.0 (copyleft)** — concepts only, no verbatim reuse
  - **Concepts adapted into:** "fail-open defaults" detection; the "when to use
                               / rationalizations to reject" skill shape →
                               informed the _Red flags — stop_ sections and
                               `fetch-php-http-security-review`

- **Repo:** [anthropics/skills](https://github.com/anthropics/skills)
  - **Inspected:** HEAD `1f630fd`
  - **License:** Per-skill (Apache-2.0 for `skill-creator`, `mcp-builder`,
                 `claude-api`); **`docx`/`pdf`/`pptx`/`xlsx` are
                 source-available — NOT used**
  - **Concepts adapted into:** SKILL.md spec/structure & progressive-disclosure
                               model → all skills

- **Repo:** [openai/skills](https://github.com/openai/skills)
  - **Inspected:** 2026-07-14
  - **License:** Per-skill Apache-2.0 (`security-best-practices`,
                 `security-threat-model`)
  - **Concepts adapted into:** threat-model skill structure →
                               `fetch-php-http-security-review`

- **Repo:** [addyosmani/agent-skills][addy-skills]
  - **Inspected:** HEAD `fefc407`
  - **License:** MIT
  - **Concepts adapted into:** individual review/testing concepts →
                               `fetch-php-code-review`,
                               `fetch-php-test-development` (cherry-picked, not
                               vendored)

- **Repo:** [algolia/algoliasearch-client-php][algolia-client],
            [neuron-core/neuron-ai][neuron-ai]
  - **Inspected:** 2026-07
  - **License:** MIT
  - **Concepts adapted into:** real-world examples of describing a Guzzle-based
                               HTTP client to an agent → structure of
                               `AGENTS.md` architecture section

### Referenced only (not sourced/copied)

- **Repo:** [openai/codex](https://github.com/openai/codex)
  - **License:** Apache-2.0
  - **Why not copied:** Runtime, not config — used only to confirm the AGENTS.md
                        convention

- **Repo:** [anthropics/claude-code](https://github.com/anthropics/claude-code)
  - **License:** Proprietary / source-available (no OSS license)
  - **Why not copied:** Product repo; conventions referenced via official docs,
                        no material copied

- **Repo:** `anthropics/skills/{docx,pdf,pptx,xlsx}`
  - **License:** Source-available proprietary
  - **Why not copied:** Explicitly excluded — must never be copied or vendored

- **Repo:** [symfony/http-client](https://github.com/symfony/http-client)
  - **License:** MIT
  - **Why not copied:** Inspected — has no `AGENTS.md`/`CLAUDE.md`; nothing to
                        source

## Keeping this current

When you adapt anything new from an external source, add a row here with the
repo, the commit/version inspected, its license, the concept adopted, whether it
was copied/adapted/inspiration, and the local files it influenced. Copyleft
(e.g. CC-BY-SA) and source-available/proprietary material must never be pasted
verbatim.

[addy-skills]: https://github.com/addyosmani/agent-skills
[algolia-client]: https://github.com/algolia/algoliasearch-client-php
[neuron-ai]: https://github.com/neuron-core/neuron-ai
