<?php

use Fetch\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

if (! function_exists('fetch')) {
    /**
     * Performs an HTTP request using Guzzle and returns a structured response.
     *
     * @param string $url
     * @param array  $options
     *
     * @return \Fetch\Response
     */
    function fetch(string $url, array $options = []): Response
    {
        return make_request($url, $options, false);
    }
}

if (! function_exists('fetchAsync')) {
    /**
     * Asynchronous version of the fetch function.
     *
     * @param string $url
     * @param array  $options
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @deprecated version 1.1.0 Use fetch_async instead
     */
    function fetchAsync(string $url, array $options = []): PromiseInterface
    {
        // Emit a warning to notify about the deprecation
        trigger_error('fetchAsync is deprecated. Use fetch_async instead.', \E_USER_DEPRECATED);

        return make_request($url, $options, true);
    }
}

if (! function_exists('fetch_async')) {
    /**
     * Asynchronous version of the fetch function.
     *
     * @param string $url
     * @param array  $options
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    function fetch_async(string $url, array $options = []): PromiseInterface
    {
        return make_request($url, $options, true);
    }
}

if (! function_exists('make_request')) {
    /**
     * Helper function to perform HTTP requests using Guzzle.
     *
     * @param string $url
     * @param array  $options
     * @param bool   $async
     *
     * @return \GuzzleHttp\Promise\PromiseInterface|\Fetch\Response
     */
    function make_request(
        string $url,
        array $options,
        bool $async
    ): PromiseInterface|Response {
        // Store the Guzzle client as a static variable to retain its instance
        static $defaultClient;

        // Use provided client or the default one
        $client = $options['client'] ?? $defaultClient;

        // Initialize default client if none provided or not already initialized
        if (! $client) {
            $defaultClient = new Client([
                'base_uri' => $options['base_uri'] ?? null,
                'timeout' => $options['timeout'] ?? 0,
                'allow_redirects' => $options['allow_redirects'] ?? true,
                'cookies' => isset($options['cookies']) ? new CookieJar() : false,
                'verify' => $options['verify'] ?? true,
                'proxy' => $options['proxy'] ?? null,
            ]);

            $client = $defaultClient;
        }

        // Prepare request method and options
        $method = $options['method'] ?? 'GET';
        $headers = $options['headers'] ?? [];

        // Prepare the request body and update headers if necessary
        [$body, $headers] = prepare_body($options, $headers);

        $query = $options['query'] ?? [];

        // Build request options array
        $requestOptions = [
            'headers' => $headers,
            'body' => $body,
            'query' => $query,
            'auth' => $options['auth'] ?? null,
        ];

        // Handle async request
        if ($async) {
            return handle_async_request($client, $method, $url, $requestOptions);
        }

        // Handle synchronous request
        return handle_sync_request($client, $method, $url, $requestOptions);
    }
}

if (! function_exists('prepare_body')) {
    /**
     * Prepare the request body based on the given options.
     *
     * @param array $options
     * @param array $headers
     *
     * @return array Returns the body and updated headers
     */
    function prepare_body(array $options, array $headers): array
    {
        if (isset($options['multipart'])) {
            $body = new MultipartStream($options['multipart']);
            $headers['Content-Type'] = 'multipart/form-data';

            return [$body, $headers];
        }

        if (isset($options['json'])) {
            $body = json_encode($options['json']);
            $headers['Content-Type'] = 'application/json';

            return [$body, $headers];
        }

        return [$options['body'] ?? null, $headers];
    }
}

if (! function_exists('handle_async_request')) {
    /**
     * Handle an asynchronous HTTP request.
     *
     * @param \GuzzleHttp\Client $client
     * @param string             $method
     * @param string             $url
     * @param array              $requestOptions
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    function handle_async_request(
        Client $client,
        string $method,
        string $url,
        array $requestOptions
    ): PromiseInterface {
        return $client->requestAsync($method, $url, $requestOptions)->then(
            fn (ResponseInterface $response) => new Response($response),
            fn (RequestException $e) => handle_request_exception($e)
        );
    }
}

if (! function_exists('handle_sync_request')) {
    /**
     * Handle a synchronous HTTP request.
     *
     * @param \GuzzleHttp\Client $client
     * @param string             $method
     * @param string             $url
     * @param array              $requestOptions
     *
     * @return \Fetch\Response
     */
    function handle_sync_request(
        Client $client,
        string $method,
        string $url,
        array $requestOptions
    ): Response {
        try {
            $response = $client->request($method, $url, $requestOptions);

            return new Response($response);
        } catch (RequestException $e) {
            return handle_request_exception($e);
        }
    }
}

if (! function_exists('handle_request_exception')) {
    /**
     * Handle a request exception and return a mock response.
     *
     * @param \GuzzleHttp\Exception\RequestException $e
     *
     * @return \Fetch\Response
     */
    function handle_request_exception(RequestException $e): Response
    {
        $response = $e->getResponse();

        return $response ? new Response($response) : create_error_response($e);
    }
}

if (! function_exists('create_error_response')) {
    /**
     * Creates a mock response for error handling.
     *
     * @param \GuzzleHttp\Exception\RequestException $e
     *
     * @return \Fetch\Response
     */
    function create_error_response(RequestException $e): Response
    {
        $mockResponse = new GuzzleResponse(
            SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR,
            [],
            $e->getMessage()
        );

        return new Response($mockResponse);
    }
}
