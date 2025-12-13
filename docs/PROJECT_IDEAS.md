# Fetch PHP: Capabilities Map & Project Ideas

This document provides a comprehensive analysis of the Fetch PHP package's capabilities and proposes 10 project ideas designed to exercise maximum feature coverage.

---

## Part 1: Capabilities Map

### Core Modules

| Module | Location | Description |
|--------|----------|-------------|
| **Http** | `src/Fetch/Http/` | Client, ClientHandler, Request, Response |
| **Enum** | `src/Fetch/Enum/` | Method, Status, ContentType type-safe enums |
| **Cache** | `src/Fetch/Cache/` | RFC 7234 caching with MemoryCache, FileCache |
| **Pool** | `src/Fetch/Pool/` | Connection pooling, DNS caching, HTTP/2 |
| **Testing** | `src/Fetch/Testing/` | MockResponse, MockResponseSequence, MockServer, Recorder |
| **Support** | `src/Fetch/Support/` | Helpers, DebugInfo, FetchProfiler, RequestOptions |
| **Exceptions** | `src/Fetch/Exceptions/` | ClientException, NetworkException, RequestException, HttpException |
| **Interfaces** | `src/Fetch/Interfaces/` | CacheableHandler, PromiseHandler, RetryableHandler, etc. |
| **Concerns/Traits** | `src/Fetch/Concerns/`, `src/Fetch/Traits/` | Request configuration, mocking, retries, promises |

### APIs & Functions

| API/Function | Description | Key Features |
|--------------|-------------|--------------|
| `fetch()` | JavaScript-like fetch function | Method chaining, options array, PSR-7 Request support |
| `fetch_client()` | Global client singleton | Configuration persistence, handler access |
| `get()`, `post()`, `put()`, `patch()`, `delete()` | HTTP method helpers | Simplified syntax, automatic JSON encoding |
| `async()`, `await()` | Async/await pattern (Matrix) | Promise-based async operations |
| `all()`, `race()`, `map()`, `batch()`, `retry()` | Promise utilities (Matrix) | Concurrent operations, controlled concurrency |

### Request Configuration Options

| Option | Type | Description |
|--------|------|-------------|
| `method` | string\|Method | HTTP method (GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS) |
| `headers` | array | Request headers |
| `body` | mixed | Raw request body |
| `json` | array | JSON data (auto-encodes, sets Content-Type) |
| `form` | array | Form data (URL-encoded) |
| `multipart` | array | Multipart form data (file uploads) |
| `query` | array | Query parameters |
| `base_uri` | string | Base URI for relative URLs |
| `timeout` | int | Total request timeout (seconds) |
| `connect_timeout` | int | Connection timeout (seconds) |
| `retries` | int | Number of retry attempts |
| `retry_delay` | int | Base delay between retries (ms) |
| `auth` | array | Basic auth credentials [username, password] |
| `token` | string | Bearer token |
| `proxy` | string | Proxy server URL |
| `cookies` | mixed | Cookie handling |
| `allow_redirects` | bool\|array | Redirect behavior |
| `cert` | string\|array | SSL certificate |
| `ssl_key` | string\|array | SSL key |
| `stream` | bool | Stream response body |

### Response Features

| Feature | Method(s) | Description |
|---------|-----------|-------------|
| JSON parsing | `json()`, `object()`, `array()` | Parse body as JSON |
| Text content | `text()`, `body()` | Raw body content |
| Binary data | `blob()`, `arrayBuffer()` | Stream/binary handling |
| XML parsing | `xml()` | Parse body as XML |
| Status checks | `successful()`, `failed()`, `ok()`, `isOk()`, etc. | Status code validation |
| Status enum | `statusEnum()`, `isStatus()` | Type-safe status handling |
| Content type | `contentType()`, `contentTypeEnum()`, `hasJsonContent()`, etc. | Content type detection |
| Headers | `headers()`, `header()`, `hasHeader()` | Header access |
| ArrayAccess | `$response['key']` | Direct JSON data access |
| Debug info | `getDebugInfo()`, `hasDebugInfo()` | Request debugging |

### Type-Safe Enums

| Enum | Values | Key Methods |
|------|--------|-------------|
| `Method` | GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS | `fromString()`, `supportsRequestBody()` |
| `Status` | All HTTP status codes (1xx-5xx) | `phrase()`, `isSuccess()`, `isClientError()`, `isCacheable()` |
| `ContentType` | JSON, FORM_URLENCODED, MULTIPART, TEXT, HTML, XML, etc. | `isJson()`, `isForm()`, `isText()` |

### Caching System (RFC 7234)

