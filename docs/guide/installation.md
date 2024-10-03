# Installation

## Prerequisites

Before installing FetchPHP, ensure that your environment meets the following requirements:

- **PHP 8.1 or higher**: FetchPHP relies on PHP Fibers, which were introduced in PHP 8.1. Make sure your project or server is running PHP 8.1 or above.
- **Composer**: FetchPHP is distributed via Composer, so you’ll need Composer installed in your environment.

## Installing FetchPHP

To install FetchPHP in your project, run the following command:

```bash
composer require jerome/fetch-php
```

This will download and install FetchPHP along with its required dependencies, including **Guzzle** for HTTP client handling and **Matrix** for asynchronous task management.

## Configuration

FetchPHP uses Guzzle as its underlying HTTP client, and you can configure various Guzzle options by passing them into the `fetch()` function or creating a custom Guzzle client. Below is a list of Guzzle configuration options that can be used in FetchPHP:

### Guzzle Client Options

- **base_uri**: The base URI to use with requests. Example: `https://example.com`.
- **timeout**: Timeout in seconds for the request. Example: `timeout => 5`.
- **connect_timeout**: Timeout in seconds for establishing a connection. Example: `connect_timeout => 2`.
- **allow_redirects**: Allows redirections. Can be `true`, `false`, or an array of options. Default is `true`.
- **proxy**: A string or array of proxy servers. Example: `'proxy' => 'tcp://localhost:8080'`.
- **auth**: Array for HTTP Basic, Digest, or NTLM authentication. Example: `['username', 'password']`.
- **headers**: Array of default headers to send with every request.
- **verify**: Enable or disable SSL certificate verification. Default is `true`. Can be a boolean or a path to a CA file.
- **cookies**: Enable or disable cookies for requests. Can be a `CookieJar` object or `true` to use a shared `CookieJar`.
- **debug**: Set to `true` to enable debug output.
- **http_errors**: Set to `false` to disable exceptions on HTTP protocol errors (e.g., 4xx, 5xx responses). Default is `true`.
- **query**: Associative array of query string parameters to include in the request URL.
- **form_params**: Associative array of data for form submissions (used for `application/x-www-form-urlencoded` requests).
- **json**: JSON data to include as the request body.
- **multipart**: An array of multipart form data fields. Used for `multipart/form-data` requests.
- **sink**: Path to a file where the response body will be saved.
- **ssl_key**: Path to SSL key file or array `[path, password]`.
- **cert**: Path to an SSL certificate file or array `[path, password]`.
- **stream**: Set to `true` to return the response body as a stream.
- **delay**: The number of milliseconds to delay before sending the request.
- **on_stats**: A callable function that receives transfer statistics after a request is completed.

> **Note**: Most options can also be invoked as methods on the `fetch()` function or `ClientHandler` class. Check the API reference for more details.

### Example of Custom Guzzle Client

You can configure FetchPHP with these options by creating a custom Guzzle client and passing it into FetchPHP’s `ClientHandler`:

```php
use GuzzleHttp\Client;
use Fetch\Http\ClientHandler;

// Create a custom Guzzle client
$client = new Client([
    'base_uri' => 'https://example.com',
    'timeout' => 5,
    'headers' => ['User-Agent' => 'My-App'],
    'auth' => ['username', 'password'],
]);

// Use the custom client with FetchPHP
$response = fetch('/endpoint', ['client' => $client]);

$data = $response->json();

// Use the custom client with FetchPHP for async requests
$response async(fn () => fetch('/endpoint', ['client' => $client]));

$data = $response->json();

// Use the custom client with directly on the client handler
$handler = new ClientHandler($client, $options); // where $options are all possible Guzzle options

$response = $handler->get('/endpoint');

$data = $response->json();
```

## Updating FetchPHP

To update FetchPHP to the latest version, run the following Composer command:

```bash
composer update jerome/fetch-php
```

This will pull the latest version of FetchPHP and its dependencies.

## Checking Installation

To verify that FetchPHP was installed correctly, you can run a simple test:

```php
<?php

$response = fetch('https://example.com');

if ($response->ok()) {
    echo "FetchPHP is working correctly!";
}
```

## Uninstalling FetchPHP

If you need to remove FetchPHP from your project, run the following Composer command:

```bash
composer remove jerome/fetch-php
```

This will uninstall FetchPHP and remove it from your `composer.json` file.
