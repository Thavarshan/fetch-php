<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Exceptions\AsyncTransportException;
use Fetch\Http\EventSource;
use Fetch\Http\Response;
use Fetch\Http\StreamedResponse;
use Fetch\Http\StreamingRequestExecutor;
use Fetch\Interfaces\Response as ResponseInterface;
use Fetch\Interfaces\StreamedResponse as StreamedResponseInterface;
use Fetch\Support\RequestContext;
use Fetch\Support\RequestOptions;
use Fetch\Validation\ValidationConfigurationException;
use Fetch\Validation\ValidationException;
use Psr\Http\Message\RequestInterface;
use React\Promise\PromiseInterface;
use RuntimeException;

trait PerformsHttpRequests
{
    /**
     * Handles an HTTP request with the given method, URI, and options.
     *
     * @param  string  $method  The HTTP method to use
     * @param  string  $uri  The URI to request
     * @param  array<string, mixed>  $options  Additional options for the request
     * @return ResponseInterface|PromiseInterface<ResponseInterface> Response or promise
     */
    public static function handle(
        string $method,
        string $uri,
        array $options = [],
    ): ResponseInterface|PromiseInterface {
        $handler = static::create();
        $handler->withOptions($options);

        return $handler->sendRequest($method, $uri);
    }

    /**
     * Send a HEAD request.
     *
     * @param  string  $uri  The URI to request
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function head(string $uri): ResponseInterface|PromiseInterface
    {
        return $this->sendRequest(Method::HEAD, $uri);
    }

    /**
     * Send a GET request.
     *
     * @param  string  $uri  The URI to request
     * @param  array<string, mixed>  $queryParams  Optional query parameters
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function get(string $uri, array $queryParams = []): ResponseInterface|PromiseInterface
    {
        $options = [];
        if (! empty($queryParams)) {
            $options['query'] = $queryParams;
        }

        return $this->sendRequest(Method::GET, $uri, $options);
    }

    /**
     * Send a POST request.
     *
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  The request body
     * @param  ContentType|string  $contentType  The content type of the request
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function post(
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = ContentType::JSON,
    ): ResponseInterface|PromiseInterface {
        return $this->sendRequestWithBody(Method::POST, $uri, $body, $contentType);
    }

    /**
     * Send a PUT request.
     *
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  The request body
     * @param  ContentType|string  $contentType  The content type of the request
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function put(
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = ContentType::JSON,
    ): ResponseInterface|PromiseInterface {
        return $this->sendRequestWithBody(Method::PUT, $uri, $body, $contentType);
    }

    /**
     * Send a PATCH request.
     *
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  The request body
     * @param  ContentType|string  $contentType  The content type of the request
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function patch(
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = ContentType::JSON,
    ): ResponseInterface|PromiseInterface {
        return $this->sendRequestWithBody(Method::PATCH, $uri, $body, $contentType);
    }

    /**
     * Send a DELETE request.
     *
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  Optional request body
     * @param  ContentType|string  $contentType  The content type of the request
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function delete(
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = ContentType::JSON,
    ): ResponseInterface|PromiseInterface {
        return $this->sendRequestWithBody(Method::DELETE, $uri, $body, $contentType);
    }

    /**
     * Send an OPTIONS request.
     *
     * @param  string  $uri  The URI to request
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function options(string $uri): ResponseInterface|PromiseInterface
    {
        return $this->sendRequest(Method::OPTIONS, $uri);
    }

    /**
     * Send a request and return an unbuffered, streamable response.
     *
     * The response body is not read into memory; callers pull chunks or lines
     * as the server produces them (see {@see StreamedResponse}). Streaming is
     * always synchronous and bypasses the response cache.
     *
     * @param  array<string, mixed>  $options  Additional options
     */
    public function stream(
        Method|string $method = Method::GET,
        string $uri = '',
        array $options = [],
    ): StreamedResponseInterface {
        $options['stream'] = true;

        return $this->sendStreamingRequest($method, $uri, $options);
    }

    /**
     * Send a request and consume the response as Server-Sent Events.
     *
     * Adds an `Accept: text/event-stream` header when none is present and
     * returns an {@see EventSource} yielding parsed {@see ServerSentEvent}s.
     *
     * @param  array<string, mixed>  $options  Additional options
     */
    public function sse(
        Method|string $method = Method::GET,
        string $uri = '',
        array $options = [],
    ): EventSource {
        $headers = $options['headers'] ?? [];

        $hasAccept = false;
        foreach (array_keys($headers) as $name) {
            if (strcasecmp((string) $name, 'Accept') === 0) {
                $hasAccept = true;
                break;
            }
        }

        if (! $hasAccept) {
            $headers['Accept'] = ContentType::EVENT_STREAM->value;
        }

        $options['headers'] = $headers;

        return new EventSource($this->stream($method, $uri, $options));
    }

