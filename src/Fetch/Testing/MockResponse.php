<?php

declare(strict_types=1);

namespace Fetch\Testing;

use Fetch\Enum\Status;
use Fetch\Http\Response;
use Fetch\Interfaces\Response as ResponseInterface;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Throwable;

class MockResponse
{
    /**
     * The response status code.
     */
    protected int $status;

    /**
     * The response body.
     */
    protected mixed $body;

    /**
     * The response headers.
     *
     * @var array<string, string|array<string>>
     */
    protected array $headers;

    /**
     * Delay before returning response (milliseconds).
     */
    protected int $delay = 0;

    /**
     * Exception to throw instead of returning response.
     */
    protected ?Throwable $throwable = null;

    /**
     * Create a new mock response instance.
     *
     * @param  int  $status  HTTP status code
     * @param  mixed  $body  Response body
     * @param  array<string, string|array<string>>  $headers  Response headers
     */
    public function __construct(int $status = 200, mixed $body = '', array $headers = [])
    {
        $this->status = $status;
        $this->body = $body;
        $this->headers = $headers;
    }

    /**
     * Create a new mock response instance.
     *
     * @param  int  $status  HTTP status code
     * @param  mixed  $body  Response body
     * @param  array<string, string|array<string>>  $headers  Response headers
     */
    public static function create(int $status = 200, mixed $body = '', array $headers = []): self
    {
        return new self($status, $body, $headers);
    }

    /**
     * Create a JSON response.
     *
     * @param  array<mixed>|object  $data  Data to encode as JSON
     * @param  int  $status  HTTP status code
     * @param  array<string, string|array<string>>  $headers  Additional headers
     */
    public static function json(array|object $data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';

        return new self($status, json_encode($data), $headers);
    }

    /**
     * Create a sequence of mock responses.
     *
     * @param  array<MockResponse>  $responses  Array of MockResponse instances
     */
    public static function sequence(array $responses = []): MockResponseSequence
    {
        return new MockResponseSequence($responses);
    }

    /**
     * Create a response with a specific HTTP status.
     *
     * @param  array<string, string|array<string>>  $headers
     */
    public static function ok(mixed $body = '', array $headers = []): self
    {
        return new self(Status::OK->value, $body, $headers);
    }

    /**
     * Create a 201 Created response.
     *
     * @param  array<string, string|array<string>>  $headers
     */
    public static function created(mixed $body = '', array $headers = []): self
    {
        return new self(Status::CREATED->value, $body, $headers);
    }

    /**
     * Create a 204 No Content response.
     *
     * @param  array<string, string|array<string>>  $headers
     */
    public static function noContent(array $headers = []): self
    {
        return new self(Status::NO_CONTENT->value, '', $headers);
    }

    /**
     * Create a 400 Bad Request response.
     *
     * @param  array<string, string|array<string>>  $headers
     */
    public static function badRequest(mixed $body = '', array $headers = []): self
    {
        return new self(Status::BAD_REQUEST->value, $body, $headers);
    }

    /**
     * Create a 401 Unauthorized response.
     *
     * @param  array<string, string|array<string>>  $headers
     */
    public static function unauthorized(mixed $body = '', array $headers = []): self
    {
        return new self(Status::UNAUTHORIZED->value, $body, $headers);
    }

    /**
     * Create a 403 Forbidden response.
     *
     * @param  array<string, string|array<string>>  $headers
     */
    public static function forbidden(mixed $body = '', array $headers = []): self
    {
        return new self(Status::FORBIDDEN->value, $body, $headers);
    }

    /**
     * Create a 404 Not Found response.
     *
     * @param  array<string, string|array<string>>  $headers
     */
    public static function notFound(mixed $body = '', array $headers = []): self
    {
        return new self(Status::NOT_FOUND->value, $body, $headers);
    }

    /**
     * Create a 422 Unprocessable Entity response.
     *
     * @param  array<string, string|array<string>>  $headers
     */
    public static function unprocessableEntity(mixed $body = '', array $headers = []): self
    {
        return new self(Status::UNPROCESSABLE_ENTITY->value, $body, $headers);
    }

    /**
     * Create a 500 Internal Server Error response.
     *
     * @param  array<string, string|array<string>>  $headers
     */
    public static function serverError(mixed $body = '', array $headers = []): self
    {
        return new self(Status::INTERNAL_SERVER_ERROR->value, $body, $headers);
    }

    /**
     * Create a 503 Service Unavailable response.
     *
     * @param  array<string, string|array<string>>  $headers
     */
    public static function serviceUnavailable(mixed $body = '', array $headers = []): self
    {
        return new self(Status::SERVICE_UNAVAILABLE->value, $body, $headers);
    }

    /**
     * Set a delay before returning the response.
     *
     * @param  int  $milliseconds  Delay in milliseconds
     */
    public function delay(int $milliseconds): self
    {
        $this->delay = $milliseconds;

        return $this;
    }

    /**
     * Throw an exception instead of returning a response.
     */
    public function throw(Throwable $throwable): self
    {
        $this->throwable = $throwable;

        return $this;
    }

    /**
     * Get the response status code.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Get the response body.
     */
    public function getBody(): mixed
    {
        return $this->body;
    }

    /**
     * Get the response headers.
     *
     * @return array<string, string|array<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get the delay in milliseconds.
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * Get the throwable exception if set.
     */
    public function getThrowable(): ?Throwable
    {
        return $this->throwable;
    }

    /**
     * Execute the mock response (apply delay, throw exception, or return response).
     *
     * @throws Throwable
     */
    public function execute(): ResponseInterface
    {
        // Apply delay if set
        if ($this->delay > 0) {
            usleep($this->delay * 1000);
        }

        // Throw exception if set
        if ($this->throwable !== null) {
            throw $this->throwable;
        }

        // Convert body to string if it's not already
        $body = is_string($this->body) ? $this->body : json_encode($this->body);

        return Response::createFromBase(
            new GuzzleResponse($this->status, $this->headers, $body)
        );
    }
}
