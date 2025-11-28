<?php

namespace Tests\Unit;

use Fetch\Pool\Connection;
use Fetch\Pool\ConnectionPool;
use Fetch\Pool\DnsCache;
use Fetch\Pool\HostConnectionPool;
use Fetch\Pool\Http2Configuration;
use Fetch\Pool\PoolConfiguration;
use PHPUnit\Framework\TestCase;

class ConnectionPoolTest extends TestCase
{
    public function test_pool_configuration_defaults(): void
    {
        $config = new PoolConfiguration;

        $this->assertTrue($config->isEnabled());
        $this->assertEquals(100, $config->getMaxConnections());
        $this->assertEquals(6, $config->getMaxPerHost());
        $this->assertEquals(3, $config->getMaxIdlePerHost());
        $this->assertEquals(30, $config->getKeepAliveTimeout());
        $this->assertEquals(10, $config->getConnectionTimeout());
        $this->assertEquals('least_connections', $config->getStrategy());
        $this->assertFalse($config->isConnectionWarmupEnabled());
        $this->assertEquals(0, $config->getWarmupConnections());
        $this->assertEquals(300, $config->getDnsCacheTtl());
    }

    public function test_pool_configuration_from_array(): void
    {
        $config = PoolConfiguration::fromArray([
            'enabled' => true,
            'max_connections' => 50,
            'max_per_host' => 10,
            'max_idle_per_host' => 5,
            'keep_alive_timeout' => 60,
            'connection_timeout' => 15,
            'strategy' => 'round_robin',
            'connection_warmup' => true,
            'warmup_connections' => 2,
            'dns_cache_ttl' => 600,
        ]);

        $this->assertTrue($config->isEnabled());
        $this->assertEquals(50, $config->getMaxConnections());
        $this->assertEquals(10, $config->getMaxPerHost());
        $this->assertEquals(5, $config->getMaxIdlePerHost());
        $this->assertEquals(60, $config->getKeepAliveTimeout());
        $this->assertEquals(15, $config->getConnectionTimeout());
        $this->assertEquals('round_robin', $config->getStrategy());
        $this->assertTrue($config->isConnectionWarmupEnabled());
        $this->assertEquals(2, $config->getWarmupConnections());
        $this->assertEquals(600, $config->getDnsCacheTtl());
    }

    public function test_pool_configuration_to_array(): void
    {
        $config = new PoolConfiguration(
            enabled: true,
            maxConnections: 200,
            maxPerHost: 8,
        );

        $array = $config->toArray();

        $this->assertTrue($array['enabled']);
        $this->assertEquals(200, $array['max_connections']);
        $this->assertEquals(8, $array['max_per_host']);
    }

    public function test_connection_lifecycle(): void
    {
        $connection = new Connection(
            host: 'example.com',
            port: 443,
            ssl: true,
        );

        $this->assertEquals('example.com', $connection->getHost());
        $this->assertEquals(443, $connection->getPort());
        $this->assertTrue($connection->isSsl());
        $this->assertEquals('https://example.com:443', $connection->getKey());
        $this->assertEquals(0, $connection->getActiveRequestCount());
        $this->assertFalse($connection->isAlive()); // No client set yet
    }

    public function test_connection_active_requests(): void
    {
        $connection = new Connection('example.com', 80, false);

        $this->assertEquals(0, $connection->getActiveRequestCount());

        $connection->incrementActiveRequests();
        $this->assertEquals(1, $connection->getActiveRequestCount());

        $connection->incrementActiveRequests();
        $this->assertEquals(2, $connection->getActiveRequestCount());

        $connection->decrementActiveRequests();
        $this->assertEquals(1, $connection->getActiveRequestCount());

        $connection->decrementActiveRequests();
        $connection->decrementActiveRequests(); // Should not go below 0
        $this->assertEquals(0, $connection->getActiveRequestCount());
    }

    public function test_connection_timestamps(): void
    {
        $before = microtime(true);
        $connection = new Connection('example.com', 80, false);
        $after = microtime(true);

        $this->assertGreaterThanOrEqual($before, $connection->getCreatedAt());
        $this->assertLessThanOrEqual($after, $connection->getCreatedAt());
        $this->assertEquals($connection->getCreatedAt(), $connection->getLastUsedAt());

        sleep(1);
        $connection->markUsed();

        $this->assertGreaterThan($connection->getCreatedAt(), $connection->getLastUsedAt());
    }

    public function test_connection_close(): void
    {
        $connection = new Connection('example.com', 80, false);
        $mockClient = $this->createMock(\GuzzleHttp\ClientInterface::class);
        $connection->setClient($mockClient);

        $this->assertTrue($connection->isAlive());

        $connection->close();

        $this->assertFalse($connection->isAlive());
        $this->assertNull($connection->getClient());
    }

    public function test_connection_pool_get_connection(): void
    {
        $config = new PoolConfiguration;
        $pool = new ConnectionPool($config);

        // Get a connection
        $connection = $pool->getConnection('example.com', 443, true);

        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals('example.com', $connection->getHost());
        $this->assertEquals(443, $connection->getPort());
        $this->assertTrue($connection->isSsl());
    }

    public function test_connection_pool_from_url(): void
    {
        $config = new PoolConfiguration;
        $pool = new ConnectionPool($config);

        $connection = $pool->getConnectionFromUrl('https://api.example.com:8443/v1/users');

        $this->assertEquals('api.example.com', $connection->getHost());
        $this->assertEquals(8443, $connection->getPort());
        $this->assertTrue($connection->isSsl());
    }

