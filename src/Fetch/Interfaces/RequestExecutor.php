<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Http\EventSource;
use React\Promise\PromiseInterface;

/**
 * Sends configured HTTP requests and returns responses or promises.
 */
interface RequestExecutor
{
    /**
     * Sends a request with the supplied method, URI, and options.
     *
     * @param  array<string, mixed>  $options
     * @return Response|PromiseInterface<Response>
     */
    public function request(
        string|Method $method,
        string $uri,
        mixed $body = null,
        string|ContentType $contentType = ContentType::JSON,
        array $options = [],
    ): Response|PromiseInterface;

    /**
     * Sends the supplied request through the configured request executor.
     *
     * @param  array<string, mixed>  $options
     * @return Response|PromiseInterface<Response>
     */
    public function sendRequest(
        Method|string $method,
        string $uri,
        array $options = [],
    ): Response|PromiseInterface;

    /**
     * Sends a HEAD request to the supplied URI.
     *
     * @return Response|PromiseInterface<Response>
     */
    public function head(string $uri): Response|PromiseInterface;

    /**
     * Sends a GET request to the supplied URI and query parameters.
     *
     * @param  array<string, mixed>  $queryParams
     * @return Response|PromiseInterface<Response>
     */
    public function get(
        string $uri,
        array $queryParams = [],
    ): Response|PromiseInterface;

    /**
     * Sends a POST request to the supplied URI and body options.
     *
     * @return Response|PromiseInterface<Response>
     */
    public function post(
        string $uri,
        mixed $body = null,
        string|ContentType $contentType = ContentType::JSON,
    ): Response|PromiseInterface;

    /**
     * Sends a PUT request to the supplied URI and body options.
     *
     * @return Response|PromiseInterface<Response>
     */
    public function put(
        string $uri,
        mixed $body = null,
        string|ContentType $contentType = ContentType::JSON,
    ): Response|PromiseInterface;

    /**
     * Sends a PATCH request to the supplied URI and body options.
     *
     * @return Response|PromiseInterface<Response>
     */
    public function patch(
        string $uri,
        mixed $body = null,
        string|ContentType $contentType = ContentType::JSON,
    ): Response|PromiseInterface;

    /**
     * Sends a DELETE request to the supplied URI and body options.
     *
     * @return Response|PromiseInterface<Response>
     */
    public function delete(
        string $uri,
        mixed $body = null,
        string|ContentType $contentType = ContentType::JSON,
    ): Response|PromiseInterface;

    /**
     * Sends an OPTIONS request to the supplied URI.
     *
     * @return Response|PromiseInterface<Response>
     */
    public function options(string $uri): Response|PromiseInterface;

    /**
     * Send a request and return an unbuffered, streamable response.
     *
     * @param  array<string, mixed>  $options
     */
    public function stream(
        Method|string $method = Method::GET,
        string $uri = '',
        array $options = [],
    ): StreamedResponse;

    /**
     * Send a request and consume the response as Server-Sent Events.
     *
     * @param  array<string, mixed>  $options
     */
    public function sse(
        Method|string $method = Method::GET,
        string $uri = '',
        array $options = [],
    ): EventSource;
}