    /**
     * Send an HTTP request.
     *
     * This method is now stateless per-request: it builds an immutable RequestContext
     * from merged options and passes it through the execution stack without mutating
     * the handler's shared state. This makes the handler safe for concurrent usage.
     *
     * @param  Method|string  $method  The HTTP method
     * @param  string  $uri  The URI to request
     * @param  array<string, mixed>  $options  Additional options
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    public function sendRequest(
        Method|string $method,
        string $uri,
        array $options = [],
    ): ResponseInterface|PromiseInterface {
        $methodStr = $method instanceof Method ? $method->value : strtoupper($method);
        $requestOptions = RequestOptions::merge(
            $this->options,
            ['async' => $this->isAsync],
            $options,
            ['method' => $methodStr, 'uri' => $uri],
        );
        RequestOptions::validate($requestOptions);
        $requestOptions['__correlation_id'] = $this->generateCorrelationId();

        $context = RequestContext::fromOptions($requestOptions);
        $fullUri = $this->buildFullUriFromContext($context);
        $policy = $this->getValidationPolicy($context);
        $middleware = $this->resolveMiddleware();

        return $this->createRequestCoordinator($context, $policy, $middleware)->send(
            $methodStr,
            $fullUri,
            $context->toGuzzleOptions(),
            $context,
        );
    }

    /**
     * Sends an HTTP request with the specified parameters.
     *
     * @param  string|Method  $method  HTTP method (e.g., GET, POST)
     * @param  string  $uri  URI to send the request to
     * @param  mixed  $body  Request body
     * @param  string|ContentType  $contentType  Content type of the request
     * @param  array<string, mixed>  $options  Additional request options
     * @return ResponseInterface|PromiseInterface<ResponseInterface> Response or promise
     */
    public function request(
        string|Method $method,
        string $uri,
        mixed $body = null,
        string|ContentType $contentType = ContentType::JSON,
        array $options = [],
    ): ResponseInterface|PromiseInterface {
        $mergedOptions = RequestOptions::merge($this->options, $options);

        if ($body !== null) {
            $mergedOptions = $this->applyBodyOptions($mergedOptions, $body, $contentType);
        }

        return $this->sendRequest($method, $uri, $mergedOptions);
    }

    /**
     * Get the effective timeout for the request.
     *
     * Supports per-request timeout via RequestContext, with handler defaults as fallback.
     *
     * @param  RequestContext|null  $context  Optional request context for per-request override
     * @return int The timeout in seconds
     */
    public function getEffectiveTimeout(?RequestContext $context = null): int
    {
        // First check RequestContext for per-request override
        if ($context !== null) {
            return $context->getTimeout();
        }

        // Next check options array
        if (isset($this->options['timeout']) && is_int($this->options['timeout'])) {
            return $this->options['timeout'];
        }

        // Then check explicitly set timeout property
        if (isset($this->timeout) && is_int($this->timeout)) {
            return $this->timeout;
        }

        // Fall back to default
        return self::DEFAULT_TIMEOUT;
    }

    /**
     * Build the PSR-7 request handed to the middleware pipeline.
     *
     * The request carries the resolved method, absolute URI, and headers, plus
     * a best-effort serialization of the body so middleware can read it. The
     * seeded body string is returned alongside so the core handler can detect
     * whether a middleware replaced it.
     *
     * @param  array<string, mixed>  $guzzleOptions
     * @return array{0: RequestInterface, 1: string|null}
     */
    protected function buildMiddlewareRequest(string $method, string $uri, array $guzzleOptions): array
    {
        return $this->requestBuilder->buildMiddlewareRequest($method, $uri, $guzzleOptions);
    }

    /** @param array<string, mixed> $options */
    protected function withQueryOptions(RequestInterface $request, array $options): RequestInterface
    {
        return $this->requestBuilder->withQueryOptions($request, $options);
    }

    protected function requireFetchResponse(mixed $response): ResponseInterface
    {
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        if ($response instanceof \Psr\Http\Message\ResponseInterface) {
            return Response::createFromBase($response);
        }

        throw new RuntimeException('The request pipeline did not return an HTTP response.');
    }

    /**
     * Serialize the outgoing body to a string for middleware inspection.
     *
     * Returns null when there is no representable body (e.g. multipart uploads,
     * which Guzzle streams itself, or no body at all).
     *
     * @param  array<string, mixed>  $guzzleOptions
     */
    protected function encodeSeedBody(array $guzzleOptions): ?string
    {
        return $this->requestBuilder->encodeSeedBody($guzzleOptions);
    }

