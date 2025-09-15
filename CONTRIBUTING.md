# Contributing to fetch-php

Thank you for your interest in contributing to fetch-php! We welcome contributions from the community and are pleased to have you join us.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [How to Contribute](#how-to-contribute)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Documentation](#documentation)
- [Issue Reporting](#issue-reporting)
- [Security Issues](#security-issues)
- [Community](#community)

## Code of Conduct

By participating in this project, you are expected to uphold our [Code of Conduct](CODE_OF_CONDUCT.md).

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally:

   ```bash
   git clone https://github.com/Thavarshan/fetch-php.git
   cd fetch-php
   ```

3. Add the upstream repository:

   ```bash
   git remote add upstream https://github.com/Thavarshan/fetch-php.git
   ```

## Development Setup

### Prerequisites

- PHP 8.2 or higher
- Composer
- Git

### Installation

1. Install dependencies:

   ```bash
   composer install
   ```

2. Run the test suite to ensure everything is working:

   ```bash
   composer test
   ```

3. Run the linter:

   ```bash
   composer lint
   ```

### Available Scripts

- `composer test` - Run PHPUnit tests
- `composer lint` - Run coding standards checks
- `composer fix` - Fix coding standard violations automatically

## How to Contribute

### Types of Contributions

We welcome several types of contributions:

- **Bug reports** - Help us identify and fix issues
- **Feature requests** - Suggest new functionality
- **Code contributions** - Submit bug fixes or new features
- **Documentation** - Improve or add documentation
- **Testing** - Add test coverage or improve existing tests

### Before You Start

1. Check existing [issues](https://github.com/Thavarshan/fetch-php/issues) and [pull requests](https://github.com/Thavarshan/fetch-php/pulls)
2. For large changes, create an issue first to discuss the approach
3. Make sure you understand the library's architecture and goals

## Pull Request Process

### 1. Create a Branch

Create a feature branch from `main`:

```bash
git checkout main
git pull upstream main
git checkout -b feature/your-feature-name
```

Use descriptive branch names:

- `feature/add-retry-mechanism`
- `fix/handle-connection-timeout`
- `docs/improve-readme`

### 2. Make Your Changes

- Write clear, readable code
- Follow the existing code style
- Add tests for new functionality
- Update documentation as needed

### 3. Test Your Changes

Run the full test suite:

```bash
composer test
composer lint
```

Make sure all tests pass and there are no linting errors.

### 4. Commit Your Changes

Write clear, descriptive commit messages:

```bash
git add .
git commit -m "feat: add retry mechanism for failed requests

- Add configurable retry attempts
- Implement exponential backoff
- Add tests for retry functionality"
```

We follow [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` for new features
- `fix:` for bug fixes
- `docs:` for documentation changes
- `test:` for adding tests
- `refactor:` for code refactoring
- `perf:` for performance improvements

### 5. Push and Create PR

```bash
git push origin feature/your-feature-name
```

Create a pull request through GitHub's interface.

### 6. PR Requirements

Your pull request should:

- [ ] Have a clear title and description
- [ ] Reference any related issues
- [ ] Include tests for new functionality
- [ ] Pass all CI checks
- [ ] Follow coding standards
- [ ] Update documentation if needed
- [ ] Be reviewed and approved by maintainers

## Coding Standards

We follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards with some additional rules:

### PHP Code Style

- Use Laravel Pint for code formatting
- Follow PSR-4 autoloading standards
- Use strict typing (`declare(strict_types=1);`)
- Use meaningful variable and method names
- Write PHPDoc comments for public methods

### Example

```php
<?php

declare(strict_types=1);

namespace Fetch\Http;

/**
 * Handles HTTP request configuration and execution.
 */
final class Request
{
    /**
     * Set the request timeout.
     */
    public function timeout(int $seconds): self
    {
        // Implementation
    }
}
```

## Testing

We use PHPUnit for testing. All contributions should include appropriate tests.

### Test Structure

- Unit tests go in `tests/Unit/`
- Integration tests go in `tests/Integration/`
- Use descriptive test method names
- Test both success and failure scenarios
- Mock external dependencies

### Writing Tests

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function test_it_can_set_timeout(): void
    {
        $request = new Request();
        $result = $request->timeout(30);

        $this->assertInstanceOf(Request::class, $result);
        $this->assertEquals(30, $result->getTimeout());
    }
}
```

## Documentation

### Code Documentation

- All public methods must have PHPDoc comments
- Include parameter types and return types
- Describe what the method does and any side effects
- Include `@throws` annotations for exceptions

### User Documentation

- Update README.md for new features
- Add examples for new functionality
- Update the changelog for significant changes
- Consider adding documentation to the docs/ folder

## Issue Reporting

### Bug Reports

When reporting bugs, please include:

- Library version
- PHP version
- Operating system
- Minimal code example
- Expected vs actual behavior
- Error messages/stack traces

### Feature Requests

For feature requests, describe:

- The problem you're trying to solve
- Your proposed solution
- Alternative approaches considered
- Example usage

## Security Issues

**DO NOT** report security vulnerabilities in public issues.

Instead, please email security concerns to: [tjthavarshan@gmail.com](mailto:tjthavarshan@gmail.com)

We take security seriously and will respond promptly to security reports.

## Community

- Join discussions in [GitHub Discussions](https://github.com/Thavarshan/fetch-php/discussions)
- Follow the project for updates
- Help others by answering questions
- Share your use cases and feedback

## Recognition

Contributors will be recognized in:

- The project's README
- Release notes for significant contributions
- The project's contributors page

## Questions?

If you have questions about contributing, please:

1. Check this document first
2. Look at existing issues and discussions
3. Create a new discussion topic
4. Reach out to maintainers

Thank you for contributing to fetch-php! 🚀
