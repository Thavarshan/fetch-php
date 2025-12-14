# FetchPHP Code Map

This reference captures the concrete public surface of the FetchPHP library as implemented under `src/Fetch`. Use it to keep documentation and tests aligned with the code.

---

## Global Helper Surface (`src/Fetch/Support/helpers.php`)

- `fetch(string|RequestInterface|null $resource = null, ?array $options = [])`
  - `RequestInterface` ➜ forwarded to the global client with no option merging.
  - `null` ➜ returns the singleton `Fetch\Http\Client` handler for chaining.
  - String URL ➜ runs through `process_request_options()`, handles `base_uri` delegation, and returns either `Fetch\Http\Response` or a promise when the handler is in async mode.
  - Supported options mirror `RequestOptions::normalizeBodyOptions()` (method, headers, body/json/form/multipart, query, timeout, connect_timeout, retries, retry_delay, auth, token, proxy, cookies, allow_redirects, cert, ssl_key, stream, progress, debug, cache, profiler configuration, etc.).
- `fetch_client(?array $options = null, bool $reset = false): Fetch\Http\Client`
  - Maintains a static `Client` instance; `reset=true` recreates it.
  - Passing `$options` clones the current handler with merged defaults (exceptions are wrapped in `RuntimeException` with context).
- Verb helpers `get/post/put/patch/delete()` call `request_method()` which coerces array bodies to JSON unless `$dataIsQuery` is true.
- Matrix async bridge helpers (`async`, `await`, `all`, `race`, `map`, `batch`, `retry`) are re-exported when the corresponding `\Matrix\*` functions exist.
- Internal helpers:
  - `process_request_options(array $options)` normalizes method enums/strings, headers, body precedence (`json` > `form` > `multipart` > `body`) and high-level flags.
  - `extract_body_and_content_type()` returns the paired body + `ContentType`.
  - `handle_request_with_base_uri()` wires `base_uri` by configuring the handler before invoking `sendRequest`.

---

## Core HTTP Clients

### `Fetch\Http\Client`

- Implements PSR-18 `ClientInterface` and PSR-3 `LoggerAwareInterface`.
- Constructor arguments: `?ClientHandlerInterface $handler = null`, `array $options = []`, `?LoggerInterface $logger = null`. Defaults to a new `ClientHandler` with provided options and a `NullLogger`.
- `static createWithBaseUri(string $baseUri, array $options = [])` preloads a handler with `base_uri`.
- `sendRequest(RequestInterface $request): PsrResponseInterface`
  - Converts PSR request to handler options, logs, and returns a PSR-7 compliant response (`Fetch\Http\Response`).
  - Wraps `ConnectException`, `Guzzle\RequestException`, and generic throwables into Fetch exceptions (`NetworkException`, `RequestException`, `ClientException`).
- `fetch(?string $url = null, ?array $options = [])`
  - When `$url` is null returns the handler for fluent chaining.
  - Normalizes HTTP method via `Fetch\Enum\Method`, infers `ContentType`, applies `base_uri` support, and delegates to `ClientHandler::withOptions()->sendRequest()`.
  - Catches `Guzzle\RequestException` to surface error responses even when exceptions are thrown.
- Verb conveniences: `get`, `post`, `put`, `patch`, `delete`, `head`, `options` call `methodRequest()` which pushes normalized method + body settings through `fetch()`.
- `getHttpClient()` exposes the underlying `GuzzleHttp\ClientInterface`.

### `Fetch\Http\ClientHandler`

Implements the union interface `Fetch\Interfaces\ClientHandler`, combining:

- `RequestConfigurator` (`withOptions`, `withHeader`, `withBody`, `withJson`, `withQueryParameters`, `timeout`, etc.).
- `RequestExecutor` (`request`, `sendRequest`, verb helpers).
- `PromiseHandler` (async orchestration).
- `RetryableHandler` (`retry`, `retryStatusCodes`, `retryExceptions` getters/setters).
- `CacheableRequestHandler` (`withCache`, `withoutCache`, `getCache`, `isCacheEnabled`).
- `DebuggableHandler` (`withLogLevel`, `withProfiler`, `withDebug`, `debug()` snapshot, `getLastDebugInfo()`).
- `PoolAwareHandler` (`withConnectionPool`, `withHttp2`, DNS cache controls).
- `HttpClientAware` (inject your own Guzzle client).

Implementation relies on traits in `src/Fetch/Concerns/`:

