<?php

declare(strict_types=1);

namespace Fetch\Support;

/**
 * Removes secrets from diagnostic request options without changing transport
 * options. Never pass the returned values back into HTTP execution.
 */
final class SensitiveOptionSanitizer
{
    /**
     * Headers containing credentials or cross-site request tokens.
     *
     * @var list<string>
     */
    private const SENSITIVE_HEADERS = [
        'authorization',
        'x-api-key',
        'api-key',
        'x-auth-token',
        'cookie',
        'set-cookie',
        'x-csrf-token',
        'x-xsrf-token',
    ];

    /**
     * Redact options used in logs and debug snapshots.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function sanitize(array $options): array
    {
        $sanitized = $options;

        if (is_array($sanitized['headers'] ?? null)) {
            foreach ($sanitized['headers'] as $key => $value) {
                if (in_array(
                    strtolower((string) $key),
                    self::SENSITIVE_HEADERS,
                    true,
                )) {
                    $sanitized['headers'][$key] = is_array($value)
                        ? array_fill(0, count($value), '[REDACTED]')
                        : '[REDACTED]';
                }
            }
        }

        foreach (['auth', 'token'] as $key) {
            if (array_key_exists($key, $sanitized)) {
                $sanitized[$key] = '[REDACTED]';
            }
        }

        if (array_key_exists('proxy', $sanitized)) {
            $proxy = $sanitized['proxy'];

            if (is_string($proxy)) {
                $sanitized['proxy'] = self::redactProxy($proxy);
            } elseif (is_array($proxy)) {
                foreach ($proxy as $key => $value) {
                    if (is_string($value)) {
                        $proxy[$key] = self::redactProxy($value);
                    }
                }

                $sanitized['proxy'] = $proxy;
            }
        }

        return $sanitized;
    }

    /**
     * Mask URL userinfo in a proxy URI while preserving its endpoint.
     */
    private static function redactProxy(string $proxy): string
    {
        return preg_replace(
            '~(://)[^/@\\s]+@~',
            '$1[REDACTED]@',
            $proxy,
        ) ?? '[REDACTED]';
    }
}
