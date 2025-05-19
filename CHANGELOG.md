# Release Notes

## [Unreleased](https://github.com/Thavarshan/fetch-php/compare/v3.2.2...HEAD)

## [v3.2.2](https://github.com/Thavarshan/fetch-php/compare/v3.2.1...v3.2.2) - 2025-05-19

### Fixed

- "Fatal error: Uncaught Error: Interface `Psr\Log\LoggerAwareInterface` not found" closes #21

## [v3.2.1](https://github.com/Thavarshan/fetch-php/compare/v3.2.0...v3.2.1) - 2025-05-17

### Changed

- Updated documentation
- Updated dependencies

## [v3.2.0](https://github.com/Thavarshan/fetch-php/compare/v3.1.1...v3.2.0) - 2025-05-16

### Added

- Added tests for various HTTP methods (GET, POST, PUT, DELETE, PATCH, OPTIONS) with appropriate assertions.

### Changed

- Simplified **`ManagesRetriesTest`** by using a mock class for retry-logic testing.
- Consolidated tests for retryable status codes and exceptions into a single method.
- Updated **`PerformsHttpRequestsTest`** to use `GuzzleHttp` client mocks for more accurate request simulation.
- Enhanced exception-handling tests to verify error messages and retry behaviour.
- Removed unnecessary reflection methods and streamlined test setup.

### Fixed

- Improved back-off delay calculations in retry tests to ensure correct timing.

