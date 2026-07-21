<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Http\EventSource;
use React\Promise\PromiseInterface;

interface RequestExecutor
{
    /**
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
     * @param  array<string, mixed>  $options
     * @return Response|PromiseInterface<Response>
     */
    public function sendRequest(
        Method|string $method,
        string $uri,
        array $options = [],
    ): Response|PromiseInterface;

    /**
     * @return Response|PromiseInterface<Response>
     */
    public function head(string $uri): Response|PromiseInterface;

    /**
     * @param  array<string, mixed>  $queryParams
     * @return Response|PromiseInterface<Response>
     */
    public function get(string $uri, array $queryParams = []): Response|PromiseInterface;

    /**
     * @return Response|PromiseInterface<Response>
     */
    public function post(string $uri, mixed $body = null, string|ContentType $contentType = ContentType::JSON): Response|PromiseInterface;

    /**
     * @return Response|PromiseInterface<Response>
     */
    public function put(string $uri, mixed $body = null, string|ContentType $contentType = ContentType::JSON): Response|PromiseInterface;

    /**
     * @return Response|PromiseInterface<Response>
     */
    public function patch(string $uri, mixed $body = null, string|ContentType $contentType = ContentType::JSON): Response|PromiseInterface;

    /**
     * @return Response|PromiseInterface<Response>
     */
    public function delete(string $uri, mixed $body = null, string|ContentType $contentType = ContentType::JSON): Response|PromiseInterface;

    /**
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