    public function test_connection_pool_from_url_default_ports(): void
    {
        $config = new PoolConfiguration;
        $pool = new ConnectionPool($config);

        $httpConnection = $pool->getConnectionFromUrl('http://example.com/path');
        $this->assertEquals(80, $httpConnection->getPort());
        $this->assertFalse($httpConnection->isSsl());

        $httpsConnection = $pool->getConnectionFromUrl('https://example.com/path');
        $this->assertEquals(443, $httpsConnection->getPort());
        $this->assertTrue($httpsConnection->isSsl());
    }

    public function test_connection_pool_release(): void
    {
        $config = new PoolConfiguration;
        $pool = new ConnectionPool($config);

        $connection = $pool->getConnection('example.com', 80, false);
        $pool->releaseConnection($connection);

        $stats = $pool->getStats();
        $this->assertEquals(1, $stats['total_requests']);
        $this->assertGreaterThanOrEqual(0, $stats['connections_reused']);
    }

    public function test_connection_pool_stats(): void
    {
        $config = new PoolConfiguration;
        $pool = new ConnectionPool($config);

        // Initial stats
        $stats = $pool->getStats();
        $this->assertTrue($stats['enabled']);
        $this->assertEquals(0, $stats['total_pools']);
        $this->assertEquals(0, $stats['active_connections']);

        // After getting a connection
        $connection = $pool->getConnection('example.com', 80, false);
        $stats = $pool->getStats();
        $this->assertEquals(1, $stats['total_pools']);
        $this->assertEquals(1, $stats['active_connections']);
        $this->assertEquals(1, $stats['total_requests']);
    }

    public function test_connection_pool_close_all(): void
    {
        $config = new PoolConfiguration;
        $pool = new ConnectionPool($config);

        // Create some connections
        $pool->getConnection('example.com', 80, false);
        $pool->getConnection('api.example.com', 443, true);

        $stats = $pool->getStats();
        $this->assertEquals(2, $stats['total_pools']);
        $this->assertEquals(2, $stats['active_connections']);

        $pool->closeAll();

        $stats = $pool->getStats();
        $this->assertEquals(0, $stats['total_pools']);
        $this->assertEquals(0, $stats['active_connections']);
    }

    public function test_host_connection_pool_stats(): void
    {
        $config = new PoolConfiguration;
        $hostPool = new HostConnectionPool(
            host: 'example.com',
            port: 443,
            ssl: true,
            config: $config,
        );

        // Initial stats
        $stats = $hostPool->getStats();
        $this->assertEquals('example.com', $stats['host']);
        $this->assertEquals(443, $stats['port']);
        $this->assertTrue($stats['ssl']);
        $this->assertEquals(0, $stats['total_borrowed']);

        // Borrow a connection
        $connection = $hostPool->borrowConnection();
        $stats = $hostPool->getStats();
        $this->assertEquals(1, $stats['total_borrowed']);
        $this->assertEquals(1, $stats['total_created']);

        // Return the connection
        $hostPool->returnConnection($connection);
        $stats = $hostPool->getStats();
        $this->assertEquals(1, $stats['total_returned']);
    }

    public function test_http2_configuration_defaults(): void
    {
        $config = new Http2Configuration;

        $this->assertTrue($config->isEnabled());
        $this->assertEquals(100, $config->getMaxConcurrentStreams());
        $this->assertEquals(65535, $config->getWindowSize());
        $this->assertEquals(4096, $config->getHeaderTableSize());
        $this->assertFalse($config->isServerPushEnabled());
        $this->assertFalse($config->isStreamPrioritizationEnabled());
    }

    public function test_http2_configuration_from_array(): void
    {
        $config = Http2Configuration::fromArray([
            'enabled' => true,
            'max_concurrent_streams' => 200,
            'window_size' => 131070,
            'enable_server_push' => true,
            'stream_prioritization' => true,
        ]);

        $this->assertTrue($config->isEnabled());
        $this->assertEquals(200, $config->getMaxConcurrentStreams());
        $this->assertEquals(131070, $config->getWindowSize());
        $this->assertTrue($config->isServerPushEnabled());
        $this->assertTrue($config->isStreamPrioritizationEnabled());
    }

    public function test_http2_curl_options(): void
    {
        $config = new Http2Configuration(enabled: true);
        $curlOptions = $config->getCurlOptions();

        $this->assertArrayHasKey(CURLOPT_HTTP_VERSION, $curlOptions);
        $this->assertEquals(CURL_HTTP_VERSION_2_0, $curlOptions[CURLOPT_HTTP_VERSION]);
    }

    public function test_http2_disabled_curl_options(): void
    {
        $config = new Http2Configuration(enabled: false);
        $curlOptions = $config->getCurlOptions();

        $this->assertArrayNotHasKey(CURLOPT_HTTP_VERSION, $curlOptions);
    }

    public function test_dns_cache_resolve(): void
    {
        $cache = new DnsCache(ttl: 300);

        // Mock the DNS lookup by testing with localhost
        $addresses = $cache->resolve('localhost');

        $this->assertNotEmpty($addresses);
    }

    public function test_dns_cache_stats(): void
    {
        $cache = new DnsCache(ttl: 300);

        $stats = $cache->getStats();

        $this->assertEquals(0, $stats['total_entries']);
        $this->assertEquals(300, $stats['ttl']);
    }

    public function test_dns_cache_clear(): void
    {
        $cache = new DnsCache(ttl: 300);

        // Resolve to populate cache
        try {
            $cache->resolve('localhost');
        } catch (\Throwable) {
            // Ignore if DNS fails in test environment
        }

        // Clear cache
        $cache->clear();

        $stats = $cache->getStats();
        $this->assertEquals(0, $stats['total_entries']);
    }

    public function test_dns_cache_ttl_setter(): void
    {
        $cache = new DnsCache(ttl: 300);
        $cache->setTtl(600);

        $stats = $cache->getStats();
        $this->assertEquals(600, $stats['ttl']);
    }
}
