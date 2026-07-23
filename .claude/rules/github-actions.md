---
paths:
  - ".github/workflows/**/*.yml"
  - ".github/workflows/**/*.yaml"
---

# GitHub Actions rules

- Pin actions and keep `permissions:` least-privilege (jobs default to
  `contents: read`; only elevate the job that needs it).
- Never add secrets, tokens, or external webhooks in plaintext; use
  `secrets.*`. Don't echo secrets into logs.
- Keep the CI matrix intact (Ubuntu/Windows/macOS × PHP 8.3/8.4/8.5 + the
  Ubuntu/8.3 `prefer-lowest` job). Don't drop a cell or add `continue-on-error`
  to hide a real failure — fix the cause (`fetch-php-ci-triage`).
- Test jobs run with `NO_NETWORK=1`; keep it.
- **`packages.yml`, tags, and releases are release infrastructure** — change
  them only via `fetch-php-release` with maintainer approval. Don't force-move
  tags or alter Packagist behavior to chase a CI failure.
