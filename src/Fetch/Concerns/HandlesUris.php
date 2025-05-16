<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use InvalidArgumentException;

trait HandlesUris
{
    /**
     * Build and get the full URI from a base URI and path.
     *
     * @param  string  $uri  The path or absolute URI
     * @return string The full URI
     *
     * @throws InvalidArgumentException If the URI is invalid
     */
    protected function buildFullUri(string $uri): string
    {
        $baseUri = $this->options['base_uri'] ?? '';
        $queryParams = $this->options['query'] ?? [];

        // Early return if URI is empty
        if (empty($uri) && empty($baseUri)) {
            throw new InvalidArgumentException('URI cannot be empty');
        }

        // Handle absolute URLs
        if ($this->isAbsoluteUrl($uri)) {
            return $this->appendQueryParameters($uri, $queryParams);
        }

        // For relative URLs, require a base URI
        if (empty($baseUri)) {
            throw new InvalidArgumentException(
                "Relative URI '{$uri}' cannot be used without a base URI. ".
                'Set a base URI using the baseUri() method.'
            );
        }

        // Handle relative URLs with base URI
        $fullUri = $this->combineBaseWithRelativeUri($baseUri, $uri);

        // Append query parameters if they exist
        return $this->appendQueryParameters($fullUri, $queryParams);
    }

    /**
     * Get the full URI using the URI from options.
     *
     * @return string The full URI
     *
     * @throws InvalidArgumentException If the URI is invalid
     */
    protected function getFullUri(): string
    {
        $uri = $this->options['uri'] ?? '';

        return $this->buildFullUri($uri);
    }

    /**
     * Check if a URI is an absolute URL.
     *
     * @param  string  $uri  The URI to check
     * @return bool Whether the URI is absolute
     */
    protected function isAbsoluteUrl(string $uri): bool
    {
        return filter_var($uri, \FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Combine a base URI with a relative URI.
     *
     * @param  string  $baseUri  The base URI
     * @param  string  $relativeUri  The relative URI
     * @return string The combined URI
     */
    protected function combineBaseWithRelativeUri(string $baseUri, string $relativeUri): string
    {
        // Ensure base URI is valid if not empty
        if (! empty($baseUri) && ! $this->isAbsoluteUrl($baseUri)) {
            throw new InvalidArgumentException("Invalid base URI: {$baseUri}");
        }

        // Remove trailing slash from base URI and leading slash from relative URI
        // Then combine them with a forward slash
        return rtrim($baseUri, '/').'/'.ltrim($relativeUri, '/');
    }

    /**
     * Append query parameters to a URI.
     *
     * @param  string  $uri  The URI
     * @param  array<string, mixed>  $queryParams  The query parameters
     * @return string The URI with query parameters
     */
    protected function appendQueryParameters(string $uri, array $queryParams): string
    {
        if (empty($queryParams)) {
            return $uri;
        }

        // Check if the URI has a fragment
        $fragments = explode('#', $uri, 2);
        $baseUri = $fragments[0];
        $fragment = isset($fragments[1]) ? '#'.$fragments[1] : '';

        // Parse the URI to determine if it already has query parameters
        $parsedUrl = parse_url($baseUri);

        // Determine the separator based on whether the URI already has query parameters
        $separator = isset($parsedUrl['query']) && ! empty($parsedUrl['query']) ? '&' : '?';

        // Build the query string
        $queryString = http_build_query($queryParams);

        // Handle special case: URI already ends with a question mark
        if (str_ends_with($baseUri, '?')) {
            return $baseUri.$queryString.$fragment;
        }

        // Append the query string to the URI before any fragment
        return $baseUri.$separator.$queryString.$fragment;
    }

    /**
     * Normalize a URI by ensuring it has the correct format.
     *
     * @param  string  $uri  The URI to normalize
     * @return string The normalized URI
     */
    protected function normalizeUri(string $uri): string
    {
        // Remove multiple consecutive slashes (except in the scheme)
        if (preg_match('~^(https?://)~i', $uri, $matches)) {
            $scheme = $matches[1];
            $rest = substr($uri, strlen($scheme));
            $rest = preg_replace('~//+~', '/', $rest);

            return $scheme.$rest;
        }

        // For non-URLs, just normalize consecutive slashes
        return preg_replace('~//+~', '/', $uri);
    }
}
