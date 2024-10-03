# `fetch` Function API Reference

The `fetch()` function in FetchPHP is designed to mimic JavaScript’s `fetch()` API for making HTTP requests. It provides an easy-to-use interface for sending both synchronous and asynchronous requests, and supports flexible configuration through options.

---

## Function Signature

```php
function fetch(?string $url = null, ?array $options = []): \Fetch\Http\Response|\Fetch\Http\ClientHandler
```

---

## Parameters

### **`$url`** (string|null)

- The URL to which the HTTP request will be sent.
- If `null`, the function returns a `ClientHandler` instance, allowing for fluent chaining of methods before sending the request.

### **`$options`** (array|null)

- An associative array of options to customize the request.
- These options include HTTP method, headers, body, and other configurations.
- If not provided, default options are merged with any specified values.

---

## Return Type

- **`Response`**: If a URL is provided, the function sends an HTTP request and returns a `Fetch\Http\Response` object that contains the response data.
- **`ClientHandler`**: If no URL is provided, the function returns a `Fetch\Http\ClientHandler` object to allow for further configuration of the request before sending it.

---

## Behavior

The `fetch()` function sends an HTTP request and handles several common use cases, including:

1. **Method Specification**:
   - The HTTP method (e.g., `GET`, `POST`, `PUT`, `DELETE`) is specified in the `$options` array using the `'method'` key.
   - If no method is specified, `GET` is used by default.
   - The method is automatically uppercased for consistency.

2. **JSON Handling**:
   - If the `body` in the options array is an associative array, the function automatically converts it to a JSON string and sets the `Content-Type` header to `application/json`.

3. **Base URI Handling**:
   - If a `base_uri` is provided in the options, the function will append the URL to this base URI. The `base_uri` is removed from the options after concatenation.

4. **Exception Handling**:
   - The function catches any exceptions thrown during the request.
   - If the exception is a `RequestException` (from Guzzle) and a response is available, the function returns the response.
   - Otherwise, it rethrows the exception for further handling.

---

## Usage Examples

### **Basic GET Request**

```php
$response = fetch('https://example.com/api/resource');

if ($response->ok()) {
    $data = $response->json();
    print_r($data);
} else {
    echo "Error: " . $response->statusText();
}
```

### **POST Request with JSON Body**

```php
$response = fetch('https://example.com/api/resource', [
    'method' => 'POST',
    'headers' => [
        'Content-Type' => 'application/json',
    ],
    'body' => ['key' => 'value'],  // Automatically converted to JSON
]);

$data = $response->json();
echo $data['key'];
```

### **Using `ClientHandler` for Fluent API**

```php
$response = fetch()
    ->baseUri('https://example.com')
    ->withHeaders('Content-Type', 'application/json')
    ->withBody(['key' => 'value'])
    ->withToken('fake-bearer-auth-token')
    ->post('/posts');

$data = $response->json();
```

### **Error Handling**

```php
try {
    $response = fetch('https://example.com/nonexistent');

    if ($response->ok()) {
        $data = $response->json();
    } else {
        echo "Error: " . $response->statusText();
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
```

---

## Available Options

The `$options` array supports the following keys:

### **`method`** (string)

- The HTTP method to be used for the request (e.g., `GET`, `POST`, `PUT`, `DELETE`).
- Default: `GET`.

### **`headers`** (array)

- An associative array of headers to include in the request.
- Example: `['Authorization' => 'Bearer token']`.

### **`body`** (mixed)

- The body of the request. If this is an associative array, it will be converted to JSON and the `Content-Type` header will be set automatically.

### **`timeout`** (int)

- Timeout for the request in seconds.
- Default: 30 seconds.

### **`auth`** (array)

- An array for HTTP Basic or Digest authentication.
- Example: `['username', 'password']`.

### **`proxy`** (string|array)

- A proxy server URL or an associative array of proxy configurations.
- Example: `'tcp://localhost:8080'`.

### **`base_uri`** (string)

- A base URI to prepend to the URL for the request.
- Example: `'https://api.example.com'`.

### **`http_errors`** (bool)

- Set to `false` to disable throwing exceptions on HTTP error responses (4xx, 5xx).
- Default: `true`.

---

## Handling Responses

FetchPHP provides several methods for handling the response:

- **`json()`**: Parses the response body as JSON.
- **`text()`**: Returns the raw response body as plain text.
- **`statusText()`**: Returns the status text of the response (e.g., "OK" for 200 responses).
- **`ok()`**: Returns `true` if the response status code is 2xx.
- **`status()`**: Retrieves the HTTP status code of the response (e.g., 200, 404).
- **`headers()`**: Retrieves the response headers as an associative array.

---

## Error Handling

### **HTTP Errors**

FetchPHP throws exceptions for HTTP errors by default (4xx and 5xx status codes). This behavior can be disabled by setting the `http_errors` option to `false`.

```php
$response = fetch('https://example.com/not-found', [
    'http_errors' => false
]);

if (!$response->ok()) {
    echo "Error: " . $response->statusText();
}
```

### **Exception Handling**

Exceptions thrown by FetchPHP, such as network issues or invalid responses, can be caught using a `try/catch` block.

```php
try {
    $response = fetch('https://example.com/api');
    echo $response->text();
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
```