| Feature | Description |
|---------|-------------|
| **Cache Backends** | MemoryCache (in-memory), FileCache (persistent) |
| **Cache-Control** | Respects `max-age`, `s-maxage`, `no-store`, `no-cache` |
| **Revalidation** | ETag (`If-None-Match`), Last-Modified (`If-Modified-Since`) |
| **Stale Content** | `stale-while-revalidate`, `stale-if-error` directives |
| **Vary Headers** | Cache variation by Accept, Accept-Encoding, Accept-Language |
| **Cache Status** | X-Cache-Status header (HIT, MISS, STALE, REVALIDATED, BYPASS) |
| **Options** | `ttl`, `respect_headers`, `is_shared_cache`, `force_refresh`, custom keys |

### Connection Pooling & HTTP/2

| Feature | Description |
|---------|-------------|
| **Global Pool** | Shared connection pool across all handlers |
| **Per-Host Limits** | `max_connections`, `max_per_host` configuration |
| **Connection TTL** | `connection_ttl`, `idle_timeout` settings |
| **DNS Caching** | `dns_cache_ttl` for DNS lookup caching |
| **HTTP/2** | Native HTTP/2 support via `withHttp2()` |
| **Statistics** | `getPoolStats()` for monitoring (connections_created, reused, latency) |

### Retry Mechanics

| Feature | Description |
|---------|-------------|
| **Configurable Retries** | `retry(attempts, delay)` method |
| **Exponential Backoff** | Automatic delay increase with jitter |
| **Retryable Status Codes** | 408, 429, 500, 502, 503, 504, 507, 509, 520-523, 525, 527, 530 |
| **Retryable Exceptions** | ConnectException and configurable types |
| **Custom Strategy** | `retryStatusCodes()`, `retryExceptions()` methods |

### Debugging & Profiling

| Feature | Description |
|---------|-------------|
| **Debug Snapshots** | `withDebug()` captures request/response details |
| **Debug Options** | request_headers, request_body, response_headers, response_body, timing, memory |
| **PSR-3 Logging** | `setLogger()` with configurable log level |
| **Sensitive Redaction** | Auto-redacts Authorization, API keys, cookies |
| **Profiling** | `FetchProfiler` for performance metrics |
| **Request Context** | Timing, memory deltas, connection stats |

### Testing Utilities

| Utility | Description |
|---------|-------------|
| `MockResponse` | Create fake responses with status, headers, body |
| `MockResponse::sequence()` | Define response sequences for retry testing |
| `MockServer::fake()` | Global request interception |
| `MockServer::assertSent()` | Assert requests were made |
| `Recorder` | Record/replay requests for fixtures |
| Factory methods | `ok()`, `created()`, `notFound()`, `serverError()`, etc. |
| Response delays | `delay()` to simulate latency |
| Exception throwing | `throw()` to simulate errors |

### Async Operations (via Matrix)

| Function | Description |
|----------|-------------|
| `async()` | Wrap callable for async execution |
| `await()` | Wait for promise resolution |
| `all()` | Execute multiple promises in parallel |
| `race()` | Return first completed promise |
| `map()` | Process items with controlled concurrency |
| `batch()` | Batch processing with size and concurrency limits |
| `retry()` | Retry with exponential backoff |

### Authentication Methods

| Method | Implementation |
|--------|----------------|
| **Basic Auth** | `auth` option, `withAuth()` method |
| **Bearer Token** | `token` option, `withToken()` method |
| **Custom Headers** | `headers` option, `withHeaders()` method |
| **OAuth 2.0** | Manual implementation with token management |

### PSR Compliance

| Standard | Implementation |
|----------|----------------|
| **PSR-7** | Request/Response message interfaces |
| **PSR-18** | HTTP Client interface |
| **PSR-3** | Logger interface support |

### Fluent Interface Methods

```php
$handler = fetch_client()->getHandler();

$response = $handler
    ->baseUri('https://api.example.com')
    ->withHeaders(['Accept' => 'application/json'])
    ->withToken('token')
    ->withAuth('user', 'pass')
    ->withQueryParameters(['page' => 1])
    ->withBody($data, ContentType::JSON)
    ->withMultipart([
        ['name' => 'file', 'contents' => fopen('/path/to/file.jpg', 'r'), 'filename' => 'upload.jpg'],
        ['name' => 'description', 'contents' => 'File description']
    ])
    ->withProxy('http://proxy:8080')
    ->withOptions(['timeout' => 10])
    ->retry(3, 100)
    ->retryStatusCodes([429, 503])
    ->withCache()
    ->withConnectionPool()
    ->withHttp2()
    ->withDebug()
    ->withProfiler($profiler)
    ->withLogLevel('info')
    ->get('/endpoint');
```

