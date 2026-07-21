<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Http\ServerSentEvent;
use PHPUnit\Framework\TestCase;

class ServerSentEventTest extends TestCase
{
    public function test_defaults(): void
    {
        $event = new ServerSentEvent;

        $this->assertSame('', $event->data);
        $this->assertSame('message', $event->type);
        $this->assertNull($event->id);
        $this->assertNull($event->retry);
    }

    public function test_exposes_all_fields(): void
    {
        $event = new ServerSentEvent(data: 'hello', type: 'greeting', id: '42', retry: 3000);

        $this->assertSame('hello', $event->data);
        $this->assertSame('greeting', $event->type);
        $this->assertSame('42', $event->id);
        $this->assertSame(3000, $event->retry);
    }

    public function test_json_decodes_payload(): void
    {
        $event = new ServerSentEvent(data: '{"delta":"hi","index":0}');

        $this->assertSame(['delta' => 'hi', 'index' => 0], $event->json());
        $this->assertSame('hi', $event->json()->delta ?? ($event->json(false)->delta));
    }

    public function test_json_returns_null_on_invalid_when_not_throwing(): void
    {
        $event = new ServerSentEvent(data: 'not-json');

        $this->assertNull($event->json(throwOnError: false));
    }

    public function test_json_throws_on_invalid_by_default(): void
    {
        $this->expectException(\JsonException::class);

        (new ServerSentEvent(data: 'not-json'))->json();
    }

    public function test_is_done_detects_sentinel(): void
    {
        $this->assertTrue((new ServerSentEvent(data: '[DONE]'))->isDone());
        $this->assertTrue((new ServerSentEvent(data: ' [DONE] '))->isDone());
        $this->assertFalse((new ServerSentEvent(data: 'done'))->isDone());
    }

    public function test_stringable(): void
    {
        $this->assertSame('payload', (string) new ServerSentEvent(data: 'payload'));
    }
}