- `ConfiguresRequests` – base URI, headers, authentication, body helpers (`withBody` enforces content-type/header consistency).
- `HandlesUris` – builds absolute URIs using handler options or immutable `RequestContext`.
- `ManagesPromises` – toggles async mode, exposes `then/catch/finally`, `map`, `sequence`, and concurrency utilities (Matrix bridge).
- `ManagesRetries` – exposes fluent retry configuration and delegates execution to `Support\RetryStrategy`.
- `PerformsHttpRequests` – orchestrates RequestContext creation, logging, caching, mocking, profiling, and actual HTTP execution (sync + async).
- `HandlesMocking` – consults `Testing\MockServer` before hitting the network.
- `ManagesConnectionPool` – bridges to `Support\GlobalServices` for shared pools/DNS cache/HTTP2.
- `ManagesDebugAndProfiling` – surfaces `withDebug`, `withProfiler`, attaches `DebugInfo` snapshots per response.

Key runtime behaviors:

- Builds an immutable `RequestContext` per call by merging factory defaults → global defaults → handler options → request overrides.
- Supports synchronous and asynchronous execution. Async requests always bypass caching and use `React\Promise` via Matrix's helpers.
- Integrates caching through `CacheManager` automatically when `withCache()` was called or a `cache` option is present.
- Supports `MockServer` fakes before network IO, `Recorder` integration when recording is enabled.
- Logging sanitizes sensitive headers and auth credentials.

---

## Request & Response Models

### `Fetch\Http\Request`

- Extends Guzzle's PSR-7 request and adds:
  - Factory helpers: `json()`, `form()`, `multipart()`, `get/post/...` with enum-aware method normalization.
  - Mutators: `withJsonBody()`, `withFormBody()`, `withBody()` (ensures stream/headers), `withContentType()`, query parameter builders, `withBearerToken`, `withBasicAuth`.
  - Inspection helpers: `supportsRequestBody()`, `getMethodEnum()`, `getContentTypeEnum()`, `hasJsonContent()` etc.
  - `RequestImmutabilityTrait` ensures fluent operations preserve the extended type and optional custom request target.

### `Fetch\Http\Response`

- Extends `GuzzleHttp\Psr7\Response`, implements `Fetch\Interfaces\Response` + `ArrayAccess`.
- Features:
  - Static constructors: `createFromBase()`, `withJson()`, `created()`, `noContent()`, `withRedirect()`.
  - Body helpers: `json()`, `object()`, `array()`, `text()`, `body()`, `blob()`, `arrayBuffer()`, `xml()`.
  - Status helpers: `status()`, `statusText()`, `statusEnum()`, `isOk()`, `isNotFound()`, `failed()`, etc.
  - Content helpers: `contentType()`, `contentTypeEnum()`, `hasJsonContent()`, `hasTextContent()`.
  - JSON ArrayAccess (`$response['data']`) for quick reads.
  - Debugging hooks: `withDebugInfo(DebugInfo $info)` and `getDebugInfo()` to inspect per-request snapshots.
  - `ResponseImmutabilityTrait` keeps buffered body contents in sync when streams are replaced.

---

## Support Services

- `Fetch\Support\RequestOptions`
  - Central source of truth for option precedence and normalization.
  - Merges defaults using `merge(...$optionSets)`, canonicalizes retry keys (`max_retries` → `retries`), enforces body precedence, injects `Content-Type` headers when missing, and exposes `withAuth`, `withJson`, `withForm`, `withMultipart` helpers.
  - `normalizeMultipart()` accepts associative or list-style arrays.
  - `toGuzzleOptions()` filters supported keys; `toFetchOptions()` extracts Fetch-specific flags (`base_uri`, `async`, `retries`, `cache`, `debug`, etc.).
  - Validates method, timeout, retry values, and `base_uri` format.

- `Fetch\Support\RequestContext`
  - Immutable snapshot of method, URI, async flag, timeouts, retries, cache/debug toggles, headers, extra options.
  - Offers `with*` clone builders (method, URI, async, timeout, retry config, cache/debug toggles, headers/options).
  - Computes derived queries: safe/idempotent method detection, `shouldUseCache()`.
  - Emits arrays/Guzzle options for compatibility (`toArray()`, `toGuzzleOptions()`).

- `Fetch\Support\Defaults` and `RetryDefaults`
  - Factory defaults (`HTTP_METHOD=GET`, `TIMEOUT=30`).
  - Retry defaults: `MAX_RETRIES=1`, `RETRY_DELAY=100ms`, jitter-capped at `MAX_DELAY_MS=30000`, status code list (`408`, `429`, `5xx`, Cloudflare 52x, etc.), default exception list (`GuzzleHttp\Exception\ConnectException`).

- `Fetch\Support\RetryStrategy`
  - Stateless service used by `ManagesRetries`.
  - Accepts overrides for max retries, base delay, retryable status codes + exception classes, and exposes `execute(callable $request, ?callable $onRetry)` plus `calculateDelay()`, `isRetryable()`, `isRetryableStatusCode()`, `isRetryableException()`.
  - Adds jitter, caps delays, emits log events (`LoggerInterface`, defaults to `NullLogger`).