---

## Part 2: Project Ideas

### Idea 1: API Health Dashboard

**Concept**
- **Name**: FetchWatch - Real-Time API Health Monitor
- **Pitch**: A CLI/web dashboard that continuously monitors multiple API endpoints, visualizes health metrics, and alerts on failures.
- **Target Users**: DevOps engineers, API maintainers, SREs
- **Primary Use Case**: Monitor production API health with uptime tracking and latency metrics

**Feature Coverage Plan**

| Package Feature | How This Project Uses It |
|-----------------|--------------------------|
| `fetch()` / HTTP methods | Core request execution to monitored endpoints |
| Async (`all()`, `race()`) | Parallel health checks across multiple endpoints |
| `map()` with concurrency | Controlled parallel execution (e.g., 10 concurrent checks) |
| Connection pooling | Reuse connections for frequent polling |
| HTTP/2 | Faster multiplexed requests to same hosts |
| Retry mechanics | Distinguish transient failures from real outages |
| Status enums | Type-safe status categorization |
| Response timing | `getDebugInfo()` for latency measurements |
| FetchProfiler | Aggregate performance statistics |
| PSR-3 logging | Log health check events |
| MockServer | Testing the monitoring logic |
| FileCache | Cache historical health data |
| ❌ Multipart uploads | Not applicable |
| ❌ XML parsing | Not applicable (most APIs are JSON) |

**Architecture**
- **Components**: CLI runner, Web UI (optional), SQLite/JSON storage, Alert dispatcher
- **Happy Path**: Scheduler triggers checks → `all()` fetches endpoints → Store results → Display dashboard
- **Failure Path**: Endpoint timeout → Retry 3x → Mark unhealthy → Trigger alert → Log event

**MVP**
- Scope: Configure endpoints in YAML, run CLI command, output health table
- Commands: `fetchwatch check`, `fetchwatch status`, `fetchwatch history`
- Endpoints: GET /health for each monitored service

**Differentiators**
1. Sub-second latency tracking with profiler integration
2. Connection pool reuse for minimal overhead on frequent checks
3. Smart retry logic to avoid false positives
4. Built-in mocking for testing alert conditions
5. HTTP/2 for efficient multi-endpoint checks

**Risks & Requirements**
- Complexity: M
- Requires: Persistent storage for history, scheduler (cron or loop)
- Constraint: Dashboard requires additional frontend work

---

### Idea 2: Multi-Source Data Aggregator

**Concept**
- **Name**: DataMesh - Unified API Aggregator
- **Pitch**: Aggregate data from multiple REST and GraphQL APIs into a single normalized response with caching and fallbacks.
- **Target Users**: Frontend developers, Data engineers, Product teams
- **Primary Use Case**: Combine user data from Auth0, posts from Contentful, and analytics from Mixpanel into one API call

**Feature Coverage Plan**

| Package Feature | How This Project Uses It |
|-----------------|--------------------------|
| `fetch()` / HTTP methods | Call various external APIs |
| Async (`all()`) | Parallel requests to all sources |
| `race()` | Fallback sources (primary vs backup) |
| `map()` / `batch()` | Paginated data fetching |
| Caching (RFC 7234) | Cache API responses with TTL |
| `stale-while-revalidate` | Serve stale during refresh |
| `stale-if-error` | Fallback to cache on failures |
| Retry mechanics | Handle rate limiting (429) |
| Bearer tokens | OAuth/API key auth per source |
| Headers customization | Different auth schemes per API |
| Response parsing (`json()`, `xml()`) | Handle JSON and XML APIs |
| Status checks | Error handling per source |
| ContentType enum | Detect response format |
| PSR-3 logging | Log aggregation operations |
| Debug snapshots | Trace slow sources |
| ❌ Multipart uploads | Not applicable |
| ❌ Connection pooling | Limited benefit (different hosts) |

**Architecture**
- **Components**: Source adapters, Aggregation engine, Cache layer, Response normalizer
- **Happy Path**: Request → Check cache → Parallel fetch sources → Normalize → Merge → Cache → Respond
- **Failure Path**: Source timeout → Use cached/stale → Log warning → Return partial data with metadata

**MVP**
- Scope: 3 configurable API sources, JSON output, basic caching
- Endpoints: GET /aggregate?sources=users,posts,comments
- Commands: CLI `datamesh fetch --sources users,posts`

**Differentiators**
1. RFC 7234 caching with intelligent revalidation
2. Graceful degradation with `stale-if-error`
3. Per-source authentication and retry configuration
4. Source dependency graph (fetch users before user-posts)
5. Response metadata showing source health

