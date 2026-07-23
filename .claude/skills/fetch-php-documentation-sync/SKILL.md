---
name: fetch-php-documentation-sync
description: Keep Fetch PHP documentation aligned with the code — update the VitePress guide/API pages and README, verify examples against the real public API and CODE_MAP.md, fix stale snippets, and run the docs build. Use when a change alters behavior, options, or the public API, or when auditing docs for drift.
---

# Fetch PHP documentation sync

Docs live in `docs/` (VitePress) with `README.md` at the root.
[CODE_MAP.md](../../../CODE_MAP.md) is the concrete public-surface reference —
treat it as the contract docs must match.

## When to use

Behavior/API changed, a new option/helper/middleware/event was added, or you're
auditing existing docs for drift.

## Layout

- `docs/guide/*` — task guides (making-requests, async, streaming, caching,
  retry, middleware, events, pooling, logging, debugging, testing, …).
- `docs/api/*` — reference per class/helper/enum.
- `docs/examples/*` — end-to-end examples.
- `README.md` — overview + headline examples.
- `CHANGELOG.md` — record user-visible changes.

## Workflow

1. **Find every doc touching the changed surface**:

   ```bash
   grep -rn "methodOrOption" docs/ README.md
   ```

2. **Verify each example against the real API** — signatures, option names,
   return types, and enum cases must match `src/Fetch` and `CODE_MAP.md`. Common
   drift: renamed options, changed defaults (retry/timeout/redirect/cache),
   helper signatures (`fetch`, `fetch_stream`, `fetch_sse`, verb helpers), and
   streaming/SSE consumption patterns.
3. **Update `CODE_MAP.md`** if the public surface itself changed — it is the
   map other docs and tests rely on.
4. **Keep examples safe**: no real secrets/tokens/hosts; show TLS-on and
   redaction-aware patterns; don't demonstrate `verify => false` as a default.
5. **Add a `CHANGELOG.md`** entry for user-visible changes.
6. **Build**:

   ```bash
   npm run build
   rm -rf docs/.vitepress/.temp   # remove generated temp before committing
   ```

## Verification

- `npm run build` succeeds; no generated `docs/.vitepress/.temp` (or `dist`/
  `cache`) left staged.
- Every changed/added public symbol has matching docs; examples run against the
  current API.
- `CODE_MAP.md` and `CHANGELOG.md` reflect the change.

## Red flags — stop

- An example uses a signature/option/default that no longer exists.
- Docs claim a security guarantee the code doesn't provide (e.g. SSRF
  protection, auto-redaction of a field that isn't redacted).
- Committing `docs/.vitepress/dist`, `cache`, or `.temp`.
