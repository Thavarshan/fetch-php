<?php

use Fetch\Http;
use Fetch\Response;
use GuzzleHttp\Promise\PromiseInterface;

if (! function_exists('fetch')) {
    /**
     * Performs a synchronous HTTP request using Guzzle and returns a structured response.
     *
     * @param string           $url
     * @param array            $options
     * @param \Fetch\Http|null $http    Custom Http instance (optional)
     *
     * @return \Fetch\Response
     */
    function fetch(string $url, array $options = [], ?Http $http = null): Response
    {
        $http ??= Http::getInstance();

        return $http->makeRequest($url, $options, false);
    }
}

if (! function_exists('fetchAsync')) {
    /**
     * Asynchronous version of the fetch function.
     *
     * @param string           $url
     * @param array            $options
     * @param \Fetch\Http|null $http    Custom Http instance (optional)
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @deprecated version 1.1.0 Use fetch_async instead
     */
    function fetchAsync(string $url, array $options = [], ?Http $http = null): PromiseInterface
    {
        // Emit a warning to notify about the deprecation
        trigger_error('fetchAsync is deprecated. Use fetch_async instead.', \E_USER_DEPRECATED);

        $http ??= Http::getInstance();

        return $http->makeRequest($url, $options, true);
    }
}

if (! function_exists('fetch_async')) {
    /**
     * Asynchronous version of the fetch function.
     *
     * @param string           $url
     * @param array            $options
     * @param \Fetch\Http|null $http    Custom Http instance (optional)
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    function fetch_async(string $url, array $options = [], ?Http $http = null): PromiseInterface
    {
        $http ??= Http::getInstance();

        return $http->makeRequest($url, $options, true);
    }
}