**Full Changelog**: [https://github.com/Thavarshan/fetch-php/compare/3.1.1...3.2.0](https://github.com/Thavarshan/fetch-php/compare/3.1.1...3.2.0)

## [v3.1.1](https://github.com/Thavarshan/fetch-php/compare/v3.1.0...v3.1.1) - 2025-05-16

### Added

- Implemented the missing `finalizeRequest()` method in the `PerformsHttpRequests` trait. This method centralizes request finalization logic, enabling internal shortcut methods like `get()`, `post()`, etc., to function correctly.

### Changed

- Internal shortcut HTTP methods (`get()`, `post()`, `put()`, etc.) in `PerformsHttpRequests` now route through the newly added `finalizeRequest()` method for consistent request handling.

### Fixed

- Fixed a fatal error caused by calling an undefined `finalizeRequest()` method in `ClientHandler`. The missing method has now been properly defined and implemented.

## [v3.1.0](https://github.com/Thavarshan/fetch-php/compare/v3.0.0...v3.1.0) - 2025-05-10

### Added

- **PSR-18 Client**: `Fetch\Http\Client` now implements `Psr\Http\Client\ClientInterface` for drop-in interoperability.

- **Fluent Request Builder**: Chainable helpers on `ClientHandler` for headers, query params, JSON/form/multipart bodies, bearer token, basic auth, timeouts, redirects, cookies, proxy, certificates.

- **Async/Promise Support**: Built-in ReactPHP-style promises (`async()`, `await()`, `all()`, `race()`, `any()`, `sequence()`), with `->async()` toggle and `wrapAsync()`/`awaitPromise()` helpers.

- **Automatic Retries**: Configurable max retries, retry delay, exponential backoff with jitter, and retry-on-status (408, 429, 5xx) or exceptions (`ConnectException`).

- **PSR-3 Logging**: Optional `LoggerInterface` injection on `Client` and `ClientHandler` with info/debug/error logs and sensitive-data masking for retries, requests, and responses.

- **Immutable PSR-7 Extensions**:

  - `Fetch\Http\Request` extends Guzzle’s PSR-7 `Request` with immutability traits and JSON/form/multipart constructors.
  - `Fetch\Http\Response` extends Guzzle’s PSR-7 `Response` with buffered body, array-access to JSON payloads, and helpers: `->json()`, `->text()`, `->xml()`, `->blob()`, `->arrayBuffer()`, status inspectors, etc.

- **Enums for Safety**: `Fetch\Enum\Method`, `ContentType`, and `Status` enums for validating methods, content types, and status codes.

- **Test Helpers**: `ClientHandler::createMockResponse()` and `createJsonResponse()` to easily stub HTTP responses in unit tests.

### Changed

- **Consolidated Handler Traits**: Core behavior refactored into six concerns (`ConfiguresRequests`, `HandlesUris`, `ManagesPromises`, `ManagesRetries`, `PerformsHttpRequests`, `SendsRequests`) for clearer separation of URI handling, retries, promises, and request dispatch.
- **Unified Fetch API**: `Client::fetch()` now returns either a `ResponseInterface` or the handler for method chaining, replacing older ad-hoc patterns.
- **Error-handling Alignment**: Top-level `Client::sendRequest()` now throws standardized `NetworkException`, `RequestException`, or `ClientException` instead of raw `RuntimeException`.
- **Default Options Management**: Moved default method, headers, and timeout into `ClientHandler::$defaultOptions` with new `getDefaultOptions()` and `setDefaultOptions()`.
- **Guzzle Configuration**: `ClientHandler` now reuses a single Guzzle client instance with `RequestOptions::HTTP_ERRORS` disabled (handling errors internally) and `CONNECT_TIMEOUT` mapped from handler timeout.

### Removed

- None

---

## [v3.0.0](https://github.com/Thavarshan/fetch-php/compare/v2.0.6...v3.0.0) - 2025-05-04

### Added

- **True Asynchronous Support**: Completely reimplemented asynchronous functionality using Matrix’s PHP Fiber-based library.

- **JavaScript-like Syntax**: Added support for JavaScript-like async/await patterns with `async()` and `await()` functions.

- **Promise-based API**: Introduced a clean Promise interface with `then()`, `catch()`, and `finally()` methods.

- **Concurrent Request Helpers**: Added support for managing multiple concurrent requests with `all()`, `race()`, and `any()` functions.

- **Task Lifecycle Management**: Implemented proper task lifecycle control (start, pause, resume, cancel, retry).

- **Enhanced Error Handling**: Added improved error handling with customizable error handlers.

- **New Helper Methods**:

  - `wrapAsync()`: For wrapping callables in async functions
  - `awaitPromise()`: For awaiting promise resolution

### Changed

- **New Matrix Integration**: Migrated from legacy `AsyncHelper` to the new Matrix Fiber-based promises.
- **Return Type Changes**: Updated method signatures to use union types (`ResponseInterface|PromiseInterface`).
- **Simplified API**: Streamlined the API to match JavaScript’s fetch pattern more closely.
- **Improved Retry Logic**: Enhanced retry mechanisms for both synchronous and asynchronous requests.
- **Updated Documentation**: Completely revised documentation to reflect the new async patterns.

### Removed

- **AsyncHelper Class**: Removed the legacy `AsyncHelper` class in favor of direct Matrix integration.

### Fixed

- **Promise Handling**: Fixed various issues with Promise resolution and rejection.
- **Retry Mechanism**: Fixed retry logic to properly handle both network and server errors.
- **Error Propagation**: Improved how errors are propagated through Promise chains.
- **Event Loop Management**: Fixed event loop management for proper async task execution.

---

## [v2.0.6](https://github.com/Thavarshan/fetch-php/compare/v2.0.5...v2.0.6) - 2025-05-03

### Added

- Added `withQueryParameter()` method for adding a single query parameter.
- Added `withJson()` method as a convenient way to set JSON request bodies.
- Added `withFormParams()` and `withMultipart()` helper methods for form submissions.
- Added `configurePostableRequest()` helper method to standardize request body handling.

### Changed

- Enhanced `withBody()` method to support multiple content types (JSON, form-encoded, multipart).
- Improved `post()` and `put()` methods to properly handle different content types.
- Improved retry mechanism with exponential backoff and jitter for better reliability.
- Enhanced error handling to be more selective about which errors trigger retries.

### Fixed

- Fixed query parameter handling to properly merge with existing parameters instead of overwriting.
- Fixed URL construction in `getFullUri()` to correctly append query parameters.
- Fixed retry logic to perform exactly the specified number of retries (not one extra).
- Fixed duplicate implementation of `isRetryableError()` method.
- Fixed retry failure detection to properly identify the last retry attempt.

---

## [v2.0.5](https://github.com/Thavarshan/fetch-php/compare/v2.0.4...v2.0.5) - 2025-03-30

### Added

- `getOptions()`, `getHeaders()`, `hasHeader(string $header)`, and `hasOption(string $option)` methods on `ClientHandler`.

**Full Changelog**: [https://github.com/Thavarshan/fetch-php/compare/v2.0.4...v2.0.5](https://github.com/Thavarshan/fetch-php/compare/v2.0.4...v2.0.5)

---

## [v2.0.4](https://github.com/Thavarshan/fetch-php/compare/v2.0.3...v2.0.4) - 2025-03-30

### Added

- Support for setting a single request header via `withHeader()` in `ClientHandler`.
- `withToken(string $token): self`, `withAuth(string $username, string $password): self`, and `withHeader(string $header, mixed $value): self` methods on `ClientHandler`.
- Added `laravel/pint` as a dev dependency.

### Changed

- Bumped `composer.json` to 2.0.4.
- Bumped `dependabot/fetch-metadata` from 2.2.0 to 2.3.0.
- README syntax fix.

### Fixed

- Restored `jerome/matrix` and `psr/http-message` dependencies.

### New Contributors

- @tresbach in [https://github.com/Thavarshan/fetch-php/pull/14](https://github.com/Thavarshan/fetch-php/pull/14)

**Full Changelog**: [https://github.com/Thavarshan/fetch-php/compare/v2.0.3...v2.0.4](https://github.com/Thavarshan/fetch-php/compare/v2.0.3...v2.0.4)

---

## [v2.0.3](https://github.com/Thavarshan/fetch-php/compare/v2.0.2...v2.0.3) - 2024-12-06

### Added

- Support for all PHP 8.x versions.

### Changed

- Updated dependencies and dev-dependencies.

**Full Changelog**: [https://github.com/Thavarshan/fetch-php/compare/v2.0.2...v2.0.3](https://github.com/Thavarshan/fetch-php/compare/v2.0.2...v2.0.3)

---

## [v2.0.2](https://github.com/Thavarshan/fetch-php/compare/v2.0.1...v2.0.2) - 2024-10-19

### Added

- Added Laravel Pint integration.
- `isAsync()` method on `ClientHandler`.

### Changed

- Updated `async()` method to accept arguments.

#### New Contributors

- @patinthehat in [https://github.com/Thavarshan/fetch-php/pull/8](https://github.com/Thavarshan/fetch-php/pull/8)

**Full Changelog**: [https://github.com/Thavarshan/fetch-php/compare/v2.0.1...v2.0.2](https://github.com/Thavarshan/fetch-php/compare/v2.0.1...v2.0.2)

---

## [v2.0.1](https://github.com/Thavarshan/fetch-php/compare/v2.0.0...v2.0.1) - 2024-10-03

### Changed

- Refactored `withBody()` in `ClientHandler` to accept only arrays and JSON-encode them.
- Documentation and typo fixes.

---

## [v2.0.0](https://github.com/Thavarshan/fetch-php/compare/v1.2.0...v2.0.0) - 2024-09-30

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

## [v1.2.0](https://github.com/Thavarshan/fetch-php/compare/v1.1.1...v1.2.0) - 2024-09-27

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

## [v1.1.1](https://github.com/Thavarshan/fetch-php/compare/v1.1.0...v1.1.1) - 2024-09-25

### Changed

- Updated `symfony/http-foundation` to `^6.0 || ^7.0`.
- Expanded GitHub Actions test matrix for Laravel 9–11.

### Fixed

- Resolved Laravel 10 install conflict.

---

## [v1.1.0](https://github.com/Thavarshan/fetch-php/compare/v1.0.0...v1.1.0) - 2024-09-24

### Changed

- Abstracted HTTP handling into a new `Http` class.
- Guzzle client reused as a singleton.
- Deprecated `fetchAsync` in favor of `fetch_async`.

### Fixed

- Single-instance Guzzle instantiation.

---

## v1.0.0 - 2024-09-14

Initial release.

## [v3.2.2](https://github.com/Thavarshan/fetch-php/compare/v3.2.1...v3.2.2) - 2025-05-19

### Fixed

- "Fatal error: Uncaught Error: Interface `Psr\Log\LoggerAwareInterface` not found" closes #21

**Full Changelog**: <https://github.com/Thavarshan/fetch-php/compare/3.2.1...3.2.2>

## [v3.2.1](https://github.com/Thavarshan/fetch-php/compare/v3.2.1...v3.2.1) - 2025-05-17

### Changed

- Updated documentation
- Updated dependencies

**Full Changelog**: <https://github.com/Thavarshan/fetch-php/compare/3.2.0...3.2.1>
