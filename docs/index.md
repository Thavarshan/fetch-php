---
layout: home
hero:
  name: Fetch PHP
  text: The JavaScript fetch API for PHP
  tagline: Modern, simple HTTP client with a familiar API
  image:
    src: /logo.png
    alt: Fetch PHP
  actions:
    - theme: brand
      text: Get Started
      link: /guide/quickstart
    - theme: alt
      text: View on GitHub
      link: https://github.com/Thavarshan/fetch-php

features:
  - title: Familiar API
    details: If you know JavaScript's fetch() API, you'll feel right at home with Fetch PHP's intuitive interface.
    icon: 🚀
  - title: Promise-Based Async
    details: Support for async/await-style programming with promises for concurrent HTTP requests.
    icon: ⚡
  - title: Fluent Interface
    details: Chain methods together for clean, expressive code that's easy to read and maintain.
    icon: 🔗
  - title: Helper Functions
    details: Simple global helpers like get(), post(), and fetch() for quick and easy HTTP requests.
    icon: 🧰
  - title: PSR Compatible
    details: Implements PSR-7 (HTTP Messages), PSR-18 (HTTP Client), and PSR-3 (Logging) standards.
    icon: 🔄
  - title: Powerful Responses
    details: Rich Response objects with methods for JSON parsing, XML handling, and more.
    icon: 📦
---

## The Modern HTTP Client for PHP

```php
// Quick API requests with fetch()
$response = fetch('https://api.example.com/users');
$users = $response->json();

// Or use HTTP method helpers
$user = post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
])->json();
```

### Flexible Authentication

```php
// Bearer token auth
$response = fetch('https://api.example.com/me', [
    'token' => 'your-oauth-token'
]);

// Basic auth
$response = fetch('https://api.example.com/private', [
    'auth' => ['username', 'password']
]);
```

### Powerful Async Support

```php
// Create promises for parallel requests
$usersPromise = fetch()->async()->get('https://api.example.com/users');
$postsPromise = fetch()->async()->get('https://api.example.com/posts');

// Wait for all to complete
fetch()->all(['users' => $usersPromise, 'posts' => $postsPromise])
    ->then(function ($results) {
        // Process results from both requests
        $users = $results['users']->json();
        $posts = $results['posts']->json();
    });
```

### Why Fetch PHP?

Fetch PHP brings the simplicity of JavaScript's fetch API to PHP, while adding powerful features like retry handling, promise-based asynchronous requests, and fluent interface for request building. It's designed to be both simple for beginners and powerful for advanced users.

### Getting Started

```bash
composer require jerome/fetch-php
```

Read the [quick start guide](/guide/quickstart) to begin working with Fetch PHP.
