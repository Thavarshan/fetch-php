---
title: Installation
description: How to install the Fetch HTTP package in your PHP project
---

# Installation

The Fetch HTTP package can be installed using Composer, the PHP dependency manager.

## Requirements

- PHP 8.1 or higher
- Composer

## Install via Composer

### 1. Add to your project

```bash
composer require jerome/fetch-php
```

### 2. Update your autoloader

If you haven't already done so, make sure you include the Composer autoloader in your project:

```php
require __DIR__ . '/vendor/autoload.php';
```

## Manual Installation (Not Recommended)

While we strongly recommend using Composer, you can also manually download the package and include it in your project:

1. Download the latest release from [GitHub](https://github.com/Thavarshan/fetch-php/releases)
2. Extract the files into your project directory
3. Set up your own autoloading system or include files manually

## Verifying Installation

After installation, you can verify that everything is working correctly by creating a simple script:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

// Import the helper function
use function Fetch\Http\fetch;

// Make a simple request
$response = fetch('https://httpbin.org/get');

// Check the response
if ($response->successful()) {
    echo "Installation successful!\n";
    echo "Response status: " . $response->status() . "\n";
    echo "Response body: " . $response->body() . "\n";
} else {
    echo "Something went wrong. HTTP status: " . $response->status() . "\n";
}
```

## Next Steps

After installation, check out the [Quickstart](/guide/quickstart) guide to begin using the Fetch HTTP package.
