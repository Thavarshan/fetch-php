<?php

namespace Tests\Mocks;

use Fetch\Concerns\ManagesRetries;
use Throwable;

/**
 * A simple test class that uses the ManagesRetries trait for testing.
 */
class ManagesRetriesTestClass
{
    use ManagesRetries;

    /**
     * Retry count used by the test host class.
     */
    public const DEFAULT_RETRIES = 1;

    /**
     * Initial retry delay used by tests, in milliseconds.
     */
    public const DEFAULT_RETRY_DELAY = 100;

    /**
     * Suppresses retry logging in the test-only handler.
     */
    public function logRetry(
        int $attempt,
        int $maxAttempts,
        Throwable $exception,
    ): void {
        // Do nothing in the test implementation
    }
}
