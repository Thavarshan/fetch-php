<?php

declare(strict_types=1);

namespace Fetch\Concerns;

use InvalidArgumentException;

trait HandlesUris
{
    /**
     * Get the full URI for the request.
     *
     * @throws InvalidArgumentException If the base URI is invalid
     */
    protected function getFullUri(): string
    {
        $baseUri = $this->options['base_uri'] ?? '';
        $uri = $this->options['uri'] ?? '';
        $queryParams = $this->options['query'] ?? [];

        // Early return if URI is empty
        if (empty($uri) && empty($baseUri)) {
            throw new InvalidArgumentException('URI cannot be empty');
        }

        // Handle absolute URLs
        if ($this->isAbsoluteUrl($uri)) {
            return $this->appendQueryParameters($uri, $queryParams);
        }

        // Handle relative URLs with base URI
        $fullUri = $this->combineBaseWithRelativeUri($baseUri, $uri);

        // Append query parameters if they exist
        return $this->appendQueryParameters($fullUri, $queryParams);
    }

    /**
     * Check if a URI is an absolute URL.
     */
    protected function isAbsoluteUrl(string $uri): bool
    {
        return filter_var($uri, \FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Combine a base URI with a relative URI.
     *
     * @throws InvalidArgumentException If the base URI is invalid when needed
     */
    protected function combineBaseWithRelativeUri(string $baseUri, string $relativeUri): string
    {
        // If there's no base URI, just return the relative URI WITHOUT trimming leading slashes
        if (empty($baseUri)) {
            return $relativeUri; // Keep the leading slash if present
        }

        // Validate the base URI if it's not empty
        if (! $this->isAbsoluteUrl($baseUri)) {
            throw new InvalidArgumentException("Invalid base URI: {$baseUri}");
        }

        // Remove trailing slash from base URI and leading slash from relative URI
        // Then combine them with a forward slash
        return rtrim($baseUri, '/').'/'.ltrim($relativeUri, '/');
    }

    /**
     * Append query parameters to a URI.
     *
     * @param  array<string, mixed>  $queryParams  The query parameters
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

    /**
     * Resolve a URI against a base URI according to RFC 3986.
     * This is a more sophisticated version for future use if needed.
     */
    protected function resolveUri(string $baseUri, string $relativeUri): string
    {
        // If the relative URI is an absolute URL, just return it
        if ($this->isAbsoluteUrl($relativeUri)) {
            return $relativeUri;
        }

        // If the relative URI starts with a slash, it's absolute to the base URI's root
        if (str_starts_with($relativeUri, '/')) {
            $parsedBase = parse_url($baseUri);
            $scheme = isset($parsedBase['scheme']) ? $parsedBase['scheme'].'://' : '';
            $host = $parsedBase['host'] ?? '';
            $port = isset($parsedBase['port']) ? ':'.$parsedBase['port'] : '';

            return $scheme.$host.$port.$relativeUri;
        }

        // Otherwise, resolve it relative to the base URI
        return $this->combineBaseWithRelativeUri($baseUri, $relativeUri);
    }
}
