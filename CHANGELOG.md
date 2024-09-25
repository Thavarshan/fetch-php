# Release Notes

## [Unreleased](https://github.com/Thavarshan/fetch-php/compare/v1.1.1...HEAD)

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
