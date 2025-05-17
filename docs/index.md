---
layout: home

# SEO-optimized title and description
title: Fetch PHP - Modern JavaScript-like HTTP Client for PHP
description: A modern HTTP client library that brings JavaScript's fetch API experience to PHP with async/await patterns, promise-based API, and powerful retry mechanics.

# SEO-optimized meta tags (these extend what's in config.js)
head:
  - - meta
    - name: keywords
      content: fetch php, javascript fetch in php, async await php, http client php, promise based http, php http, guzzle alternative
  - - meta
    - property: og:title
      content: Fetch PHP - JavaScript's fetch API for PHP
  - - meta
    - property: og:description
      content: Write HTTP requests just like you would in JavaScript with PHP's Fetch API. Modern, simple HTTP client with a familiar API.
  - - link
    - rel: canonical
      href: https://fetch-php.thavarshan.com/

# Your existing hero section (unchanged)
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

# Your existing features section (unchanged)
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

<!-- The rest of your existing content starts here, properly formatted with Markdown -->

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
$usersPromise = async(function() {
    return fetch('https://api.example.com/users');
});

$postsPromise = async(function() {
    return fetch('https://api.example.com/posts');
});

// Wait for all to complete
all(['users' => $usersPromise, 'posts' => $postsPromise])
    ->then(function ($results) {
        // Process results from both requests
        $users = $results['users']->json();
        $posts = $results['posts']->json();
    });
```

### Modern Await-Style Syntax

```php
await(async(function() {
    // Process multiple requests in parallel
    $results = await(all([
        'users' => async(fn() => fetch('https://api.example.com/users')),
        'posts' => async(fn() => fetch('https://api.example.com/posts'))
    ]));

    // Work with results as if they were synchronous
    foreach ($results['users']->json() as $user) {
        echo $user['name'] . "\n";
    }
}));
```

## Why Fetch PHP?

Fetch PHP brings the simplicity of JavaScript's fetch API to PHP, while adding powerful features like retry handling, promise-based asynchronous requests, and fluent interface for request building. It's designed to be both simple for beginners and powerful for advanced users.

<div class="custom-block tip">
  <p><strong>Key Benefits:</strong></p>
  <ul>
    <li>JavaScript-like syntax that's familiar to full-stack developers</li>
    <li>Promise-based API with .then(), .catch(), and .finally() methods</li>
    <li>Built on Guzzle for rock-solid performance with an elegant API</li>
    <li>Type-safe enums for HTTP methods, content types, and status codes</li>
    <li>Automatic retry mechanics with exponential backoff</li>
  </ul>
</div>

## Getting Started

```bash
composer require jerome/fetch-php
```

Read the [quick start guide](/guide/quickstart) to begin working with Fetch PHP.

<!-- Add FAQ section for long-tail SEO keywords -->
## Frequently Asked Questions

### How does Fetch PHP compare to Guzzle?

While Guzzle is a powerful HTTP client, Fetch PHP enhances the experience by providing a JavaScript-like API, global client management, simplified requests, enhanced error handling, and modern PHP 8.1+ enums.

### Can I use Fetch PHP with Laravel or Symfony?

Yes! Fetch PHP works seamlessly with all PHP frameworks including Laravel, Symfony, CodeIgniter, and others. It requires PHP 8.1 or higher.

### Does Fetch PHP support file uploads?

Absolutely. Fetch PHP provides an elegant API for file uploads, supporting both single and multiple file uploads with progress tracking.

### Is Fetch PHP suitable for production use?

Yes. Fetch PHP is built on top of Guzzle, one of the most battle-tested HTTP clients in the PHP ecosystem, while providing a more modern developer experience.

<div class="custom-block warning">
  <p>Having trouble? <a href="https://github.com/Thavarshan/fetch-php/issues">Open an issue</a> on our GitHub repository.</p>
</div>
