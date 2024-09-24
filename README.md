[![Fetch PHP](./assets/Banner.jpg)](https://github.com/Thavarshan/fetch-php)

# About Fetch PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jerome/fetch-php.svg)](https://packagist.org/packages/jerome/fetch-php)
[![Tests](https://github.com/Thavarshan/fetch-php/actions/workflows/run-tests.yml/badge.svg?label=tests&branch=main)](https://github.com/Thavarshan/fetch-php/actions/workflows/run-tests.yml)
[![Check & fix styling](https://github.com/Thavarshan/fetch-php/actions/workflows/php-cs-fixer.yml/badge.svg?label=code%20style&branch=main)](https://github.com/Thavarshan/fetch-php/actions/workflows/php-cs-fixer.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/jerome/fetch-php.svg)](https://packagist.org/packages/jerome/fetch-php)

FetchPHP is a PHP library that mimics the behavior of JavaScript’s `fetch` API using the powerful Guzzle HTTP client. FetchPHP supports both synchronous and asynchronous requests and provides an easy-to-use, flexible API for making HTTP requests in PHP.

## **Installation**

To install FetchPHP, run the following command:

```bash
composer require jerome/fetch-php
```

---

## **Core Functions Overview**

FetchPHP provides two main functions:

1. **`fetch`** – Performs a **synchronous** HTTP request.
2. **`fetch_async`** – Performs an **asynchronous** HTTP request and returns a Guzzle `PromiseInterface`.

> **Deprecation Warning:** The function `fetchAsync` has been deprecated in version `1.1.0`. Please use `fetch_async` instead. If you continue using `fetchAsync`, you will see a deprecation warning in your code.

---

### **Guzzle Client Instantiation**

FetchPHP uses a **singleton** pattern within the `Http` class to manage the Guzzle HTTP client. This ensures that the same client instance is reused across multiple requests unless a custom client is explicitly provided. This refactor resolves the issues with static variables being shared across all requests globally.

#### **Custom Guzzle Client Handling**

If you want to provide a custom Guzzle client (with custom configurations or middleware), you can pass it through the `options` parameter. This allows you to add middleware for things like logging, retries, caching, etc.

```php
<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

// Create a Guzzle handler stack with custom middleware
$stack = HandlerStack::create();

// Add custom middleware to the handler stack
$stack->push(Middleware::mapRequest(function (RequestInterface $request) {
    // Example middleware to modify the request
    return $request->withHeader('X-Custom-Header', 'Value');
}));

// Create a Guzzle client with middleware
$client = new Client([
    'handler' => $stack,
    'base_uri' => 'https://jsonplaceholder.typicode.com',
    'timeout' => 10.0,
]);

// Pass the custom Guzzle client into the fetch function via options
$response = fetch('/todos/1', [
    'client' => $client
]);

// The custom Guzzle client will only be used for this request
$response2 = fetch('/todos/2', [
    'client' => $client
]);

print_r($response->json());
print_r($response2->json());
```

### **Why is the Singleton Guzzle Client Still Useful?**

- The singleton pattern ensures the client instance is reused across multiple requests to avoid creating new client instances unnecessarily.
- You can still pass a custom client for specific requests as needed.
- The client lifecycle is now more controlled, ensuring that the potential issues with static variables and shared state have been resolved.

---

### **1. Synchronous Requests with `fetch`**

The `fetch` function performs an HTTP request and returns a `Response` object, which provides convenient methods to interact with the response.

#### **Basic GET Request Example**

```php
<?php

require 'vendor/autoload.php';

$response = fetch('https://jsonplaceholder.typicode.com/todos/1');

// Get the JSON response
$data = $response->json();
print_r($data);

// Get the status text (e.g., "OK")
echo $response->statusText();
```

#### **Available Response Methods**

The `Response` class provides various methods to interact with the response data, similar to how Laravel's response works but with FetchPHP-specific enhancements:

- **`json(bool $assoc = true)`**: Decodes the response body as JSON. If `$assoc` is `true`, it returns an associative array. If `false`, it returns an object. Throws a `RuntimeException` if JSON decoding fails.
- **`text()`**: Returns the response body as plain text.
- **`blob()`**: Returns the response body as a PHP stream resource, which can be used like a "blob."
- **`arrayBuffer()`**: Returns the raw binary data from the response body as a string.
- **`status()`**: Returns the HTTP status code (e.g., `200` for success, `404` for not found). This replaces the old `getStatusCode()` method, but remains backwards compatible.
- **`statusText()`**: Returns the HTTP status text (e.g., "OK" for `200`).
- **`content()`**: Returns the raw response content as a string.
- **`setStatus(int $code)`**: Sets a custom status code on the response.
- **`header(string $key)`**: Retrieves a specific header from the response.
- **`headers()`**: Retrieves all headers as an associative array.
- **`withHeaders(array $headers)`**: Sets new headers for the response and returns a new instance with the headers applied.
- **`addHeader(string $key, string $value)`**: Adds a value to an existing header or creates a new header if it doesn't exist.
- **`ok()`**: Returns `true` if the status code is between `200-299`.
- **`isInformational()`**: Returns `true` if the status code is between `100-199`.
- **`isRedirection()`**: Returns `true` if the status code is between `300-399`.
- **`isClientError()`**: Returns `true` if the status code is between `400-499`.
- **`isServerError()`**: Returns `true` if the status code is between `500-599`.

---

### **2. Asynchronous Requests with `fetch_async`**

The `fetch_async` function returns a `PromiseInterface` object. You can use the `.then()` and `.wait()` methods to manage the asynchronous flow.

#### **Basic Asynchronous GET Request Example**

```php
<?php

require 'vendor/autoload.php';

$data = [];

$promise = fetch_async('https://jsonplaceholder.typicode.com/todos/1');

$promise->then(function ($response) use (&$data) {
    $data = $response->json();
    print_r($data);
});

// Wait for the promise to resolve
$promise->wait();

echo "Data received: " . $data['title'];
```

#### **Error Handling in Asynchronous Requests**

You can handle errors with the `catch` or `then` method of the promise:

```php
$promise = fetch_async('https://nonexistent-url.com');

$promise->then(function ($response) {
    // handle success
}, function ($exception) {
    // handle failure
    echo "Request failed: " . $exception->getMessage();
});

$promise->wait();
```

---

## **Request Options**

FetchPHP accepts an array of options as the second argument in both `fetch` and `fetch_async`. These options configure how the request is handled.

### **Available Request Options**

- **`method`**: HTTP method (e.g., `'GET'`, `'POST'`, `'PUT'`, `'DELETE'`). Default is `'GET'`.
- **`headers`**: An array of HTTP headers (e.g., `['Authorization' => 'Bearer token']`).
- **`body`**: Request body for POST, PUT, PATCH requests.
- **`json`**: JSON data to send as the request body. Automatically sets `Content-Type: application/json`.
- **`multipart`**: An array of multipart form data for file uploads.
- **`query`**: Associative array of query parameters.
- **`timeout`**: Timeout in seconds. Default is `10`.
- **`allow_redirects`**: Whether to follow redirects (`true`/`false`). Default is `true`.
- **`cookies`**: Boolean to enable cookies. If `true`, a new `CookieJar` is used. You can also pass an instance of `GuzzleHttp\Cookie\CookieJar`.
- **`auth`**: Array for HTTP Basic or Digest authentication.
- **`proxy`**: Proxy server URL to route requests through.
- **`client`**: A custom instance of Guzzle Client (e.g., singleton) to be reused for multiple requests.

---

### **Advanced Usage Examples**

#### **1. POST Request with JSON Data**

```php
<?php

$response = fetch('https://jsonplaceholder.typicode.com/posts', [
    'method' => 'POST',
    'json' => [
        'title' => 'My Post',
        'body' => 'This is the body of the post',
        'userId' => 1,
    ],
]);

print_r($response->json());
```

#### **2. GET Request with Query Parameters**

```php
<?php

$response = fetch('https://jsonplaceholder.typicode.com/posts', [
    'query' => ['userId' => 1],
]);

print_r($response->json());
```

#### **3. File Upload with Multipart Data**

```php
<?php

$response = fetch('https://example.com/upload', [
    'method' => 'POST',
    'multipart' => [
        [
            'name' => 'file',
            'contents' => fopen('/path/to/file.jpg', 'r'),
            'filename' => 'file.jpg'
        ],
    ]
]);

echo $response->statusText();
```

---

### **Detailed Request Customization**

#### **Custom Headers**

You can specify custom headers using the `headers` option:

```php
<?php

$response = fetch('https://example.com/endpoint', [
    'method' => 'POST',
    'headers' => [
        'Authorization' => 'Bearer YOUR_TOKEN',
        'Accept' => 'application/json'
    ],
    'json' => [
        'key' => 'value'
    ]
]);

print_r($response->json());
```

#### **Handling Cookies**

To enable cookies, set the `cookies` option to `true` or pass a `CookieJar` instance:

```php
<?php

use GuzzleHttp\Cookie\CookieJar;

$jar = new CookieJar();

$response = fetch('https://example.com', [
    'cookies' => $jar
]);

print_r($response->json());
```

#### **Timeouts and Redirects**

You can control timeouts and whether redirects are followed:

```php
<?php

$response = fetch('https://example.com/slow-request', [
    'timeout' => 5,   // 5-second timeout
    'allow_redirects' => false
]);

echo $response->statusText();
```

---

### **Error Handling**

FetchPHP gracefully handles errors, returning a `500` status code and error message in the response when a request fails.

#### **Handling Errors in Synchronous Requests**

```php
<?php

$response = fetch('https://nonexistent-url.com');

echo $response->status();  // Outputs 500
echo $response->text();  // Outputs error message
```

#### **Handling Errors in Asynchronous Requests**

```php
<?php

$promise = fetch_async('https://nonexistent-url.com');

$promise->then(function ($response) {
    echo $response->text();
}, function ($exception) {
    echo "Request failed: " . $exception->getMessage();
});

$promise->wait();
```

---

### **Proxy Support**

FetchPHP allows requests to be routed through a proxy server using the `proxy` option:

```php
<?php

$response = fetch('https://example.com', [
    'proxy' => 'tcp://localhost:8080'
]);

echo $response->statusText();
```

---

### **Authentication**

You can specify HTTP Basic or Digest authentication using the `auth` option:

```php
<?php

$response = fetch('https://example.com/secure-endpoint', [
    'auth' => ['username', 'password']
]);

echo $response->statusText();
```

---

### **Working with the Response Object**

The `Response` class provides convenient methods for interacting with the response body, headers, and status codes. For a detailed overview of available methods, refer to the **Available Response Methods** section above.

#### **Example: Accessing Response Data**

```php
<?php

$response = fetch('https://jsonplaceholder.typicode.com/todos/1');

// Get the response body as JSON
$data = $response->json();
print_r($data);

// Get the status text (e.g., "OK")
echo $response->statusText();
```

---

### **Comprehensive Usage Example**

```php
<?php

require 'vendor/autoload.php';

// POST request with JSON data, custom headers, query parameters, and authentication
$response = fetch('https://api.example.com/data', [
    'method' => 'POST',
    'headers' => [
        'Authorization' => 'Bearer YOUR_TOKEN',
        'Custom-Header' => 'MyHeaderValue'
    ],
    'query' => ['param1' => 'value1'],
    'json' => ['key' => 'value'],
    'auth' => ['username', 'password'],
    'timeout' => 10,
    'allow_redirects' => true
]);

if ($response->ok()) {
    // Print the JSON response
    print_r($response->json());
} else {
    echo "Error: " . $response->statusText();
}
```

---

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

## Contributing

Contributions are what make the open-source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repository and create a pull request. You can also simply open an issue with the tag "enhancement".

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