**Risks & Requirements**
- Complexity: L
- Requires: Schema normalization logic, source configuration
- Constraint: Different API rate limits require careful orchestration

---

### Idea 3: HTTP Request Recorder & Replayer

**Concept**
- **Name**: FetchReplay - HTTP Traffic Capture & Playback
- **Pitch**: Record HTTP interactions from production/staging, export as fixtures, and replay for testing or debugging.
- **Target Users**: QA engineers, Backend developers, API testers
- **Primary Use Case**: Capture a complex API workflow, export it, and use it for regression testing

**Feature Coverage Plan**

| Package Feature | How This Project Uses It |
|-----------------|--------------------------|
| `fetch()` / HTTP methods | Execute recorded requests |
| Recorder utility | Core recording functionality |
| MockServer | Replay recorded responses |
| MockResponseSequence | Sequential response playback |
| Request/Response interfaces | Serialize/deserialize HTTP messages |
| JSON/XML parsing | Parse response bodies for assertions |
| All HTTP methods | Support GET, POST, PUT, DELETE, etc. |
| Headers handling | Capture and replay all headers |
| Authentication | Record auth flows (with redaction) |
| Status enums | Categorize recorded responses |
| PSR-3 logging | Log recording sessions |
| Debug snapshots | Capture timing information |
| FileCache | Store recordings persistently |
| ❌ Connection pooling | Not needed (replay is synchronous) |
| ❌ HTTP/2 | Protocol transparent in recordings |

**Architecture**
- **Components**: Recording proxy, Storage backend, Replay engine, CLI interface
- **Happy Path**: Start recording → Execute workflow → Stop → Export JSON → Import in tests → Replay
- **Failure Path**: Recording storage full → Warn user → Continue in memory → Export what we have

**MVP**
- Scope: CLI to start/stop recording, export/import JSON, PHPUnit integration
- Commands: `fetchreplay record`, `fetchreplay stop`, `fetchreplay export file.json`
- Integration: `Recorder::importFromJson()` in test setUp

**Differentiators**
1. Native integration with Fetch PHP's Recorder
2. Automatic sensitive header redaction
3. Timing preservation for realistic playback
4. Sequence support for stateful API testing
5. PHPUnit trait for easy test integration

**Risks & Requirements**
- Complexity: S
- Requires: File storage for recordings
- Constraint: Large recordings may consume significant memory

---

### Idea 4: Webhook Testing Server

**Concept**
- **Name**: HookCatcher - Webhook Development & Testing Platform
- **Pitch**: Receive, inspect, forward, and replay webhooks during development with signature verification and request logging.
- **Target Users**: Full-stack developers, Integration engineers
- **Primary Use Case**: Test Stripe/GitHub/Twilio webhooks locally without deploying

**Feature Coverage Plan**

| Package Feature | How This Project Uses It |
|-----------------|--------------------------|
| `fetch()` / `post()` | Forward webhooks to local dev server |
| Headers handling | Preserve original webhook headers |
| Body handling (`json()`, `text()`) | Parse webhook payloads |
| Multipart handling | Handle multipart webhook payloads |
| Retry mechanics | Retry failed forwards with backoff |
| Timeout configuration | Handle slow local services |
| MockServer | Test webhook handlers without real webhooks |
| MockResponse | Simulate webhook payloads |
| PSR-3 logging | Log all webhook traffic |
| Debug snapshots | Full request/response capture |
| Status checks | Validate forward responses |
| Request immutability | Safe payload transformations |
| FileCache | Store webhook history |
| ❌ Connection pooling | Single host forwarding |
| ❌ HTTP/2 | Not relevant for webhooks |

**Architecture**
- **Components**: HTTP server (ReactPHP/Swoole), Webhook inspector, Forwarder, Replayer, CLI
- **Happy Path**: External service → HookCatcher → Log → Forward to localhost:3000 → Return response
- **Failure Path**: Forward timeout → Retry 3x → Store for manual replay → Respond 200 to source

**MVP**
- Scope: CLI server on configurable port, forward to localhost, web UI to view history
- Commands: `hookcatcher start --port 8080 --forward localhost:3000`
- UI: List recent webhooks, view payload, replay button

**Differentiators**
1. Retry forwarding with configurable backoff
2. Payload inspection and search
3. One-click replay for debugging
4. Signature verification (Stripe, GitHub, etc.)
5. MockServer integration for testing webhook handlers

**Risks & Requirements**
- Complexity: M
- Requires: HTTP server runtime (ReactPHP or built-in PHP server)
- Constraint: Requires public URL for external webhooks (ngrok integration)

---

### Idea 5: API Rate Limit Tester

