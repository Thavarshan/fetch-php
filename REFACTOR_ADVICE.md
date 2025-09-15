Confirmed Issues

- Retries only on exceptions
  - Current retryRequest() retries only when a Fetch\RequestException is thrown. But executeSyncRequest() wraps
  Guzzle errors in RuntimeException, and normal 5xx/429 responses don’t cause exceptions (http_errors=false), so
  status-based retries never trigger.
- Exception type mismatch for retries
  - Network errors become RuntimeException (losing type) → isRetryableError() never sees a retryable exception
  class (e.g., ConnectException). It also only inspects RequestException chains.
- Async logging never runs
  - In executeAsyncRequest(), it checks method_exists($this, 'logger'), but logger is a property. That block
  never executes, so async failures aren’t logged.
- Static handle() not test-friendly
  - PerformsHttpRequests::handle() uses new static; it can’t use an injected/mocked client. That’s why the unit
  test attempted to touch the network (we skipped it in no-network). Using static::create() would enable dependency
  injection via overridden factories.
- Minor: timeout mapping
  - getHttpClient() sets connect_timeout from options['timeout']; total timeout is set later in Guzzle options
  but connect vs total can be inconsistent across paths; worth tightening.
- Docs were fixed, but repo metadata mismatches remain
  - README says MIT while composer.json is GPL-3.0-or-later and LICENSE file doesn’t match README claim. This is
  important to resolve.
- phpstan.neon appears stale
  - Includes larastan and references paths: src/Filterable that don’t exist in this library. That likely reduces
  static analysis value or can mislead.

  High-Impact Refactors

- Implement status-based retry
  - Option A (in retryRequest): After a response is returned, if status in retryableStatusCodes, trigger a retry
  (e.g., throw a lightweight RequestException to enter retry loop).
  - Option B (in executeSyncRequest): Immediately after creating Response, check status; if retryable and
  attempts remain, throw a RequestException with response attached (preferred: centralizes).
- Normalize exception wrapping for retries
  - In executeSyncRequest() catch GuzzleException $e and rethrow Fetch\Exceptions\RequestException with
  previous=$e and a synthetic Request (built from method/URI), so isRetryableError() sees a known class via
  $e->getPrevious().
- Fix async logging
  - Replace method_exists($this, 'logger') with a simple isset($this->logger) (or property_exists). That will
  actually log async errors.
- Make static handle() DI-friendly
  - Change to static::create() inside handle() so subclasses can override create() to provide a mock client. Then
  update tests to use an override (no env skip needed).

  Medium/Low-Risk Improvements

- Tighten timeout handling
  - Ensure both connect_timeout and timeout are set consistently in prepareGuzzleOptions(), using
  getEffectiveTimeout() for timeout and a sensible fraction for connect_timeout if not provided.
- Logging sanitization
  - Extend sanitizeOptions() to mask tokens in query strings or headers beyond Authorization (e.g., X-API-Key,
  Cookie).
- phpstan config
  - Remove larastan include and invalid paths; align to this project structure to regain useful analysis.
- README license
  - Align README license section with LICENSE and composer.json (choose one authoritative license).

Suggested Implementation Plan

1. Add status-based retry in executeSyncRequest() and rethrow Fetch\RequestException for both retryable HTTP status
and GuzzleException with previous set.
2. Fix async logging guard (use isset property check).
3. Update PerformsHttpRequests::handle() to use static::create() instead of new static.
4. Adjust tests to remove NO_NETWORK skip and use an overridable factory for injecting mocked clients.
5. Improve timeout mapping and logging sanitization.
6. Clean up phpstan configs and license inconsistency.
7. Run lint, unit tests, and optionally phpstan.
