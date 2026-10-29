<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Enum\Method;
use PHPUnit\Framework\TestCase;

class MethodTest extends TestCase
{
    public function test_trace_and_connect_methods_are_supported(): void
    {
        $this->assertSame(Method::TRACE, Method::fromString('trace'));
        $this->assertSame(Method::CONNECT, Method::fromString('CONNECT'));
        $this->assertFalse(Method::TRACE->supportsRequestBody());
        $this->assertFalse(Method::CONNECT->supportsRequestBody());
    }
}
