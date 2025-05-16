<?php

declare(strict_types=1);

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Http\Client;
use Fetch\Interfaces\ClientHandler as ClientHandlerInterface;
use Fetch\Interfaces\Response as ResponseInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;

if (! function_exists('fetch')) {
    /**
     * Perform an HTTP request similar to JavaScript's fetch API.
     *
     * @param  string|RequestInterface|null  $resource  URL to fetch or a pre-configured Request object
     * @param  array<string, mixed>|null  $options  Request options including:
     *                                              - method: HTTP method (string|Method enum)
     *                                              - headers: Request headers (array)
     *                                              - body: Request body (mixed)
     *                                              - json: JSON data to send as body (array, takes precedence over body)
     *                                              - form: Form data to send as body (array, takes precedence if no json)
     *                                              - multipart: Multipart form data (array, takes precedence if no json/form)
     *                                              - query: Query parameters (array)
     *                                              - base_uri: Base URI (string)
     *                                              - timeout: Request timeout in seconds (int)
     *                                              - retries: Number of retries (int)
     *                                              - auth: Basic auth credentials [username, password] (array)
     *                                              - token: Bearer token (string)
     * @return ResponseInterface|ClientHandlerInterface|Client Response or handler for method chaining
     *
     * @throws ClientExceptionInterface If a client exception occurs
     */
    function fetch(string|RequestInterface|null $resource = null, ?array $options = []): ResponseInterface|ClientHandlerInterface|Client
    {
        $options = $options ?? [];

        // If a Request object is provided, we can't use options with it
        if ($resource instanceof RequestInterface) {
            return fetch_client()->sendRequest($resource);
        }

        // If no resource is provided, return the client handler for chaining
        if ($resource === null) {
            return fetch_client();
        }

        // Process fetch-style options
        $processedOptions = process_request_options($options);

        // Handle base URI if provided
        if (isset($options['base_uri'])) {
            return handle_request_with_base_uri($resource, $options, $processedOptions);
        }

        // No base URI, use direct fetch with options
        return fetch_client()->fetch($resource, $processedOptions);
    }
}

if (! function_exists('process_request_options')) {
    /**
     * Process and normalize request options.
     *
     * @param  array<string, mixed>  $options  Raw options
     * @return array<string, mixed> Processed options
     */
    function process_request_options(array $options): array
    {
        $processedOptions = [];

        // Method (default to GET)
        $method = $options['method'] ?? Method::GET;
        $methodValue = $method instanceof Method ? $method->value : (string) $method;
        $processedOptions['method'] = $methodValue;

        // Headers
        if (isset($options['headers'])) {
            $processedOptions['headers'] = $options['headers'];
        }

        // Content type and body handling
        [$body, $contentType] = extract_body_and_content_type($options);

        if ($body !== null) {
            $processedOptions['body'] = $body;
            if ($contentType !== null) {
                $processedOptions['content_type'] = $contentType;
            }
        }

        // Query parameters
        if (isset($options['query'])) {
            $processedOptions['query'] = $options['query'];
        }

        // Copy other direct pass options
        $directPassOptions = [
            'timeout', 'retries', 'auth', 'token',
            'proxy', 'cookies', 'allow_redirects', 'cert', 'ssl_key', 'stream',
        ];

        foreach ($directPassOptions as $opt) {
            if (isset($options[$opt])) {
                $processedOptions[$opt] = $options[$opt];
            }
        }

        return $processedOptions;
    }
}

if (! function_exists('extract_body_and_content_type')) {
    /**
     * Extract body and content type from options.
     *
     * @param  array<string, mixed>  $options  Request options
     * @return array{0: mixed, 1: ?ContentType|string} Tuple of [body, contentType]
     */
    function extract_body_and_content_type(array $options): array
    {
        $body = null;
        $contentType = null;

        // Body handling - json takes precedence, then form, then multipart, then raw body
        if (isset($options['json'])) {
            $body = $options['json'];
            $contentType = ContentType::JSON;
        } elseif (isset($options['form'])) {
            $body = $options['form'];
            $contentType = ContentType::FORM_URLENCODED;
        } elseif (isset($options['multipart'])) {
            $body = $options['multipart'];
            $contentType = ContentType::MULTIPART;
        } elseif (isset($options['body'])) {
            $body = $options['body'];
            // Use specified content type or default to JSON for arrays
            $rawContentType = $options['content_type'] ?? (is_array($options['body']) ? ContentType::JSON : null);
            $contentType = $rawContentType !== null ? ContentType::normalizeContentType($rawContentType) : null;
        }

        return [$body, $contentType];
    }
}

if (! function_exists('handle_request_with_base_uri')) {
    /**
     * Handle a request with a base URI.
     *
     * @param  string  $resource  URL to fetch
     * @param  array<string, mixed>  $options  Original options
     * @param  array<string, mixed>  $processedOptions  Processed options
     * @return ResponseInterface The response
     */
    function handle_request_with_base_uri(string $resource, array $options, array $processedOptions): ResponseInterface
    {
        $client = fetch_client();
        $handler = $client->getHandler();
        $handler->baseUri($options['base_uri']);
        $handler->withOptions($processedOptions);

        // Extract body and content type if not already processed
        [$body, $contentType] = extract_body_and_content_type($options);

        if ($body !== null) {
            if ($contentType !== null) {
                $handler->withBody($body, $contentType);
            } else {
                $handler->withBody($body);
            }
        }

        return $handler->sendRequest($processedOptions['method'], $resource);
    }
}

