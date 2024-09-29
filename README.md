# Fetch PHP

Fetch PHP merges the power of **Python's HTTPX** and the simplicity of **JavaScript's fetch API** to provide PHP developers with a modern and developer-friendly HTTP client. It supports both **synchronous** and **asynchronous** requests, allowing developers to write clean, readable code.

Fetch PHP uses **Guzzle** for synchronous requests and the custom **Matrix** async engine for true asynchronous capabilities. This unique combination offers the ease of **JavaScript-like APIs** with the performance benefits of non-blocking PHP.

---

## Key Features

- **JavaScript-Like API**: Use familiar syntax like `fetch()`, `async()`, `then()`, and `catch()`.
- **Synchronous Requests with Guzzle**: Simple, reliable, and fast HTTP requests.
- **Asynchronous Requests with Matrix**: Fully non-blocking, async tasks powered by Matrix.
- **Fluent API**: Use a fluent interface to configure your requests on both synchronous and asynchronous calls.
- **HTTPX-Like Developer Experience**: A developer-friendly API inspired by Python's HTTPX.

---

## Installation

To install Fetch PHP, run:

```bash
composer require jerome/fetch-php
```

---

## Usage Examples

### 1. Using the `fetch` Helper for Simple Requests

Fetch PHP provides a **fetch** helper function that supports both **synchronous** and **asynchronous** requests, offering a **fluent API** to chain methods for configuring your requests.

#### Example: Basic GET Request

##### Synchronous Request

```php
<?php

require 'vendor/autoload.php';

$response = fetch('https://jsonplaceholder.typicode.com/todos/1', [
    'headers' => ['Authorization' => 'Bearer token']
    'query' => ['page' => 1, 'limit' => 10]
    'timeout' => 5
]);

// Get the JSON response
$data = $response->json();
print_r($data);

// Get the status text (e.g., "OK")
echo $response->statusText();
```

##### Asynchronous Request

```php
<?php

require 'vendor/autoload.php';

async(fn () => fetch('https://jsonplaceholder.typicode.com/todos/1', [
    'headers' => ['Authorization' => 'Bearer token']
    'query' => ['page' => 1, 'limit' => 10]
    'timeout' => 5
]))
    ->then(fn ($response) => print_r($response->json()))
    ->catch(fn ($error) => echo "Request failed: " . $error->getMessage());
```

---

### 2. Fluent API on `fetch`

The `fetch` helper supports a **fluent API**, which allows developers to configure HTTP requests in a clear and readable manner. This is particularly useful for building complex requests involving headers, query parameters, retries, and more.

#### Example: Fluent API on Synchronous Requests

```php
<?php

$response = fetch()
    ->withHeaders(['Authorization' => 'Bearer token'])
    ->withQueryParameters(['page' => 1, 'limit' => 10])
    ->get('https://example.com/api/data');

// Print the JSON response
print_r($response->json());
```

#### Example: Fluent API on Asynchronous Requests with `async()`

The **fluent API** also works seamlessly with asynchronous requests, powered by **Matrix**. Here's how you can structure an async request with the fluent interface.

```php
<?php

async(function () {
    return fetch()
        ->baseUri('https://jsonplaceholder.typicode.com')
        ->withHeaders(['Content-Type' => 'application/json'])
        ->withQueryParameters(['_limit' => 5])
        ->get('/posts');
    })
    ->then(fn ($response) => print_r($response->json()))
    ->catch(fn ($error) => echo $error->getMessage());
```

In the above example:

- **`baseUri()`**: Sets a base URI for the request, allowing you to define a root URL for relative paths like `/posts`.
- **`withHeaders()`**: Adds custom headers to the request.
- **`withQueryParameters()`**: Appends query parameters to the URL.

---

### 3. Asynchronous Requests with Matrix

Fetch PHP’s asynchronous requests are powered by **Matrix**, enabling true non-blocking execution. The `async()` method, combined with `then()` and `catch()`, mirrors JavaScript's promises, making asynchronous PHP development simpler and more efficient.

#### Example: Async GET Request with Fluent API

```php
<?php

async(function () {
    return fetch()
        ->withHeaders(['Authorization' => 'Bearer token'])
        ->get('https://example.com/api/data');
    })
    ->then(function ($response) {
        print_r($response->json());
    })
    ->catch(function ($exception) {
        echo "Request failed: " . $exception->getMessage();
    });
```

