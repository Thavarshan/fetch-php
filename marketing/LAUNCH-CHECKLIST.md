# Owner-only launch checklist

This document contains the manual tasks we **cannot complete by editing GitHub
source**.

## First: verify Composer package distribution (blocking)

1. Open https://packagist.org/packages/jerome/fetch-php and sign in using the
   Packagist owner account.
2. Check the listed versions. At the 2026-11-14 audit, latest shown stable was
   `3.8.1` , whereas GitHub had tagged `4.1.0` . If versions still disagree,
   open the package's Update action, inspect the resulting status/errors, and
   check whether the GitHub Packagist webhook is active.
3. In GitHub, open repository **Settings → Webhooks** and inspect Packagist
   delivery events; check **Actions → Package & Release** for the v4.1.0 tag's
   post-release verification results. Do not replace a working webhook blindly.
4. In a disposable directory, use:
   ```sh
   composer clear-cache
   composer show jerome/fetch-php --all
   composer require jerome/fetch-php
   composer show jerome/fetch-php
   ```
Create a `composer.json` using
`composer init --no-interaction --name=local/fetch-php-smoke` if needed. Verify
PHP >= 8.3 and platform extensions before attributing install failures to
Packagist.
5. Ensure the Composer-installed version matches release notes and examples.
   Update [issue #187](https://github.com/Thavarshan/fetch-php/issues/187) with
   results; **never share package tokens**.

## Next: merge and deploy reviewed source changes

1. Review the draft onboarding PR and marketing-assets PR for accuracy.
2. Check the PR Actions tab. Run `composer docs:examples` , `npm ci` ,
   `npm run typecheck` , `npm run build` and `npm run check-links` if CI does
   not cover them.
3. Verify the docs landing page at desktop and mobile widths, and check the
   public site after deployment.
4. Update the GitHub repository description under **About → gear icon** to a
   concise statement that fits GitHub's character limit, e.g.
   “JavaScript-inspired fetch() for PHP 8.3+. Guzzle-powered requests, retries,
   streaming and PSR support.”
5. Add an appropriate **Social preview** image in **Settings → General → Social
   preview**. Test its appearance when sharing the repo link. Avoid screenshots
   with tokens or personal credentials.
6. Under **Settings → General → Features**, enable **Discussions** if you will
   actively moderate it. Start with a welcome discussion and invite bug reports
   to Issues instead. Do not enable it if you cannot maintain it.
7. Do not change `ext-pcntl` solely for marketing. Resolve compatibility in
   [#152](https://github.com/Thavarshan/fetch-php/issues/152) with actual tests
   before promising Windows support.

## Publish after the release gate

1. Publish `posts/javascript-fetch-in-php.md` on your own domain or DEV
   Community, replacing publication notes and testing all snippets against the
   installable release.
2. If publishing both on your own domain and DEV, use a canonical URL where
   supported.
3. Share the short LinkedIn draft as your own post with a meaningful code
   example.
4. Publish `posts/testing-http-requests-offline.md` at least a few days later.
5. Only post the Reddit draft if the community's current rules permit it;
   tailor it to discussion, not soliciting votes.
6. Suggest your best public article to PHP Weekly via their official
   contact/submission channel. Do not claim editorial acceptance.
7. Respond to comments, issues and new PRs; write down recurring developer
   questions.

## Check outcomes (launch day, +7, +30, +90)

- In **Insights → Traffic**, record views, unique visitors, clones and
  referrers (GitHub normally displays short lookback windows; capture it
  regularly).
- Record stars/forks, external PRs, helpful feedback and Packagist installs.
- Save each snapshot dated and compare *changes*, not unrelated totals.
- Pause low-quality or repetitive posting; invest in the tutorial that produces
  meaningful developer questions and installs.

## What not to do

No paid/fake stars, engagement swaps, unsolicited mass DMs, fabricated
testimonials, untested performance benchmarks, or spam PRs to awesome lists.
