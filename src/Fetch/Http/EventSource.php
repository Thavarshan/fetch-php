<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Interfaces\StreamedResponse as StreamedResponseInterface;
use Generator;
use IteratorAggregate;

/**
 * Parses a `text/event-stream` body into {@see ServerSentEvent} objects.
 *
 * Implements the line-parsing and event-dispatching algorithm from the
 * WHATWG Server-Sent Events specification, working incrementally over the
 * lines produced by a {@see StreamedResponse}. Events are yielded lazily as
 * their terminating blank line is read, so a long-lived stream (such as a
 * streaming LLM completion) can be consumed with a simple `foreach`.
 *
 * ```php
 * $events = fetch_sse('https://api.example.com/v1/stream');
 *
 * foreach ($events as $event) {
 *     if ($event->isDone()) {
 *         break;
 *     }
 *
 *     $payload = $event->json();
 * }
 * ```
 *
 * @see https://html.spec.whatwg.org/multipage/server-sent-events.html#event-stream-interpretation
 *
 * @implements IteratorAggregate<int, ServerSentEvent>
 */
class EventSource implements IteratorAggregate
{
    /**
     * The last event ID seen on the stream.
     *
     * Per the specification this persists across events and is carried onto
     * subsequent events that omit an `id:` field.
     */
    protected ?string $lastEventId = null;

    /**
     * The most recent reconnection time (ms) advertised via a `retry:` field.
     */
    protected ?int $reconnectionTime = null;

    public function __construct(
        protected readonly StreamedResponseInterface $response,
    ) {}

    /**
     * The underlying streamed response.
     */
    public function response(): StreamedResponseInterface
    {
        return $this->response;
    }

    /**
     * The last event ID observed on the stream so far.
     */
    public function lastEventId(): ?string
    {
        return $this->lastEventId;
    }

    /**
     * The most recent reconnection time (in milliseconds), if advertised.
     */
    public function reconnectionTime(): ?int
    {
        return $this->reconnectionTime;
    }

    /**
     * Yield parsed events as they arrive on the stream.
     *
     * @return Generator<int, ServerSentEvent>
     */
    public function events(): Generator
    {
        // Per-event accumulators, reset after each dispatch.
        $dataBuffer = '';
        $eventType = '';
        $hasData = false;
        $hasFields = false;

        foreach ($this->response->lines() as $line) {
            // A blank line dispatches the buffered event (if any).
            if ($line === '') {
                if ($hasData || $hasFields) {
                    $event = $this->buildEvent($dataBuffer, $eventType, $hasData);

                    if ($event !== null) {
                        yield $event;
                    }
                }

                $dataBuffer = '';
                $eventType = '';
                $hasData = false;
                $hasFields = false;

                continue;
            }

            // Lines beginning with a colon are comments and are ignored.
            if ($line[0] === ':') {
                continue;
            }

            [$field, $value] = $this->splitField($line);

            switch ($field) {
                case 'event':
                    $eventType = $value;
                    $hasFields = true;
                    break;

                case 'data':
                    $dataBuffer .= $value."\n";
                    $hasData = true;
                    $hasFields = true;
                    break;

                case 'id':
                    // The spec ignores an id containing a NUL character.
                    if (! str_contains($value, "\0")) {
                        $this->lastEventId = $value;
                    }
                    $hasFields = true;
                    break;

                case 'retry':
                    if ($value !== '' && ctype_digit($value)) {
                        $this->reconnectionTime = (int) $value;
                    }
                    $hasFields = true;
                    break;

                default:
                    // Unknown fields are ignored per the specification.
                    break;
            }
        }

        // The stream ended without a trailing blank line: the spec discards
        // any incomplete event, so no final dispatch is performed here.
    }

    /**
     * Alias for {@see events()} so the source is directly foreach-able.
     *
     * @return Generator<int, ServerSentEvent>
     */
    public function getIterator(): Generator
    {
        yield from $this->events();
    }

    /**
     * Build a dispatchable event from the current accumulators.
     *
     * Returns null when the buffer holds no data lines (only metadata), which
     * per the spec must not fire a message event.
     */
    protected function buildEvent(string $dataBuffer, string $eventType, bool $hasData): ?ServerSentEvent
    {
        if (! $hasData) {
            return null;
        }

        // A single trailing newline is stripped from the data buffer.
        $data = substr($dataBuffer, -1) === "\n"
            ? substr($dataBuffer, 0, -1)
            : $dataBuffer;

        return new ServerSentEvent(
            data: $data,
            type: $eventType !== '' ? $eventType : 'message',
            id: $this->lastEventId,
            retry: $this->reconnectionTime,
        );
    }

    /**
     * Split a field line into its name and value per the SSE grammar.
     *
     * A line with no colon is a field with an empty value. A single space
     * immediately following the colon is stripped from the value.
     *
     * @return array{0: string, 1: string}
     */
    protected function splitField(string $line): array
    {
        $pos = strpos($line, ':');

        if ($pos === false) {
            return [$line, ''];
        }

        $field = substr($line, 0, $pos);
        $value = substr($line, $pos + 1);

        if ($value !== '' && $value[0] === ' ') {
            $value = substr($value, 1);
        }

        return [$field, $value];
    }
}