**Concept**
- **Name**: RateBuster - API Rate Limit Discovery & Testing Tool
- **Pitch**: Discover API rate limits, test throttling behavior, and generate load profiles for capacity planning.
- **Target Users**: API developers, Performance engineers, QA teams
- **Primary Use Case**: Determine exact rate limits and verify 429 handling before production deployment

**Feature Coverage Plan**

| Package Feature | How This Project Uses It |
|-----------------|--------------------------|
| `fetch()` / HTTP methods | Execute test requests |
| Async (`map()`, `batch()`) | Concurrent request bursts |
| Controlled concurrency | Precise concurrency levels |
| Retry mechanics | Test 429 retry behavior |
| `retryStatusCodes([429])` | Custom retry on rate limit |
| Status enum (TOO_MANY_REQUESTS) | Detect rate limiting |
| Response headers | Parse X-RateLimit-* headers |
| FetchProfiler | Track requests per second |
| Connection pooling | Maximize request throughput |
| HTTP/2 | Test HTTP/2 rate limits |
| Timeouts | Detect when limits cause delays |
| PSR-3 logging | Log all test attempts |
| Debug snapshots | Capture rate limit responses |
| MockServer | Test rate limit handling logic |
| ❌ Caching | Would skew results |
| ❌ Multipart | Not relevant |

**Architecture**
- **Components**: Load generator, Rate limit analyzer, Report generator, CLI interface
- **Happy Path**: Configure target → Ramp up requests → Detect 429 → Calculate limits → Generate report
- **Failure Path**: Target crashes → Detect connection errors → Back off → Report observed limits

**MVP**
- Scope: CLI tool, configurable ramp-up, text report
- Commands: `ratebuster test https://api.example.com/endpoint --max-rps 100`
- Output: Detected limit, retry-after behavior, headers found

**Differentiators**
1. Automatic X-RateLimit header parsing
2. Exponential ramp-up to find exact limits
3. Connection pool stats for throughput analysis
4. HTTP/2 multiplexing for higher throughput
5. Retry behavior validation

**Risks & Requirements**
- Complexity: M
- Requires: Careful use to avoid IP bans
- Constraint: May trigger security alerts on target APIs

---

### Idea 6: API Documentation Validator

**Concept**
- **Name**: SpecChecker - Live API vs Documentation Validator
- **Pitch**: Compare live API responses against OpenAPI/Swagger specs, detect documentation drift, and generate deviation reports.
- **Target Users**: API product managers, Technical writers, Backend developers
- **Primary Use Case**: Ensure API documentation stays in sync with actual implementation

**Feature Coverage Plan**

| Package Feature | How This Project Uses It |
|-----------------|--------------------------|
| `fetch()` / All HTTP methods | Execute spec-defined requests |
| JSON parsing | Validate response structure |
| XML parsing | Validate XML API responses |
| Status enum | Verify documented status codes |
| ContentType enum | Verify documented content types |
| Headers validation | Check documented headers |
| Response structure | Compare against JSON Schema |
| Authentication | Test authenticated endpoints |
| Async (`all()`) | Parallel endpoint validation |
| Retry mechanics | Handle flaky endpoints |
| MockServer | Test validator logic |
| PSR-3 logging | Log validation results |
| Debug snapshots | Capture failed validations |
| FileCache | Cache OpenAPI spec parsing |
| ❌ Connection pooling | Limited benefit |
| ❌ HTTP/2 | Not relevant |

**Architecture**
- **Components**: OpenAPI parser, Request generator, Response validator, Report generator
- **Happy Path**: Load spec → Generate requests → Execute → Compare responses → Generate report
- **Failure Path**: Endpoint fails → Mark as drift → Continue with other endpoints → Report all issues

**MVP**
- Scope: CLI tool, OpenAPI 3.0 support, JSON schema validation
- Commands: `specchecker validate ./openapi.yaml https://api.example.com`
- Output: Passed/failed endpoints, specific deviations

**Differentiators**
1. All HTTP methods from spec
2. Schema validation with detailed diff
3. Authentication flow testing
4. Concurrent validation for speed
5. CI-friendly JSON/JUnit output

**Risks & Requirements**
- Complexity: L
- Requires: OpenAPI parser library
- Constraint: Complex specs with polymorphism are harder to validate

---

### Idea 7: Distributed Cache Warmer

**Concept**
- **Name**: CacheForge - Intelligent API Cache Pre-Warmer
- **Pitch**: Pre-populate caches by crawling APIs based on sitemap/config, with intelligent prioritization and staleness detection.
- **Target Users**: Platform engineers, Site reliability engineers
- **Primary Use Case**: Warm CDN/application caches before traffic spikes or deployments

**Feature Coverage Plan**

