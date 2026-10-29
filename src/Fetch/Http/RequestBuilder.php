<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Enum\ContentType;
use Fetch\Support\RequestContext;
use Fetch\Support\RequestOptions;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Psr\Http\Message\RequestInterface;

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
    public function buildMiddlewareRequest(string $method, string $uri, array $guzzleOptions): array
    {
        $headers = $guzzleOptions['headers'] ?? [];
        $seedBody = $this->encodeSeedBody($guzzleOptions);

        return [new GuzzleRequest($method, $uri, $headers, $seedBody ?? ''), $seedBody];
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
     * @param  array<string, mixed>  $guzzleOptions
     * @return array<string, mixed>
     */
    public function mergeMiddlewareRequest(RequestInterface $request, array $guzzleOptions, ?string $seedBody): array
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[$name] = count($values) === 1 ? $values[0] : $values;
        }

        if ($headers !== []) {
            $guzzleOptions['headers'] = $headers;
        }

        unset($guzzleOptions['base_uri']);
        $body = (string) $request->getBody();

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
