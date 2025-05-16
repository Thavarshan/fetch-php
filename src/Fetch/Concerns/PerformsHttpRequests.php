<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Interfaces\Response as ResponseInterface;
use React\Promise\PromiseInterface;

trait PerformsHttpRequests
{
    /**
     * Finalize and send a HEAD request.
     */
    public function head(string $uri): ResponseInterface|PromiseInterface
    {
        return $this->finalizeRequest(Method::HEAD->value, $uri);
    }

    /**
     * Finalize and send a GET request.
     *
     * @param  string  $uri  The URI to request
     * @param  array<string, mixed>  $queryParams  Optional query parameters
     */
    public function get(string $uri, array $queryParams = []): ResponseInterface|PromiseInterface
    {
        if (! empty($queryParams)) {
            $this->withQueryParameters($queryParams);
        }

        return $this->finalizeRequest(Method::GET->value, $uri);
    }

    /**
     * Finalize and send a POST request.
     *
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  The request body
     * @param  string  $contentType  The content type of the request
     */
    public function post(
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = 'application/json'
    ): ResponseInterface|PromiseInterface {
        if ($body !== null) {
            $this->configurePostableRequest($body, $contentType);
        }

        return $this->finalizeRequest(Method::POST->value, $uri);
    }

    /**
     * Finalize and send a PATCH request.
     *
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  The request body
     * @param  string  $contentType  The content type of the request
     */
    public function patch(
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = 'application/json'
    ): ResponseInterface|PromiseInterface {
        if ($body !== null) {
            $this->configurePostableRequest($body, $contentType);
        }

        return $this->finalizeRequest(Method::PATCH->value, $uri);
    }

    /**
     * Finalize and send a PUT request.
     *
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  The request body
     * @param  string  $contentType  The content type of the request
     */
    public function put(
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = 'application/json'
    ): ResponseInterface|PromiseInterface {
        if ($body !== null) {
            $this->configurePostableRequest($body, $contentType);
        }

        return $this->finalizeRequest(Method::PUT->value, $uri);
    }

    /**
     * Finalize and send a DELETE request.
     *
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  Optional request body
     * @param  string  $contentType  The content type of the request
     */
    public function delete(
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = 'application/json'
    ): ResponseInterface|PromiseInterface {
        if ($body !== null) {
            $this->configurePostableRequest($body, $contentType);
        }

        return $this->finalizeRequest(Method::DELETE->value, $uri);
    }

    /**
     * Finalize and send an OPTIONS request.
     *
     * @param  string  $uri  The URI to request
     */
    public function options(string $uri): ResponseInterface|PromiseInterface
    {
        return $this->finalizeRequest(Method::OPTIONS->value, $uri);
    }

    /**
     * Finalize and send a request with the specified method and URI.
     *
     * @param  string  $method  The HTTP method to use
     * @param  string  $uri  The URI to request
     * @return ResponseInterface|PromiseInterface The response or promise
     */
    protected function finalizeRequest(string $method, string $uri): ResponseInterface|PromiseInterface
    {
        // Create a local copy of the options
        $options = $this->options;

        // Set the method in the local options
        $options['method'] = $method;

        // Create a request object
        $request = $this->createRequest($method, $uri);

        // Apply any configured options to the request
        $request = $this->applyOptionsToRequest($request, $options);

        // Send the request
        return $this->sendRequest($request);
    }
}
