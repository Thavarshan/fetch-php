# Release Notes

## [Unreleased]

### Fixed

- Route `Client::fetch()`, request helpers, request builders, and fluent body
  setters through `RequestOptions` normalization. Request-scope body values now
  replace lower-scope defaults. Named JSON and form bodies retain their chosen
  serialization with custom Content-Type headers, while multipart boundaries
  remain transport-generated.
- Preserve `GlobalServices` defaults when a handler initializes its services.

### Deprecated

- Deprecate handler APIs that imply custom connection-pool or DNS-cache
  transport integration. They remain callable for source compatibility; use
  Guzzle's configured client and handler for transport behavior.
- Retain `buildFullUri()` for 4.x subclass compatibility with no scheduled
  removal release. It delegates to the context-based URI builder.

### Security

- The stable documentation graph remains blocked on VitePress 1.6.4's Vite 5
  dependency range. `npm audit` reports Vite, esbuild, and VitePress findings:
  GHSA-4w7w-66w2-5vf9, GHSA-v6wh-96g9-6wx3, GHSA-698v-4r3x-3vcv,
  GHSA-r825-w8mm-5pxw, GHSA-587h-7wmj-h5pm, GHSA-9jrq-w75r-8gcw,
  GHSA-rq7h-c2jc-7f22, GHSA-fx2h-pf6j-xcff, and GHSA-67mh-4wv8-2f99.
  No compatible stable VitePress update is available; the full development
  audit remains unresolved and is tracked in issue #131. Production audit is
  clean.

### Changed

- Restore upstream shadcn-vue Button, Tabs, and Tooltip variants and Slate
  semantic colors, retaining blue primary controls and VitePress navigation.
- Refine the labelled Composer surface, align hero columns at the start,
  reset accessible copy feedback, and contain mobile navigation focus.
- Present a normal application in Quickstart, highlight API signatures, and
  verify keyboard scrolling, sampled contrast, and responsive reading layouts.

- Replace the homepage grid workarounds with a supported custom Vue layout,
  shared Shiki examples, an integrated request/response workbench, and a
  consistent blue/slate design system across the homepage and documentation.
- Preserve VitePress's default component initialization and enforce Vue
  type-checking and deterministic browser smoke tests in documentation CI.

- Redesign the documentation as a reading-first VitePress site with a
  code-led homepage, local fonts, page-specific metadata, and the native
  navigation and code-copy controls. Correct the pool transport limitation and
  use deterministic Node 24 builds on Netlify and in documentation CI.

## [v4.0.1] - 2026-10-30