| Package Feature | How This Project Uses It |
|-----------------|--------------------------|
| `fetch()` / `get()` | Fetch endpoints to warm |
| Caching (RFC 7234) | Local cache for deduplication |
| Cache-Control headers | Respect endpoint TTLs |
| ETag/Last-Modified | Check staleness before re-warming |
| Async (`map()`, `batch()`) | Parallel warming with concurrency control |
| Connection pooling | Efficient connection reuse |
| HTTP/2 | Multiplexed requests |
| FetchProfiler | Track warming progress |
| Retry mechanics | Handle transient failures |
| Status checks | Validate successful warming |
| Response headers | Parse caching headers |
| PSR-3 logging | Log warming operations |
| Debug snapshots | Debug slow endpoints |
| FileCache | Track warming history |
| ❌ Multipart | Not applicable |
| ❌ XML parsing | Most cache endpoints are JSON |

**Architecture**
- **Components**: URL discoverer, Priority queue, Warmer engine, Progress tracker, CLI
- **Happy Path**: Load URL list → Prioritize by TTL → Warm in batches → Track progress → Report complete
- **Failure Path**: Endpoint error → Retry → Mark failed → Continue → Report failures at end

**MVP**
- Scope: CLI tool, URL list input, progress bar, summary report
- Commands: `cacheforge warm --urls urls.txt --concurrency 20`
- Output: Warmed count, cache hits (already fresh), failures

**Differentiators**
1. RFC 7234 staleness detection
2. Smart prioritization by TTL
3. Connection pooling for throughput
4. HTTP/2 multiplexing
5. Real-time progress tracking

**Risks & Requirements**
- Complexity: M
- Requires: URL list or sitemap parser
- Constraint: May overwhelm origin servers without rate limiting

---

### Idea 8: API Diff Tool

**Concept**
- **Name**: APIDelta - API Version Comparison Tool
- **Pitch**: Compare responses between two API versions (staging vs production, v1 vs v2) and generate detailed diff reports.
- **Target Users**: API developers, Release managers, QA teams
- **Primary Use Case**: Validate API upgrade doesn't break existing functionality

**Feature Coverage Plan**

| Package Feature | How This Project Uses It |
|-----------------|--------------------------|
| `fetch()` / All methods | Fetch from both versions |
| Async (`all()`) | Parallel requests to both versions |
| JSON parsing | Compare JSON structures |
| XML parsing | Compare XML responses |
| Status checks | Compare status codes |
| Headers handling | Compare response headers |
| ContentType enum | Detect content type changes |
| Response body | Deep comparison |
| Authentication | Same auth for both versions |
| Retry mechanics | Handle flaky responses |
| MockServer | Test diff logic |
| PSR-3 logging | Log comparison results |
| Debug snapshots | Capture response details |
| FileCache | Cache baseline responses |
| ❌ Connection pooling | Different hosts |
| ❌ HTTP/2 | Not relevant |

**Architecture**
- **Components**: Dual fetcher, Diff engine, Report generator, CLI interface
- **Happy Path**: Fetch v1 → Fetch v2 → Compute diff → Generate report
- **Failure Path**: One version fails → Mark as error → Report which version failed

**MVP**
- Scope: CLI tool, JSON diff, colored terminal output
- Commands: `apidelta compare https://v1.api.com https://v2.api.com --endpoints endpoints.txt`
- Output: Matching endpoints, different responses, missing endpoints

**Differentiators**
1. Parallel fetching for speed
2. Semantic JSON diff (ignore ordering)
3. Header comparison
4. Status code change detection
5. Baseline caching for repeated comparisons

**Risks & Requirements**
- Complexity: M
- Requires: Diff algorithm for JSON/XML
- Constraint: Dynamic responses (timestamps, IDs) need filtering

---

### Idea 9: File Sync Service

**Concept**
- **Name**: CloudSync - HTTP-Based File Synchronization
- **Pitch**: Sync files between local filesystem and HTTP endpoints (S3-compatible, WebDAV, custom APIs) with multipart upload support.
- **Target Users**: DevOps engineers, Content managers, Backup administrators
- **Primary Use Case**: Sync local assets to CDN or backup server via HTTP API

**Feature Coverage Plan**

| Package Feature | How This Project Uses It |
|-----------------|--------------------------|
| `fetch()` / `put()`, `post()`, `delete()` | CRUD operations on remote files |
| Multipart uploads | Upload large files |
| Streaming responses (`stream`) | Download large files |
| `blob()`, `arrayBuffer()` | Binary file handling |
| Headers handling | Content-Type, Content-Length |
| Authentication | API keys, Bearer tokens |
| Async (`map()`) | Parallel file transfers |
| Connection pooling | Reuse connections for batch sync |
| HTTP/2 | Faster multiplexed transfers |
| Retry mechanics | Retry failed transfers |
| Caching | Track sync state |
| FetchProfiler | Track transfer speeds |
| Status checks | Validate upload success |
| PSR-3 logging | Log sync operations |
| Debug snapshots | Debug failed transfers |
| ContentType enum | Set correct MIME types |

