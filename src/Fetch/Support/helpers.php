<?php

declare(strict_types=1);

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Http\Client;
use Fetch\Http\ClientHandler;
use Fetch\Http\Request;
use Fetch\Interfaces\Response as ResponseInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

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
     * @return ResponseInterface|ClientHandler Response or handler for method chaining
     *
     * @throws ClientExceptionInterface If a client exception occurs
     */
    function fetch(string|RequestInterface|null $resource = null, ?array $options = []): ResponseInterface|ClientHandler|Client
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
        $processedOptions = [];

        // Method (default to GET)
        $method = $options['method'] ?? Method::GET;
        $processedOptions['method'] = $method instanceof Method ? $method->value : (string) $method;

        // Headers
        if (isset($options['headers'])) {
            $processedOptions['headers'] = $options['headers'];
        }

        // Content type handling
        $contentType = null;

        // Body handling - json takes precedence, then form, then multipart, then raw body
        if (isset($options['json'])) {
            $processedOptions['body'] = $options['json'];
            $contentType = ContentType::JSON;
        } elseif (isset($options['form'])) {
            $processedOptions['body'] = $options['form'];
            $contentType = ContentType::FORM_URLENCODED;
        } elseif (isset($options['multipart'])) {
            $processedOptions['body'] = $options['multipart'];
            $contentType = ContentType::MULTIPART;
        } elseif (isset($options['body'])) {
            $processedOptions['body'] = $options['body'];
            // Use specified content type or default to JSON for arrays
            $contentType = $options['content_type'] ?? (is_array($options['body']) ? ContentType::JSON : null);
        }

        // Set content type if determined
        if ($contentType !== null) {
            $contentTypeValue = $contentType instanceof ContentType ? $contentType->value : $contentType;
            $processedOptions['content_type'] = $contentTypeValue;

            // Set Content-Type header if not already set
            if (! isset($processedOptions['headers']['Content-Type'])) {
                $processedOptions['headers']['Content-Type'] = $contentTypeValue;
            }
        }

        // Query parameters
        if (isset($options['query'])) {
            $processedOptions['query'] = $options['query'];
        }

        // Other options
        $directPassOptions = [
            'base_uri', 'timeout', 'retries', 'auth', 'token',
            'proxy', 'cookies', 'allow_redirects', 'cert', 'ssl_key', 'stream',
        ];

        foreach ($directPassOptions as $opt) {
            if (isset($options[$opt])) {
                $processedOptions[$opt] = $options[$opt];
            }
        }

        // Send the request
        return fetch_client()->fetch($resource, $processedOptions);
    }
}

if (! function_exists('fetch_client')) {
    /**
     * Get or configure the global fetch client instance.
     *
     * @param  array<string, mixed>|null  $options  Global client options
     * @param  LoggerInterface|null  $logger  PSR-3 compatible logger
     * @param  bool  $reset  Whether to reset the client instance
     * @return Client The client instance
     */
    function fetch_client(?array $options = null, ?LoggerInterface $logger = null, bool $reset = false): Client
    {
        static $client = null;

        if ($client === null || $reset) {
            $client = new Client(options: $options ?? [], logger: $logger);
        } elseif ($options !== null || $logger !== null) {
            // Apply new options and/or logger to the existing client if provided
            if ($options !== null) {
                $client = new Client(
                    handler: $client->getHandler()->withOptions($options),
                    logger: $logger ?? ($client->hasLogger() ? $client->getLogger() : null)
                );
            } elseif ($logger !== null && method_exists($client, 'setLogger')) {
                $client->setLogger($logger);
            }
        }

        return $client;
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
        $options = $options ?? [];
        $options['method'] = 'GET';

        if ($query !== null) {
            $options['query'] = $query;
        }

        return fetch($url, $options);
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
        $options = $options ?? [];
        $options['method'] = 'POST';

        // Automatically handle the data appropriately
        if ($data !== null) {
            if (is_array($data)) {
                $options['json'] = $data; // Treat arrays as JSON by default
            } else {
                $options['body'] = $data;
            }
        }

        return fetch($url, $options);
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
        $options = $options ?? [];
        $options['method'] = 'PUT';

        // Automatically handle the data appropriately
        if ($data !== null) {
            if (is_array($data)) {
                $options['json'] = $data; // Treat arrays as JSON by default
            } else {
                $options['body'] = $data;
            }
        }

        return fetch($url, $options);
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
        $options = $options ?? [];
        $options['method'] = 'PATCH';

        // Automatically handle the data appropriately
        if ($data !== null) {
            if (is_array($data)) {
                $options['json'] = $data; // Treat arrays as JSON by default
            } else {
                $options['body'] = $data;
            }
        }

        return fetch($url, $options);
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
        $options = $options ?? [];
        $options['method'] = 'DELETE';

        // Automatically handle the data appropriately
        if ($data !== null) {
            if (is_array($data)) {
                $options['json'] = $data; // Treat arrays as JSON by default
            } else {
                $options['body'] = $data;
            }
        }

        return fetch($url, $options);
    }
}
