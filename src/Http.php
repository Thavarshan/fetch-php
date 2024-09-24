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
     * The Guzzle client instance.
     *
     * @var \GuzzleHttp\Client|null
     */
    protected static ?Client $client = null;

    /**
     * Get the Guzzle client instance.
     *
     * @param array $options
     *
     * @return \GuzzleHttp\Client
     */
    public static function getClient(array $options = []): Client
    {
        if (self::$client === null) {
            self::$client = new Client($options);
        }

        return self::$client;
    }

    /**
     * Set the Guzzle client instance.
     *
     * @param \GuzzleHttp\Client $client
     *
     * @return void
     */
    public static function setClient(Client $client): void
    {
        self::$client = $client;
    }

    /**
     * Helper function to perform HTTP requests using Guzzle.
     *
     * @param string $url
     * @param array  $options
     * @param bool   $async
     *
     * @return \GuzzleHttp\Promise\PromiseInterface|\Fetch\Response
     */
    public static function makeRequest(
        string $url,
        array $options,
        bool $async
    ): PromiseInterface|Response {
        if (isset($options['client'])) {
            self::setClient($options['client']);
        }

        $client = self::getClient([
            'base_uri' => $options['base_uri'] ?? null,
            'timeout' => $options['timeout'] ?? 0,
            'allow_redirects' => $options['allow_redirects'] ?? true,
            'cookies' => isset($options['cookies']) ? new CookieJar() : false,
            'verify' => $options['verify'] ?? true,
            'proxy' => $options['proxy'] ?? null,
        ]);

        $method = $options['method'] ?? 'GET';
        $headers = $options['headers'] ?? [];
        $body = $options['body'] ?? null;
        $query = $options['query'] ?? [];

        if (isset($options['multipart'])) {
            $body = new MultipartStream($options['multipart']);
            $headers['Content-Type'] = 'multipart/form-data';
        } elseif (isset($options['json'])) {
            $body = json_encode($options['json']);
            $headers['Content-Type'] = 'application/json';
        }

        $requestOptions = [
            'headers' => $headers,
            'body' => $body,
            'query' => $query,
            'auth' => $options['auth'] ?? null,
        ];

        if ($async) {
            return $client->requestAsync($method, $url, $requestOptions)->then(
                fn (ResponseInterface $response) => new Response($response),
                fn (RequestException $e) => self::handleRequestException($e)
            );
        }

        try {
            $response = $client->request($method, $url, $requestOptions);

            return new Response($response);
        } catch (RequestException $e) {
            return self::handleRequestException($e);
        }
    }

    /**
     * Handles the RequestException and returns a Response.
     *
     * @param \GuzzleHttp\Exception\RequestException $e
     *
     * @return \Fetch\Response
     */
    protected static function handleRequestException(RequestException $e): Response
    {
        $response = $e->getResponse();

        if ($response) {
            return new Response($response);
        }

        return self::createErrorResponse($e);
    }

    /**
     * Creates a mock response for error handling.
     *
     * @param \GuzzleHttp\Exception\RequestException $e
     *
     * @return \Fetch\Response
     */
    protected static function createErrorResponse(RequestException $e): Response
    {
        $mockResponse = new GuzzleResponse(
            SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR,
            [],
            $e->getMessage()
        );

        return new Response($mockResponse);
    }
}
