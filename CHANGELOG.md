# Release Notes

## [Unreleased](https://github.com/Thavarshan/fetch-php/compare/v3.0.0...HEAD)

## [v3.0.0](https://github.com/Thavarshan/fetch-php/compare/v2.0.6...v3.0.0) - 2025-05-04

### Added

- **True Asynchronous Support**: Completely reimplemented asynchronous functionality using Matrix's PHP Fiber-based library
- **JavaScript-like Syntax**: Added support for JavaScript-like async/await patterns with `async()` and `await()` functions
- **Promise-based API**: Introduced a clean Promise interface with `then()`, `catch()`, and `finally()` methods
- **Concurrent Request Helpers**: Added support for managing multiple concurrent requests with `all()`, `race()`, and `any()` functions
- **Task Lifecycle Management**: Implemented proper task lifecycle control (start, pause, resume, cancel, retry)
- **Enhanced Error Handling**: Added improved error handling with customizable error handlers
- **New Helper Methods**:
  - `wrapAsync()`: For wrapping callables in async functions
  - `awaitPromise()`: For awaiting promise resolution

### Changed

- **New Matrix Integration**: Migrated from legacy AsyncHelper to the new Matrix Fiber-based promises
- **Return Type Changes**: Updated method signatures to use union types (`ResponseInterface|PromiseInterface`)
- **Simplified API**: Streamlined the API to match JavaScript's fetch pattern more closely
- **Improved Retry Logic**: Enhanced retry mechanisms for both synchronous and asynchronous requests
- **Updated Documentation**: Completely revised documentation to reflect the new async patterns

### Removed

- **AsyncHelper Class**: Removed the legacy AsyncHelper class in favor of direct Matrix integration

### Fixed

- **Promise Handling**: Fixed various issues with Promise resolution and rejection
- **Retry Mechanism**: Fixed retry logic to properly handle both network and server errors
- **Error Propagation**: Improved how errors are propagated through Promise chains
- **Event Loop Management**: Fixed event loop management for proper async task execution

## [v2.0.6](https://github.com/Thavarshan/fetch-php/compare/v2.0.5...v2.0.6) - 2025-05-03

### Added

- Added `withQueryParameter()` method for adding a single query parameter
- Added `withJson()` method as a convenient way to set JSON request bodies
- Added `withFormParams()` and `withMultipart()` helper methods for form submissions
- Added `configurePostableRequest()` helper method to standardize request body handling

### Changed

- Enhanced `withBody()` method to support multiple content types (JSON, form-encoded, multipart)
- Improved `post()` and `put()` methods to properly handle different content types
- Improved retry mechanism with exponential backoff and jitter for better reliability
- Enhanced error handling to be more selective about which errors trigger retries

### Fixed

- Fixed query parameter handling to properly merge with existing parameters instead of overwriting
- Fixed URL construction in `getFullUri()` to correctly append query parameters
- Fixed retry logic to perform exactly the specified number of retries (not one extra)
- Fixed duplicate implementation of `isRetryableError()` method
- Fixed retry failure detection to properly identify the last retry attempt

## [v2.0.5](https://github.com/Thavarshan/fetch-php/compare/v2.0.4...v2.0.5) - 2025-03-30

### Added

- Added the following utility methods to `ClientHandler`:
  - `getOptions()`: Retrieve the full request options array.
  - `getHeaders()`: Retrieve the headers array from the request options.
  - `hasHeader(string $header): bool`: Check if a specific header is set in the request.
  - `hasOption(string $option): bool`: Check if a specific option is set in the request.

**Full Changelog**: <https://github.com/Thavarshan/fetch-php/compare/2.0.4...2.0.5>

## [v2.0.4](https://github.com/Thavarshan/fetch-php/compare/v2.0.3...v2.0.4) - 2025-03-30

### Added

- Added support for setting a single request header via `withHeader()` method in `ClientHandler`.
- Introduced new methods in `ClientHandler` interface:
  - `withToken(string $token): self`
  - `withAuth(string $username, string $password): self`
  - `withHeader(string $header, mixed $value): self`
- Added `laravel/pint` as a development dependency.

### Changed

- Updated package version in `composer.json` from `2.0.3` to `2.0.4`.
- Bump dependabot/fetch-metadata from 2.2.0 to 2.3.0 by @dependabot in <https://github.com/Thavarshan/fetch-php/pull/13>
- Update README fixing syntax error by @tresbach in <https://github.com/Thavarshan/fetch-php/pull/14>

### Fixed

- Restored required dependencies `jerome/matrix` and `psr/http-message` to `composer.json`.

### New Contributors

- @tresbach made their first contribution in <https://github.com/Thavarshan/fetch-php/pull/14>

**Full Changelog**: <https://github.com/Thavarshan/fetch-php/compare/2.0.3...2.0.4>

## [v2.0.3](https://github.com/Thavarshan/fetch-php/compare/v2.0.2...v2.0.3) - 2024-12-06

### Added

