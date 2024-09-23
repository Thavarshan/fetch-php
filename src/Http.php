<?php

namespace Fetch;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Http
{
    /**
     * Singleton instance of the Http class.
     *
     * @var \Fetch\Http|null
     */
    protected static ?Http $instance = null;

    /**
     * The Guzzle client instance.
     *
     * @var \GuzzleHttp\Client|null
     */
    protected ?Client $client = null;

    /**
     * Get the singleton instance of the Http class.
     *
     * @return \Fetch\Http
     */
    public static function getInstance(): Http
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Reset the singleton instance of the Http class.
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Set the Guzzle client instance.
     *
     * @param \GuzzleHttp\Client $client
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * Get the Guzzle client instance.
     *
     * @return \GuzzleHttp\Client|null
     */
    public function getClient(): ?Client
    {
        return $this->client;
    }

    /**
     * Perform an HTTP request using Guzzle.
     *
     * @param string $url
     * @param array  $options
     * @param bool   $async
     *
     * @return \GuzzleHttp\Promise\PromiseInterface|\Fetch\Response
     */
    public function makeRequest(
        string $url,
        array $options = [],
        bool $async = false
    ): PromiseInterface|Response {
        // Ensure the Guzzle client is created only once
        if ($this->client === null) {
            $this->client = $this->createClient($options);
        }

        // Prepare the request options
        $requestOptions = $this->prepareRequestOptions($options);

        // Handle async or sync request
        return $async
            ? $this->handleAsyncRequest($this->client, $options['method'] ?? 'GET', $url, $requestOptions)
            : $this->handleSyncRequest($this->client, $options['method'] ?? 'GET', $url, $requestOptions);
    }

    /**
     * Create a Guzzle client.
     *
     * @param array $options
     *
     * @return \GuzzleHttp\Client
     */
    protected function createClient(array $options): Client
    {
        return $options['client'] ?? new Client([
            'base_uri' => $options['base_uri'] ?? null,
            'timeout' => $options['timeout'] ?? 0,
            'allow_redirects' => $options['allow_redirects'] ?? true,
            'cookies' => isset($options['cookies']) ? new CookieJar() : false,
            'verify' => $options['verify'] ?? true,
            'proxy' => $options['proxy'] ?? null,
        ]);
    }

    /**
     * Prepare the request options, including headers and body.
     *
     * @param array $options
     *
     * @return array
     */
    protected function prepareRequestOptions(array $options): array
    {
        // Prepare the request method and options
        $headers = $options['headers'] ?? [];

        // Prepare the request body and update headers if necessary
        [$body, $headers] = $this->prepareBody($options, $headers);

        $query = $options['query'] ?? [];

        // Build request options array
        return [
            'headers' => $headers,
            'body' => $body,
            'query' => $query,
            'auth' => $options['auth'] ?? null,
            'allow_redirects' => $options['allow_redirects'] ?? true,
        ];
    }

    /**
     * Prepare the request body based on the given options.
     *
     * @param array $options
     * @param array $headers
     *
     * @return array
     */
    protected function prepareBody(array $options, array $headers): array
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
    protected function handleAsyncRequest(
        Client $client,
        string $method,
        string $url,
        array $requestOptions
    ): PromiseInterface {
        return $client->requestAsync($method, $url, $requestOptions)->then(
            fn (ResponseInterface $response) => new Response($response),
            fn (RequestException $e) => $this->handleRequestException($e)
        );
    }

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
    protected function handleSyncRequest(
        Client $client,
        string $method,
        string $url,
        array $requestOptions
    ): Response {
        try {
            $response = $client->request($method, $url, $requestOptions);

            return new Response($response);
        } catch (RequestException $e) {
            return $this->handleRequestException($e);
        }
    }

    /**
     * Handle a request exception and return a mock response.
     *
     * @param \GuzzleHttp\Exception\RequestException $e
     *
     * @return \Fetch\Response
     */
    protected function handleRequestException(RequestException $e): Response
    {
        $response = $e->getResponse();

        return $response ? new Response($response) : new Response(
            new GuzzleResponse(
                SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR,
                [],
                $e->getMessage()
            )
        );
    }
}
