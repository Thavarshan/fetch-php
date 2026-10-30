<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Exceptions\NetworkException;
use Fetch\Pool\DnsCache;
use PHPUnit\Framework\TestCase;

class DnsCacheTest extends TestCase
{
    public function test_it_caches_resolutions_until_the_ttl_expires(): void
    {
        $now = 100;
        $lookups = 0;
        $resolver = static function (string $hostname) use (&$lookups): array {
            $lookups++;

            return [$hostname === 'api.test' ? '192.0.2.10' : '192.0.2.11'];
        };
        $clock = static function () use (&$now): int {
            return $now;
        };
        $cache = new DnsCache(10, $resolver, $clock);

        self::assertSame(['192.0.2.10'], $cache->resolve('api.test'));
        self::assertSame(['192.0.2.10'], $cache->resolve('api.test'));
        self::assertSame(1, $lookups);

        $now = 110;

        self::assertSame(['192.0.2.10'], $cache->resolve('api.test'));
        self::assertSame(2, $lookups);
    }

    public function test_failed_resolution_is_not_cached(): void
    {
        $results = [[], ['192.0.2.12']];
        $resolver = static function (string $hostname) use (&$results): array {
            return array_shift($results);
        };
        $cache = new DnsCache(60, $resolver);

        try {
            $cache->resolve('missing.test');
            self::fail('Expected an empty DNS result to fail.');
        } catch (NetworkException) {
            self::assertSame(
                ['192.0.2.12'],
                $cache->resolve('missing.test'),
            );
        }
    }

    public function test_clear_removes_one_hostname_or_every_entry(): void
    {
        $cache = new DnsCache(
            resolver: static fn (string $hostname): array => [
                $hostname === 'one.test' ? '192.0.2.1' : '192.0.2.2',
            ],
        );
        $cache->resolve('one.test');
        $cache->resolve('two.test');

        $cache->clear('one.test');

        self::assertSame(1, $cache->getStats()['total_entries']);

        $cache->clear();

        self::assertSame(0, $cache->getStats()['total_entries']);
    }
}
