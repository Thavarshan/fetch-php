# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Core Commands

### Testing

- **Run all tests**: `composer test`
- **Run unit tests only**: `composer test:unit`
- **Run integration tests only**: `composer test:integration`
- **Run tests with coverage**: `composer test:coverage`
- **Run specific test**: `./vendor/bin/phpunit tests/Unit/SomeTest.php`
- **Run tests with filter**: `./vendor/bin/phpunit --filter=testMethodName`

### Code Quality

- **Lint code**: `composer lint` (runs Duster)
- **Fix code style**: `composer fix` (runs Duster fix)
- **Static analysis**: `composer analyse` (runs PHPStan)
- **Run all checks**: `composer check` (lint + analyse + test)

### Installation & Setup

- **Install dependencies**: `composer install`
- **Run with NO_NETWORK=1**: For tests that shouldn't make network calls

## Architecture Overview

**Fetch PHP** is a modern HTTP client library that brings JavaScript's fetch API experience to PHP, built on top of Guzzle HTTP.

### Core Structure

```text
src/Fetch/
├── Http/                    # Core HTTP components
│   ├── Client.php          # Main HTTP client (PSR-18 compliant)
│   ├── ClientHandler.php   # Handler for client operations
│   ├── Request.php         # HTTP request wrapper
│   └── Response.php        # HTTP response wrapper
├── Concerns/               # Trait-based functionality
│   ├── ConfiguresRequests.php    # Request configuration
│   ├── HandlesUris.php          # URI handling
│   ├── ManagesPromises.php      # Async promise management
│   ├── ManagesRetries.php       # Retry logic
│   └── PerformsHttpRequests.php # HTTP request execution
├── Enum/                   # Type-safe enumerations
│   ├── Method.php          # HTTP methods (GET, POST, etc.)
│   ├── ContentType.php     # Content type constants
│   └── Status.php          # HTTP status codes
├── Support/
│   └── helpers.php         # Global helper functions
└── Interfaces/             # Contracts
```

### Key Design Patterns

1. **Multiple API Styles**: Supports both JavaScript-like `fetch()` and traditional PHP helper functions (`get()`, `post()`, etc.)

2. **Global Client Management**: Uses a singleton pattern via `fetch_client()` for application-wide configuration

3. **Trait-based Architecture**: Core functionality split into focused traits for better separation of concerns

4. **PSR Compliance**: Implements PSR-18 (HTTP Client), PSR-7 (HTTP Messages), and PSR-3 (Logger)

5. **Async/Promise Support**: Built on React PHP promises with `async()`, `await()`, `all()`, `race()` utilities

### Helper Functions (src/Fetch/Support/helpers.php)

- `fetch()` - Main JavaScript-like API
- `fetch_client()` - Global client instance
- HTTP method helpers: `get()`, `post()`, `put()`, `patch()`, `delete()`
- Async utilities: `async()`, `await()`, `all()`, `race()`, `map()`, `batch()`, `retry()`

## Development Guidelines

### Code Style

- Uses **Duster** (by Tighten) for code formatting
- Configuration in `pint.json` (Laravel Pint rules)
- Runs PHP CS Fixer and PHPStan under the hood
- Enforces PSR-12 with custom rules

### Testing Framework

- **PHPUnit** for unit testing
- Test structure: `tests/Unit/`, `tests/Integration/`
- Coverage reports generated with Xdebug
- CI runs tests on PHP 8.3, 8.4 across Ubuntu, Windows, macOS

### Key Dependencies

- **Guzzle HTTP** 7.9+ (underlying HTTP client)
- **React components** (event-loop, promise) for async operations
- **Jerome/Matrix** 3.3+ (utility library)

### Branching Strategy

- Main branch: `main`
- Feature branches: `feature/*`, `fix/*`, `refactor/*`
- Development branch: `develop`

### Environment Variables

- `NO_NETWORK=1` - Disables network calls in tests

## Common Development Tasks

### Adding New HTTP Methods

1. Add enum value to `src/Fetch/Enum/Method.php`
2. Add helper function in `src/Fetch/Support/helpers.php`
3. Update `PerformsHttpRequests` trait if needed
4. Add tests in `tests/Unit/`

### Adding New Configuration Options

1. Update `ConfiguresRequests` trait
2. Add to options processing in helper functions
3. Document in README examples
4. Add tests for new option

### Debugging Failed Tests

- Check CI logs for specific PHP version failures
- Run locally with same PHP version: `php -v`
- Use `NO_NETWORK=1` for offline testing
- Check coverage reports in `coverage/` directory
