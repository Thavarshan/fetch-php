<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Enum\ContentType;
use Fetch\Interfaces\ClientHandler;
use GuzzleHttp\Cookie\CookieJarInterface;
use InvalidArgumentException;

trait ConfiguresRequests
{
    /**
     * Set the base URI for the request.
     *
     * @param  string  $baseUri  The base URI for requests
     * @return $this
     *
     * @throws InvalidArgumentException If the base URI is invalid
     */
    public function baseUri(string $baseUri): ClientHandler
    {
        if (! filter_var($baseUri, \FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid base URI: {$baseUri}");
        }

        $this->options['base_uri'] = rtrim($baseUri, '/');

        return $this;
    }

    /**
     * Set multiple options for the request.
     *
     * @param  array<string, mixed>  $options  Request options
     * @return $this
     */
    public function withOptions(array $options): ClientHandler
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Set a single option for the request.
     *
     * @param  string  $key  Option key
     * @param  mixed  $value  Option value
     * @return $this
     */
    public function withOption(string $key, mixed $value): ClientHandler
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Set the form parameters for the request.
     *
     * @param  array<string, mixed>  $params  Form parameters
     * @return $this
     */
    public function withFormParams(array $params): ClientHandler
    {
        $this->options['form_params'] = $params;

        // Set the content type header for form params
        if (! $this->hasHeader('Content-Type')) {
            $this->withHeader('Content-Type', ContentType::FORM_URLENCODED->value);
        }

        return $this;
    }

    /**
     * Set the multipart data for the request.
     *
     * @param  array<int, array{name: string, contents: mixed, headers?: array<string, string>}>  $multipart  Multipart data
     * @return $this
     */
    public function withMultipart(array $multipart): ClientHandler
    {
        $this->options['multipart'] = $multipart;

        // Remove any content type headers as they're automatically set by the multipart boundary
        if ($this->hasHeader('Content-Type')) {
            unset($this->options['headers']['Content-Type']);
        }

        return $this;
    }

    /**
     * Set the bearer token for the request.
     *
     * @param  string  $token  Bearer token
     * @return $this
     */
    public function withToken(string $token): ClientHandler
    {
        $this->withHeader('Authorization', 'Bearer '.$token);

        return $this;
    }

    /**
     * Set basic authentication for the request.
     *
     * @param  string  $username  Username
     * @param  string  $password  Password
     * @return $this
     */
    public function withAuth(string $username, string $password): ClientHandler
    {
        $this->options['auth'] = [$username, $password];

        return $this;
    }

    /**
     * Set multiple headers for the request.
     *
     * @param  array<string, mixed>  $headers  Headers
     * @return $this
     */
    public function withHeaders(array $headers): ClientHandler
    {
        // Initialize headers array if not set
        if (! isset($this->options['headers'])) {
            $this->options['headers'] = [];
        }

        $this->options['headers'] = array_merge(
            $this->options['headers'],
            $headers
        );

        return $this;
    }

    /**
     * Set a single header for the request.
     *
     * @param  string  $header  Header name
     * @param  mixed  $value  Header value
     * @return $this
     */
    public function withHeader(string $header, mixed $value): ClientHandler
    {
        // Initialize headers array if not set
        if (! isset($this->options['headers'])) {
            $this->options['headers'] = [];
        }

        $this->options['headers'][$header] = $value;

        return $this;
    }

    /**
     * Set the request body.
     *
     * @param  array|string  $body  Request body
     * @param  string|ContentType  $contentType  Content type
     * @return $this
     */
    public function withBody(array|string $body, string|ContentType $contentType = ContentType::JSON): ClientHandler
    {
        // Convert string content type to enum if necessary
        $contentTypeEnum = ContentType::normalizeContentType($contentType);
        $contentTypeValue = $contentTypeEnum instanceof ContentType
            ? $contentTypeEnum->value
            : $contentTypeEnum;

        if (is_array($body)) {
            if ($contentTypeEnum === ContentType::JSON) {
                // Use Guzzle's json option for proper JSON handling
                $this->options['json'] = $body;

                // Set JSON content type header if not already set
                if (! $this->hasHeader('Content-Type')) {
                    $this->withHeader('Content-Type', ContentType::JSON->value);
                }
            } elseif ($contentTypeEnum === ContentType::FORM_URLENCODED) {
                $this->withFormParams($body);
            } elseif ($contentTypeEnum === ContentType::MULTIPART) {
                $this->withMultipart($body);
            } else {
                // For any other content type, serialize the array to JSON in body
                $this->options['body'] = json_encode($body);
                if (! $this->hasHeader('Content-Type')) {
                    $this->withHeader('Content-Type', $contentTypeValue);
                }
            }
        } else {
            // For string bodies, use body option
            $this->options['body'] = $body;

            if (! $this->hasHeader('Content-Type')) {
                $this->withHeader('Content-Type', $contentTypeValue);
            }
        }

        return $this;
    }

    /**
     * Set the JSON body for the request.
     *
     * @param  array<string, mixed>  $data  JSON data
     * @param  int  $options  JSON encoding options
     * @return $this
     */
    public function withJson(array $data, int $options = 0): ClientHandler
    {
        // Use Guzzle's built-in json option for proper handling
        $this->options['json'] = $data;

        // Set JSON content type if not already set
        if (! $this->hasHeader('Content-Type')) {
            $this->withHeader('Content-Type', ContentType::JSON->value);
        }

        return $this;
    }

    /**
     * Set multiple query parameters for the request.
     *
     * @param  array<string, mixed>  $queryParams  Query parameters
     * @return $this
     */
    public function withQueryParameters(array $queryParams): ClientHandler
    {
        // Initialize query array if not set
        if (! isset($this->options['query'])) {
            $this->options['query'] = [];
        }

        $this->options['query'] = array_merge(
            $this->options['query'],
            $queryParams
        );

        return $this;
    }

    /**
     * Set a single query parameter for the request.
     *
     * @param  string  $name  Parameter name
     * @param  mixed  $value  Parameter value
     * @return $this
     */
    public function withQueryParameter(string $name, mixed $value): ClientHandler
    {
        // Initialize query array if not set
        if (! isset($this->options['query'])) {
            $this->options['query'] = [];
        }

        $this->options['query'][$name] = $value;

        return $this;
    }

    /**
     * Set the timeout for the request.
     *
     * @param  int  $seconds  Timeout in seconds
     * @return $this
     */
    public function timeout(int $seconds): ClientHandler
    {
        $this->timeout = $seconds;
        $this->options['timeout'] = $seconds;

        return $this;
    }

    /**
     * Set the proxy for the request.
     *
     * @param  string|array  $proxy  Proxy configuration
     * @return $this
     */
    public function withProxy(string|array $proxy): ClientHandler
    {
        $this->options['proxy'] = $proxy;

        return $this;
    }

    /**
     * Set the cookies for the request.
     *
     * @param  bool|CookieJarInterface  $cookies  Cookie jar or boolean
     * @return $this
     */
    public function withCookies(bool|CookieJarInterface $cookies): ClientHandler
    {
        $this->options['cookies'] = $cookies;

        return $this;
    }

    /**
     * Set whether to follow redirects.
     *
     * @param  bool|array  $redirects  Redirect configuration
     * @return $this
     */
    public function withRedirects(bool|array $redirects = true): ClientHandler
    {
        $this->options['allow_redirects'] = $redirects;

        return $this;
    }

    /**
     * Set the certificate for the request.
     *
     * @param  string|array  $cert  Certificate path or array
     * @return $this
     */
    public function withCert(string|array $cert): ClientHandler
    {
        $this->options['cert'] = $cert;

        return $this;
    }

    /**
     * Set the SSL key for the request.
     *
     * @param  string|array  $sslKey  SSL key configuration
     * @return $this
     */
    public function withSslKey(string|array $sslKey): ClientHandler
    {
        $this->options['ssl_key'] = $sslKey;

        return $this;
    }

    /**
     * Set the stream option for the request.
     *
     * @param  bool  $stream  Whether to stream the response
     * @return $this
     */
    public function withStream(bool $stream): ClientHandler
    {
        $this->options['stream'] = $stream;

        return $this;
    }

    /**
     * Reset the handler state.
     *
     * @return $this
     */
    public function reset(): ClientHandler
    {
        $this->options = [];
        $this->timeout = null;
        $this->maxRetries = null;
        $this->retryDelay = null;
        $this->isAsync = false;

        return $this;
    }

    /**
     * Configure the request body for POST/PUT/PATCH/DELETE requests.
     *
     * @param  mixed  $body  The request body
     * @param  string|ContentType  $contentType  The content type of the request
     */
    protected function configureRequestBody(mixed $body = null, string|ContentType $contentType = ContentType::JSON): void
    {
        if (is_null($body)) {
            return;
        }

        // Normalize content type
        $contentTypeEnum = ContentType::normalizeContentType($contentType);

        if (is_array($body)) {
            match ($contentTypeEnum) {
                ContentType::JSON => $this->withJson($body),
                ContentType::FORM_URLENCODED => $this->withFormParams($body),
                ContentType::MULTIPART => $this->withMultipart($body),
                default => $this->withBody($body, $contentType)
            };

            return;
        }

        $this->withBody($body, $contentType);
    }
}
