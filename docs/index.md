---
layout: home

hero:
  name: "Fetch PHP"
  text: "The PHP library that brings the power of the JavaScript fetch API."
  tagline: "Synchronous and Asynchronous HTTP requests made easy with PHP Fibers."
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: API Reference
      link: /api/

features:
  - title: JavaScript-like API
    details: FetchPHP mimics the intuitive syntax of JavaScript's `fetch` API for both synchronous and asynchronous requests.
  - title: True Asynchronous Support
    details: Powered by PHP Fibers and Matrix, FetchPHP allows for true async task management with fine-grained control.
  - title: Fluent API for Flexibility
    details: Chain methods and build requests effortlessly, inspired by Laravel's HTTP client for flexible, readable code.
  - title: Retry and Concurrency
    details: Built-in retry mechanisms with exponential backoff and support for concurrent requests with all(), race(), and any().

---

## Simple, Intuitive, Powerful

```php
// Synchronous request
$response = fetch('https://api.example.com/users');
$users = $response->json();

// Asynchronous request with JavaScript-like syntax
use function Matrix\async;
use function Matrix\await;

async(fn () => fetch('https://api.example.com/users'))
    ->then(fn ($response) => $response->json())
    ->catch(fn ($error) => handleError($error));

// With async/await pattern
$response = await(async(fn () => fetch('https://api.example.com/users')));
$users = $response->json();
```

The added code example immediately shows how your library works and how it mirrors JavaScript's fetch API. The additional feature highlights another key aspect of your library's functionality.

Would you like to proceed with this updated landing page, or would you prefer something else?