- Support all PHP `8.x` versions

### Changed

- Updated dependencies
- Updated dev dependencies

**Full Changelog**: <https://github.com/Thavarshan/fetch-php/compare/2.0.2...2.0.3>

## [v2.0.2](https://github.com/Thavarshan/fetch-php/compare/v2.0.1...v2.0.2) - 2024-10-19

### Added

- Add Laravel Pint by @patinthehat in <https://github.com/Thavarshan/fetch-php/pull/8>
- Add `isAsync` method to `ClientHandler`

### Changed

- Update `async` method to accept arguments

#### New Contributors

- @patinthehat made their first contribution in <https://github.com/Thavarshan/fetch-php/pull/8>

**Full Changelog**: <https://github.com/Thavarshan/fetch-php/compare/2.0.1...2.0.2>

## [v2.0.1](https://github.com/Thavarshan/fetch-php/compare/v2.0.0...v2.0.1) - 2024-10-03

### Changed

- Refactor `withBody` method in `ClientHandler` to only accept array arguments and encode given arguments to json string when called
- Update documentation and fix typos

## [v2.0.0](https://github.com/Thavarshan/fetch-php/compare/v1.2.0...v2.0.0) - 2024-09-30

### Added

- **VitePress Documentation**: Introduced a new VitePress documentation site, including comprehensive API reference, examples, and guides. The site is accessible under the `./docs/` directory and deployable on Netlify.
- **Async Functionality**: Introduced a new async functionality using `async()` function powered by PHP Fibers for true asynchronous operations. This provides a modern, JavaScript-like `async/await` syntax for handling non-blocking requests.
- **Fluent API for HTTP Requests**: Added support for a fluent, chainable API inspired by Laravel’s HTTP client. Methods like `withHeaders()`, `withBody()`, `withToken()`, and others allow for more intuitive and flexible request building.
- **Task Lifecycle Management**: Introduced `Matrix`-powered task lifecycle management, allowing tasks to be paused, resumed, canceled, and retried for asynchronous requests.
- **Retry Logic with Exponential Backoff**: Added customizable retry logic for both synchronous and asynchronous requests, allowing for retries with exponential backoff in case of network failures.

### Changed

- **Refactor of `fetch()` Function**: Refactored the core `fetch()` function to support both synchronous and asynchronous requests seamlessly. Now supports direct chaining of promises with `then()` and `catch()` for async requests.
- **Improved Error Handling**: Enhanced error handling to provide better control over exceptions during both sync and async requests. Introduced customizable error handlers and improved promise rejection management.
- **Response Class Overhaul**: The `Response` class has been revamped to better handle different response formats, including JSON, text, binary data (array buffer), and streams. This improves the handling of diverse response types.
- **Expanded Guzzle Configuration Options**: The package now exposes all of Guzzle’s configuration options directly via the `ClientHandler`, including proxy settings, cookies, redirects, and SSL certificates.

### Removed

- **Deprecated `fetchAsync` Method**: The `fetchAsync` function has been fully removed, replaced by the new `async()` helper function. This provides better async handling and aligns with the new architecture based on PHP Fibers.
- **Symfony `Response` Dependency**: Completely removed the dependency on Symfony’s `Response` class in favor of Guzzle’s PSR-7 compliant `Response` implementation. This reduces the package size and simplifies the dependencies.

### Fixed

- **Laravel 10 Compatibility Enhancements**: Improved compatibility with Laravel 10 and Symfony 6.x by resolving issues related to HTTP foundation conflicts. The package now supports a wider range of Symfony and Laravel versions without dependency clashes.
- **Async Error Handling**: Fixed issues with async request error handling, ensuring that promises are properly resolved or rejected with meaningful exception messages.
- **Persistent Guzzle Client Instances**: Fixed an issue where a new Guzzle client was instantiated for every request. The Guzzle client is now reused efficiently across both sync and async operations.

## [v1.2.0](https://github.com/Thavarshan/fetch-php/compare/v1.1.1...v1.2.0) - 2024-09-27

### Added

- **Guzzle PSR-7 Response Support**: Introduced the `guzzlehttp/psr7` library for managing HTTP responses, providing more flexibility and aligning with PSR-7 standards.
- **Enhanced Error Handling**: Improved error handling for both synchronous and asynchronous requests using Guzzle's `RequestException`.
- **New README Documentation**: Updated the README to reflect the changes in response handling and usage of the `guzzlehttp/psr7` response.

### Changed

- **Refactor Response Class**: The `Response` class now extends `guzzlehttp/psr7\Response` instead of Symfony's `Response`, reducing dependency overhead and improving compatibility with PSR-7.
- **HTTP Request Handling**: Refactored the `Http` class to ensure seamless request handling with the new `guzzlehttp/psr7` response model.
- **Removed Symfony Response Dependency**: Dropped Symfony's `Response` class in favor of the more lightweight and standard `guzzlehttp/psr7`.

### Fixed

