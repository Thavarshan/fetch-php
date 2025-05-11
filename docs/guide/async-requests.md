---
title: Asynchronous Requests
description: Learn how to make asynchronous HTTP requests with the Fetch HTTP package
---

# Asynchronous Requests

This guide explains how to work with asynchronous HTTP requests in the Fetch HTTP package. Asynchronous requests allow you to execute multiple HTTP operations in parallel, which can significantly improve performance when making multiple independent requests.

## Enabling Async Mode

To make asynchronous requests, you need to enable async mode:

```php
use Fetch\Http\ClientHandler;

// Create a client with async mode enabled
$client = ClientHandler::create()->async();

// Or enable async mode on an existing client
$client->async(true);
```

## Making Asynchronous Requests

When async mode is enabled, HTTP methods return promises instead of responses:

```php
// Get the client
$client = fetch_client();

// Make an async request
$promise = $client->async()->get('https://api.example.com/users');

// The promise represents a future response
// Do other work here while the request is in
