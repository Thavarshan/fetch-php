<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Support\GlobalServices;
use PHPUnit\Framework\TestCase;

class GlobalServicesTest extends TestCase
{
    protected function setUp(): void
    {
        GlobalServices::reset();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        GlobalServices::reset();
        parent::tearDown();
    }

    public function test_services_initialize_lazily_and_share_instances(): void
    {
        $this->assertFalse(GlobalServices::isInitialized());
        $pool = GlobalServices::getConnectionPool();

        $this->assertTrue(GlobalServices::isInitialized());
        $this->assertSame($pool, GlobalServices::getConnectionPool());
        $this->assertNotNull(GlobalServices::getDnsCache());
    }

    public function test_custom_defaults_merge_and_reset(): void
    {
        GlobalServices::setDefaultOptions(['timeout' => 8, 'headers' => ['X-Test' => 'yes']]);

        $options = GlobalServices::getDefaultOptions();
        $this->assertSame(8, $options['timeout']);
        $this->assertSame(['X-Test' => 'yes'], $options['headers']);
        $this->assertTrue(GlobalServices::getStats()['default_options_customized']);

        GlobalServices::reset();

        $this->assertFalse(GlobalServices::getStats()['default_options_customized']);
        $this->assertFalse(GlobalServices::isInitialized());
    }

    public function test_reset_can_preserve_custom_defaults(): void
    {
        GlobalServices::setDefaultOptions(['timeout' => 11]);
        GlobalServices::reset(preserveDefaults: true);

        $this->assertSame(11, GlobalServices::getDefaultOptions()['timeout']);
        $this->assertFalse(GlobalServices::isInitialized());
    }
}
