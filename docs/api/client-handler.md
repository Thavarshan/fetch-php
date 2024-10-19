# `ClientHandler` Class API Reference

The `ClientHandler` class in FetchPHP provides a fluent API for building and sending HTTP requests. This class allows you to easily configure requests by chaining methods like `withHeaders()`, `withBody()`, `withToken()`, and more. It supports both synchronous and asynchronous requests, allowing flexible request handling based on your application's needs.

## Class Definition

```php
namespace Fetch\Http;

use Fetch\Interfaces\ClientHandler as ClientHandlerInterface;

class ClientHandler implements ClientHandlerInterface
```

The `ClientHandler` class is responsible for managing HTTP requests and providing methods to set various request options. It supports:

- Fluent API for constructing requests.
- Built-in retry and timeout mechanisms.
- Handling synchronous and asynchronous requests.
- Full support for configuring headers, body, query parameters, and more.

## Constructor

```php
public function __construct(
    ?ClientInterface $syncClient = null,
    array $options = [],
    ?int $timeout = null,
    ?int $retries = null,
    ?int $retryDelay = null,
    bool $isAsync = false
)
```

### Parameters

- **`$syncClient`** (ClientInterface|null): The synchronous HTTP client, typically an instance of Guzzle’s `Client`.
- **`$options`** (array): An array of options to configure the request, such as headers, method, etc.
- **`$timeout`** (int|null): Timeout in seconds for the request.
- **`$retries`** (int|null): Number of retries for the request in case of failure.
- **`$retryDelay`** (int|null): Delay in milliseconds between retries.
- **`$isAsync`** (bool): Indicates whether the request should be asynchronous.

## Available Methods

### **`handle()`**

```php
public static function handle(string $method, string $uri, array $options = []): mixed
```

Executes the HTTP request based on the provided method, URI, and options.

- **`$method`**: HTTP method (e.g., `GET`, `POST`).
- **`$uri`**: The URI for the request.
- **`$options`**: An array of request options.

**Returns**: A response object for synchronous requests or an `AsyncHelper` for asynchronous requests.

### **`baseUri()`**

```php
public function baseUri(string $baseUri): self
```

Sets the base URI for the request.

- **`$baseUri`**: The base URL for the request.

**Returns**: The `ClientHandler` instance for chaining.

### **`withHeaders()`**

```php
public function withHeaders(array $headers): self
```

Sets the headers for the request.

- **`$headers`**: An associative array of headers, where the key is the header name and the value is the header value.

**Returns**: The `ClientHandler` instance for chaining.

### **`withBody()`**

```php
public function withBody(array $body): self
```

Sets the request body.

- **`$body`**: The content of the body, which should be an array as key-value pairs and will be encoded as JSON.

**Returns**: The `ClientHandler` instance for chaining.

### **`withQueryParameters()`**

```php
public function withQueryParameters(array $queryParams): self
```

Adds query parameters to the request URL.

- **`$queryParams`**: An associative array of query parameters.

**Returns**: The `ClientHandler` instance for chaining.

### **`withToken()`**

```php
public function withToken(string $token): self
```

Adds a Bearer token for authentication.

- **`$token`**: The Bearer token string.

**Returns**: The `ClientHandler` instance for chaining.

### **`withAuth()`**

```php
public function withAuth(string $username, string $password): self
```

Adds Basic Authentication credentials.

- **`$username`**: Username for authentication.
- **`$password`**: Password for authentication.

**Returns**: The `ClientHandler` instance for chaining.

### **`timeout()`**

```php
public function timeout(int $seconds): self
```

Sets the timeout for the request.

- **`$seconds`**: Timeout in seconds.

**Returns**: The `ClientHandler` instance for chaining.

### **`retry()`**

```php
public function retry(int $retries, int $delay = 100): self
```

Configures retry logic for failed requests.

- **`$retries`**: Number of retry attempts.
- **`$delay`**: Delay in milliseconds between retries.

**Returns**: The `ClientHandler` instance for chaining.

### **`async()`**

```php
public function async(?bool $async = true): self
```

Enables asynchronous requests.

- **`$async`**: `true` to enable asynchronous requests.

**Returns**: The `ClientHandler` instance for chaining.

### **`withProxy()`**

```php
public function withProxy(string|array $proxy): self
```

Sets a proxy server for the request.

- **`$proxy`**: A proxy URL or an array of proxy configurations.

**Returns**: The `ClientHandler` instance for chaining.

### **`withCookies()`**

```php
public function withCookies(bool|CookieJarInterface $cookies): self
```

Adds cookies to the request.

- **`$cookies`**: Either `true` to enable cookies, or an instance of `CookieJarInterface` to manage cookies.

**Returns**: The `ClientHandler` instance for chaining.

### **`withRedirects()`**

```php
public function withRedirects(bool|array $redirects = true): self
```

Configures whether the request should follow redirects.

- **`$redirects`**: Either `true` to follow redirects, or an array of redirect options.

**Returns**: The `ClientHandler` instance for chaining.

### **`withCert()`**

```php
public function withCert(string|array $cert): self
```

Specifies SSL certificates for the request.

- **`$cert`**: A path to the certificate or an array of certificate options.

**Returns**: The `ClientHandler` instance for chaining.

### **`withSslKey()`**

```php
public function withSslKey(string|array $sslKey): self
```

Specifies the SSL key for the request.

- **`$sslKey`**: A path to the SSL key or an array of key options.

**Returns**: The `ClientHandler` instance for chaining.

### **`withStream()`**

```php
public function withStream(bool $stream): self
```

Configures whether the request should be streamed.

- **`$stream`**: `true` to enable streaming.

**Returns**: The `ClientHandler` instance for chaining.

### **`get()`**

```php
public function get(string $uri): mixed
```

Sends a `GET` request.

- **`$uri`**: The URI for the request.

**Returns**: The response for synchronous requests, or `AsyncHelper` for async requests.

### **`post()`**

```php
public function post(string $uri, mixed $body = null): mixed
```

Sends a `POST` request.

- **`$uri`**: The URI for the request.
- **`$body`**: Optional request body.

**Returns**: The response for synchronous requests, or `AsyncHelper` for async requests.

### **`put()`**

```php
public function put(string $uri, mixed $body = null): mixed
```

Sends a `PUT` request.

- **`$uri`**: The URI for the request.
- **`$body`**: Optional request body.

**Returns**: The response for synchronous requests, or `AsyncHelper` for async requests.

### **`delete()`**

```php
public function delete(string $uri): mixed
```

Sends a `DELETE` request.

- **`$uri`**: The URI for the request.

**Returns**: The response for synchronous requests, or `AsyncHelper` for async requests.

### **`options()`**

```php
public function options(string $uri): mixed
```

Sends an `OPTIONS` request.

- **`$uri`**: The URI for the request.

**Returns**: The response for synchronous requests, or `AsyncHelper` for async requests.

### **`isAsync()`**

```php
public function isAsync(): bool
```

Checks if the request is asynchronous.

**Returns**: `true` if the request is asynchronous, `false` otherwise.