    /**
     * Fold any middleware changes to the request back into the Guzzle options.
     *
     * Headers from the (possibly modified) request replace the outgoing header
     * set. If a middleware replaced the body, it is sent verbatim and the
     * structured body keys are dropped so they do not conflict.
     *
     * @param  array<string, mixed>  $guzzleOptions
     * @return array<string, mixed>
     */
    protected function mergeRequestIntoOptions(RequestInterface $request, array $guzzleOptions, ?string $seedBody): array
    {
        return $this->requestBuilder->mergeMiddlewareRequest($request, $guzzleOptions, $seedBody);
    }

    /**
     * Execute a synchronous streaming request and wrap the raw PSR-7 response.
     *
     * @param  array<string, mixed>  $options  Additional options
     */
    protected function sendStreamingRequest(
        Method|string $method,
        string $uri,
        array $options = [],
    ): StreamedResponseInterface {
        $methodStr = $method instanceof Method ? $method->value : strtoupper($method);

        // Streaming is inherently synchronous: force async off for this path.
        $requestOptions = RequestOptions::merge(
            $this->options,
            $options,
            ['method' => $methodStr, 'uri' => $uri, 'async' => false, 'stream' => true],
        );

        RequestOptions::validate($requestOptions);

        $context = RequestContext::fromOptions($requestOptions);
        $fullUri = $this->buildFullUriFromContext($context);
        if ($this->getValidationPolicy($context)->isEnabled()) {
            throw new ValidationConfigurationException(
                'Streaming and SSE responses cannot be schema-validated without buffering; disable validation for this request.'
            );
        }

        $guzzleOptions = $context->toGuzzleOptions();
        $guzzleOptions['stream'] = true;

        $requestId = $this->startProfiling($methodStr, $fullUri);

        $this->logRequest($methodStr, $fullUri, $guzzleOptions);

        try {
            $this->recordProfilingEvent($requestId, 'request_sent');

            $streamedResponse = (new StreamingRequestExecutor($this->getHttpClient()))->execute(
                $methodStr,
                $fullUri,
                $guzzleOptions,
            );

            $this->recordProfilingEvent($requestId, 'response_start');
            $this->endProfiling($requestId, $streamedResponse->getStatusCode());

            return $streamedResponse;
        } catch (\Throwable $e) {
            throw $this->withErrorContext($e, $methodStr, $fullUri);
        }
    }

    /**
     * Apply body-related options without mutating handler state.
     *
     * This is a pure function that takes options and body config, and returns
     * a new options array with body configuration applied. It does not mutate
     * any handler properties, making it safe for concurrent usage.
     *
     * @param  array<string, mixed>  $options  Base options array
     * @param  mixed  $body  The request body
     * @param  ContentType|string  $contentType  The content type
     * @return array<string, mixed> New options array with body applied
     */
    protected function applyBodyOptions(array $options, mixed $body, ContentType|string $contentType): array
    {
        return $this->requestBuilder->applyBodyOptions($options, $body, $contentType);
    }

    /**
     * Send an HTTP request with a body.
     *
     * @param  Method|string  $method  The HTTP method
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  The request body
     * @param  ContentType|string  $contentType  The content type
     * @param  array<string, mixed>  $options  Additional options
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    protected function sendRequestWithBody(
        Method|string $method,
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = ContentType::JSON,
        array $options = [],
    ): ResponseInterface|PromiseInterface {
        // Skip if no body
        if ($body === null) {
            return $this->sendRequest($method, $uri, $options);
        }

        $mergedOptions = RequestOptions::merge($this->options, $options);
        $bodyOptions = $this->applyBodyOptions($mergedOptions, $body, $contentType);

        return $this->sendRequest($method, $uri, $bodyOptions);
    }

    /**
     * Prepare options for Guzzle using the request context.
     *
     * This method now prefers the explicit RequestContext parameter for stateless
     * operation. When context is not provided, it falls back to building a context
     * from handler options (for backwards compatibility).
     *
     * @param  RequestContext|null  $context  Optional explicit context (preferred)
     * @return array<string, mixed>
     */
    protected function prepareGuzzleOptions(?RequestContext $context = null): array
    {
        return $this->requestBuilder->prepareGuzzleOptions($this->options, $context);
    }

    /**
     * Add request context to exceptions while preserving the original chain.
     */
    protected function withErrorContext(\Throwable $e, string $method, string $uri): \Throwable
    {
        if ($e instanceof AsyncTransportException || $e instanceof ValidationException || $e instanceof \Matrix\Exceptions\CancellationException) {
            return $e;
        }

        $contextMessage = sprintf('Request %s %s failed', strtoupper($method), $uri);

        // Avoid double-wrapping if the exception already contains context
        if (str_contains($e->getMessage(), $contextMessage)) {
            return $e;
        }

        return new RuntimeException($contextMessage.': '.$e->getMessage(), (int) $e->getCode(), $e);
    }
}
