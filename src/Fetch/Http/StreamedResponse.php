<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Enum\ContentType;
use Fetch\Enum\Status;
use Fetch\Interfaces\StreamedResponse as StreamedResponseInterface;
use Generator;
use GuzzleHttp\Psr7\Response as BaseResponse;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

/**
 * A response whose body is read incrementally instead of being buffered.
 *
 * Obtained when a request is made with the `stream` option enabled (or via
 * the `fetch_stream()` / `->stream()` helpers). The underlying PSR-7 body is
 * left unread so callers can pull chunks, lines, or Server-Sent Events as the
 * server produces them — the PHP equivalent of JavaScript's `response.body`.
 *
 * The read methods ({@see stream()}, {@see lines()}) consume the underlying
 * stream, so it can only be traversed once. Use {@see buffer()} to fall back
 * to a fully materialised {@see Response} when random access is needed.
 */
class StreamedResponse extends BaseResponse implements StreamedResponseInterface
{
    /**
     * Create a new streamed response instance.
     */
    public static function createFromBase(PsrResponseInterface $response): self
    {
        return new self(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody(),
            $response->getProtocolVersion(),
            $response->getReasonPhrase()
        );
    }

    /**
     * Yield raw body chunks as they arrive from the underlying stream.
     *
     * @return Generator<int, string>
     */
    public function stream(int $chunkSize = 8192): Generator
    {
        if ($chunkSize < 1) {
            throw new InvalidArgumentException('Chunk size must be a positive integer.');
        }

        $body = $this->getBody();

        // Rewind when possible so a fresh iteration starts from the top.
        if ($body->isSeekable() && $body->tell() !== 0) {
            $body->rewind();
        }

        while (! $body->eof()) {
            $chunk = $body->read($chunkSize);

            // A seekable, in-memory stream can legitimately return an empty
            // string at EOF without eof() flipping first; guard against a
            // busy loop by breaking on empty reads.
            if ($chunk === '') {
                break;
            }

            yield $chunk;
        }
    }

    /**
     * Yield the body one line at a time, handling chunk boundaries.
     *
     * @return Generator<int, string>
     */
    public function lines(): Generator
    {
        $buffer = '';

        foreach ($this->stream() as $chunk) {
            $buffer .= $chunk;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);

                yield rtrim($line, "\r");
            }
        }

        // Emit any trailing content not terminated by a newline.
        if ($buffer !== '') {
            yield rtrim($buffer, "\r");
        }
    }

    /**
     * Consume the stream as Server-Sent Events.
     */
    public function sse(): EventSource
    {
        return new EventSource($this);
    }

    /**
     * Make the streamed response directly iterable over its raw chunks.
     *
     * @return Generator<int, string>
     */
    public function getIterator(): Generator
    {
        yield from $this->stream();
    }

    /**
     * Drain the remaining stream into a fully buffered {@see Response}.
     */
    public function buffer(): Response
    {
        return new Response(
            $this->getStatusCode(),
            $this->getHeaders(),
            (string) $this->getBody(),
            $this->getProtocolVersion(),
            $this->getReasonPhrase()
        );
    }

    /**
     * Get the HTTP status code of the response.
     */
    public function status(): int
    {
        return $this->getStatusCode();
    }

    /**
     * Get the status as an enum, or null for unknown codes.
     */
    public function statusEnum(): ?Status
    {
        return Status::tryFrom($this->getStatusCode());
    }

    /**
     * Determine whether the response status code is a success (2xx).
     */
    public function ok(): bool
    {
        return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    /**
     * Determine whether the response is a client or server error (4xx/5xx).
     */
    public function failed(): bool
    {
        return $this->getStatusCode() >= 400;
    }

    /**
     * Get all response headers.
     *
     * @return array<string, array<int, string>>
     */
    public function headers(): array
    {
        return $this->getHeaders();
    }

    /**
     * Get a single response header line, or null when absent.
     */
    public function header(string $header): ?string
    {
        return $this->hasHeader($header) ? $this->getHeaderLine($header) : null;
    }

    /**
     * Get the Content-Type header without parameters (e.g. charset).
     */
    public function contentType(): ?string
    {
        $header = $this->getHeaderLine('Content-Type') ?: null;

        if ($header === null) {
            return null;
        }

        if (($pos = strpos($header, ';')) !== false) {
            return trim(substr($header, 0, $pos));
        }

        return $header;
    }

    /**
     * Get the Content-Type as an enum.
     */
    public function contentTypeEnum(): ?ContentType
    {
        $contentType = $this->contentType();

        return $contentType !== null ? ContentType::tryFromString($contentType) : null;
    }
}
