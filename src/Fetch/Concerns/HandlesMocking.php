<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Http\Request;
use Fetch\Interfaces\Response as ResponseInterface;
use Fetch\Testing\MockServer;
use Fetch\Testing\Recorder;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

trait HandlesMocking
{
    /**
     * Check if a request should be mocked and return the mock response if applicable.
     *
     * @param  string  $method  The HTTP method
     * @param  string  $uri  The full URI
     * @param  array<string, mixed>  $options  The request options
     * @return ResponseInterface|null The mock response or null if not mocked
     */
    protected function handleMockRequest(string $method, string $uri, array $options): ?ResponseInterface
    {
        $mockServer = MockServer::getInstance();
        if (! $mockServer->isActive()) {
            return null;
        }

        // Create a PSR-7 request for the mock server
        $psrRequest = $this->createPsrRequest($method, $uri, $options);

        // Wrap it in our Request class
        $request = Request::createFromBase($psrRequest);

        // Check if MockServer has a response for this request
        try {
            $mockResponse = $mockServer->handleRequest($request);

            if ($mockResponse !== null) {
                // Record the request/response if recording is enabled
                if (Recorder::isRecording()) {
                    Recorder::record($request, $mockResponse);
                }

                return $mockResponse;
            }
        } catch (\InvalidArgumentException $e) {
            // Re-throw if it's a stray request prevention error
            throw $e;
        }

        return null;
    }

    /**
     * Create a PSR-7 request from the given parameters.
     *
     * @param  string  $method  The HTTP method
     * @param  string  $uri  The full URI
     * @param  array<string, mixed>  $options  The request options
     */
    protected function createPsrRequest(string $method, string $uri, array $options): RequestInterface
    {
        $headers = $options['headers'] ?? [];
        $body = null;

        // Handle different body types
        if (isset($options['json'])) {
            $body = Utils::streamFor(json_encode($options['json']));
            $headers['Content-Type'] = 'application/json';
        } elseif (isset($options['form_params'])) {
            $body = Utils::streamFor(http_build_query($options['form_params']));
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        } elseif (isset($options['body'])) {
            $body = Utils::streamFor($this->copyBodyForMocking($options['body']));
        } elseif (isset($options['multipart'])) {
            // For multipart, we'll create a simple representation
            // In a real scenario, Guzzle handles the complex multipart encoding
            $body = Utils::streamFor(''); // Empty for now, as multipart is complex
        }

        // Append query parameters to URI if present
        if (isset($options['query']) && is_array($options['query'])) {
            $separator = strpos($uri, '?') !== false ? '&' : '?';
            $uri .= $separator.http_build_query($options['query']);
        }

        return new GuzzleRequest($method, $uri, $headers, $body);
    }

    private function copyBodyForMocking(mixed $body): mixed
    {
        if ($body instanceof StreamInterface) {
            $seekable = $body->isSeekable();
            $position = $seekable ? $body->tell() : null;
            try {
                return $body->getContents();
            } finally {
                if ($seekable && $position !== null) {
                    $body->seek($position);
                }
            }
        }

        if (! is_resource($body)) {
            return $body;
        }

        $metadata = stream_get_meta_data($body);
        $seekable = $metadata['seekable'];
        $position = $seekable ? ftell($body) : false;
        $contents = stream_get_contents($body);
        if ($seekable && $position !== false && fseek($body, $position) !== 0) {
            throw new RuntimeException('Unable to restore the request body stream position for mock matching.');
        }
        if ($contents === false) {
            throw new RuntimeException('Unable to read the request body stream for mock matching.');
        }

        return $contents;
    }
}
