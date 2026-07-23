---
name: fetch-php-http-security-review
description: Security review for Fetch PHP's HTTP behavior — SSRF, header/credential leakage, redirect and proxy handling, TLS verification, logging/debug redaction, cache isolation, and unsafe URI handling. Use when changing URL/URI handling, auth, redirects, proxies, TLS, logging/profiling, caching, or middleware/events.
---

# Fetch PHP HTTP security review

Read-only, adversarial review. Assume attacker-controlled URLs, headers, and
response bodies. Report each finding as `file:line` → concrete attack → fix.

## When to use

Any change to: URI building/validation (`HandlesUris`), auth
(`withBearerToken`/`withBasicAuth`, `Authorization`), redirects, proxies, TLS
(`verify`, `cert`, `ssl_key`), logging/debug/profiling redaction
(`LoggingMiddleware`, `DebugInfo`, `ProfilerBridge`), caching
(`CacheKeyGenerator`, `is_shared_cache`), DNS/pooling, or middleware/events.

## Threats to check

1. **SSRF / unsafe URLs.** `HandlesUris` validates *format* only (rejects
   whitespace, requires a base URI for relative paths) — it does **not** block
   private/link-local/metadata hosts or restrict schemes beyond
   `FILTER_VALIDATE_URL`. Flag any new path where user input reaches a request
   URL, base URI, redirect target, or proxy without the caller opting into
   host/scheme restrictions. Do not claim the library prevents SSRF.
2. **Credential & header leakage.** Auth headers, bearer/basic tokens, cookies,
   proxy credentials, and query-string secrets must never appear unredacted in
   logs, `DebugInfo` snapshots, profiler output, exception messages, or docs
   examples. Verify redaction covers new sinks. Prefer marking secret-bearing
   parameters `#[\SensitiveParameter]` so they are scrubbed from stack traces.
3. **Redirect behavior.** Check whether headers (especially `Authorization`/
   `Cookie`) are forwarded across host/scheme changes on redirect, and whether
   redirect count/limits are enforced. Cross-origin credential forwarding is a
   leak.
4. **TLS.** Verification must stay on by default; a change that sets
   `verify => false`, weakens cert checks, or exposes an easy "disable TLS"
   default is a fail-open regression.
5. **Cache isolation.** `CacheKeyGenerator` must incorporate the right `vary`
   dimensions; a shared cache (`is_shared_cache`) must not serve one user's
   authorized response to another. Never cache responses keyed without auth
   context when auth affects the body.
6. **Header/URI correctness.** Compare header names case-insensitively and
   locale-independently; fail *closed* on validation errors (`preg_match`
   returning `false`), never fail open. Watch for CRLF/whitespace injection into
   headers or URIs and request-smuggling-style ambiguity.
7. **Response body handling.** Untrusted bodies (JSON/XML) parsed safely; no
   unbounded buffering of attacker-controlled streams (see
   `fetch-php-performance-review`).

## Verification

- Trace one concrete attacker input end-to-end to the sink for each finding.
- Confirm redaction with a test asserting the secret is absent from output.
- `NO_NETWORK=1 composer check` still green after any hardening.

## Red flags — stop and escalate

- A new default that disables TLS verification or follows redirects with auth.
- A secret rendered in a log/debug/exception/test fixture/doc example.
- Cache keys or vary logic that could cross user/auth boundaries.
- Validation that fails open, or trusts `filter_var` alone as an SSRF control.