**Architecture**
- **Components**: File scanner, Sync engine, Transfer manager, State tracker, CLI
- **Happy Path**: Scan local → Compare with remote → Upload new/changed → Delete removed → Update state
- **Failure Path**: Upload fails → Retry 3x → Mark failed → Continue → Report failures

**MVP**
- Scope: CLI tool, single directory sync, S3-compatible endpoint
- Commands: `cloudsync push ./local-dir https://cdn.example.com/bucket`
- Output: Uploaded count, skipped (unchanged), failed

**Differentiators**
1. Multipart upload for large files
2. Parallel transfers with connection pooling
3. HTTP/2 for faster transfers
4. Intelligent retry on failures
5. Streaming downloads for memory efficiency

**Risks & Requirements**
- Complexity: L
- Requires: File comparison logic, state persistence
- Constraint: Memory usage for large files (streaming helps)

---

### Idea 10: API Mock Server Generator

**Concept**
- **Name**: MockForge - Dynamic Mock Server from OpenAPI Specs
- **Pitch**: Generate a fully functional mock server from OpenAPI/Swagger specs with dynamic responses, latency simulation, and error injection.
- **Target Users**: Frontend developers, Integration testers, API consumers
- **Primary Use Case**: Develop frontend against API that doesn't exist yet

**Feature Coverage Plan**

| Package Feature | How This Project Uses It |
|-----------------|--------------------------|
| MockServer | Core mock serving functionality |
| MockResponse | Generate fake responses |
| MockResponseSequence | Stateful mock responses |
| Response delays | Simulate network latency |
| Exception throwing | Simulate errors |
| Status enum | Return correct status codes |
| ContentType enum | Set correct content types |
| Headers handling | Return documented headers |
| JSON/XML generation | Generate response bodies |
| PSR-7 interfaces | HTTP message handling |
| All HTTP methods | Support all spec-defined methods |
| PSR-3 logging | Log mock requests |
| FileCache | Cache generated responses |
| ❌ Connection pooling | Not applicable (server mode) |
| ❌ HTTP/2 | Server implementation detail |
| ❌ Retry mechanics | Client feature |

**Architecture**
- **Components**: OpenAPI parser, Response generator, Mock server, Admin API, CLI
- **Happy Path**: Load spec → Parse endpoints → Generate handlers → Serve requests → Return mocked data
- **Failure Path**: Invalid spec → Report parsing errors → Exit with error

**MVP**
- Scope: CLI server, OpenAPI 3.0, JSON responses, basic examples
- Commands: `mockforge serve ./openapi.yaml --port 8080`
- Features: Automatic JSON generation from schemas

**Differentiators**
1. Native MockResponse/MockServer integration
2. Latency simulation for realistic testing
3. Error injection for failure testing
4. Sequence support for stateful mocking
5. Admin API to control mock behavior at runtime

**Risks & Requirements**
- Complexity: L
- Requires: OpenAPI parser, HTTP server, JSON Schema faker
- Constraint: Complex schemas require sophisticated generation

---

## Part 3: Project Rankings

### Ranking Criteria

**A. Feature Coverage** (How many package features does it exercise?)
**B. Real-World Usefulness** (How valuable is this to actual users?)
**C. Buildability** (How feasible is the MVP scope?)

### Ranking Table

| Rank | Project | Feature Coverage | Usefulness | Buildability | Total Score |
|------|---------|------------------|------------|--------------|-------------|
| 1 | API Health Dashboard | ⭐⭐⭐⭐⭐ (18/22) | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | 14 |
| 2 | Multi-Source Aggregator | ⭐⭐⭐⭐⭐ (19/22) | ⭐⭐⭐⭐ | ⭐⭐⭐ | 12 |
| 3 | HTTP Recorder & Replayer | ⭐⭐⭐⭐ (17/22) | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | 14 |
| 4 | Cache Warmer | ⭐⭐⭐⭐⭐ (18/22) | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | 13 |
| 5 | File Sync Service | ⭐⭐⭐⭐⭐ (19/22) | ⭐⭐⭐⭐ | ⭐⭐⭐ | 12 |
| 6 | Rate Limit Tester | ⭐⭐⭐⭐ (16/22) | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | 12 |
| 7 | API Diff Tool | ⭐⭐⭐⭐ (16/22) | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | 12 |
| 8 | Webhook Testing Server | ⭐⭐⭐⭐ (15/22) | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | 12 |
| 9 | API Documentation Validator | ⭐⭐⭐⭐ (17/22) | ⭐⭐⭐⭐ | ⭐⭐⭐ | 11 |
| 10 | Mock Server Generator | ⭐⭐⭐ (14/22) | ⭐⭐⭐⭐ | ⭐⭐⭐ | 10 |

