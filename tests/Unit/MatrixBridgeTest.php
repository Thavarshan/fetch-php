<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MatrixBridgeTest extends TestCase
{
    public function test_global_matrix_helpers_bridge_to_matrix_four(): void
    {
        $promise = \async(static fn (): string => 'ready');

        $this->assertSame('ready', \await($promise));
        $this->assertSame(
            ['first', 'second'],
            \await(
                \all([
                    \async(static fn (): string => 'first'),
                    \async(static fn (): string => 'second'),
                ]),
            ),
        );
        $this->assertSame(
            'winner',
            \await(\any([\async(static fn (): string => 'winner')])),
        );
        $this->assertSame(
            [2, 4],
            \await(\map([1, 2], static fn (int $value): int => $value * 2, 2)),
        );
        $this->assertSame(
            [1, 2, 3],
            \await(
                \batch(
                    [1, 2, 3],
                    static fn (array $items): array => $items,
                    2,
                    1,
                ),
            ),
        );

        $attempts = 0;
        $retried = \retry(
            static function () use (&$attempts): string {
                if (++$attempts < 2) {
                    throw new \RuntimeException('retry');
                }

                return 'retried';
            },
            2,
            0,
        );

        $this->assertSame('retried', \await($retried));

        $callbackAttempts = 0;
        $callableDelay = \retry(
            static function () use (&$callbackAttempts): string {
                if (++$callbackAttempts === 1) {
                    throw new \RuntimeException('retry with callback delay');
                }

                return 'callback delay';
            },
            2,
            static fn (): int => 0,
        );
        $this->assertSame('callback delay', \await($callableDelay));
    }
}