---

## Fluent API Overview

The **fluent API** makes it easy to chain methods for building HTTP requests. You can use it in both **synchronous** and **asynchronous** requests.

### Available Fluent Methods

- **`baseUri(string $uri)`**: Set the base URI for the request (especially useful when making multiple requests to the same API).
- **`withHeaders(array $headers)`**: Set custom HTTP headers.
- **`withBody(mixed $body)`**: Set the request body for POST, PUT, PATCH requests.
- **`withQueryParameters(array $queryParams)`**: Set query parameters for GET requests.
- **`timeout(int $seconds)`**: Set a timeout for the request.
- **`retry(int $retries, int $delay)`**: Set the number of retries and the delay between retries.
- **`async()`**: Set the request to be asynchronous.
- **`withProxy($proxy)`**: Set a proxy for the request.

#### Example: Fluent API for POST Requests

```php
<?php

$response = fetch()
    ->withHeaders(['Authorization' => 'Bearer token'])
    ->withBody(json_encode(['title' => 'New Post', 'body' => 'Post content']))
    ->post('https://example.com/api/posts');

// Check the response
if ($response->getStatusCode() === 201) {
    echo "Post created successfully!";
}
```

---

## Custom Guzzle Client

If needed, Fetch PHP allows you to pass a custom Guzzle client for requests. This gives you full control over the client configuration, such as setting base URIs, timeouts, and headers.

#### Example: Custom Guzzle Client

```php
<?php

use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://api.example.com',
    'timeout' => 5
]);

$response = fetch('/endpoint', ['client' => $client]);

print_r($response->json());
```

---

## Matrix-Powered Asynchronous Engine

Fetch PHP’s asynchronous requests are powered by the **Matrix** engine, allowing for true non-blocking execution. The `async` method, combined with `then()` and `catch()`, mirrors JavaScript's promises, making asynchronous PHP development simple and efficient.

---

## Response Object

The `Response` class provides convenient methods for interacting with HTTP responses, including decoding JSON, retrieving plain text, and checking status codes.

### Response Methods

- **`json(bool $assoc = true)`**: Decodes the response body as JSON (returns associative array or object).
- **`text()`**: Retrieves the response body as plain text.
- **`blob()`**: Retrieves the response body as a PHP stream (like a "blob").
- **`arrayBuffer()`**: Returns the response body as binary data.
- **`statusText()`**: Returns the HTTP status text (e.g., "OK").
- **`ok()`**: Returns `true` if the status code is 2xx.
- **`isClientError()`**, **`isServerError()`**: Helpers for status codes in the 4xx and 5xx ranges.

---

## Retry Logic and Timeout Handling

Both synchronous and asynchronous requests support **retries** and **timeouts**.

#### Example: Retry Failed Requests with Timeout

```php
<?php

$response = fetch()
    ->withHeaders(['Authorization' => 'Bearer token'])
    ->timeout(5)
    ->retry(3) // Retry up to 3 times
    ->get('https://example.com/api/data');

if ($response->ok()) {
    print_r($response->json());
} else {
    echo "Request failed after retries.";
}
```

---

## License

Fetch PHP is licensed under the MIT License. See the [LICENSE.md](LICENSE.md) file for details.

---

## Contributing

Contributions are welcome! Feel free to fork the repository, make improvements, and submit a pull request.

Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/amazing-feature`)
3. Commit your Changes (`git commit -m 'Add some amazing-feature'`)
4. Push to the Branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Authors

- **[Jerome Thayananthajothy]** - *Initial work* - [Thavarshan](https://github.com/Thavarshan)

See also the list of [contributors](https://github.com/Thavarshan/fetch-php/contributors) who participated in this project.

## Acknowledgments

- Hat tip to Guzzle HTTP for their [Guzzle](https://github.com/guzzle/guzzle) package, which provided the basis for this project.

## **Get Involved**

Fetch PHP offers a JavaScript-like `fetch` API for making HTTP requests in PHP. It supports both synchronous and asynchronous requests and provides a flexible, easy-to-use interface for working with HTTP responses. **Star the repository on GitHub** to help Matrix grow and to stay updated on new features.
