<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use Fetch\Enum\ContentType;
use Fetch\Interfaces\ClientHandler;
use Fetch\Support\RequestOptions;
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
        $baseOptions = $this->options;
        $hasBody = RequestOptions::hasBodyOption($options);
        $hasContentType = RequestOptions::hasHeader(
            $options['headers'] ?? [],
            'Content-Type',
        ) || array_key_exists('content_type', $options);
        $contentType = RequestOptions::getExplicitContentType($options);

        if ($hasBody) {
            $normalized = RequestOptions::normalizeBodyOptions($options);

            if (isset($normalized['multipart'])) {
                $baseOptions = RequestOptions::withoutHeader(
                    $baseOptions,
                    'Content-Type',
                );
                $this->explicitContentType = null;
            } elseif ($hasContentType) {
                $this->explicitContentType = $contentType;

                if ($contentType === null) {
                    $baseOptions = RequestOptions::withoutHeader(
                        $baseOptions,
                        'Content-Type',
                    );
                }
            } elseif ($this->explicitContentType !== null) {
                $options = RequestOptions::withHeaderValue(
                    $options,
                    'Content-Type',
                    $this->explicitContentType instanceof ContentType
                        ? $this->explicitContentType->value
                        : $this->explicitContentType,
                );
            } else {
                $baseOptions = RequestOptions::withoutHeader(
                    $baseOptions,
                    'Content-Type',
                );
            }
        } elseif ($hasContentType) {
            $this->explicitContentType = $contentType;
        }

        $this->options = RequestOptions::merge($baseOptions, $options);

        // Map retry-related options to handler properties
        if (isset($options['retries']) && is_numeric($options['retries'])) {
            $this->maxRetries = max(0, (int) $options['retries']);
        }

        if (
            isset($options['retry_delay']) &&
            is_numeric($options['retry_delay'])
        ) {
            $this->retryDelay = max(0, (int) $options['retry_delay']);
        }

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
        if (in_array(
            $key,
            ['body', 'json', 'form', 'form_params', 'multipart'],
            true,
        )) {
            $this->replaceConfiguredBodyOptions([$key => $value]);

            return $this;
        }

        if ($key === 'headers' && is_array($value)) {
            $this->options = RequestOptions::merge(
                $this->options,
                ['headers' => $value],
            );

            if (RequestOptions::hasHeader($value, 'Content-Type')) {
                $this->explicitContentType =
                    RequestOptions::getExplicitContentType([
                        'headers' => $value,
                    ]);
            }

            return $this;
        }

        if ($key === 'content_type') {
            $this->explicitContentType = $value instanceof ContentType ||
                is_string($value)
                ? $value
                : null;
        }

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
        $this->replaceConfiguredBodyOptions(['form_params' => $params]);

        return $this;
    }

    /**
     * Set the multipart data for the request.
     *
     * @param  array<int, array{
     *     name: string,
     *     contents: mixed,
     *     headers?: array<string, string>
     * }>  $multipart
     * @return $this
     */
    public function withMultipart(array $multipart): ClientHandler
    {
        $this->replaceConfiguredBodyOptions(['multipart' => $multipart]);

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
        $this->options = RequestOptions::merge(
            $this->options,
            ['headers' => $headers],
        );

        if (RequestOptions::hasHeader($headers, 'Content-Type')) {
            $this->explicitContentType =
                RequestOptions::getExplicitContentType([
                    'headers' => $headers,
                ]);
        }

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
        $this->options = RequestOptions::merge(
            $this->options,
            ['headers' => [$header => $value]],
        );

        if (strcasecmp($header, 'Content-Type') === 0) {
            $this->explicitContentType =
                RequestOptions::getExplicitContentType([
                    'headers' => [$header => $value],
                ]);
        }

        return $this;
    }

    /**
     * Set the request body.
     *
     * @param  array<string, mixed>|string  $body  Request body
     * @param  string|ContentType  $contentType  Content type
     * @return $this
     */
    public function withBody(
        array|string $body,
        string|ContentType $contentType = ContentType::JSON,
    ): ClientHandler {
        $this->replaceConfiguredBodyOptions(
            ['body' => $body],
            $contentType,
        );

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
        $this->replaceConfiguredBodyOptions(['json' => $data]);

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
            $queryParams,
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
    public function withQueryParameter(
        string $name,
        mixed $value,
    ): ClientHandler {
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
     * @param  string|array<string, mixed>  $proxy  Proxy configuration
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
     * @param  bool|array<string, mixed>  $redirects  Redirect configuration
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
     * @param  string|array<string, mixed>  $cert  Certificate path or array
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
     * @param  string|array<string, mixed>  $sslKey  SSL key configuration
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
        $this->explicitContentType = null;
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
    protected function configureRequestBody(
        mixed $body = null,
        string|ContentType $contentType = ContentType::JSON,
    ): void {
        if (is_null($body)) {
            return;
        }

        // Normalize content type
        $contentTypeEnum = ContentType::normalizeContentType($contentType);

        if (is_array($body)) {
            match ($contentTypeEnum) {
                ContentType::JSON => $this->withJson($body),
                ContentType::FORM_URLENCODED => $this->withFormParams($body),
                ContentType::MULTIPART => $this->withMultipart(
                    $this->normalizeMultipart($body),
                ),
                default => $this->withBody($body, $contentType),
            };

            return;
        }

        $this->withBody($body, $contentType);
    }

    /**
     * Remove conflicting options from the request options array.
     *
     * @param  array<string>  $keys  The keys to unset from options
     */
    protected function unsetConflictingOptions(array $keys): void
    {
        foreach ($keys as $key) {
            unset($this->options[$key]);
        }
    }

    /**
     * Normalize multipart array to the expected shape for analysis and runtime.
     *
     * @param  array<int, array{
     *     name: string,
     *     contents: mixed,
     *     headers?: array<string, string>
     * }>|array<string, mixed>  $multipart
     * @return array<int, array{
     *     name: string,
     *     contents: mixed,
     *     headers?: array<string, string>
     * }>
     */
    protected function normalizeMultipart(array $multipart): array
    {
        return RequestOptions::normalizeMultipart($multipart);
    }

    /**
     * Replace body options while retaining only an explicit Content-Type.
     *
     * @param  array<string, mixed>  $bodyOptions
     */
    private function replaceConfiguredBodyOptions(
        array $bodyOptions,
        ContentType|string|null $serializationType = null,
    ): void {
        $multipart = isset($bodyOptions['multipart']) ||
            $this->isMultipartContentType(
                $serializationType ?? $bodyOptions['content_type'] ?? null,
            );

        if ($multipart) {
            $this->explicitContentType = null;
        }

        $this->options = RequestOptions::replaceBodyOptions(
            $this->options,
            $bodyOptions,
            $this->explicitContentType !== null,
            $serializationType,
        );
    }

    private function isMultipartContentType(mixed $contentType): bool
    {
        if ($contentType instanceof ContentType) {
            return $contentType === ContentType::MULTIPART;
        }

        return is_string($contentType) && str_starts_with(
            strtolower($contentType),
            ContentType::MULTIPART->value,
        );
    }
}
