<?php

namespace Tests\Unit;

use Fetch\Http\ClientHandler;
use Fetch\Pool\ConnectionPool;
use Fetch\Pool\Http2Configuration;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;

use function Matrix\Support\await;

class ManagesConnectionPoolTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset static pool after each test
        (new ClientHandler)->resetPool();
    }

    public function test_with_connection_pool_enabled(): void
    {
        $handler = ClientHandler::create();

        $result = $handler->withConnectionPool(true);

        $this->assertSame($handler, $result);
        $this->assertTrue($handler->isPoolingEnabled());
    }

    public function test_with_connection_pool_config(): void
    {
        $handler = ClientHandler::create();

        $handler->withConnectionPool([
            'enabled' => true,
            'max_connections' => 50,
            'max_per_host' => 10,
        ]);

        $this->assertTrue($handler->isPoolingEnabled());

        $pool = $handler->getConnectionPool();
        $this->assertInstanceOf(ConnectionPool::class, $pool);
        $this->assertEquals(50, $pool->getConfig()->getMaxConnections());
        $this->assertEquals(10, $pool->getConfig()->getMaxPerHost());
    }

    public function test_with_http2_enabled(): void
    {
        $handler = ClientHandler::create();

        // Pre-set a custom version to ensure HTTP/2 setup does not overwrite it
        $handler->withOptions(['version' => 1.1]);

        $handler->withHttp2(true);

        $this->assertTrue($handler->isHttp2Enabled());

        $config = $handler->getHttp2Config();
        $this->assertInstanceOf(Http2Configuration::class, $config);
        $this->assertTrue($config->isEnabled());
        // Custom version should remain since user set it explicitly
        $this->assertSame(1.1, $handler->getOptions()['version']);
        $this->assertArrayHasKey(CURLOPT_HTTP_VERSION, $handler->getOptions()['curl']);
    }

    public function test_with_http2_config(): void
    {
        $handler = ClientHandler::create();

        $handler->withHttp2([
            'enabled' => true,
            'max_concurrent_streams' => 200,
            'enable_server_push' => true,
        ]);

        $this->assertTrue($handler->isHttp2Enabled());

        $config = $handler->getHttp2Config();
        $this->assertEquals(200, $config->getMaxConcurrentStreams());
        $this->assertTrue($config->isServerPushEnabled());
        $this->assertSame(2.0, $handler->getOptions()['version']);
        $this->assertArrayHasKey(CURLOPT_HTTP_VERSION, $handler->getOptions()['curl']);
    }

    public function test_with_http2_disabled(): void
    {
        $handler = ClientHandler::create();

        $handler->withHttp2(false);

        $this->assertFalse($handler->isHttp2Enabled());
    }

    public function test_get_pool_stats(): void
    {
        $handler = ClientHandler::create();

        // Before pooling is configured
        $stats = $handler->getPoolStats();
        $this->assertTrue($stats['enabled']);

        // After pooling is configured
        $handler->withConnectionPool([
            'enabled' => true,
            'max_connections' => 100,
        ]);

        $stats = $handler->getPoolStats();
        $this->assertTrue($stats['enabled']);
        $this->assertEquals(0, $stats['total_pools']);
        $this->assertEquals(0, $stats['active_connections']);
    }

    public function test_get_dns_cache_stats(): void
    {
        $handler = ClientHandler::create();

        // Before DNS cache is configured
        $stats = $handler->getDnsCacheStats();
        $this->assertTrue($stats['enabled']);

        // After pooling is configured (DNS cache is initialized too)
        $handler->withConnectionPool([
            'enabled' => true,
            'dns_cache_ttl' => 600,
        ]);

        $stats = $handler->getDnsCacheStats();
        $this->assertTrue($stats['enabled']);
        $this->assertEquals(600, $stats['ttl']);
    }

    public function test_clear_dns_cache(): void
    {
        $handler = ClientHandler::create();
        $handler->withConnectionPool([
            'enabled' => true,
            'dns_cache_ttl' => 300,
        ]);

        $result = $handler->clearDnsCache();

        $this->assertSame($handler, $result);

        $stats = $handler->getDnsCacheStats();
        $this->assertEquals(0, $stats['total_entries']);
    }

    public function test_close_all_connections(): void
    {
        $handler = ClientHandler::create();
        $handler->withConnectionPool([
            'enabled' => true,
        ]);

        $result = $handler->closeAllConnections();

        $this->assertSame($handler, $result);

        $stats = $handler->getPoolStats();
        $this->assertEquals(0, $stats['active_connections']);
    }

    public function test_reset_pool(): void
    {
        $handler = ClientHandler::create();
        $handler->withConnectionPool([
            'enabled' => true,
        ]);

        $this->assertTrue($handler->isPoolingEnabled());

        $handler->resetPool();

        $this->assertFalse($handler->isPoolingEnabled());
        $this->assertNull($handler->getConnectionPool());
        $this->assertNull($handler->getDnsCache());
    }

    public function test_http2_adds_curl_options(): void
    {
        $handler = ClientHandler::create();
        $handler->withHttp2(true);

        $options = $handler->getOptions();

        $this->assertArrayHasKey('curl', $options);
        $this->assertArrayHasKey(CURLOPT_HTTP_VERSION, $options['curl']);
        $this->assertEquals(CURL_HTTP_VERSION_2_0, $options['curl'][CURLOPT_HTTP_VERSION]);
        $this->assertEquals(2.0, $options['version']);
    }

    public function test_chaining_pool_and_http2_config(): void
    {
        $handler = ClientHandler::create()
            ->withConnectionPool([
                'enabled' => true,
                'max_connections' => 100,
            ])
            ->withHttp2([
                'enabled' => true,
                'max_concurrent_streams' => 50,
            ]);

        $this->assertTrue($handler->isPoolingEnabled());
        $this->assertTrue($handler->isHttp2Enabled());

        $this->assertEquals(100, $handler->getConnectionPool()->getConfig()->getMaxConnections());
        $this->assertEquals(50, $handler->getHttp2Config()->getMaxConcurrentStreams());
    }

    public function test_dns_cache_stats_reflect_entries(): void
    {
        $handler = ClientHandler::create();
        $handler->withConnectionPool(['enabled' => true, 'dns_cache_ttl' => 10]);

        $dnsCache = $handler->getDnsCache();
        $this->assertNotNull($dnsCache);

        $ref = new \ReflectionClass($dnsCache);
        $prop = $ref->getProperty('cache');
        $prop->setAccessible(true);
        $prop->setValue($dnsCache, [
            'example.com' => ['addresses' => ['127.0.0.1'], 'expires_at' => time() + 10],
        ]);

        $stats = $handler->getDnsCacheStats();
        $this->assertTrue($stats['enabled']);
        $this->assertSame(1, $stats['total_entries']);
        $this->assertSame(1, $stats['valid_entries']);
    }

    public function test_sync_request_with_pooling_enabled(): void
    {
        $mock = new MockHandler([new GuzzleResponse(200, [], 'ok')]);
        $stack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $stack]);

        $handler = ClientHandler::createWithClient($client);
        $handler->withConnectionPool(true);

        $response = $handler->get('https://example.com');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_async_request_with_pooling_enabled(): void
    {
        $mock = new MockHandler([new GuzzleResponse(200, [], 'ok')]);
        $stack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $stack]);

        $handler = ClientHandler::createWithClient($client);
        $handler->withConnectionPool(true)->async();

        $promise = $handler->get('https://example.com');
        $response = await($promise);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
