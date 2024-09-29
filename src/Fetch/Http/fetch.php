<?php

use Fetch\Http\ClientHandler;
use Fetch\Http\Response;
use GuzzleHttp\Exception\RequestException;

if (! function_exists('fetch')) {
    /**
     * Perform an HTTP request similar to JavaScript's fetch API.
     *
     * @param string $url
     * @param array  $options
     *
     * @return \Fetch\Http\Response
     */
    function fetch(string $url, array $options = []): mixed
    {
        $options = array_merge(ClientHandler::getDefaultOptions(), $options);

        // Uppercase the method
        $options['method'] = strtoupper($options['method']);

        // Automatically set JSON headers if body is an array
        if (is_array($options['body'] ?? null)) {
            $options['body'] = json_encode($options['body']);
            $options['headers']['Content-Type'] = 'application/json';
        }

        // Synchronous request handling
        try {
            return ClientHandler::handle($options['method'], $url, $options);
        } catch (\Throwable $e) {
            // Handle exceptions and return the response
            if ($e instanceof RequestException && $e->hasResponse()) {
                return Response::createFromBase($e->getResponse());
            }

            throw $e; // Rethrow for other unhandled errors
        }
    }
}
