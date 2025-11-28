<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Cache\CacheControl;
use Fetch\Cache\CachedResponse;
use Fetch\Cache\CacheKeyGenerator;
use Fetch\Cache\FileCache;
use Fetch\Cache\MemoryCache;
use Fetch\Http\Response;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    private string $testCacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testCacheDir = sys_get_temp_dir().'/fetch-cache-test-'.uniqid();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up test cache directory
        if (is_dir($this->testCacheDir)) {
            $files = glob($this->testCacheDir.'/*');
            if ($files) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
            @rmdir($this->testCacheDir);
        }
    }

    // ==================== CacheControl Tests ====================

    public function test_parse_cache_control_header(): void
    {
        $cc = CacheControl::parse('max-age=3600, must-revalidate, private');

        $this->assertEquals(3600, $cc->getMaxAge());
        $this->assertTrue($cc->mustRevalidate());
        $this->assertTrue($cc->isPrivate());
        $this->assertFalse($cc->isPublic());
    }

    public function test_parse_cache_control_with_s_maxage(): void
    {
        $cc = CacheControl::parse('max-age=3600, s-maxage=7200, public');

        $this->assertEquals(3600, $cc->getMaxAge());
        $this->assertEquals(7200, $cc->getSharedMaxAge());
        $this->assertTrue($cc->isPublic());
    }

    public function test_parse_cache_control_no_store(): void
    {
        $cc = CacheControl::parse('no-store, no-cache');

        $this->assertTrue($cc->hasNoStore());
        $this->assertTrue($cc->hasNoCache());
    }

    public function test_parse_cache_control_stale_directives(): void
    {
        $cc = CacheControl::parse('max-age=3600, stale-while-revalidate=300, stale-if-error=86400');

        $this->assertEquals(300, $cc->getStaleWhileRevalidate());
        $this->assertEquals(86400, $cc->getStaleIfError());
    }

    public function test_cache_control_should_cache(): void
    {
        $cc = CacheControl::parse('max-age=3600');
        $response = new Response(200, [], 'test');

        $this->assertTrue($cc->shouldCache($response, false));
        $this->assertTrue($cc->shouldCache($response, true));
    }

    public function test_cache_control_should_not_cache_no_store(): void
    {
        $cc = CacheControl::parse('no-store');
        $response = new Response(200, [], 'test');

        $this->assertFalse($cc->shouldCache($response, false));
    }

    public function test_cache_control_should_not_cache_private_in_shared(): void
    {
        $cc = CacheControl::parse('private, max-age=3600');
        $response = new Response(200, [], 'test');

        $this->assertTrue($cc->shouldCache($response, false)); // Private cache is OK
        $this->assertFalse($cc->shouldCache($response, true)); // Shared cache should not cache
    }

    public function test_cache_control_get_ttl(): void
    {
        $cc = CacheControl::parse('max-age=3600');
        $response = new Response(200, [], 'test');

        $this->assertEquals(3600, $cc->getTtl($response, false));
    }

    public function test_cache_control_get_ttl_shared_uses_s_maxage(): void
    {
        $cc = CacheControl::parse('max-age=3600, s-maxage=1800');
        $response = new Response(200, [], 'test');

        $this->assertEquals(1800, $cc->getTtl($response, true));
        $this->assertEquals(3600, $cc->getTtl($response, false));
    }

    public function test_cache_control_build(): void
    {
        $header = CacheControl::build([
            'max-age' => 3600,
            'public' => true,
            'must-revalidate' => true,
        ]);

        $this->assertStringContainsString('max-age=3600', $header);
        $this->assertStringContainsString('public', $header);
        $this->assertStringContainsString('must-revalidate', $header);
    }

    // ==================== CacheKeyGenerator Tests ====================

    public function test_generate_cache_key(): void
    {
        $gen = new CacheKeyGenerator();

        $key1 = $gen->generate('GET', 'https://api.example.com/users');
        $key2 = $gen->generate('GET', 'https://api.example.com/users');
        $key3 = $gen->generate('GET', 'https://api.example.com/posts');

        $this->assertEquals($key1, $key2);
        $this->assertNotEquals($key1, $key3);
    }

    public function test_generate_cache_key_different_methods(): void
    {
        $gen = new CacheKeyGenerator();

        $key1 = $gen->generate('GET', 'https://api.example.com/users');
        $key2 = $gen->generate('POST', 'https://api.example.com/users');

        $this->assertNotEquals($key1, $key2);
    }

    public function test_generate_cache_key_with_query_params(): void
    {
        $gen = new CacheKeyGenerator();

        $key1 = $gen->generate('GET', 'https://api.example.com/users', ['query' => ['page' => 1]]);
        $key2 = $gen->generate('GET', 'https://api.example.com/users', ['query' => ['page' => 2]]);

        $this->assertNotEquals($key1, $key2);
    }

    public function test_generate_custom_cache_key(): void
    {
        $gen = new CacheKeyGenerator();

        $key = $gen->generateCustom('my-custom-key');

        $this->assertEquals('fetch:my-custom-key', $key);
    }

    // ==================== CachedResponse Tests ====================

    public function test_cached_response_from_response(): void
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json', 'ETag' => '"abc123"', 'Last-Modified' => 'Thu, 01 Jan 2020 00:00:00 GMT'],
            '{"data": "test"}'
        );

        $cached = CachedResponse::fromResponse($response, 3600);

        $this->assertEquals(200, $cached->getStatusCode());
        $this->assertEquals('{"data": "test"}', $cached->getBody());
        $this->assertEquals('"abc123"', $cached->getETag());
        $this->assertEquals('Thu, 01 Jan 2020 00:00:00 GMT', $cached->getLastModified());
    }

    public function test_cached_response_is_fresh(): void
    {
        $response = new Response(200, [], 'test');
        $cached = CachedResponse::fromResponse($response, 3600);

        $this->assertTrue($cached->isFresh());
        $this->assertFalse($cached->isExpired());
    }

    public function test_cached_response_is_expired(): void
    {
        // Create a cached response that expired 1 second ago
        $cached = new CachedResponse(
            statusCode: 200,
            headers: [],
            body: 'test',
            createdAt: time() - 3601,
            expiresAt: time() - 1
        );

        $this->assertFalse($cached->isFresh());
        $this->assertTrue($cached->isExpired());
    }

    public function test_cached_response_get_age(): void
    {
        $createdAt = time() - 100;
        $cached = new CachedResponse(
            statusCode: 200,
            headers: [],
            body: 'test',
            createdAt: $createdAt
        );

        $age = $cached->getAge();
        $this->assertGreaterThanOrEqual(100, $age);
        $this->assertLessThanOrEqual(102, $age); // Allow 2 seconds for test execution
    }

    public function test_cached_response_serialize_and_deserialize(): void
    {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json', 'ETag' => '"abc123"'],
            '{"data": "test"}'
        );
        $cached = CachedResponse::fromResponse($response, 3600);

        $data = $cached->toArray();
        $restored = CachedResponse::fromArray($data);

        $this->assertNotNull($restored);
        $this->assertEquals($cached->getStatusCode(), $restored->getStatusCode());
        $this->assertEquals($cached->getBody(), $restored->getBody());
        $this->assertEquals($cached->getETag(), $restored->getETag());
    }

    public function test_cached_response_is_usable_as_stale(): void
    {
        // Create a cached response that expired 30 seconds ago
        $cached = new CachedResponse(
            statusCode: 200,
            headers: [],
            body: 'test',
            createdAt: time() - 3630,
            expiresAt: time() - 30
        );

        // Should be usable if stale period is 60 seconds
        $this->assertTrue($cached->isUsableAsStale(60));

        // Should not be usable if stale period is 10 seconds
        $this->assertFalse($cached->isUsableAsStale(10));
    }

    // ==================== MemoryCache Tests ====================

    public function test_memory_cache_set_and_get(): void
    {
        $cache = new MemoryCache();
        $response = new Response(200, [], 'test');
        $cached = CachedResponse::fromResponse($response, 3600);

        $cache->set('test-key', $cached);
        $retrieved = $cache->get('test-key');

        $this->assertNotNull($retrieved);
        $this->assertEquals($cached->getBody(), $retrieved->getBody());
    }

    public function test_memory_cache_has(): void
    {
        $cache = new MemoryCache();
        $response = new Response(200, [], 'test');
        $cached = CachedResponse::fromResponse($response, 3600);

        $this->assertFalse($cache->has('test-key'));

        $cache->set('test-key', $cached);
        $this->assertTrue($cache->has('test-key'));
    }

    public function test_memory_cache_delete(): void
    {
        $cache = new MemoryCache();
        $response = new Response(200, [], 'test');
        $cached = CachedResponse::fromResponse($response, 3600);

        $cache->set('test-key', $cached);
        $this->assertTrue($cache->has('test-key'));

        $result = $cache->delete('test-key');
        $this->assertTrue($result);
        $this->assertFalse($cache->has('test-key'));
    }

    public function test_memory_cache_clear(): void
    {
        $cache = new MemoryCache();
        $response = new Response(200, [], 'test');

        $cache->set('key1', CachedResponse::fromResponse($response, 3600));
        $cache->set('key2', CachedResponse::fromResponse($response, 3600));

        $this->assertEquals(2, $cache->count());

        $cache->clear();
        $this->assertEquals(0, $cache->count());
    }

    public function test_memory_cache_max_items(): void
    {
        $cache = new MemoryCache(maxItems: 2);
        $response = new Response(200, [], 'test');

        $cache->set('key1', CachedResponse::fromResponse($response, 3600));
        $cache->set('key2', CachedResponse::fromResponse($response, 3600));
        $cache->set('key3', CachedResponse::fromResponse($response, 3600));

        // Should only have 2 items (oldest evicted)
        $this->assertEquals(2, $cache->count());
    }

    public function test_memory_cache_expired_items_not_returned(): void
    {
        $cache = new MemoryCache();

        // Create an already expired cached response with TTL of 0 (immediate expiration)
        $cached = new CachedResponse(
            statusCode: 200,
            headers: [],
            body: 'test',
            createdAt: time() - 100,
            expiresAt: time() - 1 // Already expired
        );

        // Set with a very short TTL (but the cache uses its own TTL calculation)
        // To test expired items, we need to directly manipulate the cache internals
        // or use the internal expiration check
        $cache->set('expired-key', $cached, -1); // TTL of -1 means already expired
        $this->assertNull($cache->get('expired-key'));
    }

    public function test_memory_cache_prune(): void
    {
        $cache = new MemoryCache();

        // Add an expired item using TTL of -1
        $expired = new CachedResponse(
            statusCode: 200,
            headers: [],
            body: 'expired',
            createdAt: time() - 100,
            expiresAt: time() - 1
        );
        $cache->set('expired', $expired, -1);

        // Add a fresh item
        $fresh = CachedResponse::fromResponse(new Response(200, [], 'fresh'), 3600);
        $cache->set('fresh', $fresh, 3600);

        $pruned = $cache->prune();
        $this->assertEquals(1, $pruned);
        $this->assertEquals(1, $cache->count());
    }

    // ==================== FileCache Tests ====================

    public function test_file_cache_set_and_get(): void
    {
        $cache = new FileCache($this->testCacheDir);
        $response = new Response(200, [], 'test');
        $cached = CachedResponse::fromResponse($response, 3600);

        $cache->set('test-key', $cached);
        $retrieved = $cache->get('test-key');

        $this->assertNotNull($retrieved);
        $this->assertEquals($cached->getBody(), $retrieved->getBody());
    }

    public function test_file_cache_has(): void
    {
        $cache = new FileCache($this->testCacheDir);
        $response = new Response(200, [], 'test');
        $cached = CachedResponse::fromResponse($response, 3600);

        $this->assertFalse($cache->has('test-key'));

        $cache->set('test-key', $cached);
        $this->assertTrue($cache->has('test-key'));
    }

    public function test_file_cache_delete(): void
    {
        $cache = new FileCache($this->testCacheDir);
        $response = new Response(200, [], 'test');
        $cached = CachedResponse::fromResponse($response, 3600);

        $cache->set('test-key', $cached);
        $this->assertTrue($cache->has('test-key'));

        $result = $cache->delete('test-key');
        $this->assertTrue($result);
        $this->assertFalse($cache->has('test-key'));
    }

    public function test_file_cache_clear(): void
    {
        $cache = new FileCache($this->testCacheDir);
        $response = new Response(200, [], 'test');

        $cache->set('key1', CachedResponse::fromResponse($response, 3600));
        $cache->set('key2', CachedResponse::fromResponse($response, 3600));

        $stats = $cache->getStats();
        $this->assertEquals(2, $stats['items']);

        $cache->clear();
        $stats = $cache->getStats();
        $this->assertEquals(0, $stats['items']);
    }

    public function test_file_cache_expired_items_not_returned(): void
    {
        $cache = new FileCache($this->testCacheDir);

        // Create an already expired cached response
        $cached = new CachedResponse(
            statusCode: 200,
            headers: [],
            body: 'test',
            createdAt: time() - 100,
            expiresAt: time() - 1 // Already expired
        );

        $cache->set('expired-key', $cached);
        $this->assertNull($cache->get('expired-key'));
    }

    public function test_file_cache_prune(): void
    {
        $cache = new FileCache($this->testCacheDir);

        // Add an expired item
        $expired = new CachedResponse(
            statusCode: 200,
            headers: [],
            body: 'expired',
            createdAt: time() - 100,
            expiresAt: time() - 1
        );
        // Directly write to bypass expiration check in set
        $key = hash('sha256', 'expired').'.cache';
        file_put_contents($this->testCacheDir.'/'.$key, serialize($expired->toArray()));

        // Add a fresh item
        $fresh = CachedResponse::fromResponse(new Response(200, [], 'fresh'), 3600);
        $cache->set('fresh', $fresh);

        $pruned = $cache->prune();
        $this->assertEquals(1, $pruned);
    }

    public function test_file_cache_get_stats(): void
    {
        $cache = new FileCache($this->testCacheDir);
        $response = new Response(200, [], 'test');

        $cache->set('key1', CachedResponse::fromResponse($response, 3600));

        $stats = $cache->getStats();
        $this->assertEquals($this->testCacheDir, $stats['directory']);
        $this->assertEquals(1, $stats['items']);
        $this->assertGreaterThan(0, $stats['size']);
    }
}
