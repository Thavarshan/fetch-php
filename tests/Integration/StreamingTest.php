<?php

declare(strict_types=1);

namespace Tests\Integration;

use Fetch\Enum\Method;
use Fetch\Http\ClientHandler;
use Fetch\Http\EventSource;
use Fetch\Http\StreamedResponse;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;

class StreamingTest extends TestCase
{
    /**
     * Build a ClientHandler backed by a Guzzle MockHandler so streaming can be
     * exercised end-to-end without any network access.
     */
    private function handlerReturning(PsrResponse $response): ClientHandler
    {
        $mock = new MockHandler([$response]);
        $stack = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $stack]);

        return (new ClientHandler)->setHttpClient($guzzle);
    }

    public function test_stream_returns_streamed_response(): void
    {
        $handler = $this->handlerReturning(
            new PsrResponse(200, ['Content-Type' => 'text/plain'], Utils::streamFor("chunk-a\nchunk-b\n"))
        );

        $response = $handler->stream(Method::GET, 'https://example.com/feed');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertTrue($response->ok());
        $this->assertSame(['chunk-a', 'chunk-b'], iterator_to_array($response->lines(), false));
    }

    public function test_sse_consumes_event_stream_end_to_end(): void
    {
        $body = "data: {\"n\":1}\n\ndata: {\"n\":2}\n\ndata: [DONE]\n\n";

        $handler = $this->handlerReturning(
            new PsrResponse(200, ['Content-Type' => 'text/event-stream'], Utils::streamFor($body))
        );

        $source = $handler->sse(Method::GET, 'https://api.example.com/v1/stream');

        $this->assertInstanceOf(EventSource::class, $source);

        $collected = [];
        foreach ($source as $event) {
            if ($event->isDone()) {
                break;
            }

            $collected[] = $event->json();
        }

        $this->assertSame([['n' => 1], ['n' => 2]], $collected);
    }

    public function test_sse_adds_accept_header(): void
    {
        $mock = new MockHandler([
            new PsrResponse(200, ['Content-Type' => 'text/event-stream'], Utils::streamFor("data: ok\n\n")),
        ]);
        $stack = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $stack]);
        $handler = (new ClientHandler)->setHttpClient($guzzle);

        iterator_to_array($handler->sse(Method::GET, 'https://api.example.com/stream')->events(), false);

        $this->assertSame('text/event-stream', $mock->getLastRequest()->getHeaderLine('Accept'));
    }

    public function test_stream_does_not_buffer_body_until_read(): void
    {
        $handler = $this->handlerReturning(
            new PsrResponse(200, [], Utils::streamFor('lazy-body'))
        );

        $response = $handler->stream(Method::GET, 'https://example.com/x');

        // Body is still readable on demand (not consumed at construction time).
        $this->assertFalse($response->getBody()->eof());
        $this->assertSame('lazy-body', implode('', iterator_to_array($response->stream(), false)));
    }
}
