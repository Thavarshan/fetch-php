<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Enum\Status;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    public function test_every_status_has_a_reason_phrase_and_matching_category(): void
    {
        foreach (Status::cases() as $status) {
            $this->assertNotSame('', trim($status->phrase()), $status->name);
            $this->assertSame($status->value >= 100 && $status->value < 200, $status->isInformational(), $status->name);
            $this->assertSame($status->value >= 200 && $status->value < 300, $status->isSuccess(), $status->name);
            $this->assertSame($status->value >= 300 && $status->value < 400, $status->isRedirection(), $status->name);
            $this->assertSame($status->value >= 400 && $status->value < 500, $status->isClientError(), $status->name);
            $this->assertSame($status->value >= 500 && $status->value < 600, $status->isServerError(), $status->name);
            $this->assertSame($status->isClientError() || $status->isServerError(), $status->isError(), $status->name);
        }
    }

    public function test_status_convenience_methods_match_their_cases(): void
    {
        $this->assertTrue(Status::NOT_MODIFIED->isNotModified());
        $this->assertTrue(Status::NO_CONTENT->isEmpty());
        $this->assertTrue(Status::NOT_MODIFIED->isEmpty());
        $this->assertFalse(Status::OK->isEmpty());
        $this->assertTrue(Status::OK->isCacheable());
        $this->assertFalse(Status::INTERNAL_SERVER_ERROR->isCacheable());
        $this->assertSame(Status::NOT_FOUND, Status::tryFromInt(404));
        $this->assertNull(Status::tryFromInt(999));
    }
}