if (! function_exists('fetch_client')) {
    /**
     * Get or configure the global fetch client instance.
     *
     * @param  array<string, mixed>|null  $options  Global client options
     * @param  bool  $reset  Whether to reset the client instance
     * @return Client The client instance
     *
     * @throws RuntimeException If client creation or configuration fails
     */
    function fetch_client(?array $options = null, bool $reset = false): Client
    {
        static $client = null;

        try {
            // Create a new client or reset the existing one
            if ($client === null || $reset) {
                $client = new Client(options: $options ?? []);
            }
            // Apply new options to the existing client if provided
            elseif ($options !== null) {
                // Get the existing handler
                $handler = $client->getHandler();

                // Only clone and apply options if there are options to apply
                if (! empty($options)) {
                    try {
                        $handler = $handler->withClonedOptions($options);
                    } catch (Throwable $e) {
                        // More specific error message for options application failures
                        throw new RuntimeException(
                            sprintf('Failed to apply options to client: %s', $e->getMessage()),
                            0,
                            $e
                        );
                    }
                }

                // Create a new client with the modified handler
                $client = new Client(handler: $handler);
            }

            return $client;
        } catch (Throwable $e) {
            // If it's already a RuntimeException from our code, re-throw it
            if ($e instanceof RuntimeException && $e->getPrevious() !== null) {
                throw $e;
            }

            // Otherwise, wrap the exception with more context
            throw new RuntimeException(
                sprintf('Error configuring fetch client: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }
}

if (! function_exists('request_method')) {
    /**
     * Common helper for making HTTP requests with various methods.
     *
     * @param  string  $method  HTTP method (GET, POST, etc.)
     * @param  string  $url  URL to fetch
     * @param  mixed  $data  Request data (for body or query parameters)
     * @param  array<string, mixed>|null  $options  Additional request options
     * @param  bool  $dataIsQuery  Whether data is used as query parameters (true) or request body (false)
     * @return ResponseInterface The response
     *
     * @throws ClientExceptionInterface If a client exception occurs
     */
    function request_method(string $method, string $url, mixed $data = null, ?array $options = [], bool $dataIsQuery = false): ResponseInterface
    {
        $options = $options ?? [];
        $options['method'] = $method;

        if ($data !== null) {
            if ($dataIsQuery) {
                $options['query'] = $data;
            } elseif (is_array($data)) {
                $options['json'] = $data; // Treat arrays as JSON by default
            } else {
                $options['body'] = $data;
            }
        }

        return fetch($url, $options);
    }
}

// We'll keep these convenience functions for PHP developers who prefer a more traditional API
if (! function_exists('get')) {
    /**
     * Perform a GET request.
     *
     * @param  string  $url  URL to fetch
     * @param  array<string, mixed>|null  $query  Query parameters
     * @param  array<string, mixed>|null  $options  Additional request options
     * @return ResponseInterface The response
     *
     * @throws ClientExceptionInterface If a client exception occurs
     */
    function get(string $url, ?array $query = null, ?array $options = []): ResponseInterface
    {
        return request_method('GET', $url, $query, $options, true);
    }
}

if (! function_exists('post')) {
    /**
     * Perform a POST request.
     *
     * @param  string  $url  URL to fetch
     * @param  mixed  $data  Request body or JSON data
     * @param  array<string, mixed>|null  $options  Additional request options
     * @return ResponseInterface The response
     *
     * @throws ClientExceptionInterface If a client exception occurs
     */
    function post(string $url, mixed $data = null, ?array $options = []): ResponseInterface
    {
        return request_method('POST', $url, $data, $options);
    }
}

if (! function_exists('put')) {
    /**
     * Perform a PUT request.
     *
     * @param  string  $url  URL to fetch
     * @param  mixed  $data  Request body or JSON data
     * @param  array<string, mixed>|null  $options  Additional request options
     * @return ResponseInterface The response
     *
     * @throws ClientExceptionInterface If a client exception occurs
     */
    function put(string $url, mixed $data = null, ?array $options = []): ResponseInterface
    {
        return request_method('PUT', $url, $data, $options);
    }
}

if (! function_exists('patch')) {
    /**
     * Perform a PATCH request.
     *
     * @param  string  $url  URL to fetch
     * @param  mixed  $data  Request body or JSON data
     * @param  array<string, mixed>|null  $options  Additional request options
     * @return ResponseInterface The response
     *
     * @throws ClientExceptionInterface If a client exception occurs
     */
    function patch(string $url, mixed $data = null, ?array $options = []): ResponseInterface
    {
        return request_method('PATCH', $url, $data, $options);
    }
}

if (! function_exists('delete')) {
    /**
     * Perform a DELETE request.
     *
     * @param  string  $url  URL to fetch
     * @param  mixed  $data  Request body or JSON data
     * @param  array<string, mixed>|null  $options  Additional request options
     * @return ResponseInterface The response
     *
     * @throws ClientExceptionInterface If a client exception occurs
     */
    function delete(string $url, mixed $data = null, ?array $options = []): ResponseInterface
    {
        return request_method('DELETE', $url, $data, $options);
    }
}
