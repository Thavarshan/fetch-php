---
title: File Uploads
description: Send multipart form data and file streams through Guzzle request options.
---

# File Uploads

Use Guzzle's `multipart` request option for multipart form submissions. Each part is an associative array; file contents can be supplied as a stream resource.

```php
$stream = fopen('/path/to/document.pdf', 'rb');

try {
    $response = fetch('https://api.example.test/v1/uploads', [
        'method' => 'POST',
        'multipart' => [
            ['name' => 'title', 'contents' => 'Quarterly report'],
            [
                'name' => 'file',
                'contents' => $stream,
                'filename' => 'report.pdf',
                'headers' => ['Content-Type' => 'application/pdf'],
            ],
        ],
    ]);
} finally {
    if (is_resource($stream)) {
        fclose($stream);
    }
}
```

Do not set the multipart `Content-Type` header manually: Guzzle generates the boundary. Validate upload size and type in your application, and avoid logging file contents or sensitive filenames. This illustrative example requires an application endpoint and is not run by the offline docs example suite.
