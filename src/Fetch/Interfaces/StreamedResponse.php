<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Enum\ContentType;
use Fetch\Enum\Status;
use Fetch\Http\EventSource;
use Fetch\Http\Response;
use Generator;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

/**
 * Contract for a response whose body is consumed incrementally.
 *
 * Unlike the buffered {@see Response}, a streamed response never reads the
 * whole body into memory up front. Consumers pull data as it arrives via
 * {@see StreamedResponse::stream()} (raw chunks) or {@see StreamedResponse::lines()}
 * (newline-delimited), mirroring JavaScript's `response.body` reader.
 *
 * @extends \IteratorAggregate<int, string>
 */
interface StreamedResponse extends \IteratorAggregate, PsrResponseInterface
{
    /**
     * Wrap a PSR-7 response for incremental consumption.
     */
    public static function createFromBase(PsrResponseInterface $response): self;

    /**
     * Yield raw body chunks as they arrive from the underlying stream.
     *
     * @param  int  $chunkSize  Maximum number of bytes to read per iteration.
     * @return Generator<int, string>
     */
    public function stream(int $chunkSize = 8192): Generator;

    /**
     * Yield the body one line at a time, handling chunk boundaries.
     *
     * Line terminators are stripped. Both "\n" and "\r\n" are recognised.
     *
     * @return Generator<int, string>
     */
    public function lines(): Generator;

    /**
     * Consume the stream as Server-Sent Events.
     */
    public function sse(): EventSource;

    /**
     * Drain the remaining stream into a fully buffered {@see Response}.
     *
     * This is the escape hatch back to the buffered API. Once buffered, the
     * underlying stream is exhausted and cannot be re-read.
     */
    public function buffer(): Response;

    /**
     * Get the HTTP status code of the response.
     */
    public function status(): int;

    /**
     * Get the status as an enum, or null for unknown codes.
     */
    public function statusEnum(): ?Status;

    /**
     * Determine whether the response status code is a success (2xx).
     */
    public function ok(): bool;

    /**
     * Determine whether the response is a client or server error.
     */
    public function failed(): bool;

    /**
     * Get all response headers.
     *
     * @return array<string, array<int, string>>
     */
    public function headers(): array;

    /**
     * Get a single response header line, or null when absent.
     */
    public function header(string $header): ?string;

    /**
     * Get the Content-Type header without parameters (e.g. charset).
     */
    public function contentType(): ?string;

    /**
     * Get the Content-Type as an enum.
     */
    public function contentTypeEnum(): ?ContentType;
}
