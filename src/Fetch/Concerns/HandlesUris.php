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
     *
     * @deprecated Use buildFullUriFromContext() instead for stateless URI building
     */
    protected function buildFullUri(string $uri): string
    {
        // Get base URI and query parameters from options
        $baseUri = $this->options['base_uri'] ?? '';
        $queryParams = $this->options['query'] ?? [];

        // Normalize URIs before processing
        $uri = $this->normalizeUri($uri);
        if (! empty($baseUri)) {
            $baseUri = $this->normalizeUri($baseUri);
        }

        // Validate inputs
        $this->validateUriInputs($uri, $baseUri);

        // Build the final URI
        $fullUri = $this->isAbsoluteUrl($uri)
            ? $uri
            : $this->joinUriPaths($baseUri, $uri);

        // Add query parameters if any
        return $this->appendQueryParameters($fullUri, $queryParams);
    }

    /**
     * Build the full URI from a RequestContext without accessing handler state.
     *
     * This method is stateless and safe for concurrent usage.
     *
     * @param  \Fetch\Support\RequestContext  $context  The request context
     * @return string The full URI
     *
     * @throws InvalidArgumentException If the URI is invalid
     */
    protected function buildFullUriFromContext(\Fetch\Support\RequestContext $context): string
    {
        $uri = $context->getUri();
        $baseUri = $context->getOption('base_uri', '');
        $queryParams = $context->getOption('query', []);

        // Normalize URIs before processing
        $uri = $this->normalizeUri($uri);
        if (! empty($baseUri)) {
            $baseUri = $this->normalizeUri((string) $baseUri);
        }

        // Validate inputs
        $this->validateUriInputs($uri, (string) $baseUri);

        // Build the final URI
        $fullUri = $this->isAbsoluteUrl($uri)
            ? $uri
            : $this->joinUriPaths((string) $baseUri, $uri);

        // Add query parameters if any
        return $this->appendQueryParameters($fullUri, is_array($queryParams) ? $queryParams : []);
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
     * Validate URI and base URI inputs.
     *
     * @param  string  $uri  The URI or path
     * @param  string  $baseUri  The base URI
     *
     * @throws InvalidArgumentException If validation fails
     */
    protected function validateUriInputs(string $uri, string $baseUri): void
    {
        // Validate URI string format first
        if (! empty($uri)) {
            $this->validateUriString($uri);
        }

        // Validate base URI string format if provided
        if (! empty($baseUri)) {
            $this->validateUriString($baseUri);
        }

        // Check if we have any URI to work with
        if (empty($uri) && empty($baseUri)) {
            throw new InvalidArgumentException('URI cannot be empty');
        }

        // For relative URIs, ensure we have a base URI
        if (! $this->isAbsoluteUrl($uri) && empty($baseUri)) {
            throw new InvalidArgumentException(
                "Relative URI '{$uri}' cannot be used without a base URI. ".
                'Set a base URI using the baseUri() method.'
            );
        }

        // Ensure base URI is valid if provided
        if (! empty($baseUri) && ! $this->isAbsoluteUrl($baseUri)) {
            throw new InvalidArgumentException("Invalid base URI: {$baseUri}");
        }
    }

    /**
     * Validate a URI string for common issues.
     *
     * @param  string  $uri  The URI to validate
     *
     * @throws InvalidArgumentException If the URI is invalid
     */
    protected function validateUriString(string $uri): void
    {
        // Check for empty or whitespace-only URI
        if (empty(trim($uri))) {
            throw new InvalidArgumentException('URI cannot be empty or whitespace');
        }

        // Check for whitespace characters (common mistake)
        if (preg_match('/\s/', $uri)) {
            throw new InvalidArgumentException(
                'URI cannot contain whitespace. Did you mean to URL-encode it?'
            );
        }
    }

    /**
     * Check if a URI string is valid.
     *
     * @param  string  $uri  The URI to check
     * @return bool Whether the URI is valid
     */
    protected function isValidUriString(string $uri): bool
    {
        try {
            $this->validateUriString($uri);

            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
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
     * Join base URI with a path properly.
     *
     * @param  string  $baseUri  The base URI
     * @param  string  $path  The path to append
     * @return string The combined URI
     */
    protected function joinUriPaths(string $baseUri, string $path): string
    {
        return rtrim($baseUri, '/').'/'.ltrim($path, '/');
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

        // Split URI to preserve any fragment
        [$baseUri, $fragment] = $this->splitUriFragment($uri);

        // Determine the separator based on URI structure
        $separator = $this->getQuerySeparator($baseUri);

        // Build the query string
        $queryString = http_build_query($queryParams);

        // Combine everything
        return $baseUri.$separator.$queryString.$fragment;
    }

    /**
     * Split a URI into its base and fragment parts.
     *
     * @param  string  $uri  The URI to split
     * @return array{0: string, 1: string} [baseUri, fragment]
     */
    protected function splitUriFragment(string $uri): array
    {
        $fragments = explode('#', $uri, 2);
        $baseUri = $fragments[0];
        $fragment = isset($fragments[1]) ? '#'.$fragments[1] : '';

        return [$baseUri, $fragment];
    }

    /**
     * Determine the appropriate query string separator for a URI.
     *
     * @param  string  $uri  The URI
     * @return string The separator ('?' or '&' or '')
     */
    protected function getQuerySeparator(string $uri): string
    {
        // Handle special case: URI already ends with a question mark
        if (str_ends_with($uri, '?')) {
            return '';
        }

        // Check if the URI already has query parameters
        $parsedUrl = parse_url($uri);

        return isset($parsedUrl['query']) && ! empty($parsedUrl['query']) ? '&' : '?';
    }

    /**
     * Normalize a URI by removing redundant slashes.
     *
     * @param  string  $uri  The URI to normalize
     * @return string The normalized URI
     */
    protected function normalizeUri(string $uri): string
    {
        // Extract scheme if present (e.g., http://)
        if (preg_match('~^(https?://)~i', $uri, $matches)) {
            $scheme = $matches[1];
            $rest = substr($uri, strlen($scheme));
            // Normalize consecutive slashes in the path
            $rest = preg_replace('~//+~', '/', $rest);

            return $scheme.$rest;
        }

        // For non-URLs, just normalize consecutive slashes
        return preg_replace('~//+~', '/', $uri);
    }
}