- `Fetch\Support\FetchProfiler`, `ProfilerInterface`, `ProfilerBridge`, `DebugConfig`, `DebugInfo`
  - `FetchProfiler` captures per-request timing + memory metrics, DNS/connect/SSL events, summary stats.
  - `ProfilerBridge` guards all profiler/debug interactions (null-safe), generates unique request IDs, records events, and builds `DebugInfo` snapshots with sanitized headers and optional body truncation per `DebugInfo::getDefaultOptions()`.
  - `DebugConfig` stores debug on/off + option overrides + linked profiler instance.

- `Fetch\Support\GlobalServices`
  - Centralizes shared instances: `ConnectionPool`, `DnsCache`, and merged default options.
  - Provides `initialize()`, `configurePool()`, `setDefaultOptions()`, `reset()`, `closeAllConnections()`, `clearDnsCache()`, `getStats()`.
  - Used implicitly by `ClientHandler::create*()` to ensure pooling infrastructure exists.

---

## HTTP Caching Subsystem (`src/Fetch/Cache`)

- `CacheInterface` contract + two implementations:
  - `MemoryCache` – in-memory LRU-ish store with max item count + TTL.
  - `FileCache` – JSON-serialized responses on disk (`/tmp/fetch-cache` by default) with size pruning.
- `CacheManager`
  - Default options: `respect_cache_headers`, `default_ttl`, `stale_while_revalidate`, `stale_if_error`, `cache_methods` (GET/HEAD), `cache_status_codes`, `vary_headers`, `is_shared_cache`.
  - `withCache()` on the handler injects configuration (`enabled`, `ttl`, `force_refresh`, `key`, `cache_body`, `respect_headers`, `is_shared_cache`, etc.).
  - `getCachedResponse()` returns either a hydrated `Response`, a stale entry requiring revalidation, or metadata for conditional headers.
  - Supports conditional requests via `If-None-Match`/`If-Modified-Since`, stale-while-revalidate serving, and stale-if-error fallbacks.
  - `cacheResponse()` stores `Fetch\Http\Response` instances as `CachedResponse` with TTL derived from Cache-Control headers (via `CacheControl`) or explicit overrides.
- `CachedResponse` – value object storing status, headers, body, timestamps, ETag, Last-Modified; exposes freshness helpers.
- `CacheControl` – parser/builder for directives, TTL calculation, `no-store`, `no-cache`, `must-revalidate`, etc.
- `CacheKeyGenerator` – creates deterministic sha256 keys using method, normalized URI (scheme/host/port/path/query), configured `vary_headers`, and optional body hash when `cache_body` is enabled for unsafe methods.

---

## Connection Pool & DNS (`src/Fetch/Pool`)

- `PoolConfiguration` – toggles pooling, max connections, max per host/idle, keep-alive timeout, connection timeout, strategy, warmup counts, DNS cache TTL.
- `ConnectionPool`
  - Manages `HostConnectionPool` instances per host:port:scheme key.
  ̀ - Tracks active connections, reuse metrics, average latency, exposes `getConnectionFromUrl()`, `getClientForUrl()`, `releaseConnection()`, `recordLatency()`, `closeAll()`.
- `HostConnectionPool`
  - Uses `SplQueue` to track idle `Connection` objects.
  - Honors warmup configuration to eagerly create connections.
  - Wraps each connection with a dedicated `Guzzle\Client` pre-configured for the host, keep-alive, and connection timeouts.
- `Connection` – records creation/last-used timestamps, active request count, keeps a reference to the host-specific Guzzle client, ensures `isReusable()` respects keep-alive.
- `DnsCache`
  - Simple TTL-based cache for DNS lookups (A + AAAA fallback to `gethostbyname`), supports `resolveFirst()`, `clear()`, `prune()`, `getStats()`, throws `NetworkException` on failure.
- `Http2Configuration`
  - Toggles HTTP/2 features, exposes curl options to enforce `CURL_HTTP_VERSION_2_0`, optional multiplexing constants when available.

---

## Async & Promise Operations

- `ManagesPromises` trait exposes:
  - `async(?bool $async = true)`, `isAsync()`.
  - `wrapAsync(callable $cb)`, `awaitPromise(PromiseInterface $promise, ?float $timeout)`, `all()`, `race()`, `any()`, `sequence()`.
  - `then`, `catch`, `finally` automatically toggle async mode and call `sendAsync()`.
  - `map(array $items, callable $cb, int $concurrency)` uses `mapBatched()` for controlled concurrency.
  - Internally delegates to Matrix helpers (e.g., `Matrix\Support\async`, `await`, `timeout`) and rethrows timeouts as `RuntimeException`.