### Top 3 Analysis

#### #1: API Health Dashboard (FetchWatch)

**Why it ranks highest:**
- **Feature Coverage**: Uses async, connection pooling, HTTP/2, profiling, retry, caching, logging, mocking, status enums, debug snapshots
- **Usefulness**: Monitoring is a universal need; every team with APIs needs health monitoring
- **Buildability**: Core functionality (fetch + async + display) is straightforward; can ship MVP in a weekend

**Feature Gaps**: Doesn't use multipart uploads or XML parsing (not relevant for health checks)

#### #2: HTTP Recorder & Replayer (FetchReplay)

**Why it ranks #2:**
- **Feature Coverage**: Exercises the Testing module deeply (Recorder, MockServer, MockResponse, MockResponseSequence)
- **Usefulness**: Directly enables better testing, reduces flaky tests, enables fixture-based development
- **Buildability**: Most functionality already exists in the package; primarily needs CLI wrapper and storage

**Feature Gaps**: Doesn't exercise connection pooling, HTTP/2, or caching (not relevant for recording)

#### #3: Multi-Source Data Aggregator (DataMesh)

**Why it ranks #3:**
- **Feature Coverage**: Uses the widest range of features including caching, stale-while-revalidate, stale-if-error, retry, auth, async, parsing
- **Usefulness**: BFF (Backend for Frontend) pattern is increasingly popular
- **Buildability**: More complex due to schema normalization and source configuration

**Feature Gaps**: Connection pooling limited benefit (different hosts); uses almost everything else

---

## Recommendation: Build API Health Dashboard (FetchWatch) First

### Justification

1. **Maximum Feature Demonstration**
   - Exercises 18 of 22 core features in a cohesive, understandable context
   - Shows off async/await, connection pooling, HTTP/2, profiling, retry, caching, and mocking
   - The features work together naturally (polling needs pooling, flaky endpoints need retry)

2. **Real-World Demand**
   - API health monitoring is a universal need
   - Can immediately provide value to any developer using Fetch PHP
   - Natural starting point for package adoption

3. **Showcases Package Strengths**
   - Connection pooling: Reuse connections for frequent polling
   - HTTP/2: Efficient multiplexing for multi-endpoint checks
   - FetchProfiler: Built-in latency tracking
   - MockServer: Easy testing of monitoring logic
   - Async: Parallel health checks

4. **Weekend-Achievable MVP**
   - Core: YAML config, CLI runner, text output
   - Uses existing package features with minimal glue code
   - Can iterate with web UI, alerts, history later

5. **Documentation Value**
   - Creates comprehensive example code for docs
   - Demonstrates best practices for async, pooling, retry
   - Shows integration patterns for profiling and logging

### MVP Implementation Outline

```php
// fetchwatch.php
use Fetch\Support\FetchProfiler;
use function async;
use function await;
use function map;

$config = yaml_parse_file('config.yaml');
$profiler = new FetchProfiler();

$handler = fetch_client()->getHandler()
    ->withConnectionPool()
    ->withHttp2()
    ->withProfiler($profiler)
    ->retry(3, 100);

$checks = await(map($config['endpoints'], function($endpoint) use ($handler) {
    return async(function() use ($handler, $endpoint) {
        $start = microtime(true);
        try {
            $response = $handler->get($endpoint['url']);
            return [
                'url' => $endpoint['url'],
                'status' => $response->status(),
                'healthy' => $response->successful(),
                'latency_ms' => (microtime(true) - $start) * 1000,
            ];
        } catch (\Throwable $e) {
            return [
                'url' => $endpoint['url'],
                'status' => 0,
                'healthy' => false,
                'error' => $e->getMessage(),
            ];
        }
    });
}, $config['concurrency'] ?? 10));

// Output results
foreach ($checks as $check) {
    $status = $check['healthy'] ? '✓' : '✗';
    echo "{$status} {$check['url']} - {$check['latency_ms']}ms\n";
}

// Connection pool stats via ClientHandler (uses ManagesConnectionPool trait)
print_r($handler->getPoolStats());
// Profiler metrics via FetchProfiler instance
print_r($profiler->getSummary());
```

This project demonstrates the full power of Fetch PHP while remaining practical and immediately useful.
