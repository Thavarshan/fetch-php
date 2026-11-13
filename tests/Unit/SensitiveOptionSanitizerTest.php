<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Support\SensitiveOptionSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Verifies that request logs cannot expose diagnostic authentication values.
 */
class SensitiveOptionSanitizerTest extends TestCase
{
    public function test_redacts_csrf_and_auth_headers_consistently(): void
    {
        $original = [
            'headers' => [
                'X-Csrf-Token' => 'csrf-secret',
                'x-xsrf-token' => ['one', 'two'],
                'Authorization' => 'Bearer abc',
                'X-Test' => 'public',
            ],
            'auth' => ['alice', 'secret'],
            'token' => 'top-secret',
        ];

        $result = SensitiveOptionSanitizer::sanitize($original);

        self::assertSame('[REDACTED]', $result['headers']['X-Csrf-Token']);
        self::assertSame(
            ['[REDACTED]', '[REDACTED]'],
            $result['headers']['x-xsrf-token'],
        );
        self::assertSame('[REDACTED]', $result['headers']['Authorization']);
        self::assertSame('[REDACTED]', $result['auth']);
        self::assertSame('[REDACTED]', $result['token']);
        self::assertSame('public', $result['headers']['X-Test']);
        self::assertSame($original['auth'], ['alice', 'secret']);
        self::assertSame('csrf-secret', $original['headers']['X-Csrf-Token']);
    }

    public function test_redacts_proxy_userinfo_for_strings_and_maps(): void
    {
        $single = SensitiveOptionSanitizer::sanitize([
            'proxy' => 'https://alice:password@proxy.example.test:8080',
        ]);
        self::assertSame(
            'https://[REDACTED]@proxy.example.test:8080',
            $single['proxy'],
        );

        $mapped = SensitiveOptionSanitizer::sanitize([
            'proxy' => [
                'http' => 'http://bob:secret@proxy.example.test',
                'https' => 'socks5://user:pass@proxy.example.test:1080',
                'no' => ['localhost'],
            ],
        ]);
        self::assertSame(
            'http://[REDACTED]@proxy.example.test',
            $mapped['proxy']['http'],
        );
        self::assertSame(
            'socks5://[REDACTED]@proxy.example.test:1080',
            $mapped['proxy']['https'],
        );
        self::assertSame(['localhost'], $mapped['proxy']['no']);
    }

    public function test_safe_proxy_url_is_not_modified(): void
    {
        $result = SensitiveOptionSanitizer::sanitize([
            'proxy' => 'http://proxy.example.test:8080',
        ]);
        self::assertSame(
            'http://proxy.example.test:8080',
            $result['proxy'],
        );
    }
}
