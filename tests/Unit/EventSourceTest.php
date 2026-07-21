<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Http\EventSource;
use Fetch\Http\ServerSentEvent;
use Fetch\Http\StreamedResponse;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

class EventSourceTest extends TestCase
{
    /**
     * @return array<int, ServerSentEvent>
     */
    private function parse(string $body): array
    {
        $response = new StreamedResponse(200, ['Content-Type' => 'text/event-stream'], Utils::streamFor($body));

        return iterator_to_array((new EventSource($response))->events(), false);
    }

    public function test_parses_simple_events(): void
    {
        $events = $this->parse("data: hello\n\ndata: world\n\n");

        $this->assertCount(2, $events);
        $this->assertSame('hello', $events[0]->data);
        $this->assertSame('world', $events[1]->data);
        $this->assertSame('message', $events[0]->type);
    }

    public function test_parses_event_type_and_id(): void
    {
        $events = $this->parse("event: update\nid: 7\ndata: payload\n\n");

        $this->assertCount(1, $events);
        $this->assertSame('update', $events[0]->type);
        $this->assertSame('7', $events[0]->id);
        $this->assertSame('payload', $events[0]->data);
    }

    public function test_joins_multiline_data(): void
    {
        $events = $this->parse("data: line one\ndata: line two\n\n");

        $this->assertCount(1, $events);
        $this->assertSame("line one\nline two", $events[0]->data);
    }

    public function test_ignores_comment_lines(): void
    {
        $events = $this->parse(": this is a comment\ndata: real\n\n");

        $this->assertCount(1, $events);
        $this->assertSame('real', $events[0]->data);
    }

    public function test_strips_only_first_leading_space_from_value(): void
    {
        $events = $this->parse("data:  two spaces\n\n");

        $this->assertSame(' two spaces', $events[0]->data);
    }

    public function test_value_without_space_after_colon(): void
    {
        $events = $this->parse("data:nospace\n\n");

        $this->assertSame('nospace', $events[0]->data);
    }

    public function test_field_without_colon_is_empty_value(): void
    {
        // A lone "data" line contributes an empty data line.
        $events = $this->parse("data\n\n");

        $this->assertCount(1, $events);
        $this->assertSame('', $events[0]->data);
    }

    public function test_events_without_data_do_not_dispatch(): void
    {
        $events = $this->parse("event: ping\nid: 1\n\ndata: kept\n\n");

        $this->assertCount(1, $events);
        $this->assertSame('kept', $events[0]->data);
    }

    public function test_last_event_id_persists_across_events(): void
    {
        $events = $this->parse("id: 100\ndata: a\n\ndata: b\n\n");

        $this->assertSame('100', $events[0]->id);
        $this->assertSame('100', $events[1]->id);
    }

    public function test_retry_field_parsed_and_carried(): void
    {
        $events = $this->parse("retry: 5000\ndata: a\n\n");

        $this->assertSame(5000, $events[0]->retry);
    }

    public function test_non_numeric_retry_is_ignored(): void
    {
        $events = $this->parse("retry: soon\ndata: a\n\n");

        $this->assertNull($events[0]->retry);
    }

    public function test_incomplete_trailing_event_is_discarded(): void
    {
        // No terminating blank line, so the final event is not dispatched.
        $events = $this->parse("data: complete\n\ndata: dangling\n");

        $this->assertCount(1, $events);
        $this->assertSame('complete', $events[0]->data);
    }

    public function test_done_sentinel_is_yielded_as_event(): void
    {
        $events = $this->parse("data: {\"x\":1}\n\ndata: [DONE]\n\n");

        $this->assertCount(2, $events);
        $this->assertFalse($events[0]->isDone());
        $this->assertTrue($events[1]->isDone());
    }

    public function test_events_split_across_stream_chunks(): void
    {
        // Build a body that will straddle the 8192-byte default chunk size,
        // forcing the line buffer to reassemble events across reads.
        $body = '';
        for ($i = 0; $i < 2000; $i++) {
            $body .= "data: event-{$i}\n\n";
        }

        $events = $this->parse($body);

        $this->assertCount(2000, $events);
        $this->assertSame('event-0', $events[0]->data);
        $this->assertSame('event-1999', $events[1999]->data);
    }

    public function test_tracks_last_event_id_and_reconnection_time(): void
    {
        $response = new StreamedResponse(200, [], Utils::streamFor("retry: 2500\nid: abc\ndata: x\n\n"));
        $source = new EventSource($response);

        iterator_to_array($source->events(), false);

        $this->assertSame('abc', $source->lastEventId());
        $this->assertSame(2500, $source->reconnectionTime());
        $this->assertSame($response, $source->response());
    }
}
