---
paths:
  - "src/Fetch/Concerns/HandlesUris.php"
  - "src/Fetch/Concerns/ConfiguresRequests.php"
  - "src/Fetch/Http/Request.php"
  - "src/Fetch/Middleware/**/*.php"
  - "src/Fetch/Cache/**/*.php"
  - "src/Fetch/Pool/**/*.php"
  - "src/Fetch/Support/DebugInfo.php"
  - "src/Fetch/Support/ProfilerBridge.php"
---

# HTTP security rules

Security-sensitive code. Assume attacker-controlled URLs, headers, and bodies.

- **Secrets never leak.** Auth headers, bearer/basic tokens, cookies, proxy
  creds, and query-string secrets must stay redacted in logs, `DebugInfo`,
  profiler output, exceptions, tests, and docs. Mark secret-bearing params
  `#[\SensitiveParameter]`.
- **No SSRF claims.** `HandlesUris` validates format only; it does not block
  private/internal hosts. Don't imply the library prevents SSRF; flag new paths
  where user input reaches a request URL/redirect/proxy.
- **Fail closed.** On validation failure (e.g. `preg_match` returns `false`),
  reject — never fall through. Compare header names case-insensitively and
  locale-independently.
- **TLS on by default.** Never introduce a default that disables `verify` or
  follows redirects while forwarding `Authorization`/`Cookie` cross-origin.
- **Cache isolation.** Cache keys must vary on the dimensions that affect the
  body; a shared cache must not serve one principal's authorized response to
  another.
- Deeper review: `fetch-php-http-security-review`.