Release-automation patch. PHP runtime behavior and the public API are unchanged
from 4.0.0. For upgrades from 3.x, follow the
[upgrade guide](https://fetch-php.thavarshan.com/guide/upgrading).

### Fixed

- Verify the published Packagist version and exact source commit through the
  existing automatic update integration. Replace the redundant authenticated
  update request, which failed when optional credentials were unset even
  though the package was already available and installable.
- Bound indexing retries and fail publication verification on a source mismatch
  or indexing timeout; no Packagist credentials are required for this check.

## [v4.0.0] - 2026-10-30

This major release integrates the repository modernization in [#118], request
execution and validation in [#127], and documentation and release hardening in
[#130]. PHP 8.3 remains the minimum supported version.

### Added

- Opt-in JSON Schema draft 2020-12 validation through `opis/json-schema ^2.6`
  and OpenAPI 3.0.x contract validation through
  `league/openapi-psr7-validator ^0.24`. YAML support uses `symfony/yaml`
  or `ext-yaml`. These adapters are optional for consumers.
- Request/response policies, structured validation errors, and per-request
  validation options. Middleware, mock, cache, and transport responses pass
  through the configured validation policy.
- A ReactPHP-compatible `AsyncTransport` contract and cURL multi bridge for
  nonblocking buffered HTTP requests, plus dedicated request, async, streaming,
  builder, coordinator, promise, and pool configuration collaborators.
- TRACE and CONNECT methods and JSON media-type recognition for JSON:API,
  JSON-LD, GeoJSON, and Problem Details.
- Executable offline documentation examples and a comprehensive API/guide
  rewrite, including architecture, validation, troubleshooting, and upgrading.

### Changed

- Require `jerome/matrix ^4.0`. Keyed, lazy mapping bounds scheduled work,
  forwards cancellation, and stops queued tasks after failure. Handler mapping
  accepts concurrency from 1 to 200.
- Use one retry policy for synchronous and asynchronous HTTP execution. Async
  retries use Matrix timers, convert millisecond delays to seconds, preserve
  the final failure, and never retry cancellation.
- Use binary-safe multipart streams, immutable request contexts, shared default
  services, and cached response JSON decoding.
- Raise PHPStan to level 8, enforce 80% line coverage, and check the 80-column
  maintained-text policy on every CI run. Generated locks/build output and
  dependencies are excluded from width enforcement.
- Restrict workflow write permissions to the jobs that require them. Release
  jobs no longer request unused package-write permission.
- Pin Scorecard action 2.5.0 to its verified commit, restoring analysis after
  the old hosted image became unavailable.
- Allow maintainers to run the complete CI matrix manually against the final
  release commit, including when the last change only updates documentation.

### Fixed

- Preserve custom handler logger forwarding through the existing public
  contract and retain every explicit named response status helper.
- Keep PSR-18 dispatch synchronous when the shared handler is in async mode,
  without changing that handler's configuration.
- Isolate per-request options and base URIs, and resolve query options once
  using Guzzle's replacement semantics.
- Preserve JSON objects versus arrays, stream positions, non-seekable upload
  replay, middleware body edits, and multipart boundaries during validation.
- Wait for deferred middleware before classifying short circuits. Async mock
  and middleware short circuits return promises consistently.
- Preserve incremental Guzzle streaming while enabling the cURL multi driver.
  Unvalidated uploads are not read into memory before dispatch.
- Validate Basic-auth and matching cookie-jar headers independently and check
  the same query and materialized payload that the transport sends.
- Run the actual offline PHPUnit suite from the commit hook instead of calling
  a nonexistent Laravel `artisan` command and masking its failure.
- Preserve validation dependency/configuration exception types across sync and
  async response paths. Keep syntax-check temporary files outside the project
  so documentation checks and the width checker can run concurrently.
- Include `composer.json` in release archives and verify essential package
  files before publication.

### Security

- Update the documentation toolchain to stable VitePress 1.6.4 and compatible
  patched transitive dependencies. The production npm audit is clean; the
  remaining Vite/esbuild development-server advisories require an upstream
  stable-toolchain migration tracked in [#131].
- Partition cached responses by final Authorization and Cookie headers,
  including middleware transformations, Basic-auth options, cookie jars, and
  custom cache keys. Credential partitions use hashes, preventing cached data
  from being reused across those authentication contexts.
- Reject external OpenAPI references and unregistered JSON Schema references
  without fetching them. Validation errors use safe structured messages.
- Preserve the HTTP URI format checks; application SSRF controls are still
  required for untrusted destinations.

### Breaking changes and migration

- Update applications to `jerome/fetch-php ^4.0` and resolve any Matrix 3
  constraints. PHP remains `^8.3`; Guzzle remains `^7.9`.
- Custom Guzzle clients must supply a loop-compatible `AsyncTransport` for
  asynchronous calls. Missing drivers reject with `AsyncTransportException`.
  Synchronous and PSR-18 calls remain synchronous.
- Handle `Matrix\Exceptions\CancellationException` when cancelling promises.
  Async short circuits now always return promises; await or chain them.
- Public client convenience return unions include `PromiseInterface`.
  Subclasses overriding those methods must keep compatible signatures.
- Request execution now delegates to collaborators. Subclasses depending on
  protected execution internals should migrate custom transport behavior to
  `AsyncTransport` or middleware. The direct `ManagesPromises` trait remains
  available as a deprecated compatibility shim.
- Credential-aware cache keys change; clear persisted Fetch caches on upgrade.
  Custom cache keys still need to distinguish unrelated resources.
- Streaming/SSE remains synchronous and bypasses validation, middleware,
  retries, mocks, and caching. Disable validation policies for streaming.
- `buildFullUri()` remains deprecated; use `buildFullUriFromContext()`.

See the [upgrade guide] for code examples and transport migration details.

[Unreleased]: https://github.com/Thavarshan/fetch-php/compare/4.0.1...HEAD
[v4.0.1]: https://github.com/Thavarshan/fetch-php/compare/4.0.0...4.0.1
[v4.0.0]: https://github.com/Thavarshan/fetch-php/compare/3.8.1...4.0.0
[#118]: https://github.com/Thavarshan/fetch-php/pull/118
[#127]: https://github.com/Thavarshan/fetch-php/pull/127
[#130]: https://github.com/Thavarshan/fetch-php/pull/130
[#131]: https://github.com/Thavarshan/fetch-php/issues/131
[upgrade guide]: https://fetch-php.thavarshan.com/guide/upgrading

## [v3.8.1] - 2026-07-23

Development-tooling and documentation release. **No runtime library changes** —
the public API, behavior, and dependencies are unchanged.

### Added

- **Cross-agent AI development configuration** targeting Claude Code and OpenAI
  Codex while staying compatible with the `AGENTS.md` / Agent Skills standard: a
  canonical `AGENTS.md`, a `CLAUDE.md` that imports it, 10 task/review skills, 6
  specialist subagents, 7 path-scoped rules, conservative
  `.claude/settings.json` permissions with a destructive-command guard hook, a
  `docs/ai/` guide with license-verified sources, and a dependency-free
  validator (`composer ai:validate`).

### Changed

- Corrected stale contributor/AI instructions against the repository: removed
  references to a nonexistent `bin/` script directory, clarified that
  `composer lint` runs Duster (PHPStan is `composer analyse`), documented the
  PHP 8.3/8.4/8.5 CI matrix, aligned the `jerome/matrix ^3.4` constraint, and
  clarified `NO_NETWORK=1` semantics.

## [v3.8.0] - 2026-07-21

### Added

- **Request/Response Hooks & Events system**
  ([#41](https://github.com/Thavarshan/fetch-php/issues/41)) for observing the
  request lifecycle:
    - Six lifecycle events under `Fetch\Events`: `RequestEvent`
      (`request.sending`), `ResponseEvent` (`response.received`), `ErrorEvent`
      (`error.occurred`), `RetryEvent` (`request.retrying`), `TimeoutEvent`
      (`request.timeout`), and `RedirectEvent` (`request.redirecting`), all
      extending `FetchEvent`.
    - `Fetch\Events\EventDispatcher` (with `Fetch\Interfaces\EventDispatcher`) —
      a priority-aware dispatcher where a throwing listener is isolated and
      logged rather than breaking the request.
    - Fluent registration on `ClientHandler` and `Client`: `on()`,
      `onRequest()`, `onResponse()`, `onError()`, `onRetry()`, `onTimeout()`,
      `onRedirect()`, and `hooks([...])` with friendly aliases (`before_send`,
      `after_response`, `on_error`, `on_retry`, `on_timeout`, `on_redirect`).
    - A correlation ID generated per request and shared across every event for
      that request.
    - Events fire in both synchronous and asynchronous modes and for cache/mock
      hits; redirects are observed through a Guzzle `on_redirect` hook wired
      only when a redirect listener is registered. Dispatch is lazy — with no
      listeners registered the overhead is a single array check.

### Fixed

- Retry logging no longer throws `InvalidArgumentException: URI cannot be empty`
  for statelessly dispatched requests; `ClientHandler::logRetry()` now reads the
  raw URI option instead of resolving handler-state URIs.

## [v3.7.0] - 2026-07-21

### Added

- **Middleware/Interceptor system**
  ([#39](https://github.com/Thavarshan/fetch-php/issues/39)) for wrapping the
  request/response lifecycle with cross-cutting concerns:
    - `Fetch\Interfaces\Middleware` —
      `handle(RequestInterface $request, callable $next)` contract; plain
      callables with the same signature are accepted anywhere a middleware is.
    - `Fetch\Http\MiddlewarePipeline` — composes an ordered, outermost-first
      stack into a single onion around the core request handler;
      transport-agnostic across sync responses and async promises.
    - `Fetch\Concerns\ManagesMiddleware` — `addMiddleware()`, `middleware()`,
      `withoutMiddleware()`, and `getMiddleware()` on `ClientHandler` and
      `Client`, with priority ordering (highest runs first, stable on ties).
    - Conditional configuration via `when()` / `unless()`.
    - Middleware can modify the request, short-circuit with any PSR-7 response
      (coerced into a buffered `Response`), inspect/modify the response, and run
      in both synchronous and asynchronous modes. They sit outside the built-in
      mocking, caching, and retry logic.
    - Built-in middleware: `Fetch\Middleware\AddHeadersMiddleware` and
      `Fetch\Middleware\LoggingMiddleware`.

## [v3.6.0] - 2026-07-21

### Added

- **Streaming responses and Server-Sent Events** for consuming response bodies
  incrementally instead of buffering them into memory:
    - `Fetch\Http\StreamedResponse` — an unbuffered response returned by the new
      `stream()` methods; pull raw chunks via `stream()`, newline-delimited
      lines via `lines()`, iterate the object directly, or fall back to a
      buffered `Response` with `buffer()`.
    - `Fetch\Http\EventSource` — a WHATWG-compliant `text/event-stream` parser
      that lazily yields events and tracks `lastEventId()`/`reconnectionTime()`.
    - `Fetch\Http\ServerSentEvent` — an immutable event value object with
      `data`, `type`, `id`, and `retry` fields plus `json()` and `isDone()`
      helpers.
    - `stream()` and `sse()` methods on `ClientHandler` and `Client`, exposed
      through the `RequestExecutor` interface.
    - `fetch_stream()` and `fetch_sse()` global helper functions mirroring the
      existing `fetch()`/`get()` style.
    - `ContentType::EVENT_STREAM` and `ContentType::NDJSON` cases with
      `isEventStream()`/`isStreamable()` helpers.
- Added PHP 8.5 to the CI test matrix across Ubuntu, Windows, and macOS.

### Changed

- Streaming requests are synchronous and intentionally bypass the response
  cache, consistent with the library's existing sync-only caching behaviour.

## [v3.5.1] - 2026-06-06

### Changed

- Pinned Duster to the known-compatible 3.3.0 release so tag builds do not drift
  onto stricter formatter rules without an intentional style migration.

## [v3.5.0] - 2026-06-06

### Added

- Introduced a custom VitePress home page and shared docs theme components for a
  clearer first-run documentation experience.
- Added lucide-powered feature icons and refreshed documentation examples around
  the JavaScript-style Fetch PHP API.

### Changed

- Updated Matrix helper imports throughout the README and documentation to
  consistently use the `Matrix\Support` namespace.
- Refined homepage copy and feature descriptions to better explain Fetch PHP's
  JavaScript-like request flow, async helpers, caching, retries, debugging, and
  testing utilities.
- Hardened the tag release workflow now that package versions are derived from
  Git tags instead of a `version` field in `composer.json`.
- Expanded the tag release workflow setup to match package extension
  requirements and run the release test suite with `NO_NETWORK=1`.

### Fixed

- Corrected stale GitHub/support links in the installation guide and repository
  support metadata.
- Removed a stale cache comment that referenced the old `ManagesCache` trait
  name.

## [v3.4.1] - 2025-12-14

### Added

- Introduced `CODE_MAP.md` as an internal reference of every public helper,
  trait, interface, and subsystem so docs/tests can stay aligned with the
  codebase.
- Added a FetchWatch-style “health monitor” example demonstrating retries,
  pooling, caching, async batching, and debug snapshots in
  `docs/examples/api-integration.md`.

### Changed

- Expanded the API docs for `fetch()`, `ClientHandler`, and `Response` to cover
  the full option surface (cache/debug/profiler/async) and newly exposed helpers
  (debug snapshots, pooling controls, cache methods).
- Clarified retry monitoring guidance to rely on PSR-3 logs or explicit counters
  instead of the `debug()` array, and documented sync-only caching behaviour for
  async requests.

### Fixed

- Removed references to the non-existent `Fetch\Exceptions\TimeoutException`
  across guides and examples, explaining how timeouts bubble up via
  `NetworkException` or Matrix’s `TimeoutException`.
- Updated error-handling documentation to disambiguate timeout handling and
  ensure users catch the correct exception classes.

## [v3.4.0] - 2025-12-07

### Added

- **RFC 7234 HTTP Caching Support** with intelligent cache control:
    - `MemoryCache` for fast in-memory caching
    - `FileCache` for persistent disk-based caching with TTL support
    - `CacheControl` parser for RFC 7234 compliant cache directive handling
    - `CacheKeyGenerator` with collision-resistant SHA-256 hashing
    - `CachedResponse` wrapper with metadata and validation support
    - `ManagesCache` trait integrated into `ClientHandler` for transparent
      caching
    - Automatic cache revalidation with `If-None-Match` and `If-Modified-Since`
      headers
    - Support for `Cache-Control`, `ETag`, `Last-Modified`, `Expires`, `Age`,
      and `Vary` headers
    - Configurable cache TTL, stale responses, and revalidation strategies
    - 802 new test assertions for caching functionality

- **Connection Pooling & HTTP/2 Support** for improved performance:
    - `ConnectionPool` for managing persistent connections across hosts
    - `HostConnectionPool` for per-host connection management with limits
    - `DnsCache` for caching DNS lookups to reduce resolution overhead
    - `Connection` class for tracking connection state and reuse
    - `PoolConfiguration` for customizable pool settings (max connections, idle
      timeout, TTL)
    - `Http2Configuration` for HTTP/2 multiplexing and push support
    - `ManagesConnectionPool` trait with connection pool lifecycle management
    - Automatic connection recycling and health monitoring
    - 558 new test assertions for connection pooling

- **Debugging & Profiling Tools** for request analysis:
    - `DebugInfo` class capturing detailed request/response information
    - `FetchProfiler` for comprehensive performance metrics and bottleneck
      detection
    - `ManagesDebugAndProfiling` trait for debug mode support
    - Sensitive header redaction and sanitization methods (Authorization, API
      keys, tokens)
    - Timing breakdown: DNS lookup, connection, TLS handshake, TTFB, transfer
      times
    - Memory usage tracking and cURL transfer statistics
    - Debug mode with detailed logging and inspection capabilities
    - 570 new test assertions for debugging/profiling features

- New interfaces for extensibility:
    - `CacheableHandler` for cache manager access
    - `CacheableRequestHandler` for cache operations
    - `DebuggableHandler` for debug/profiling support
    - `PoolAwareHandler` for connection pool management

### Changed

- Refactored `ClientHandler` interface to extend multiple focused interfaces for
  better separation of concerns
- Enhanced `PerformsHttpRequests` trait with integrated caching support
- Improved PHPDoc comments with detailed parameter types for better IDE support
  and static analysis
- Updated method signatures in `RequestConfigurator` to return specific types
  instead of `self`
- Streamlined pre-commit script to use Composer commands instead of shell
  commands
- Enhanced type hints across `CacheManager`, `ManagesDebugAndProfiling`,
  `ManagesRetries`, and `PerformsHttpRequests` traits

### Fixed

- Fixed timing precision issues in delay tests on Windows (#72)
- Fixed PHPStan type inference for `ClientHandler` method chaining
- Improved TTL behavior consistency in cache implementations
- Enhanced null handling and race condition prevention in cache operations
- Fixed status code handling consistency across caching layer
- Improved URI handling in connection pool exceptions
- Corrected time-to-first-byte calculation in profiling metrics
- Enhanced code style consistency: proper namespace imports, class element
  ordering, and PHPDoc cleanup

**Full Changelog**:
<https://github.com/Thavarshan/fetch-php/compare/v3.3.0...v3.4.0>

## [v3.3.0] - 2025-01-17

### Added

- **Comprehensive Testing Utilities** inspired by Laravel's HTTP client testing:
    - `MockServer` with URL pattern matching (wildcards, method-specific,
      callbacks)
    - `MockResponse` fluent builder with convenience methods for all HTTP status
      codes
    - `MockResponseSequence` for testing retry logic and flaky endpoints
    - `Recorder` for capturing and replaying request/response pairs
    - Comprehensive assertion helpers (`assertSent()`, `assertNotSent()`,
      `assertSentCount()`, `assertNothingSent()`)
    - Request recording with JSON export/import for test fixtures
    - Stray request prevention with allowlist support
- `HandlesMocking` trait integrated into `ClientHandler` for request
  interception
- `Request::createFromBase()` method for PSR-7 request conversion
- 117 new tests with 288 assertions for testing utilities
- Comprehensive testing documentation in `docs/guide/testing.md` (617 lines)
- Complete API reference in `docs/api/testing.md` (539 lines)
- Testing utilities section in VitePress sidebar navigation

### Changed

- Updated `PerformsHttpRequests` to support mock request interception
- Enhanced `ClientHandler` with `HandlesMocking` trait for testing support

### Fixed

- Fixed async helper function imports to use correct `Matrix\Support\*`
  namespace:
    - Updated imports in `ManagesPromises` trait (`async`, `await`, `all`,
      `any`, `race`, `reject`, `resolve`, `timeout`)
    - Updated imports in `PerformsHttpRequests` trait (`async`)
    - Updated imports in `ManagesPromisesTest` and `AsyncRequestsTest`
- All 282 tests now passing with 841 assertions

### Removed

- Removed Dependabot configuration (`dependabot.yml`)
- Removed Dependabot auto-merge workflow

**Full Changelog**:
<https://github.com/Thavarshan/fetch-php/compare/v3.2.3...v3.3.0>

## [v3.2.3] - 2025-05-24

### Added

- Comprehensive test coverage for array body handling in
  `ConfiguresRequestsTest`
- Explicit option conflict prevention in request body configuration
- Better error prevention for Guzzle HTTP client integration

### Changed

- `ConfiguresRequests::withBody()` method now ensures mutual exclusivity of
  body-related options
- Request option handling now explicitly removes conflicting options (`json`,
  `body`, `form_params`, `multipart`)
- Improved reliability of JSON array requests by preventing option conflicts

### Fixed

- **BREAKING FIX**: Resolved Guzzle `InvalidArgumentException` when sending POST
  requests with array bodies
- Fixed issue where both `json` and `body` options were set simultaneously,
  causing Guzzle to reject requests
- Eliminated "Passing in the 'body' request option as an array to send a request
  is not supported" error
- Restored ability to send structured JSON data using PHP arrays in request
  bodies
- Fixed helper functions (`post()`, `put()`, `patch()`, etc.) failing with array
  payloads

**Full Changelog**:
<https://github.com/Thavarshan/fetch-php/compare/3.2.2...3.2.3>

## [v3.2.2] - 2025-05-19

### Fixed

- "Fatal error: Uncaught Error: Interface `Psr\Log\LoggerAwareInterface` not
  found" closes #21

## [v3.2.1] - 2025-05-17

### Changed

- Updated documentation
- Updated dependencies

## [v3.2.0] - 2025-05-16

### Added

- Added tests for various HTTP methods (GET, POST, PUT, DELETE, PATCH, OPTIONS)
  with appropriate assertions.

### Changed

- Simplified **`ManagesRetriesTest`** by using a mock class for retry-logic
  testing.
- Consolidated tests for retryable status codes and exceptions into a single
  method.
- Updated **`PerformsHttpRequestsTest`** to use `GuzzleHttp` client mocks for
  more accurate request simulation.
- Enhanced exception-handling tests to verify error messages and retry
  behaviour.
- Removed unnecessary reflection methods and streamlined test setup.

### Fixed

- Improved back-off delay calculations in retry tests to ensure correct timing.

**Full Changelog**:
[Full changelog][v3.2.0]

## [v3.1.1] - 2025-05-16

### Added

- Implemented the missing `finalizeRequest()` method in the
  `PerformsHttpRequests` trait. This method centralizes request finalization
  logic, enabling internal shortcut methods like `get()`, `post()`, etc., to
  function correctly.

### Changed

- Internal shortcut HTTP methods (`get()`, `post()`, `put()`, etc.) in
  `PerformsHttpRequests` now route through the newly added `finalizeRequest()`
  method for consistent request handling.

### Fixed

- Fixed a fatal error caused by calling an undefined `finalizeRequest()` method
  in `ClientHandler`. The missing method has now been properly defined and
  implemented.

## [v3.1.0] - 2025-05-10

### Added

- **PSR-18 Client**: `Fetch\Http\Client` now implements
  `Psr\Http\Client\ClientInterface` for drop-in interoperability.

- **Fluent Request Builder**: Chainable helpers on `ClientHandler` for headers,
  query params, JSON/form/multipart bodies, bearer token, basic auth, timeouts,
  redirects, cookies, proxy, certificates.

- **Async/Promise Support**: Built-in ReactPHP-style promises (`async()`,
  `await()`, `all()`, `race()`, `any()`, `sequence()`), with `->async()` toggle
  and `wrapAsync()`/`awaitPromise()` helpers.

- **Automatic Retries**: Configurable max retries, retry delay, exponential
  backoff with jitter, and retry-on-status (408, 429, 5xx) or exceptions
  (`ConnectException`).

- **PSR-3 Logging**: Optional `LoggerInterface` injection on `Client` and
  `ClientHandler` with info/debug/error logs and sensitive-data masking for
  retries, requests, and responses.

- **Immutable PSR-7 Extensions**:

    - `Fetch\Http\Request` extends Guzzle’s PSR-7 `Request` with immutability
      traits and JSON/form/multipart constructors.
    - `Fetch\Http\Response` extends Guzzle’s PSR-7 `Response` with buffered
      body, array-access to JSON payloads, and helpers: `->json()`, `->text()`,
      `->xml()`, `->blob()`, `->arrayBuffer()`, status inspectors, etc.

- **Enums for Safety**: `Fetch\Enum\Method`, `ContentType`, and `Status` enums
  for validating methods, content types, and status codes.

- **Test Helpers**: `ClientHandler::createMockResponse()` and
  `createJsonResponse()` to easily stub HTTP responses in unit tests.

### Changed

- **Consolidated Handler Traits**: Core behavior refactored into six concerns
  (`ConfiguresRequests`, `HandlesUris`, `ManagesPromises`, `ManagesRetries`,
  `PerformsHttpRequests`, `SendsRequests`) for clearer separation of URI
  handling, retries, promises, and request dispatch.
- **Unified Fetch API**: `Client::fetch()` now returns either a
  `ResponseInterface` or the handler for method chaining, replacing older ad-hoc
  patterns.
- **Error-handling Alignment**: Top-level `Client::sendRequest()` now throws
  standardized `NetworkException`, `RequestException`, or `ClientException`
  instead of raw `RuntimeException`.
- **Default Options Management**: Moved default method, headers, and timeout
  into `ClientHandler::$defaultOptions` with new `getDefaultOptions()` and
  `setDefaultOptions()`.
- **Guzzle Configuration**: `ClientHandler` now reuses a single Guzzle client
  instance with `RequestOptions::HTTP_ERRORS` disabled (handling errors
  internally) and `CONNECT_TIMEOUT` mapped from handler timeout.

### Removed

- None

---

## [v3.0.0] - 2025-05-04

### Added

- **True Asynchronous Support**: Completely reimplemented asynchronous
  functionality using Matrix’s PHP Fiber-based library.

- **JavaScript-like Syntax**: Added support for JavaScript-like async/await
  patterns with `async()` and `await()` functions.

- **Promise-based API**: Introduced a clean Promise interface with `then()`,
  `catch()`, and `finally()` methods.

- **Concurrent Request Helpers**: Added support for managing multiple concurrent
  requests with `all()`, `race()`, and `any()` functions.

- **Task Lifecycle Management**: Implemented proper task lifecycle control
  (start, pause, resume, cancel, retry).

- **Enhanced Error Handling**: Added improved error handling with customizable
  error handlers.

- **New Helper Methods**:

    - `wrapAsync()`: For wrapping callables in async functions
    - `awaitPromise()`: For awaiting promise resolution

### Changed

- **New Matrix Integration**: Migrated from legacy `AsyncHelper` to the new
  Matrix Fiber-based promises.
- **Return Type Changes**: Updated method signatures to use union types
  (`ResponseInterface|PromiseInterface`).
- **Simplified API**: Streamlined the API to match JavaScript’s fetch pattern
  more closely.
- **Improved Retry Logic**: Enhanced retry mechanisms for both synchronous and
  asynchronous requests.
- **Updated Documentation**: Completely revised documentation to reflect the new
  async patterns.

### Removed

- **AsyncHelper Class**: Removed the legacy `AsyncHelper` class in favor of
  direct Matrix integration.

### Fixed

- **Promise Handling**: Fixed various issues with Promise resolution and
  rejection.
- **Retry Mechanism**: Fixed retry logic to properly handle both network and
  server errors.
- **Error Propagation**: Improved how errors are propagated through Promise
  chains.
- **Event Loop Management**: Fixed event loop management for proper async task
  execution.

---

## [v2.0.6] - 2025-05-03

### Added

- Added `withQueryParameter()` method for adding a single query parameter.
- Added `withJson()` method as a convenient way to set JSON request bodies.
- Added `withFormParams()` and `withMultipart()` helper methods for form
  submissions.
- Added `configurePostableRequest()` helper method to standardize request body
  handling.

### Changed

- Enhanced `withBody()` method to support multiple content types (JSON,
  form-encoded, multipart).
- Improved `post()` and `put()` methods to properly handle different content
  types.
- Improved retry mechanism with exponential backoff and jitter for better
  reliability.
- Enhanced error handling to be more selective about which errors trigger
  retries.

### Fixed

- Fixed query parameter handling to properly merge with existing parameters
  instead of overwriting.
- Fixed URL construction in `getFullUri()` to correctly append query parameters.
- Fixed retry logic to perform exactly the specified number of retries (not one
  extra).
- Fixed duplicate implementation of `isRetryableError()` method.
- Fixed retry failure detection to properly identify the last retry attempt.

---

## [v2.0.5] - 2025-03-30

### Added

- `getOptions()`, `getHeaders()`, `hasHeader(string $header)`, and
  `hasOption(string $option)` methods on `ClientHandler`.

**Full Changelog**:
[Full changelog][v2.0.5]

---

## [v2.0.4] - 2025-03-30

### Added

- Support for setting a single request header via `withHeader()` in
  `ClientHandler`.
- `withToken(string $token): self`,
  `withAuth(string $username, string $password): self`, and
  `withHeader(string $header, mixed $value): self` methods on `ClientHandler`.
- Added `laravel/pint` as a dev dependency.

### Changed

- Bumped `composer.json` to 2.0.4.
- Bumped `dependabot/fetch-metadata` from 2.2.0 to 2.3.0.
- README syntax fix.

### Fixed

- Restored `jerome/matrix` and `psr/http-message` dependencies.

### New Contributors

- @tresbach in
  [Pull request #14][pr-14]

**Full Changelog**:
[Full changelog][v2.0.4]

---

## [v2.0.3] - 2024-12-06

### Added

- Support for all PHP 8.x versions.

### Changed

- Updated dependencies and dev-dependencies.

**Full Changelog**:
[Full changelog][v2.0.3]

---

## [v2.0.2] - 2024-10-19

### Added

- Added Laravel Pint integration.
- `isAsync()` method on `ClientHandler`.

### Changed

- Updated `async()` method to accept arguments.

#### New Contributors

- @patinthehat in
  [Pull request #8][pr-8]

**Full Changelog**:
[Full changelog][v2.0.2]

---

## [v2.0.1] - 2024-10-03

### Changed

- Refactored `withBody()` in `ClientHandler` to accept only arrays and
  JSON-encode them.
- Documentation and typo fixes.

---

## [v2.0.0] - 2024-09-30

### Added

- VitePress documentation site under `./docs/`.
- PHP-Fiber-powered `async()` for JavaScript-like async/await.
- Fluent, chainable HTTP API (headers, body, token, auth, etc.).
- Task lifecycle control (pause, resume, cancel, retry).
- Retry logic with exponential backoff.

### Changed

- Refactored core `fetch()` for seamless sync/async.
- Enhanced error handling and promise rejection.
- Overhauled `Response` class for JSON, text, binary, and streams.
- Exposed Guzzle options (proxy, cookies, redirects, SSL).

### Removed

- Deprecated `fetchAsync` method.
- Symfony `Response` dependency.

### Fixed

- Laravel 10 / Symfony 6 compatibility.
- Async error handling.
- Persistent Guzzle client reuse.

---

## [v1.2.0] - 2024-09-27

### Added

- Guzzle PSR-7 support via `guzzlehttp/psr7`.
- Improved error handling with Guzzle’s `RequestException`.
- Updated README for PSR-7 usage.

### Changed

- `Response` now extends `guzzlehttp/psr7\Response`.
- Refactored HTTP class for PSR-7 compliance.
- Removed Symfony `Response` dependency.

### Fixed

- Laravel 10 compatibility.
- Composer dependency conflicts.
- Async exception handling.

---

## [v1.1.1] - 2024-09-25

### Changed

- Updated `symfony/http-foundation` to `^6.0 || ^7.0`.
- Expanded GitHub Actions test matrix for Laravel 9–11.

### Fixed

- Resolved Laravel 10 install conflict.

---

## [v1.1.0] - 2024-09-24

### Changed

- Abstracted HTTP handling into a new `Http` class.
- Guzzle client reused as a singleton.
- Deprecated `fetchAsync` in favor of `fetch_async`.

### Fixed

- Single-instance Guzzle instantiation.

---

## v1.0.0 - 2024-09-14

Initial release.

[v3.8.1]: https://github.com/Thavarshan/fetch-php/compare/3.8.0...3.8.1
[v3.8.0]: https://github.com/Thavarshan/fetch-php/compare/3.7.0...3.8.0
[v3.7.0]: https://github.com/Thavarshan/fetch-php/compare/3.6.0...3.7.0
[v3.6.0]: https://github.com/Thavarshan/fetch-php/compare/3.5.1...3.6.0
[v3.5.1]: https://github.com/Thavarshan/fetch-php/compare/3.5.0...3.5.1
[v3.5.0]: https://github.com/Thavarshan/fetch-php/compare/3.4.1...3.5.0
[v3.4.1]: https://github.com/Thavarshan/fetch-php/compare/v3.4.0...v3.4.1
[v3.4.0]: https://github.com/Thavarshan/fetch-php/compare/v3.3.0...v3.4.0
[v3.3.0]: https://github.com/Thavarshan/fetch-php/compare/v3.2.3...v3.3.0
[v3.2.3]: https://github.com/Thavarshan/fetch-php/compare/v3.2.2...v3.2.3
[v3.2.2]: https://github.com/Thavarshan/fetch-php/compare/v3.2.1...v3.2.2
[v3.2.1]: https://github.com/Thavarshan/fetch-php/compare/v3.2.0...v3.2.1
[v3.2.0]: https://github.com/Thavarshan/fetch-php/compare/v3.1.1...v3.2.0
[v3.1.1]: https://github.com/Thavarshan/fetch-php/compare/v3.1.0...v3.1.1
[v3.1.0]: https://github.com/Thavarshan/fetch-php/compare/v3.0.0...v3.1.0
[v3.0.0]: https://github.com/Thavarshan/fetch-php/compare/v2.0.6...v3.0.0
[v2.0.6]: https://github.com/Thavarshan/fetch-php/compare/v2.0.5...v2.0.6
[v2.0.5]: https://github.com/Thavarshan/fetch-php/compare/v2.0.4...v2.0.5
[v2.0.4]: https://github.com/Thavarshan/fetch-php/compare/v2.0.3...v2.0.4
[v2.0.3]: https://github.com/Thavarshan/fetch-php/compare/v2.0.2...v2.0.3
[v2.0.2]: https://github.com/Thavarshan/fetch-php/compare/v2.0.1...v2.0.2
[v2.0.1]: https://github.com/Thavarshan/fetch-php/compare/v2.0.0...v2.0.1
[v2.0.0]: https://github.com/Thavarshan/fetch-php/compare/v1.2.0...v2.0.0
[v1.2.0]: https://github.com/Thavarshan/fetch-php/compare/v1.1.1...v1.2.0
[v1.1.1]: https://github.com/Thavarshan/fetch-php/compare/v1.1.0...v1.1.1
[v1.1.0]: https://github.com/Thavarshan/fetch-php/compare/v1.0.0...v1.1.0
[pr-14]: https://github.com/Thavarshan/fetch-php/pull/14
[pr-8]: https://github.com/Thavarshan/fetch-php/pull/8