---

## Retry & Error Handling

- `ManagesRetries::retry(int $retries, int $delayMs = 100)` updates handler defaults; negative inputs throw `InvalidArgumentException`.
- `retryStatusCodes()` / `retryExceptions()` allow per-handler overrides beyond defaults.
- Execution pipeline (sync + async) routes through `retryRequest(RequestContext|null, callable $request)` which uses `RetryStrategy::execute()`.
- Stored status codes/exceptions are also injected from `RequestContext` when per-request options specify `retry_status_codes` or `retry_exceptions`.
- Exceptions:
  - `Fetch\Exceptions\ClientException` – base class implementing `Psr\Http\Client\ClientExceptionInterface`.
  - `NetworkException` – implements PSR `NetworkExceptionInterface`.
  - `RequestException` – wraps request/response pair.
  - `HttpException` – legacy runtime exception exposing `setResponse()/getResponse()`.

---

## Testing & Mocking Utilities (`src/Fetch/Testing`)

- `MockServer`
  - Singleton with `fake(array|Closure|null $patterns)`, `preventStrayRequests()`, `allowStrayRequests()`, `startRecording()`, `stopRecording()`, `recorded()`, `assertSent()`, `assertNotSent()`, `assertSentCount()`, `assertNothingSent()`, `resetInstance()`.
  - Accepts `MockResponse`, `MockResponseSequence`, closures (receiving `Fetch\Http\Request`), or arrays (auto-converted to JSON responses).
  - Matches URL patterns with glob-style wildcards (`*`), optionally with HTTP method prefixes (e.g., `POST https://api/*`).
- `MockResponse`
  - Builder for fake responses (`ok()`, `created()`, `noContent()`, `badRequest()`, `unauthorized()`, `forbidden()`, `notFound()`, `unprocessableEntity()`, `serverError()`, `serviceUnavailable()`).
  - Supports `json()`, `sequence()`, `delay(ms)`, `throw(Throwable)`, and `execute()` to yield a real `Fetch\Http\Response`.
- `MockResponseSequence`
  - Queue/loop semantics with `push()`, `pushJson()`, `pushStatus()`, `pushResponse()`, `whenEmpty()`, `loop()`, `next()`.
- `Recorder`
  - `start()`, `stop()`, `record(Request, ResponseInterface)`, `isRecording()`, `getRecordings()`, `clear()`, `reset()`.
  - `replay($recordings)` converts previous recordings into `MockServer` fakes; `exportToJson()/importFromJson()` support persistence.
- `HandlesMocking` trait ensures every handler call consults `MockServer` and triggers `Recorder::record()` when active.

---

## Enumerations (`src/Fetch/Enum`)

- `Method` – HTTP verbs with helpers: `fromString()`, `tryFromString()`, `supportsRequestBody()`.
- `ContentType` – Common content types with `fromString`, `tryFromString`, `normalizeContentType`, `isJson`, `isForm`, `isMultipart`, `isText`.
- `Status` – Full catalog of HTTP status codes with helper predicates (`isInformational`, `isSuccess`, `isClientError`, `isServerError`, `isCacheable`, `isNotModified`, `isEmpty`) and `phrase()`.

---

## Modules & Traits Summary

- `Fetch\Concerns\ConfiguresRequests` – option/header/body/auth helpers plus `reset()`.
- `Fetch\Concerns\HandlesUris` – validation, normalization, query building, base URI enforcement.
- `Fetch\Concerns\PerformsHttpRequests` – public `handle()` factory, verb wrappers, caching/mocking/profiling pipeline, sync/async execution, stale-if-error logic.
- `Fetch\Concerns\ManagesPromises` – promise orchestration.
- `Fetch\Concerns\ManagesRetries` – fluent retry configuration and integration with `RetryStrategy`.
- `Fetch\Concerns\ManagesConnectionPool` – toggles pooling + HTTP/2, exposes diagnostics, DNS cache helpers.
- `Fetch\Concerns\ManagesDebugAndProfiling` – `withDebug`, `withProfiler`, `getProfiler()`, debug snapshot capture.
- `Fetch\Concerns\HandlesMocking` – converts handler options into PSR requests for the mock server.

---

## Exception Types (`src/Fetch/Exceptions`)

- `ClientException` – base runtime exception implementing `ClientExceptionInterface`.
- `NetworkException` – indicates transport errors and carries the originating request.
- `RequestException` – wraps both request and response; used for retry logic and surfaced when `Guzzle` raises.
- `HttpException` – legacy helper storing a `ResponseInterface`.

---

This code map should be kept up to date as the implementation evolves to guarantee documentation and user guides remain accurate.
