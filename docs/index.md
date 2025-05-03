---
layout: home

hero:
  name: "Fetch PHP"
  text: "The PHP library that brings the power of the JavaScript fetch API."
  tagline: "Synchronous and Asynchronous HTTP requests made easy with modern PHP."
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: API Reference
      link: /api/

features:
  - title: JavaScript-like API
    details: Fetch PHP mimics the intuitive syntax of JavaScript's `fetch` API for both synchronous and asynchronous requests.
  - title: True Asynchronous Support
    details: Powered by Matrix, a custom PHP async engine, Fetch PHP allows for true async task management with fine-grained control.
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
use function async;
use function await;

async(fn () => fetch('https://api.example.com/users'))
    ->then(fn ($response) => $response->json())
    ->catch(fn ($error) => handleError($error));

// With async/await pattern
$response = await(async(fn () => fetch('https://api.example.com/users')));
$users = $response->json();
```

---
