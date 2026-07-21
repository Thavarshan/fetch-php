<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Enum\ContentType;
use Fetch\Enum\Status;
use Fetch\Http\EventSource;
use Fetch\Http\Response;
use Fetch\Http\StreamedResponse;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

class StreamedResponseTest extends TestCase
{
    private function make(string $body, array $headers = [], int $status = 200): StreamedResponse
    {
        return new StreamedResponse($status, $headers, Utils::streamFor($body));
    }

    public function test_streams_body_in_chunks(): void
    {
        $response = $this->make('abcdefghij');

        $chunks = iterator_to_array($response->stream(4), false);

        $this->assertSame(['abcd', 'efgh', 'ij'], $chunks);
    }

    public function test_stream_rejects_non_positive_chunk_size(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        iterator_to_array($this->make('x')->stream(0));
    }

    public function test_yields_lines_across_chunk_boundaries(): void
    {
        $response = $this->make("first line\r\nsecond line\nthird");

        $lines = iterator_to_array($response->lines(), false);

        $this->assertSame(['first line', 'second line', 'third'], $lines);
    }

    public function test_lines_handles_trailing_newline(): void
    {
        $response = $this->make("only\n");

        $this->assertSame(['only'], iterator_to_array($response->lines(), false));
    }

    public function test_buffer_returns_full_response(): void
    {
        $buffered = $this->make('{"ok":true}', ['Content-Type' => 'application/json'])->buffer();

        $this->assertInstanceOf(Response::class, $buffered);
        $this->assertSame(['ok' => true], $buffered->json());
    }

    public function test_status_helpers(): void
    {
        $ok = $this->make('', [], 200);
        $this->assertTrue($ok->ok());
        $this->assertFalse($ok->failed());
        $this->assertSame(Status::OK, $ok->statusEnum());

        $error = $this->make('', [], 503);
        $this->assertFalse($error->ok());
        $this->assertTrue($error->failed());
        $this->assertSame(503, $error->status());
    }

    public function test_header_accessors(): void
    {
        $response = $this->make('', ['Content-Type' => 'text/event-stream; charset=utf-8']);

        $this->assertSame('text/event-stream; charset=utf-8', $response->header('Content-Type'));
        $this->assertNull($response->header('X-Missing'));
        $this->assertSame('text/event-stream', $response->contentType());
        $this->assertSame(ContentType::EVENT_STREAM, $response->contentTypeEnum());
    }

    public function test_is_iterable_over_chunks(): void
    {
        $response = $this->make('hello world');

        $this->assertSame('hello world', implode('', iterator_to_array($response, false)));
    }

    public function test_sse_returns_event_source(): void
    {
        $this->assertInstanceOf(EventSource::class, $this->make('')->sse());
    }

    public function test_create_from_base_preserves_metadata(): void
    {
        $base = new PsrResponse(201, ['X-Test' => 'yes'], 'body', '2', 'Created');

        $response = StreamedResponse::createFromBase($base);

        $this->assertSame(201, $response->status());
        $this->assertSame('yes', $response->header('X-Test'));
        $this->assertSame('2', $response->getProtocolVersion());
    }
}
