# Release Notes

## [Unreleased](https://github.com/Thavarshan/fetch-php/compare/v1.1.0...HEAD)

## v1.0.0 - 2024-09-14

Initial release.

## [v1.1.0](https://github.com/Thavarshan/fetch-php/compare/v1.1.4...v1.1.0) - 2024-09-24

### Changed

- **`Http` Class**: The HTTP request handling has been abstracted away into a new `Http` class, improving the overall structure of the library and allowing for cleaner, centralized management of requests via Guzzle.
- **Guzzle Client Reuse**: The Guzzle client is now instantiated as a singleton, meaning it is created once and reused across all HTTP requests. This change improves the performance of the library when making multiple requests.
- **Deprecation of `fetchAsync`**: The `fetchAsync` method is now deprecated in favor of `fetch_async`. A deprecation warning has been added to guide users to transition to the new method.

### Fixed

- **Guzzle Client Instantiation Issue**: Resolved an issue where the Guzzle client was instantiated for every HTTP request, leading to performance inefficiencies. Now, the client is reused across requests, reducing overhead.
