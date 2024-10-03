# Synchronous HTTP Requests

FetchPHP provides an easy-to-use API for making synchronous HTTP requests. You can use either the familiar JavaScript-like `fetch()` function or the powerful Fluent API to build and send requests.

## JavaScript-like Fetch API

Here’s an example of a basic synchronous GET request using the JavaScript-like `fetch()` function:

### **GET Request Example**

```php
$response = fetch('https://example.com/api/resource');

if ($response->ok()) {
    $data = $response->json();  // Parse the response as JSON
    print_r($data);
} else {
    echo "Error: " . $response->statusText();
}
```

### **POST Request Example with JSON**

```php
$response = fetch('https://example.com/api/resource', [
    'method' => 'POST',
    'headers' => [
        'Content-Type' => 'application/json',
    ],
    'body' => json_encode(['key' => 'value']),
]);

$data = $response->json();
echo $data['key'];
```

## Fluent API

The Fluent API in FetchPHP allows for more flexible request building by chaining methods. This API makes it easier to manage headers, body content, query parameters, authentication, and more, in a clean and readable way.

### **POST Request Example Using Fluent API**

```php
$response = fetch()
    ->baseUri('https://example.com')
    ->withHeaders('Content-Type', 'application/json')
    ->withBody(['key' => 'value'])
    ->withToken('fake-bearer-auth-token')
    ->post('/posts');

$data = $response->json();
```

In this example:

- `baseUri()`: Sets the base URI for the request.
- `withHeaders()`: Adds custom headers like `Content-Type`.
- `withBody()`: Sets the request body, which in this case is JSON.
- `withToken()`: Adds a Bearer token to the request for authentication.
- `post()`: Sends the request as a POST to the specified endpoint.

### **GET Request Example Using Fluent API**

```php
$response = fetch()
    ->baseUri('https://example.com')
    ->withQueryParameters(['page' => 2])
    ->withToken('fake-bearer-auth-token')
    ->get('/resources');

$data = $response->json();
```

In this example:

- `withQueryParameters()`: Adds query parameters to the request URL.
- `get()`: Sends a GET request to the `/resources` endpoint.

## Available Fluent API Methods

The following methods are available in FetchPHP’s Fluent API:

- **baseUri(string $uri)**: Set the base URI for the request.
- **withHeaders(array $headers)**: Add or modify headers for the request.
- **withBody(mixed $body)**: Set the request body (e.g., JSON, form data).
- **withQueryParameters(array $params)**: Add query parameters to the URL.
- **withToken(string $token)**: Add a Bearer token for authentication.
- **withAuth(string $username, string $password)**: Add Basic authentication credentials.
- **timeout(int $seconds)**: Set a timeout for the request in seconds.
- **retry(int $retries, int $delay = 100)**: Configure retry logic for failed requests.
- **withProxy(string|array $proxy)**: Add a proxy server for the request.
- **withCookies(bool|CookieJarInterface $cookies)**: Manage cookies for the request.
- **withRedirects(bool|array $redirects = true)**: Enable or disable redirects.
- **withCert(string|array $cert)**: Specify SSL certificates for secure requests.
- **withSslKey(string|array $sslKey)**: Provide an SSL key for the request.
- **withStream(bool $stream)**: Set the response to be streamed.

## Advanced Examples

### **PUT Request with Fluent API**

```php
$response = fetch()
    ->baseUri('https://example.com')
    ->withHeaders('Content-Type', 'application/json')
    ->withBody(json_encode(['key' => 'updated_value']))
    ->put('/resource/1');

$data = $response->json();
echo $data['key'];
```

### **DELETE Request with Fluent API**

```php
$response = fetch()
    ->baseUri('https://example.com')
    ->delete('/resource/1');

if ($response->ok()) {
    echo "Resource deleted successfully.";
} else {
    echo "Error: " . $response->statusText();
}
```

### **Using Proxies with Fluent API**

```php
$response = fetch()
    ->baseUri('https://example.com')
    ->withProxy('tcp://localhost:8080')
    ->get('/resource');

$data = $response->json();
```

### **Basic Authentication with Fluent API**

```php
$response = fetch()
    ->baseUri('https://example.com')
    ->withAuth('username', 'password')
    ->get('/secure-endpoint');

$data = $response->json();
```

## Handling Responses

FetchPHP provides several methods to handle and process the response data:

- **json()**: Parse the response body as JSON.
- **text()**: Get the raw response body as plain text.
- **statusText()**: Get the response's status text (e.g., "OK" for 200 responses).
- **ok()**: Returns `true` if the response status code is in the 2xx range.
- **status()**: Get the response status code (e.g., 200, 404, etc.).
- **headers()**: Retrieve the response headers as an associative array.

Example:

```php
$response = fetch('https://example.com/api/resource');

if ($response->ok()) {
    $data = $response->json();
    echo "JSON Response: " . print_r($data, true);
} else {
    echo "Error: " . $response->getStatusCode() . " - " . $response->statusText();
}
```

---

The Fluent API provides a more flexible and chainable way of building and sending HTTP requests with FetchPHP. For more advanced usage, check out the [Asynchronous Requests](./async-requests.md) page.
