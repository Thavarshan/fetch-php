<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Http\Request;
use Fetch\Interfaces\Response as ResponseInterface;
use Fetch\Testing\MockServer;
use Fetch\Testing\Recorder;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Utils;

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
        // Create a PSR-7 request for the mock server
        $psrRequest = $this->createPsrRequest($method, $uri, $options);

        // Wrap it in our Request class
        $request = Request::createFromBase($psrRequest);

        // Check if MockServer has a response for this request
        try {
            $mockResponse = MockServer::getInstance()->handleRequest($request);

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
    protected function createPsrRequest(string $method, string $uri, array $options): \Psr\Http\Message\RequestInterface
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
            $body = Utils::streamFor($options['body']);
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
}
