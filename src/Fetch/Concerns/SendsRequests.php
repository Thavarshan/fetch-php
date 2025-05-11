<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Http\Request;
use Fetch\Http\Response;
use Fetch\Interfaces\Response as ResponseInterface;
use GuzzleHttp\Client as SyncClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use React\Promise\PromiseInterface;
use RuntimeException;

use function async;

trait SendsRequests
{
    /**
     * Apply options and execute the request.
     *
     * @param  string  $method  The HTTP method to use
     * @param  string  $uri  The URI to request
     * @param  array<string, mixed>  $options  The request options
     * @return ResponseInterface|PromiseInterface The response or promise
     *
     * @throws RuntimeException If the request fails
     */
    public static function handle(
        string $method,
        string $uri,
        array $options = []
    ): ResponseInterface|PromiseInterface {
        $handler = new static;
        $handler->applyOptions($options);

        // Create a Request object and send it
        $request = $handler->createRequest($method, $uri);

        return $handler->sendRequest($request);
    }

    /**
     * Make a request with any HTTP method.
     *
     * @param  string  $method  The HTTP method to use
     * @param  string  $uri  The URI to request
     * @param  mixed  $body  Optional request body
     * @param  string  $contentType  The content type of the request
     * @param  array<string, mixed>  $options  Additional request options
     * @return ResponseInterface|PromiseInterface The response or promise
     *
     * @throws RuntimeException If the request fails
     */
    public function request(
        string $method,
        string $uri,
        mixed $body = null,
        ContentType|string $contentType = ContentType::JSON->value,
        array $options = []
    ): ResponseInterface|PromiseInterface {
        // Apply any additional options
        if (! empty($options)) {
            $this->withOptions($options);
        }

        // Normalize method to uppercase
        $method = strtoupper($method);

        // Try to convert to enum to validate
        try {
            $methodEnum = Method::fromString($method);
            $method = $methodEnum->value;
        } catch (\ValueError $e) {
            throw new InvalidArgumentException("Invalid HTTP method: {$method}");
        }

        // Create a base request
        $request = $this->createRequest($method, $uri);

        // Configure request body for methods that accept one
        if ($methodEnum->supportsRequestBody() && $body !== null) {
            $request = $this->configureRequestBody($request, $body, $contentType);
        }

        // Apply any additional options to the request
        $request = $this->applyOptionsToRequest($request);

        // Send the request
        return $this->sendRequest($request);
    }

    /**
     * Get the synchronous HTTP client.
     *
     * @return ClientInterface The HTTP client
     */
    public function getSyncClient(): ClientInterface
    {
        if (! $this->syncClient) {
            $this->syncClient = new SyncClient([
                RequestOptions::CONNECT_TIMEOUT => $this->options['timeout'] ?? self::DEFAULT_TIMEOUT,
                RequestOptions::HTTP_ERRORS => false, // We'll handle HTTP errors ourselves
            ]);
        }

        return $this->syncClient;
    }

    /**
     * Get the raw prepared options array ready for Guzzle.
     *
     * @return array<string, mixed> The prepared options
     */
    public function getPreparedOptions(): array
    {
        return $this->preparedOptions ?? $this->options;
    }

    /**
     * Apply the options to the handler.
     *
     * @param  array<string, mixed>  $options  The request options
     */
    protected function applyOptions(array $options): void
    {
        // Extract and set client if provided
        if (isset($options['client'])) {
            if (! ($options['client'] instanceof ClientInterface)) {
                throw new InvalidArgumentException('Client must be an instance of GuzzleHttp\ClientInterface');
            }
            $this->setSyncClient($options['client']);
            unset($options['client']); // Remove client from options to avoid conflicts
        }

        // Merge options
        $this->options = array_merge($this->options, $options);

        // Set specific properties
        $this->timeout = $options['timeout'] ?? $this->timeout;
        $this->retries = $options['retries'] ?? $this->retries;
        $this->retryDelay = $options['retry_delay'] ?? $this->retryDelay;
        $this->isAsync = ! empty($options['async']);

        // Handle base URI
        if (isset($options['base_uri'])) {
            $this->baseUri($options['base_uri']);
            // Keep base_uri in options for Guzzle's constructor
        }
    }

    /**
     * Create a Request object with the specified method and URI.
     */
    protected function createRequest(string $method, string $uri): Request
    {
        // Create a basic request with the method and URI
        return new Request($method, $uri);
    }

