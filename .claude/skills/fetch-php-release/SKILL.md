---
name: fetch-php-release
description: Prepare, publish, verify, and recover Fetch PHP releases through Git tags, GitHub Actions, GitHub Releases, and Packagist. Use when asked to make a new release, fix a failed Fetch PHP release, handle Packagist version immutability errors, update release notes, or verify release workflow/tag state for this repository.
---

# Fetch PHP Release

## Core Rules

- Treat stable Packagist versions as immutable. After Packagist observes a stable tag, never move that tag to new code.
- If a published stable tag points at the wrong commit, restore it to the previously published commit and tag a new patch release for the fix.
- Follow the repository's tag convention: numeric tags such as `3.5.1`, not `v3.5.1`.
- Keep `composer.json` without a `version` field. Package versions come from Git tags.
- Use `NO_NETWORK=1` for test gates unless deliberately testing network behavior.
- Do not rely on `gh auth status` alone. Public `gh run`/`gh release` commands may work even if an old default token is reported invalid.

## Normal Release Workflow

1. Inspect state.

```bash
git status --short --branch
git log --oneline --decorate -n 10
git tag --sort=-creatordate | head
gh run list --repo Thavarshan/fetch-php --limit 10
```

2. Choose the next version.

- Patch release for release-infra/doc fixes after a release: `x.y.(z+1)`.
- Minor release for planned release-readiness or user-visible docs/features: `(x).(y+1).0`.
- Never reuse a version that Packagist or GitHub has already published.

3. Update `CHANGELOG.md`.

- Set `Unreleased` compare link to the new tag.
- Add a section:

```markdown
## [vX.Y.Z](https://github.com/Thavarshan/fetch-php/compare/PREVIOUS...X.Y.Z) - YYYY-MM-DD
```

- Put release workflow fixes under `Changed`.
- Do not edit old release content unless correcting metadata after restoring an immutable tag.

4. Run local gates.

```bash
NO_NETWORK=1 composer check
npm run build
```

If `npm run build` creates `docs/.vitepress/.temp/`, remove it before committing:

```bash
rm -rf docs/.vitepress/.temp
```

5. Commit.

```bash
git add CHANGELOG.md composer.json .github/workflows/packages.yml .github/SUPPORT.md docs/guide/installation.md src/Fetch/Cache/CacheControl.php
git commit -m "Prepare vX.Y.Z release"
```

Only stage files that actually changed.

6. Create an annotated numeric tag.

```bash
git tag -a X.Y.Z -m "Release X.Y.Z"
```

7. Push.

```bash
git push origin main
git push origin X.Y.Z
```

8. Verify GitHub Actions and release.

```bash
gh run list --repo Thavarshan/fetch-php --workflow "Package & Release" --limit 5
gh run watch <run-id> --repo Thavarshan/fetch-php --exit-status
gh release view X.Y.Z --repo Thavarshan/fetch-php --json tagName,name,isDraft,isPrerelease,publishedAt,url
```

Also check the normal push workflows:

```bash
gh run list --repo Thavarshan/fetch-php --limit 10
```

## Packagist Immutability Recovery

Use this when Packagist emails that a stable version update was blocked because a source/dist reference changed.

1. Identify the references from the email.

- Previously published reference: `OLD_SHA`
- Newly observed reference: `NEW_SHA`
- Blocked version: `X.Y.Z`

2. Restore the published tag to `OLD_SHA`.

```bash
git tag -f -a X.Y.Z OLD_SHA -m "Release X.Y.Z"
git push --force origin X.Y.Z
```

This force push is acceptable only because it restores the tag to Packagist's already-published immutable reference.

3. Move the fix to a new patch release.

- Add `X.Y.(Z+1)` to `CHANGELOG.md`.
- Move any changelog bullet that belongs to `NEW_SHA` from `X.Y.Z` into `X.Y.(Z+1)`.
- Commit with `Prepare vX.Y.(Z+1) release`.
- Tag and push `X.Y.(Z+1)`.

```bash
git tag -a X.Y.N -m "Release X.Y.N"
git push origin main
git push origin X.Y.N
```

4. Correct GitHub release notes if needed.

If the old GitHub release body mentions changes that now belong to the new patch release, update the old release body so it matches `OLD_SHA`:

```bash
gh release edit X.Y.Z --repo Thavarshan/fetch-php --notes-file /tmp/release-X.Y.Z.md
```

Use a notes file extracted from the restored `CHANGELOG.md` section. Do not invent release notes.

5. Verify both tags.

```bash
git ls-remote --tags origin X.Y.Z X.Y.N
gh release view X.Y.Z --repo Thavarshan/fetch-php --json tagName,url,publishedAt
gh release view X.Y.N --repo Thavarshan/fetch-php --json tagName,url,publishedAt
```

## Release Workflow Guardrails

The tag workflow should:

- accept numeric tags and optional `v` tags;
- strip an optional `v` before semantic-version validation;
- not require `composer.json` to contain `version`;
- install all required extensions, including `pcntl`, `xml`, `dom`, `simplexml`, `tokenizer`, and `intl`;
- run release tests with `NO_NETWORK=1`.

If `Package & Release` fails in `composer check`, inspect job logs:

```bash
gh run view <run-id> --repo Thavarshan/fetch-php --log-failed
```

If the failure is caused by unplanned dev-tool drift and no lockfile is committed, prefer a narrow dev-tool constraint in `composer.json` over force-moving a published tag.
