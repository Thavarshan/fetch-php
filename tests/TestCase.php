<?php

declare(strict_types=1);

namespace Tests;

use Fetch\Support\GlobalServices;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base fixture that resets shared FetchPHP services after each test.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Reset global services after each test to ensure isolation.
     */
    protected function tearDown(): void
    {
        GlobalServices::reset();
        parent::tearDown();
    }
}
