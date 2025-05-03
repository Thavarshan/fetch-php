# Installation

## Prerequisites

Before installing Fetch PHP, ensure your environment meets the following requirements:

- **PHP 8.1 or higher**: Fetch PHP relies on PHP Fibers, which were introduced in PHP 8.1
- **The `sockets` PHP extension**: Required for the underlying ReactPHP event loop
- **Composer**: Fetch PHP is distributed via Composer

## Installing Fetch PHP

To install Fetch PHP in your project, run the following command:

```bash
composer require jerome/fetch-php
```

This will download and install Fetch PHP along with its required dependencies, including:

- **Guzzle** for HTTP client handling
- **Matrix** for asynchronous task management with PHP Fibers

## Verifying Installation

You can verify that Fetch PHP was installed correctly by running a simple test:

```php
<?php

require 'vendor/autoload.php';

$response = fetch('https://example.com');

if ($response->ok()) {
    echo "Fetch PHP is working correctly!";
}
```

## Configuration Options

Fetch PHP uses Guzzle as its underlying HTTP client. You can configure various options by passing them into the `fetch()` function or by using the fluent API.

### Common Options

- **method**: HTTP method (GET, POST, PUT, etc.). Default is `'GET'`.
- **headers**: Associative array of request headers.
- **body**: Request body (arrays are automatically JSON-encoded).
- **timeout**: Request timeout in seconds. Default is `30`.
- **retries**: Number of retry attempts for failed requests. Default is `1`.
- **retry_delay**: Delay between retries in milliseconds. Default is `100`.
- **base_uri**: Base URI to prepend to all request URLs.
- **query**: Associative array of query parameters.

### Authentication Options

- **auth**: Array for HTTP Basic authentication, e.g., `['username', 'password']`.
- **token**: Bearer token for OAuth authentication.

### Advanced Options

- **proxy**: Proxy server URL or configuration.
- **cookies**: Enable cookies for requests.
- **allow_redirects**: Control redirect behavior.
- **verify**: SSL certificate verification settings.
- **cert**: SSL client certificate.
- **ssl_key**: SSL client key.
- **stream**: Stream the response instead of loading it all into memory.
- **async**: Set to `true` to make asynchronous requests.

## Using a Custom Guzzle Client

You can use a custom Guzzle client with Fetch PHP for more advanced configuration:

```php
use GuzzleHttp\Client;
use Fetch\Http\ClientHandler;

// Create a custom Guzzle client
$client = new Client([
    'base_uri' => 'https://api.example.com',
    'timeout' => 5,
    'headers' => ['User-Agent' => 'My-App'],
    'auth' => ['username', 'password'],
]);

// Method 1: Use with fetch()
$response = fetch('/users', ['client' => $client]);

// Method 2: Create a ClientHandler directly
$handler = new ClientHandler(syncClient: $client);
$response = $handler->get('/users');

// Method 3: Use with async
use function Matrix\async;
use function Matrix\await;

$response = await(async(fn () => fetch('/users', ['client' => $client])));
```

## Updating Fetch PHP

To update Fetch PHP to the latest version:

```bash
composer update jerome/fetch-php
```

## Uninstalling Fetch PHP

If you need to remove Fetch PHP from your project:

```bash
composer remove jerome/fetch-php
```
