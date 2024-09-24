<?php

use Fetch\Http;
use Fetch\Response;
use GuzzleHttp\Promise\PromiseInterface;

if (! function_exists('fetch')) {
    /**
     * Performs an HTTP request using Guzzle and returns a structured response.
     *
     * @param string $url
     * @param array  $options
     *
     * @return \Fetch\Response
     */
    function fetch(string $url, array $options = []): Response
    {
        return Http::makeRequest($url, $options, false);
    }
}

if (! function_exists('fetch_async')) {
    /**
     * Asynchronous version of the fetch function.
     *
     * @param string $url
     * @param array  $options
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    function fetch_async(string $url, array $options = []): PromiseInterface
    {
        return Http::makeRequest($url, $options, async: true);
    }
}

if (! function_exists('fetchAsync')) {
    /**
     * Asynchronous version of the fetch function.
     *
     * @param string $url
     * @param array  $options
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     *
     * @deprecated v1.1.0 Use fetch_async instead
     */
    function fetchAsync(string $url, array $options = []): PromiseInterface
    {
        trigger_error('Function fetchAsync is deprecated. Use fetch_async instead.', \E_USER_DEPRECATED);

        return Http::makeRequest($url, $options, async: true);
    }
}
