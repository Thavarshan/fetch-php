<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Enum\ContentType;
use Fetch\Support\RequestContext;
use Fetch\Support\RequestOptions;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/** Builds normalized request options and the PSR-7 request used by middleware. */
final class RequestBuilder
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function applyBodyOptions(array $options, mixed $body, ContentType|string $contentType): array
    {
        if ($body === null) {
            return $options;
        }

        $contentTypeEnum = ContentType::normalizeContentType($contentType);
        $contentTypeValue = $contentTypeEnum instanceof ContentType ? $contentTypeEnum->value : (string) $contentTypeEnum;
        $options['headers'] ??= [];
        unset($options['body'], $options['json'], $options['form_params'], $options['multipart']);

        if (is_array($body)) {
            return match ($contentTypeEnum) {
                ContentType::JSON => $this->withJsonBody($options, $body),
                ContentType::FORM_URLENCODED => $this->withFormBody($options, $body),
                ContentType::MULTIPART => $this->withMultipartBody($options, $body),
                default => $this->withGenericArrayBody($options, $body, $contentTypeValue),
            };
        }

        $options['body'] = $body;
        if (! isset($options['headers']['Content-Type'])) {
            $options['headers']['Content-Type'] = $contentTypeValue;
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $guzzleOptions
     * @return array{0: RequestInterface, 1: string|null}
     */
    public function buildMiddlewareRequest(string $method, string $uri, array $guzzleOptions, bool $captureStreamBody = true): array
    {
        $headers = $guzzleOptions['headers'] ?? [];

        if (isset($guzzleOptions['multipart']) && is_array($guzzleOptions['multipart'])) {
            $multipartBody = new MultipartStream(RequestOptions::normalizeMultipart($guzzleOptions['multipart']));
            foreach (array_keys($headers) as $name) {
                if (strcasecmp((string) $name, 'Content-Type') === 0) {
                    unset($headers[$name]);
                }
            }
            $headers['Content-Type'] = 'multipart/form-data; boundary='.$multipartBody->getBoundary();

            return [new GuzzleRequest($method, $uri, $headers, $multipartBody), null];
        }

        $body = $guzzleOptions['body'] ?? null;
        $isStreamBody = is_resource($body) || $body instanceof StreamInterface;
        $seedBody = $isStreamBody && ! $captureStreamBody ? null : $this->encodeSeedBody($guzzleOptions);
        $requestBody = $isStreamBody && ! $captureStreamBody ? $body : ($seedBody ?? '');

        return [new GuzzleRequest($method, $uri, $headers, $requestBody), $seedBody];
    }

    /**
     * Wrap PHP resources as PSR-7 streams without reading or rewinding them.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function normalizeStreamBody(array $options): array
    {
        if (isset($options['body']) && is_resource($options['body'])) {
            $options['body'] = Utils::streamFor($options['body']);
        }

        return $options;
    }

    /**
     * Make a non-seekable request body replayable after its contents are inspected by validation.
     *
     * @param  array<string, mixed>  $options
     * @return array{0: RequestInterface, 1: array<string, mixed>}
     */
    public function makeRequestBodyReplayable(RequestInterface $request, array $options): array
    {
        $body = $request->getBody();
        if ($body->isSeekable()) {
            return [$request, $options];
        }

        $replayableBody = Utils::streamFor($body->getContents());
        $options['body'] = $replayableBody;

        return [$request->withBody($replayableBody), $options];
    }

    /** @param array<string, mixed> $options */
    public function withQueryOptions(RequestInterface $request, array $options): RequestInterface
    {
        if (! isset($options['query'])) {
            return $request;
        }

        $query = is_array($options['query']) ? http_build_query($options['query']) : (string) $options['query'];
        $existing = $request->getUri()->getQuery();
        if ($existing !== '') {
            $query = $existing.($query === '' ? '' : '&'.$query);
        }

        return $request->withUri($request->getUri()->withQuery($query));
    }

    /** @param array<string, mixed> $options */
    public function encodeSeedBody(array $options): ?string
    {
        if (isset($options['body']) && is_string($options['body'])) {
            return $options['body'];
        }

        if (isset($options['body']) && (is_resource($options['body']) || $options['body'] instanceof StreamInterface)) {
            return $this->readStreamBody($options['body']);
        }

        if (array_key_exists('json', $options)) {
            try {
                return json_encode($options['json'], JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return null;
            }
        }

        if (isset($options['form_params']) && is_array($options['form_params'])) {
            return http_build_query($options['form_params'], '', '&');
        }

        return null;
    }

    /**
     * Replace a consumed non-seekable body stream with the bytes captured for middleware.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function replaceConsumedNonSeekableBody(array $options, ?string $seedBody): array
    {
        if ($seedBody === null || ! isset($options['body'])) {
            return $options;
        }

        $body = $options['body'];
        $seekable = match (true) {
            is_resource($body) => stream_get_meta_data($body)['seekable'],
            $body instanceof StreamInterface => $body->isSeekable(),
            default => true,
        };

        if (! $seekable && (is_resource($body) || $body instanceof StreamInterface)) {
            $options['body'] = $seedBody;
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $guzzleOptions
     * @return array<string, mixed>
     */
    public function mergeMiddlewareRequest(
        RequestInterface $request,
        array $guzzleOptions,
        ?string $seedBody,
        ?StreamInterface $initialBody = null,
    ): array {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[$name] = count($values) === 1 ? $values[0] : $values;
        }

        if ($headers !== []) {
            $guzzleOptions['headers'] = $headers;
        }

        unset($guzzleOptions['base_uri']);
        $requestBody = $request->getBody();

        if ($initialBody !== null && $requestBody === $initialBody) {
            if ($seedBody !== null && $requestBody->isSeekable()) {
                $body = $this->readFullStreamBody($requestBody);
                if ($body !== $seedBody) {
                    unset($guzzleOptions['json'], $guzzleOptions['form_params'], $guzzleOptions['multipart']);
                    $guzzleOptions['body'] = $body;

                    return $guzzleOptions;
                }
            }

            if (isset($guzzleOptions['multipart'])) {
                unset($guzzleOptions['multipart']);
                $guzzleOptions['body'] = $requestBody;
            }

            return $guzzleOptions;
        }

        if ($initialBody !== null) {
            unset($guzzleOptions['json'], $guzzleOptions['form_params'], $guzzleOptions['multipart']);
            $guzzleOptions['body'] = $requestBody;

            return $guzzleOptions;
        }

        $body = (string) $requestBody;

        if ($seedBody !== null && $body !== $seedBody) {
            unset($guzzleOptions['json'], $guzzleOptions['form_params'], $guzzleOptions['multipart']);
            $guzzleOptions['body'] = $body;
        } elseif ($seedBody === null && $body !== ''
            && ! isset($guzzleOptions['multipart'])
            && ! isset($guzzleOptions['form_params'])
            && ! isset($guzzleOptions['json'])) {
            $guzzleOptions['body'] = $body;
        }

        return $guzzleOptions;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function prepareGuzzleOptions(array $options, ?RequestContext $context = null): array
    {
        return ($context ?? RequestContext::fromOptions($options))->toGuzzleOptions();
    }

    /** Read from the body's current position and restore it when the source is seekable. */
    private function readStreamBody(mixed $body): string
    {
        if ($body instanceof StreamInterface) {
            $seekable = $body->isSeekable();
            $position = $seekable ? $body->tell() : null;
            try {
                return $body->getContents();
            } finally {
                if ($seekable && $position !== null) {
                    $body->seek($position);
                }
            }
        }

        $metadata = stream_get_meta_data($body);
        $seekable = $metadata['seekable'];
        $position = $seekable ? ftell($body) : false;
        $contents = stream_get_contents($body);
        if ($seekable && $position !== false && fseek($body, $position) !== 0) {
            throw new RuntimeException('Unable to restore the request body stream position.');
        }
        if ($contents === false) {
            throw new RuntimeException('Unable to read the request body stream.');
        }

        return $contents;
    }

    private function readFullStreamBody(StreamInterface $body): string
    {
        $position = $body->tell();
        $body->rewind();

        try {
            return $body->getContents();
        } finally {
            $body->seek($position);
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function withJsonBody(array $options, array $body): array
    {
        $options['json'] = $body;
        $options['headers']['Content-Type'] ??= ContentType::JSON->value;

        return $options;
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function withFormBody(array $options, array $body): array
    {
        $options['form_params'] = $body;
        $options['headers']['Content-Type'] ??= ContentType::FORM_URLENCODED->value;

        return $options;
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function withMultipartBody(array $options, array $body): array
    {
        $options['multipart'] = RequestOptions::normalizeMultipart($body);
        unset($options['headers']['Content-Type']);

        return $options;
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function withGenericArrayBody(array $options, array $body, string $contentType): array
    {
        $options['body'] = json_encode($body);
        $options['headers']['Content-Type'] ??= $contentType;

        return $options;
    }
}
