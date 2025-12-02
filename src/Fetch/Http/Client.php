<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Exceptions\ClientException;
use Fetch\Exceptions\NetworkException;
use Fetch\Exceptions\RequestException;
use Fetch\Interfaces\ClientHandler as ClientHandlerInterface;
use Fetch\Interfaces\Response as ResponseInterface;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Client implements ClientInterface, LoggerAwareInterface
{
    /**
     * The HTTP client handler.
     */
    protected ClientHandlerInterface $handler;

    /**
     * The logger instance.
     */
    protected LoggerInterface $logger;

    /**
     * Client constructor.
     *
     * @param ClientHandlerInterface|null $handler The client handler
     * @param array<string, mixed>        $options Default request options
     * @param LoggerInterface|null        $logger  PSR-3 logger
     */
    public function __construct(
        ?ClientHandlerInterface $handler = null,
        array $options = [],
        ?LoggerInterface $logger = null,
    ) {
        $this->handler = $handler ?? new ClientHandler(options: $options);
        $this->logger = $logger ?? new NullLogger();

        // If handler supports logging, set the logger
        if (method_exists($this->handler, 'setLogger')) {
            $this->handler->setLogger($this->logger);
        }
    }

    /**
     * Create a new client with a base URI.
     *
     * @param string               $baseUri The base URI for all requests
     * @param array<string, mixed> $options Default request options
     *
     * @return static New client instance
     */
    public static function createWithBaseUri(string $baseUri, array $options = []): static
    {
        $handler = ClientHandler::createWithBaseUri($baseUri);

        if ([] !== $options) {
            $handler->withOptions($options);
        }

        return new static($handler);
    }

    /**
     * Set a PSR-3 logger.
     *
     * @param LoggerInterface $logger PSR-3 logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;

        // If handler supports logging, set the logger
        if (method_exists($this->handler, 'setLogger')) {
            $this->handler->setLogger($logger);
        }
    }

    /**
     * Get the client handler.
     *
     * @return ClientHandlerInterface The client handler
     */
    public function getHandler(): ClientHandlerInterface
    {
        return $this->handler;
    }

    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request PSR-7 request
     *
     * @return PsrResponseInterface PSR-7 response
     *
     * @throws ClientExceptionInterface If an error happens while processing the request
     */
    public function sendRequest(RequestInterface $request): PsrResponseInterface
    {
        try {
            $method = $request->getMethod();
            $uri = (string) $request->getUri();
            $options = $this->extractOptionsFromRequest($request);

            $this->logger->info('Sending PSR-7 request', [
                'method' => $method,
                'uri' => $uri,
            ]);

            // Use the new sendRequest method instead of request
            $response = $this->handler->sendRequest($method, $uri, $options);

            // Ensure we return a PSR-7 response
            if ($response instanceof ResponseInterface) {
                return $response;
            }

            // Handle case where a promise was returned (should not happen in sendRequest)
            throw new \RuntimeException('Async operations not supported in sendRequest()');
        } catch (ConnectException $e) {
            $this->logger->error('Network error', [
                'message' => $e->getMessage(),
                'uri' => (string) $request->getUri(),
            ]);

            throw new NetworkException('Network error: '.$e->getMessage(), $request, $e);
        } catch (GuzzleRequestException $e) {
            $this->logger->error('Request error', [
                'message' => $e->getMessage(),
                'uri' => (string) $request->getUri(),
                'code' => $e->getCode(),
            ]);

            // Return the error response if available
            if ($e->hasResponse()) {
                return Response::createFromBase($e->getResponse());
            }

            throw new RequestException('Request error: '.$e->getMessage(), $request, null, $e);
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error', [
                'message' => $e->getMessage(),
                'uri' => (string) $request->getUri(),
                'type' => get_class($e),
            ]);

            throw new ClientException('Unexpected error: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Create and send an HTTP request.
     *
     * @param string|null               $url     The URL to fetch
     * @param array<string, mixed>|null $options Request options
     *
     * @return ResponseInterface|ClientHandlerInterface Response or handler for method chaining
     *
     * @throws \RuntimeException If the request fails
     */
    public function fetch(?string $url = null, ?array $options = []): ResponseInterface|ClientHandlerInterface
    {
        // If no URL is provided, return the handler for method chaining
        if (is_null($url)) {
            return $this->handler;
        }

        $options = array_merge(ClientHandler::getDefaultOptions(), $options ?? []);

        // Normalize the HTTP method
        $method = strtoupper($options['method'] ?? Method::GET->value);

        try {
            $methodEnum = Method::fromString($method);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException("Invalid HTTP method: {$method}");
        }

        // Process the request body
        $body = null;
        $contentType = ContentType::JSON;

        if (isset($options['body'])) {
            $body = $options['body'];
            $contentTypeStr = $options['headers']['Content-Type'] ?? ContentType::JSON->value;

            try {
                $contentType = ContentType::tryFromString($contentTypeStr);
            } catch (\ValueError $e) {
                $contentType = $contentTypeStr;
            }
        }

        // Handle JSON body specifically
        if (isset($options['json'])) {
            $body = $options['json'];
            $contentType = ContentType::JSON;
        }

        // Handle base URI if provided
        if (isset($options['base_uri'])) {
            $this->handler->baseUri($options['base_uri']);
            unset($options['base_uri']);
        }

        $this->logger->info('Sending fetch request', [
            'method' => $method,
            'url' => $url,
        ]);

        // Send the request using the new unified approach
        try {
            $handler = $this->handler->withOptions($options);

            if (null !== $body) {
                $handler = $handler->withBody($body, $contentType);
            }

            /* @var ClientHandlerInterface $handler */
            return $handler->sendRequest($method, $url);
        } catch (GuzzleRequestException $e) {
            // Handle Guzzle exceptions - Note: this catch block is incomplete in the original
            $this->logger->error('Request exception', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            // If the exception has a response, return it
            if ($e->hasResponse()) {
                return Response::createFromBase($e->getResponse());
            }

            // Otherwise, re-throw
            throw $e;
        }
    }

    /**
     * Make a GET request.
     *
     * @param string                    $url         The URL to fetch
     * @param array<string, mixed>|null $queryParams Query parameters
     * @param array<string, mixed>|null $options     Request options
     *
     * @return ResponseInterface The response
     */
    public function get(string $url, ?array $queryParams = null, ?array $options = []): ResponseInterface
    {
        $options = $options ?? [];

        if ($queryParams) {
            $options['query'] = $queryParams;
        }

        return $this->methodRequest(Method::GET, $url, null, ContentType::JSON, $options);
    }

    /**
     * Make a POST request.
     *
     * @param string                    $url         The URL to fetch
     * @param mixed                     $body        Request body
     * @param string|ContentType        $contentType Content type
     * @param array<string, mixed>|null $options     Request options
     *
     * @return ResponseInterface The response
     */
    public function post(
        string $url,
        mixed $body = null,
        string|ContentType $contentType = ContentType::JSON,
        ?array $options = [],
    ): ResponseInterface {
        return $this->methodRequest(Method::POST, $url, $body, $contentType, $options);
    }

    /**
     * Make a PUT request.
     *
     * @param string                    $url         The URL to fetch
     * @param mixed                     $body        Request body
     * @param string|ContentType        $contentType Content type
     * @param array<string, mixed>|null $options     Request options
     *
     * @return ResponseInterface The response
     */
    public function put(
        string $url,
        mixed $body = null,
        string|ContentType $contentType = ContentType::JSON,
        ?array $options = [],
    ): ResponseInterface {
        return $this->methodRequest(Method::PUT, $url, $body, $contentType, $options);
    }

    /**
     * Make a PATCH request.
     *
     * @param string                    $url         The URL to fetch
     * @param mixed                     $body        Request body
     * @param string|ContentType        $contentType Content type
     * @param array<string, mixed>|null $options     Request options
     *
     * @return ResponseInterface The response
     */
    public function patch(
        string $url,
        mixed $body = null,
        string|ContentType $contentType = ContentType::JSON,
        ?array $options = [],
    ): ResponseInterface {
        return $this->methodRequest(Method::PATCH, $url, $body, $contentType, $options);
    }

    /**
     * Make a DELETE request.
     *
     * @param string                    $url         The URL to fetch
     * @param mixed                     $body        Request body
     * @param string|ContentType        $contentType Content type
     * @param array<string, mixed>|null $options     Request options
     *
     * @return ResponseInterface The response
     */
    public function delete(
        string $url,
        mixed $body = null,
        string|ContentType $contentType = ContentType::JSON,
        ?array $options = [],
    ): ResponseInterface {
        return $this->methodRequest(Method::DELETE, $url, $body, $contentType, $options);
    }

    /**
     * Make a HEAD request.
     *
     * @param string                    $url     The URL to fetch
     * @param array<string, mixed>|null $options Request options
     *
     * @return ResponseInterface The response
     */
    public function head(string $url, ?array $options = []): ResponseInterface
    {
        return $this->methodRequest(Method::HEAD, $url, null, ContentType::JSON, $options);
    }

    /**
     * Make an OPTIONS request.
     *
     * @param string                    $url     The URL to fetch
     * @param array<string, mixed>|null $options Request options
     *
     * @return ResponseInterface The response
     */
    public function options(string $url, ?array $options = []): ResponseInterface
    {
        return $this->methodRequest(Method::OPTIONS, $url, null, ContentType::JSON, $options);
    }

    /**
     * Get the underlying Guzzle HTTP client.
     */
    public function getHttpClient(): GuzzleClientInterface
    {
        return $this->handler->getHttpClient();
    }

    /**
     * Make a request with a specific HTTP method.
     *
     * @param Method                    $method      The HTTP method
     * @param string                    $url         The URL to fetch
     * @param mixed                     $body        Request body
     * @param string|ContentType        $contentType Content type
     * @param array<string, mixed>|null $options     Request options
     *
     * @return ResponseInterface The response
     */
    protected function methodRequest(
        Method $method,
        string $url,
        mixed $body = null,
        string|ContentType $contentType = ContentType::JSON,
        ?array $options = [],
    ): ResponseInterface {
        $options = $options ?? [];
        $options['method'] = $method->value;

        if (null !== $body) {
            $options['body'] = $body;

            // Use the global normalize_content_type function
            $normalizedContentType = ContentType::normalizeContentType($contentType);

            if ($normalizedContentType instanceof ContentType) {
                $options['headers']['Content-Type'] = $normalizedContentType->value;
            } else {
                $options['headers']['Content-Type'] = $normalizedContentType;
            }
        }

        return $this->fetch($url, $options);
    }

    /**
     * Extract options from a PSR-7 request.
     *
     * @param RequestInterface $request PSR-7 request
     *
     * @return array<string, mixed> Request options
     */
    protected function extractOptionsFromRequest(RequestInterface $request): array
    {
        $options = [];

        // Add headers
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        if ([] !== $headers) {
            $options['headers'] = $headers;
        }

        // Add body if present
        $body = (string) $request->getBody();
        if ('' !== $body) {
            $options['body'] = $body;
        }

        return $options;
    }
}
