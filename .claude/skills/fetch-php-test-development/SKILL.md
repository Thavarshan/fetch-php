---
name: fetch-php-test-development
description: Write and run PHPUnit 11 tests for Fetch PHP following repo conventions — unit vs integration placement, Mockery and MockServer, snake_case test methods, deterministic async/timing tests, and offline (no-network) execution. Use when adding or fixing tests, or when a change needs coverage.
---

# Fetch PHP test development

Follow the Testing section of [AGENTS.md](../../../AGENTS.md).

## When to use

Adding coverage for a feature/fix, writing a regression test, or making an
existing test deterministic. For diagnosing *why* a test fails, use
`fetch-php-debug-failure`.

## Conventions

- **PHPUnit 11**, namespace `Tests\`. Files end in `*Test.php`.
- **Test methods are `snake_case`** (`public function test_it_does_x(): void`) —
  enforced by `pint.json`; camelCase will fail `composer lint`.
- Placement: `tests/Unit/` for isolated class behavior, `tests/Integration/`
  for cross-component flows (events, middleware, streaming, concurrency).
  Reusable fakes go in `tests/Mocks/` (excluded from the suite and PHPStan).
- Extend `Tests\TestCase` when you touch global state — it resets
  `GlobalServices` in `tearDown`. Also reset `MockServer::resetInstance()` and
  `fetch_client(reset: true)` when used. `src/Fetch/Support` is excluded from
  coverage.

## Faking HTTP (never hit the network)

Choose the lightest fake that fits:

- **`Fetch\Testing\MockServer`** — `MockServer::fake([...])`, `MockResponse`,
  `MockResponseSequence`, `preventStrayRequests()`, and `assertSent*` helpers.
  Best for end-to-end handler/helper behavior.
- **Guzzle `MockHandler`** injected via a custom client for transport-level
  control.
- **Mockery** for collaborators (loggers, profilers, dispatchers).

## Deterministic async & timing

- Never assert on wall-clock durations or use `sleep()`. Inject/await promises
  and drive the event loop deterministically.
- Test retry logic through `RetryStrategy` with fixed inputs; assert on the
  computed delay/`isRetryable*` results, not real elapsed time.
- Cover cancellation, timeout, and rejection paths, and exceptions crossing
  async boundaries.

## Workflow

1. Write the test first for a bug (it must fail before the fix).
2. Run it focused: `./vendor/bin/phpunit --filter=test_your_case`.
3. Broaden: `composer test:unit` or `composer test:integration`.
4. Full offline gate: `NO_NETWORK=1 composer test`, then `composer lint`.

## Red flags — stop

- The test only passes with real network access or specific timing.
- Shared singletons leak between tests (missing reset → order-dependent flake).
- A camelCase test method name (lint will reject it).
- Asserting implementation details that would break on a valid refactor.
