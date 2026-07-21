<?php

declare(strict_types=1);

namespace Fetch\Http;

/**
 * Immutable value object representing a single Server-Sent Event.
 *
 * Follows the WHATWG "Server-Sent Events" specification for the event
 * stream format. A dispatched event carries an optional `id` and `event`
 * (type) field, the accumulated `data` payload, and an optional `retry`
 * reconnection time (in milliseconds).
 *
 * @see https://html.spec.whatwg.org/multipage/server-sent-events.html
 *
 * @psalm-immutable
 */
final class ServerSentEvent
{
    /**
     * @param  string  $data  The event data payload (multiple `data:` lines joined with "\n").
     * @param  string  $type  The event type (from the `event:` field), defaulting to "message".
     * @param  string|null  $id  The last event ID (from the `id:` field), if present.
     * @param  int|null  $retry  The reconnection time in milliseconds (from the `retry:` field), if present.
     */
    public function __construct(
        public readonly string $data = '',
        public readonly string $type = 'message',
        public readonly ?string $id = null,
        public readonly ?int $retry = null,
    ) {}

    /**
     * Decode the event data payload as JSON.
     *
     * Most streaming APIs (LLM completions, live feeds) send a JSON object
     * per event, so this is provided as a convenience over `->data`.
     *
     * @throws \JsonException When the payload is not valid JSON and $throwOnError is true.
     */
    public function json(bool $assoc = true, bool $throwOnError = true, int $depth = 512, int $options = 0): mixed
    {
        try {
            return json_decode($this->data, $assoc, $depth, $options | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            if ($throwOnError) {
                throw $e;
            }

            return null;
        }
    }

    /**
     * Determine whether this event's data is the conventional stream
     * termination sentinel used by many APIs (e.g. OpenAI's "[DONE]").
     */
    public function isDone(): bool
    {
        return trim($this->data) === '[DONE]';
    }

    /**
     * Get the event data payload when cast to a string.
     */
    public function __toString(): string
    {
        return $this->data;
    }
}
