---
paths:
  - "tests/**/*.php"
---

# Test rules (`tests/**`)

- PHPUnit 11, namespace `Tests\`. Files end in `*Test.php`.
- **Test methods are `snake_case`** (`test_it_does_x`) — camelCase fails
  `composer lint` (`pint.json` `php_unit_method_casing`).
- Unit → `tests/Unit/`, integration → `tests/Integration/`, reusable fakes →
  `tests/Mocks/` (excluded from the suite and PHPStan).
- **No real network.** Use `Fetch\Testing\MockServer`, Mockery, or a Guzzle
  `MockHandler`. Keep tests deterministic — no `sleep()` or wall-clock asserts.
- Reset shared state: extend `Tests\TestCase` (resets `GlobalServices`), and
  reset `MockServer` / `fetch_client(reset: true)` when used.
- Bug fixes need a regression test that fails before the fix. Cover error,
  timeout, and cancellation paths, not just the happy path.
- Full detail: `fetch-php-test-development`.
