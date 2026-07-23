---
paths:
  - "docs/**/*.md"
  - "README.md"
  - "CODE_MAP.md"
  - "CHANGELOG.md"
---

# Documentation rules

- Examples must match the real API (`src/Fetch` + `CODE_MAP.md`): signatures,
  option names, defaults, enum cases, and streaming/SSE patterns.
- When the public surface changes, update `CODE_MAP.md` (the contract other docs
  follow) and add a `CHANGELOG.md` entry.
- No secrets/real tokens/private hosts in examples. Show TLS-on and
  redaction-aware patterns; never present `verify => false` as a default; don't
  claim security guarantees the code lacks (e.g. SSRF protection).
- Build docs with `npm run build`; remove generated `docs/.vitepress/.temp`,
  `dist`, and `cache` before committing.
- Workflow: `fetch-php-documentation-sync`.