- **Laravel 10 Compatibility**: Addressed compatibility issues with Laravel 10 by ensuring the package can work with Symfony 6.x versions while allowing flexibility for future upgrades.
- **Composer Dependency Conflicts**: Resolved conflicts with Symfony’s HTTP Foundation, ensuring compatibility for projects that depend on specific versions of Symfony and other packages.
- **Asynchronous Error Handling**: Fixed issues related to handling exceptions in asynchronous requests, ensuring proper error handling and promise resolution.

## [v1.1.1](https://github.com/Thavarshan/fetch-php/compare/v1.1.0...v1.1.1) - 2024-09-25

### Changed

- **Updated Composer Dependencies:**
  The `symfony/http-foundation` dependency has been updated to support versions `^6.0 || ^7.0`, ensuring compatibility with Laravel 10 and future versions. This change allows smooth integration with Laravel 9.x, 10.x, and future Laravel versions without version conflicts.
- **Enhanced Test Suite for Compatibility:**
  The GitHub Actions test matrix has been expanded to test against Laravel 9.x, 10.x, and 11.x, ensuring future compatibility with new Laravel releases.

### Fixed

- **Compatibility Issue with Laravel 10:**
  The package had an installation conflict when used with Laravel 10 due to outdated `symfony/http-foundation` version constraints. This release resolves the issue, allowing users to install the package without dependency conflicts in Laravel 10 environments.

## [v1.1.0](https://github.com/Thavarshan/fetch-php/compare/v1.1.4...v1.1.0) - 2024-09-24

### Changed

- **`Http` Class**: The HTTP request handling has been abstracted away into a new `Http` class, improving the overall structure of the library and allowing for cleaner, centralized management of requests via Guzzle.
- **Guzzle Client Reuse**: The Guzzle client is now instantiated as a singleton, meaning it is created once and reused across all HTTP requests. This change improves the performance of the library when making multiple requests.
- **Deprecation of `fetchAsync`**: The `fetchAsync` method is now deprecated in favor of `fetch_async`. A deprecation warning has been added to guide users to transition to the new method.

### Fixed

- **Guzzle Client Instantiation Issue**: Resolved an issue where the Guzzle client was instantiated for every HTTP request, leading to performance inefficiencies. Now, the client is reused across requests, reducing overhead.

## v1.0.0 - 2024-09-14

Initial release.

## [v2.0.5](https://github.com/Thavarshan/fetch-php/compare/v2.0.4...v2.0.5) - 2025-03-29

### Added

- Added the following utility methods to `ClientHandler`:
  - `getOptions()`: Retrieve the full request options array.
  - `getHeaders()`: Retrieve the headers array from the request options.
  - `hasHeader(string $header): bool`: Check if a specific header is set in the request.
  - `hasOption(string $option): bool`: Check if a specific option is set in the request.

**Full Changelog**: <https://github.com/Thavarshan/fetch-php/compare/2.0.4...2.0.5>

## [v2.0.4](https://github.com/Thavarshan/fetch-php/compare/v2.0.3...v2.0.4) - 2025-03-29

### Added

- Added support for setting a single request header via `withHeader()` method in `ClientHandler`.

- Introduced new methods in `ClientHandler` interface:

  - `withToken(string $token): self`
  - `withAuth(string $username, string $password): self`
  - `withHeader(string $header, mixed $value): self`

- Added `laravel/pint` as a development dependency.

### Changed

- Updated package version in `composer.json` from `2.0.3` to `2.0.4`.
- Bump dependabot/fetch-metadata from 2.2.0 to 2.3.0 by @dependabot in <https://github.com/Thavarshan/fetch-php/pull/13>
- Update README fixing syntax error by @tresbach in <https://github.com/Thavarshan/fetch-php/pull/14>

### Fixed

- Restored required dependencies `jerome/matrix` and `psr/http-message` to `composer.json`.

### New Contributors

- @tresbach made their first contribution in <https://github.com/Thavarshan/fetch-php/pull/14>

**Full Changelog**: <https://github.com/Thavarshan/fetch-php/compare/2.0.3...2.0.4>

## [v2.0.3](https://github.com/Thavarshan/fetch-php/compare/v2.0.2...v2.0.3) - 2024-12-06

### Added

- Support all PHP `8.x` versions

### Changed

- Updated dependencies
- Updated dev dependencies

**Full Changelog**: <https://github.com/Thavarshan/fetch-php/compare/2.0.2...2.0.3>

## [v2.0.2](https://github.com/Thavarshan/fetch-php/compare/v2.0.2...v2.0.2) - 2024-10-19

### Added

- Add Laravel Pint by @patinthehat in <https://github.com/Thavarshan/fetch-php/pull/8>
- Add `isAsync` method to `ClientHandler`

### Changed

- Update `async` method to accept arguments

#### New Contributors

- @patinthehat made their first contribution in <https://github.com/Thavarshan/fetch-php/pull/8>

**Full Changelog**: <https://github.com/Thavarshan/fetch-php/compare/2.0.1...2.0.2>