    /**
     * Configure the body for a request.
     */
    protected function configureRequestBody(Request $request, mixed $body, ContentType|string $contentType): Request
    {
        // Convert string content type to enum if necessary
        if (is_string($contentType)) {
            try {
                $contentType = ContentType::tryFromString($contentType, ContentType::JSON);
            } catch (\ValueError $e) {
                // If it's not a valid enum value, keep it as a string
            }
        }

        // Handle different body types based on content type
        if (is_array($body)) {
            if ($contentType === ContentType::JSON) {
                // Use the JSON body method
                return $request->withJsonBody($body);
            } elseif ($contentType === ContentType::FORM_URLENCODED) {
                // Use the form body method
                return $request->withFormBody($body);
            } else {
                // For any other content type, serialize the array to JSON
                $json = json_encode($body);
                if ($json === false) {
                    throw new InvalidArgumentException('Failed to encode array body as JSON');
                }

                $contentTypeValue = $contentType instanceof ContentType ? $contentType->value : $contentType;

                return $request->withBody($json)->withContentType($contentTypeValue);
            }
        } else {
            // For string bodies
            $contentTypeValue = $contentType instanceof ContentType ? $contentType->value : $contentType;

            return $request->withBody($body)->withContentType($contentTypeValue);
        }
    }

    /**
     * Apply the current options to a Request object.
     */
    protected function applyOptionsToRequest(Request $request): Request
    {
        // Apply headers from options
        if (isset($this->options['headers']) && is_array($this->options['headers'])) {
            foreach ($this->options['headers'] as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
        }

        // Apply query parameters
        if (isset($this->options['query']) && is_array($this->options['query'])) {
            foreach ($this->options['query'] as $name => $value) {
                $request = $request->withQueryParam($name, $value);
            }
        }

        // Apply basic auth if set
        if (isset($this->options['auth']) && is_array($this->options['auth']) && count($this->options['auth']) >= 2) {
            $request = $request->withBasicAuth($this->options['auth'][0], $this->options['auth'][1]);
        }

        // Apply bearer token if set
        if (isset($this->options['token'])) {
            $request = $request->withBearerToken($this->options['token']);
        }

        // Apply protocol version if specified
        if (isset($this->options['protocol_version'])) {
            $request = $request->withProtocolVersion($this->options['protocol_version']);
        }

        return $request;
    }

    /**
     * Extract options from a Request object to prepare for Guzzle.
     */
    protected function extractOptionsFromRequest(RequestInterface $request): array
    {
        $options = [];

        // Add headers
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        if (! empty($headers)) {
            $options['headers'] = $headers;
        }

        // Add body if present
        $body = (string) $request->getBody();
        if (! empty($body)) {
            $options['body'] = $body;
        }

        // Add our custom options
        $options = array_merge($options, $this->getCustomOptions());

        return $options;
    }

    /**
     * Get the custom options that are not part of the Request object.
     */
    protected function getCustomOptions(): array
    {
        $customOptions = [];

        // Add timeout
        if (isset($this->timeout)) {
            $customOptions['timeout'] = $this->timeout;
        } else {
            $customOptions['timeout'] = $this->options['timeout'] ?? self::DEFAULT_TIMEOUT;
        }

        if (array_key_exists('verify', $this->options)) {
            $customOptions['verify'] = $this->options['verify'];
        }

        // Add other custom options...
        if (isset($this->options['proxy'])) {
            $customOptions['proxy'] = $this->options['proxy'];
        }

        if (isset($this->options['cookies'])) {
            $customOptions['cookies'] = $this->options['cookies'];
        }

        if (isset($this->options['allow_redirects'])) {
            $customOptions['allow_redirects'] = $this->options['allow_redirects'];
        }

        if (isset($this->options['cert'])) {
            $customOptions['cert'] = $this->options['cert'];
        }

        if (isset($this->options['ssl_key'])) {
            $customOptions['ssl_key'] = $this->options['ssl_key'];
        }

        if (isset($this->options['stream'])) {
            $customOptions['stream'] = $this->options['stream'];
        }

        return $customOptions;
    }

    /**
     * Send a request and return the response.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface|PromiseInterface
    {
        // Extract the necessary information for logging
        $method = $request->getMethod();
        $uri = (string) $request->getUri();

        // Prepare options for Guzzle
        $options = $this->extractOptionsFromRequest($request);

        // Store for future reference
        $this->preparedOptions = $options;

        // Send async or sync based on configuration
        return $this->isAsync ? $this->sendAsyncRequest($request) : $this->sendSyncRequest($request);
    }

    /**
     * Send a synchronous HTTP request.
     */
    protected function sendSyncRequest(RequestInterface $request): ResponseInterface
    {
        return $this->retryRequest(function () use ($request): ResponseInterface {
            try {
                $psrResponse = $this->getSyncClient()->send(
                    $request,
                    $this->preparedOptions ?? []
                );

                return Response::createFromBase($psrResponse);
            } catch (GuzzleException $e) {
                throw new RuntimeException(
                    sprintf(
                        'Request %s %s failed: %s',
                        $request->getMethod(),
                        (string) $request->getUri(),
                        $e->getMessage()
                    ),
                    $e->getCode(),
                    $e
                );
            }
        });
    }

    /**
     * Send an asynchronous HTTP request.
     */
    protected function sendAsyncRequest(RequestInterface $request): PromiseInterface
    {
        return async(function () use ($request): ResponseInterface {
            return $this->sendSyncRequest($request);
        });
    }
}
