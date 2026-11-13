# Fetch PHP community launch kit

Editorial assets for a **zero-budget, evidence-based** Fetch PHP launch. These
are drafts, not published announcements. This folder is outside the VitePress
docs site intentionally: reviewing a PR must not automatically publish social
posts.

## Positioning

> JavaScript-inspired `fetch()` for modern PHP. A Guzzle-powered client with a
familiar request entry point, plus opt-in retries, middleware, streaming, async
workflows and PSR interoperability.

**Use:** "JavaScript-inspired", "Guzzle-powered", "PHP 8.3+",
"framework-independent".
**Avoid:** "Browser Fetch polyfill", "drop-in replacement for browser fetch",
"faster than Guzzle", "fully nonblocking by default", "works on every PHP
installation", or unverified AI streaming claims.

## Release gate (BLOCKER)

Before promoting current v4 functionality, resolve
[Issue #187](https://github.com/Thavarshan/fetch-php/issues/187)
. The publicly displayed stable Packagist version was 3.8.1 on 2026-11-14 while
GitHub had released 4.1.0. Never assume a GitHub tag means a Composer install
will resolve it. Confirm a disposable Composer installation matches the
documented examples.

Also review
[Issue #152](https://github.com/Thavarshan/fetch-php/issues/152)
before promising cross-platform compatibility. For AI streams, respect the
product gates in #155, #156 and #160.

## Ready-to-review assets

- [JavaScript's fetch() in PHP](posts/javascript-fetch-in-php.md) — primary
  technical article, includes strengths *and* limitations.
- 
  [Testing HTTP calls without a network](posts/testing-http-requests-offline.md)
  — article based on the existing README's documented mock API.
- [LinkedIn launch post](posts/linkedin-launch.md) — conversation-first short
  form.
- [Reddit community discussion](posts/reddit-discussion.md) — post only where
  self-promotion rules permit, with substantive code and questions.
- [PHP Weekly pitch](posts/php-weekly-pitch.md) — editorial suggestion, no
  promise of placement.
- [Owner launch checklist](LAUNCH-CHECKLIST.md) — step-by-step permissions,
  publication and measurement.

## Suggested four-week distribution

- **Week 1:** Repair #187, verify the docs and installation, and record
  initial Packagist/GitHub measurements.
- **Week 2:** Publish the JavaScript/PHP comparison, then share a native
  code example and request feedback on LinkedIn.
- **Week 3:** Publish offline HTTP testing and pitch the best article to
  PHP Weekly.
- **Week 4:** Share a tested demo in a suitable community, respond to
  feedback and update the docs.

Do not cross-post identical text to multiple communities on the same day. Ask
for developer feedback, not artificial stars. For outside publications, add
canonical URLs only when the platform supports them.

## Evidence and tracking

At launch and days 7/30/90, record stars, forks, issue/PR participation,
Packagist installs and (from owner Insights) GitHub traffic/referrers. GitHub
stars do not prove adoption; unique visitors and actual production usage are
different signals. Do not calculate "visitor-to-star conversion" unless the
populations/time windows match, and label such ratios as indicative.

See the repo's existing adoption initiative
[#162](https://github.com/Thavarshan/fetch-php/issues/162) , which owns the
broader program.
