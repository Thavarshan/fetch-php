# Release Notes

## [Unreleased](https://github.com/Thavarshan/fetch-php/compare/v1.2.0...HEAD)

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
