# Overview

The FetchPHP API reference provides detailed documentation on all the available functions, classes, and methods within the FetchPHP library. FetchPHP offers a robust, flexible, and easy-to-use interface for making HTTP requests in PHP, providing both synchronous and asynchronous capabilities, with an API similar to JavaScript’s `fetch()`.

This section will cover the core features and functionality of FetchPHP, including:

- **`fetch()` Function**: The main function for performing HTTP requests, modeled after the JavaScript `fetch()` API.
- **ClientHandler Class**: A fluent API for constructing and sending HTTP requests with flexible options.
- **Response Handling**: Methods for parsing and interacting with the response data returned from requests.
- **Asynchronous Requests**: A guide on making async requests using PHP Fibers and managing tasks.
- **Task Lifecycle Management**: Features for pausing, resuming, retrying, and canceling asynchronous tasks.

## Key Components

### **`fetch()` Function**

The `fetch()` function is the core API for making HTTP requests. It allows you to perform synchronous and asynchronous requests, similar to the JavaScript `fetch()` API. It supports various HTTP methods, flexible configuration through options, and automatic JSON handling.

For more details, check the [fetch() API Reference](./fetch.md).

### **ClientHandler Class**

The `ClientHandler` class provides a fluent interface for constructing complex HTTP requests. By chaining methods like `withHeaders()`, `withBody()`, and `withToken()`, you can easily build and send requests with full control over the request's configuration.

For more information, refer to the [ClientHandler API](./client-handler.md).

### **Response Handling**

FetchPHP’s response object allows you to interact with the data returned from HTTP requests. You can easily parse JSON, retrieve status codes, and access response headers. The `Response` class provides methods like `json()`, `text()`, `status()`, and `headers()` for processing responses.

Learn more about response handling in the [Response API](./response.md).

### **Asynchronous Requests**

FetchPHP enables asynchronous HTTP requests using PHP Fibers, providing true concurrency. The `async()` function allows you to perform non-blocking requests while handling the results using `.then()` and `.catch()` for success and error scenarios. You can also manage the lifecycle of asynchronous tasks, including pausing, resuming, canceling, and retrying tasks.

Explore the [Async API](https://fetch-php.thavarshan.com/guide/async-requests.md) for details on making asynchronous requests.

### **Task Lifecycle Management**

For asynchronous tasks, FetchPHP provides control mechanisms to manage long-running processes or tasks. The `Task` class, powered by the Matrix package, allows you to start, pause, resume, cancel, and retry tasks, making it ideal for handling asynchronous workflows that require fine-grained control.

Refer to the [Task Management API](https://github.com/Thavarshan/matrix) for more information.

### Error Handling

FetchPHP offers robust error-handling mechanisms for both synchronous and asynchronous requests. You can manage exceptions using `try/catch` blocks, disable automatic HTTP error exceptions, and implement custom retry logic for failed requests.

Detailed information can be found in the [Error Handling API](https://fetch-php.thavarshan.com/guide/error-handling.md).
