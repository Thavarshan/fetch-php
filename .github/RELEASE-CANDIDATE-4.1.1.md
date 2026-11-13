# Fetch PHP v4.1.1 release candidate checklist

**Status:** Preparation only. No tag or GitHub Release has been created.

The intent is a narrow, backward-compatible patch release after the in-flight
maintenance PRs are merged and the public Composer distribution is verified.

## Candidate changes (include only once merged)

- [x] #191 / #167: mock allowlist correctly blocks other unmatched requests.
- [x] #190 / #147, #148: stale file-cache reads and shared-cache TTL precedence.
- [x] #192 / #146: bearer token option on the global `fetch()` helper.
- [x] #193 / #151: preserve user callback alongside redirect events.
- [x] #194 / #150: bound line size while streaming.
- [x] #195 / #152: remove unnecessary hard `ext-pcntl` requirement.
- [ ] #196 / #149: shared secret redaction for diagnostics.

The three earlier adoption PRs (#186, #188, #189) have already merged.

## Composer distribution verification

- [x] Fetch PHP 4.1.0 is indexed in Packagist's official Composer P2 API.
- [x] Matrix 4.0.0 is indexed in Packagist's official Composer P2 API.
- [x] The stale public HTML snapshot was a false alarm; #187 is closed.
- [ ] Inspect `Package & Release` workflow results for the previous tag.
- [ ] Confirm the Composer-installed version on the target PHP platform.
- [ ] On a clean machine, ensure `composer show jerome/matrix --all` and
  `composer show jerome/fetch-php --all` show intended versions.
- [ ] Create a disposable Composer project and install the latest **stable**
  Fetch PHP release. Do not test from a local checkout alone.

## Pre-tag checks

1. All included PRs merged to `main` with full PHP matrix green across
   supported PHP versions and operating systems.
2. No package-level breaking API change or hidden platform requirement.
3. No release-blocking security issue; track unresolved docs-only findings.
4. `composer validate --strict`, `composer check` and
   `composer docs:examples` pass in a clean checkout.
5. `npm ci`, `npm run typecheck`, `npm run build`,
   `npm run check-links` and browser smoke checks pass.
6. Changelog adds an accurate `## [v4.1.1]` section with **only merged**
   changes; update any in-product version refs if required.
7. Re-check the latest release/tag and ensure `4.1.1` does not exist.

## Publish (repository maintainer, after gates)

From a clean and updated checkout of `main`:

```sh
git checkout main
git pull --ff-only
composer validate --strict
composer check
composer docs:examples
npm ci
npm run typecheck
npm run build
npm run check-links

# Only after all checks and Packagist indexing are confirmed:
git tag -a 4.1.1 -m "Fetch PHP v4.1.1"
git push origin 4.1.1
```

The existing `.github/workflows/packages.yml` listens for version tags,
tests the release source, creates a GitHub Release, and verifies Packagist
indexing. If the workflow fails, do **not** make a duplicate tag or create
a second release until the cause is diagnosed. Never force-reuse release tags.

After the release completes:

- [ ] Inspect the GitHub Release and final release workflow.
- [ ] Confirm Packagist indexes `4.1.1` with matching source commit.
- [ ] Verify `composer require jerome/fetch-php:^4.1` on a clean PHP 8.3+
  project, plus Windows installation if #195 was merged.
- [ ] Check documentation examples against installed `4.1.1`.
- [ ] Post the honest v4.1.1 announcement and ask for technical feedback.
- [x] Close false-alarm issue #187 after P2 API verification.

## Not included in this patch

The ongoing async streaming transport, promise-first v5, advanced retry
policy, Laravel 13 parity, Redis caching, OpenAPI-to-MCP bridge, and future
AI SDK interoperability projects remain separate issues. Do not mark them
closed or advertise unreleased APIs as implemented.
