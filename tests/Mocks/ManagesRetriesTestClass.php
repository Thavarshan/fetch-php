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

    public const DEFAULT_RETRIES = 1;

    public const DEFAULT_RETRY_DELAY = 100;

    public function logRetry(int $attempt, int $maxAttempts, Throwable $exception): void
    {
        // Do nothing in the test implementation
    }
}
