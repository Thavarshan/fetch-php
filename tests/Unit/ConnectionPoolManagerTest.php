<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Pool\ConnectionPoolManager;
use Tests\TestCase;

/**
 * Checks legacy pool state sharing and handler-local HTTP/2 settings.
 */
class ConnectionPoolManagerTest extends TestCase
{
    public function test_pool_is_shared_and_enablement_is_local(): void
    {
        $first = new ConnectionPoolManager;
        $second = new ConnectionPoolManager;

        $first->withConnectionPool([
            'enabled' => true,
            'max_connections' => 27,
        ]);

        self::assertTrue($first->isPoolingEnabled());
        self::assertFalse($second->isPoolingEnabled());
        self::assertSame(
            $first->getConnectionPool(),
            $second->getConnectionPool(),
        );
        self::assertSame(
            27,
            $second->getConnectionPool()?->getConfig()->getMaxConnections(),
        );

        $second->withConnectionPool(true);

        self::assertTrue($second->isPoolingEnabled());
    }

    public function test_http2_configuration_is_local_to_each_manager(): void
    {
        $first = new ConnectionPoolManager;
        $second = new ConnectionPoolManager;

        $first->withHttp2(true);

        self::assertTrue($first->isHttp2Enabled());
        self::assertFalse($second->isHttp2Enabled());
    }
}
