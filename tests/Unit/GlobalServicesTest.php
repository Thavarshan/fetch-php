<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Http\ClientHandler;
use Fetch\Support\GlobalServices;
use PHPUnit\Framework\TestCase;

/**
 * Checks lazy shared services, default options, and reset behavior.
 */
class GlobalServicesTest extends TestCase
{
    /**
     * Resets global services before the test case starts.
     */
    protected function setUp(): void
    {
        GlobalServices::reset();
        parent::setUp();
    }

    /**
     * Resets global services after the test case finishes.
     */
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
        GlobalServices::setDefaultOptions([
            'timeout' => 8,
            'headers' => ['X-Test' => 'yes'],
        ]);

        $options = GlobalServices::getDefaultOptions();
        $this->assertSame(8, $options['timeout']);
        $this->assertSame(['X-Test' => 'yes'], $options['headers']);
        $this->assertTrue(
            GlobalServices::getStats()['default_options_customized'],
        );

        GlobalServices::reset();

        $this->assertFalse(
            GlobalServices::getStats()['default_options_customized'],
        );
        $this->assertFalse(GlobalServices::isInitialized());
    }

    public function test_reset_can_preserve_custom_defaults(): void
    {
        GlobalServices::setDefaultOptions(['timeout' => 11]);
        GlobalServices::reset(preserveDefaults: true);

        $this->assertSame(11, GlobalServices::getDefaultOptions()['timeout']);
        $this->assertFalse(GlobalServices::isInitialized());
    }

    public function test_reset_recreates_pool_and_dns_cache_instances(): void
    {
        $pool = GlobalServices::getConnectionPool();
        $dnsCache = GlobalServices::getDnsCache();

        GlobalServices::reset();

        $newPool = GlobalServices::getConnectionPool();
        $newDnsCache = GlobalServices::getDnsCache();

        self::assertNotSame($pool, $newPool);
        self::assertNotSame($dnsCache, $newDnsCache);
    }

    public function test_repeated_reset_is_safe(): void
    {
        GlobalServices::getConnectionPool();

        GlobalServices::reset();
        GlobalServices::reset();

        self::assertFalse(GlobalServices::isInitialized());
        self::assertFalse(GlobalServices::hasConnectionPool());
        self::assertFalse(GlobalServices::hasDnsCache());
    }

    public function test_initialization_preserves_options_set_before_it(): void
    {
        GlobalServices::setDefaultOptions([
            'timeout' => 13,
            'headers' => ['X-Global' => 'present'],
        ]);

        $handler = ClientHandler::create();

        self::assertSame(13, $handler->getOptions()['timeout']);
        self::assertSame(
            'present',
            $handler->getOptions()['headers']['X-Global'],
        );
    }

    public function test_handler_options_override_global_defaults(): void
    {
        GlobalServices::setDefaultOptions([
            'timeout' => 13,
            'headers' => ['X-Global' => 'present'],
        ]);

        $handler = ClientHandler::create()->withOptions(['timeout' => 7]);

        self::assertSame(7, $handler->getOptions()['timeout']);
        self::assertSame(
            'present',
            $handler->getOptions()['headers']['X-Global'],
        );
    }

    public function test_pool_reconfiguration_replaces_services(): void
    {
        $pool = GlobalServices::getConnectionPool();
        $dnsCache = GlobalServices::getDnsCache();

        GlobalServices::configurePool(['enabled' => true]);

        self::assertNotSame($pool, GlobalServices::getConnectionPool());
        self::assertNotSame($dnsCache, GlobalServices::getDnsCache());
        self::assertTrue(
            GlobalServices::getConnectionPool()->getConfig()->isEnabled(),
        );
    }
}
