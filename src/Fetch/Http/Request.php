<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Traits\RequestImmutabilityTrait;
use GuzzleHttp\Psr7\Request as BaseRequest;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class Request extends BaseRequest implements RequestInterface
{
    use RequestImmutabilityTrait;

    /**
     * The custom request target, if set.
     */
    protected ?string $customRequestTarget = null;

    /**
     * Create a new Request instance.
     */
    public function __construct(
        string|Method $method,
        string|UriInterface $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        ?string $requestTarget = null
    ) {
        // Normalize the method
        $methodValue = $method instanceof Method ? $method->value : strtoupper($method);

        // Convert string URI to UriInterface if needed
        $uriObject = is_string($uri) ? new Uri($uri) : $uri;

        // Initialize with parent constructor
        parent::__construct($methodValue, $uriObject, $headers, $body, $version);

        // Store custom request target if provided
        if ($requestTarget !== null) {
            $this->customRequestTarget = $requestTarget;
        }
    }

    /**
     * Create a new Request instance with a JSON body.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string|array<int, string>>  $headers
     */
    public static function json(
        string|Method $method,
        string|UriInterface $uri,
        array $data,
        array $headers = []
    ): static {
        // Normalize the method
        $methodValue = $method instanceof Method ? $method->value : strtoupper($method);

        // Prepare the JSON body
        $body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($body === false) {
            throw new InvalidArgumentException('Unable to encode data to JSON: '.json_last_error_msg());
        }

        // Add the Content-Type header if not already present
        $headers['Content-Type'] = ContentType::JSON->value;

        // Add Content-Length if not already set
        if (! isset($headers['Content-Length'])) {
            $headers['Content-Length'] = (string) strlen($body);
        }

        return new static($methodValue, $uri, $headers, $body);
    }

    /**
     * Create a new Request instance with form parameters.
     *
     * @param  array<string, mixed>  $formParams
     * @param  array<string, string|array<int, string>>  $headers
     */
    public static function form(
        string|Method $method,
        string|UriInterface $uri,
        array $formParams,
        array $headers = []
    ): static {
        // Normalize the method
        $methodValue = $method instanceof Method ? $method->value : strtoupper($method);

        // Prepare the form body
        $body = http_build_query($formParams);

        // Add the Content-Type header if not already present
        $headers['Content-Type'] = ContentType::FORM_URLENCODED->value;

        // Add Content-Length if not already set
        if (! isset($headers['Content-Length'])) {
            $headers['Content-Length'] = (string) strlen($body);
        }

        return new static($methodValue, $uri, $headers, $body);
    }

    /**
     * Create a new Request instance with multipart form data.
     *
     * @param  array<int, array{name: string, contents: mixed, headers?: array<string, string>, filename?: string}>  $multipart
     * @param  array<string, string|array<int, string>>  $headers
     */
    public static function multipart(
        string|Method $method,
        string|UriInterface $uri,
        array $multipart,
        array $headers = []
    ): static {
        // Normalize the method
        $methodValue = $method instanceof Method ? $method->value : strtoupper($method);

        // Generate a boundary
        $boundary = uniqid('', true);

        // Build the multipart body
        $body = '';
        foreach ($multipart as $part) {
            $body .= "--{$boundary}\r\n";

            // Add part headers
            if (isset($part['headers']) && is_array($part['headers'])) {
                foreach ($part['headers'] as $name => $value) {
                    $body .= "{$name}: {$value}\r\n";
                }
            }

            // Add Content-Disposition
            $body .= 'Content-Disposition: form-data; name="'.$part['name'].'"';

            // Add filename if present
            if (isset($part['filename'])) {
                $body .= '; filename="'.$part['filename'].'"';
            }

            $body .= "\r\n\r\n";

            // Add contents
            $body .= $part['contents']."\r\n";
        }

        // Add the final boundary
        $body .= "--{$boundary}--\r\n";

        // Set the Content-Type header with the boundary
        $headers['Content-Type'] = ContentType::MULTIPART->value.'; boundary='.$boundary;

        // Add Content-Length if not already set
        if (! isset($headers['Content-Length'])) {
            $headers['Content-Length'] = (string) strlen($body);
        }

        return new static($methodValue, $uri, $headers, $body);
    }

    /**
     * Create a new GET request.
     *
     * @param  array<string, string|array<int, string>>  $headers
     */
    public static function get(string|UriInterface $uri, array $headers = []): static
    {
        return new static(Method::GET->value, $uri, $headers);
    }

    /**
     * Create a new POST request.
     *
     * @param  array<string, string|array<int, string>>  $headers
     */
    public static function post(
        string|UriInterface $uri,
        mixed $body = null,
        array $headers = [],
        ContentType|string|null $contentType = null
    ): static {
        if ($contentType) {
            $contentTypeValue = $contentType instanceof ContentType ? $contentType->value : $contentType;
            $headers['Content-Type'] = $contentTypeValue;
        }

        return new static(Method::POST->value, $uri, $headers, $body);
    }

    /**
     * Create a new PUT request.
     *
     * @param  array<string, string|array<int, string>>  $headers
     */
    public static function put(
        string|UriInterface $uri,
        mixed $body = null,
        array $headers = [],
        ContentType|string|null $contentType = null
    ): static {
        if ($contentType) {
            $contentTypeValue = $contentType instanceof ContentType ? $contentType->value : $contentType;
            $headers['Content-Type'] = $contentTypeValue;
        }

        return new static(Method::PUT->value, $uri, $headers, $body);
    }

    /**
     * Create a new PATCH request.
     *
     * @param  array<string, string|array<int, string>>  $headers
     */
    public static function patch(
        string|UriInterface $uri,
        mixed $body = null,
        array $headers = [],
        ContentType|string|null $contentType = null
    ): static {
        if ($contentType) {
            $contentTypeValue = $contentType instanceof ContentType ? $contentType->value : $contentType;
            $headers['Content-Type'] = $contentTypeValue;
        }

        return new static(Method::PATCH->value, $uri, $headers, $body);
    }

    /**
     * Create a new DELETE request.
     *
     * @param  array<string, string|array<int, string>>  $headers
     */
    public static function delete(
        string|UriInterface $uri,
        mixed $body = null,
        array $headers = [],
        ContentType|string|null $contentType = null
    ): static {
        if ($contentType) {
            $contentTypeValue = $contentType instanceof ContentType ? $contentType->value : $contentType;
            $headers['Content-Type'] = $contentTypeValue;
        }

        return new static(Method::DELETE->value, $uri, $headers, $body);
    }

    /**
     * Create a new HEAD request.
     *
     * @param  array<string, string|array<int, string>>  $headers
     */
    public static function head(string|UriInterface $uri, array $headers = []): static
    {
        return new static(Method::HEAD->value, $uri, $headers);
    }

    /**
     * Create a new OPTIONS request.
     *
     * @param  array<string, string|array<int, string>>  $headers
     */
    public static function options(string|UriInterface $uri, array $headers = []): static
    {
        return new static(Method::OPTIONS->value, $uri, $headers);
    }

    /**
     * Create a new Request instance from a PSR-7 request.
     */
    public static function createFromBase(RequestInterface $request): static
    {
        return new static(
            $request->getMethod(),
            $request->getUri(),
            $request->getHeaders(),
            $request->getBody(),
            $request->getProtocolVersion()
        );
    }

    /**
     * Override getRequestTarget to use our custom target if set.
     */
    public function getRequestTarget(): string
    {
        if ($this->customRequestTarget !== null) {
            return $this->customRequestTarget;
        }

        return parent::getRequestTarget();
    }

    /**
     * Override withRequestTarget to store the custom target.
     */
    public function withRequestTarget($requestTarget): static
    {
        $new = clone $this;
        $new->customRequestTarget = $requestTarget;

        return $new;
    }

    /**
     * Check if the request method supports a request body.
     */
    public function supportsRequestBody(): bool
    {
        try {
            $method = Method::fromString($this->getMethod());

            return $method->supportsRequestBody();
        } catch (\ValueError $e) {
            // Unknown method, assume it might support a body
            return true;
        }
    }

    /**
     * Get the method as an enum.
     */
    public function getMethodEnum(): ?Method
    {
        return Method::tryFromString($this->getMethod());
    }

    /**
     * Get the content type from the headers.
     */
    public function getContentTypeEnum(): ?ContentType
    {
        if (! $this->hasHeader('Content-Type')) {
            return null;
        }

        $contentType = $this->getHeaderLine('Content-Type');

        // Strip parameters like charset
        if (($pos = strpos($contentType, ';')) !== false) {
            $contentType = trim(substr($contentType, 0, $pos));
        }

        return ContentType::tryFromString($contentType);
    }

    /**
     * Check if the request has JSON content.
     */
    public function hasJsonContent(): bool
    {
        $contentType = $this->getContentTypeEnum();

        return $contentType === ContentType::JSON;
    }

    /**
     * Check if the request has form content.
     */
    public function hasFormContent(): bool
    {
        $contentType = $this->getContentTypeEnum();

        return $contentType === ContentType::FORM_URLENCODED;
    }

    /**
     * Check if the request has multipart content.
     */
    public function hasMultipartContent(): bool
    {
        $contentType = $this->getContentTypeEnum();

        return $contentType === ContentType::MULTIPART;
    }

    /**
     * Check if the request has text content.
     */
    public function hasTextContent(): bool
    {
        $contentType = $this->getContentTypeEnum();

        return $contentType && $contentType->isText();
    }

    /**
     * Get the request body as a string.
     */
    public function getBodyAsString(): string
    {
        $body = $this->getBody();
        $body->rewind();

        return $body->getContents();
    }

    /**
     * Get the request body as JSON.
     *
     * @throws InvalidArgumentException If the body is not valid JSON
     */
    public function getBodyAsJson(bool $assoc = true, int $depth = 512, int $options = 0): mixed
    {
        $body = $this->getBodyAsString();

        if (empty($body)) {
            return $assoc ? [] : new \stdClass;
        }

        $data = json_decode($body, $assoc, $depth, $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON: '.json_last_error_msg());
        }

        return $data;
    }

    /**
     * Get the request body as form parameters.
     *
     * @return array<string, mixed>
     */
    public function getBodyAsFormParams(): array
    {
        $body = $this->getBodyAsString();

        if (empty($body)) {
            return [];
        }

        $params = [];
        parse_str($body, $params);

        return $params;
    }

    /**
     * Set the request body.
     */
    public function withBody($body): static
    {
        if (is_string($body)) {
            $body = Utils::streamFor($body);
        }

        return $this->toStatic(parent::withBody($body));
    }

    /**
     * Set the content type of the request.
     */
    public function withContentType(ContentType|string $contentType): static
    {
        $value = $contentType instanceof ContentType ? $contentType->value : $contentType;

        return $this->withHeader('Content-Type', $value);
    }

    /**
     * Set a query parameter on the request URI.
     */
    public function withQueryParam(string $name, string|int|float|bool|null $value): static
    {
        $uri = $this->getUri();
        $query = $uri->getQuery();

        $params = [];
        if (! empty($query)) {
            parse_str($query, $params);
        }

        // Add or update the parameter
        $params[$name] = $value;

        // Build the new query string
        $newQuery = http_build_query($params);

        // Create a new URI with the updated query
        $newUri = $uri->withQuery($newQuery);

        // Return a new request with the updated URI
        return $this->withUri($newUri);
    }

    /**
     * Set multiple query parameters on the request URI.
     *
     * @param  array<string, string|int|float|bool|null>  $params
     */
    public function withQueryParams(array $params): static
    {
        $uri = $this->getUri();
        $query = $uri->getQuery();

        $existingParams = [];
        if (! empty($query)) {
            parse_str($query, $existingParams);
        }

        // Merge the existing parameters with the new ones
        $mergedParams = array_merge($existingParams, $params);

        // Build the new query string
        $newQuery = http_build_query($mergedParams);

        // Create a new URI with the updated query
        $newUri = $uri->withQuery($newQuery);

        // Return a new request with the updated URI
        return $this->withUri($newUri);
    }

    /**
     * Set an authorization header with a bearer token.
     */
    public function withBearerToken(string $token): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$token);
    }

    /**
     * Set a basic authentication header.
     */
    public function withBasicAuth(string $username, string $password): static
    {
        $auth = base64_encode("$username:$password");

        return $this->withHeader('Authorization', 'Basic '.$auth);
    }

    /**
     * Set a JSON body on the request.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidArgumentException If the data cannot be encoded as JSON
     */
    public function withJsonBody(array $data, int $options = 0): static
    {
        $json = json_encode($data, $options);

        if ($json === false) {
            throw new InvalidArgumentException('Unable to encode data to JSON: '.json_last_error_msg());
        }

        $request = $this->withBody(Utils::streamFor($json));

        // Add or update Content-Type header
        return $request->withContentType(ContentType::JSON);
    }

    /**
     * Set a form body on the request.
     *
     * @param  array<string, string|int|float|bool|null>  $data
     */
    public function withFormBody(array $data): static
    {
        $body = http_build_query($data);
        $request = $this->withBody(Utils::streamFor($body));

        // Add or update Content-Type header
        return $request->withContentType(ContentType::FORM_URLENCODED);
    }
}
