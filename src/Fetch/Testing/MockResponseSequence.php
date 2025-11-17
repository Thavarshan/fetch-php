<?php

declare(strict_types=1);

namespace Fetch\Testing;

use OutOfBoundsException;

class MockResponseSequence
{
    /**
     * The responses in the sequence.
     *
     * @var array<MockResponse>
     */
    protected array $responses = [];

    /**
     * The current index in the sequence.
     */
    protected int $currentIndex = 0;

    /**
     * The default response to use when the sequence is empty.
     */
    protected ?MockResponse $defaultResponse = null;

    /**
     * Whether the sequence should loop.
     */
    protected bool $shouldLoop = false;

    /**
     * Create a new mock response sequence.
     *
     * @param  array<MockResponse>  $responses  Initial responses
     */
    public function __construct(array $responses = [])
    {
        $this->responses = $responses;
    }

    /**
     * Add a response to the sequence.
     *
     * @param  int  $status  HTTP status code
     * @param  mixed  $body  Response body
     * @param  array<string, string|array<string>>  $headers  Response headers
     */
    public function push(int $status = 200, mixed $body = '', array $headers = []): self
    {
        $this->responses[] = MockResponse::create($status, $body, $headers);

        return $this;
    }

    /**
     * Add a JSON response to the sequence.
     *
     * @param  array<mixed>|object  $data  Data to encode as JSON
     * @param  int  $status  HTTP status code
     * @param  array<string, string|array<string>>  $headers  Additional headers
     */
    public function pushJson(array|object $data, int $status = 200, array $headers = []): self
    {
        $this->responses[] = MockResponse::json($data, $status, $headers);

        return $this;
    }

    /**
     * Add a status-only response to the sequence.
     *
     * @param  array<string, string|array<string>>  $headers
     */
    public function pushStatus(int $status, array $headers = []): self
    {
        $this->responses[] = MockResponse::create($status, '', $headers);

        return $this;
    }

    /**
     * Add a response instance to the sequence.
     */
    public function pushResponse(MockResponse $response): self
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Set the default response to use when the sequence is exhausted.
     */
    public function whenEmpty(MockResponse $response): self
    {
        $this->defaultResponse = $response;

        return $this;
    }

    /**
     * Make the sequence loop back to the beginning when exhausted.
     */
    public function loop(): self
    {
        $this->shouldLoop = true;

        return $this;
    }

    /**
     * Get the next response in the sequence.
     *
     * @throws OutOfBoundsException
     */
    public function next(): MockResponse
    {
        if (empty($this->responses)) {
            if ($this->defaultResponse !== null) {
                return $this->defaultResponse;
            }

            throw new OutOfBoundsException('No more responses in the sequence.');
        }

        if ($this->currentIndex >= count($this->responses)) {
            if ($this->shouldLoop) {
                $this->currentIndex = 0;
            } elseif ($this->defaultResponse !== null) {
                return $this->defaultResponse;
            } else {
                throw new OutOfBoundsException('No more responses in the sequence.');
            }
        }

        $response = $this->responses[$this->currentIndex];
        $this->currentIndex++;

        return $response;
    }

    /**
     * Check if there are more responses in the sequence.
     */
    public function hasMore(): bool
    {
        return $this->currentIndex < count($this->responses)
            || $this->shouldLoop
            || $this->defaultResponse !== null;
    }

    /**
     * Reset the sequence to the beginning.
     */
    public function reset(): self
    {
        $this->currentIndex = 0;

        return $this;
    }

    /**
     * Get the current index in the sequence.
     */
    public function getCurrentIndex(): int
    {
        return $this->currentIndex;
    }

    /**
     * Get the total number of responses in the sequence.
     */
    public function count(): int
    {
        return count($this->responses);
    }

    /**
     * Check if the sequence is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->responses);
    }
}
