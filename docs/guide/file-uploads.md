---
title: File Uploads
description: Learn how to upload files and work with multipart form data
---

# File Uploads

This guide explains how to upload files and work with multipart form data using the Fetch HTTP package.

## Basic File Upload

To upload a file, you need to use multipart form data. The simplest way is with the helper functions:

```php
use function Fetch\Http\fetch;

// Upload a file
$response = fetch('https://api.example.com/upload', [
    'method' => 'POST',
    'multipart' => [
        [
            'name' => 'file',
            'contents' => file_get_contents('/path/to/image.jpg'),
            'filename' => 'upload.jpg',
            'headers' => ['Content-Type' => 'image/jpeg']
        ],
        [
            'name' => 'description',
            'contents' => 'Profile picture upload'
        ]
    ]
]);

// Check if upload was successful
if ($response->successful()) {
    $result = $response->json();
    echo "File uploaded successfully. URL: " . $result['url'];
} else {
    echo "Upload failed with status: " . $response->status();
}
```

## Using ClientHandler for File Uploads

You can also use the ClientHandler class for more control:

```php
use Fetch\Http\ClientHandler;

$client = ClientHandler::create();
$response = $client->withMultipart([
    [
        'name' => 'file',
        'contents' => file_get_contents('/path/to/document.pdf'),
        'filename' => 'document.pdf',
        'headers' => ['Content-Type' => 'application/pdf']
    ],
    [
        'name' => 'document_type',
        'contents' => 'invoice'
    ],
    [
        'name' => 'description',
        'contents' => 'Monthly invoice #12345'
    ]
])->post('https://api.example.com/documents');
```

## Uploading Multiple Files

You can upload multiple files in a single request:

```php
$response = fetch('https://api.example.com/gallery', [
    'method' => 'POST',
    'multipart' => [
        [
            'name' => 'files[]',
            'contents' => file_get_contents('/path/to/image1.jpg'),
            'filename' => 'image1.jpg',
            'headers' => ['Content-Type' => 'image/jpeg']
        ],
        [
            'name' => 'files[]',
            'contents' => file_get_contents('/path/to/image2.jpg'),
            'filename' => 'image2.jpg',
            'headers' => ['Content-Type' => 'image/jpeg']
        ],
        [
            'name' => 'album',
            'contents' => 'Vacation Photos'
        ]
    ]
]);
```

## Uploading From a Stream

For large files, you can upload directly from a stream rather than loading the entire file into memory:

```php
// Open file as a stream
$stream = fopen('/path/to/large-file.zip', 'r');

$response = fetch('https://api.example.com/upload', [
    'method' => 'POST',
    'multipart' => [
        [
            'name' => 'file',
            'contents' => $stream,
            'filename' => 'large-file.zip',
            'headers' => ['Content-Type' => 'application/zip']
        ],
        [
            'name' => 'description',
            'contents' => 'Large file upload'
        ]
    ]
]);

// Don't forget to close the stream
fclose($stream);
```

## Using the Request Class

You can also create a multipart request using the Request class:

```php
use Fetch\Http\Request;
use Fetch\Enum\Method;

// Create a multipart request
$request = Request::multipart(
    Method::POST,
    'https://api.example.com/upload',
    [
        [
            'name' => 'file',
            'contents' => file_get_contents('/path/to/image.jpg'),
            'filename' => 'upload.jpg',
            'headers' => ['Content-Type' => 'image/jpeg']
        ],
        [
            'name' => 'description',
            'contents' => 'Profile picture upload'
        ]
    ]
);

// Send the request using fetch()
$response = fetch($request);
```

## File Upload with Progress Tracking

For large file uploads, you might want to track progress. This can be done using Guzzle's progress middleware:

```php
use Fetch\Http\ClientHandler;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Utils;

// Create a function to track upload progress
$progress = function ($totalBytes, $downloadedBytes, $uploadedBytes, $uploadTotal) {
    if ($uploadTotal > 0) {
        $percent = round(($uploadedBytes / $uploadTotal) * 100);
        echo "Upload progress: {$percent}% ({$uploadedBytes}/{$uploadTotal} bytes)\n";
    }
};

// Create a handler stack with the progress middleware
$stack = HandlerStack::create();
$stack->push(Middleware::tap(null, $progress));

// Create a custom Guzzle client with the stack
$guzzleClient = new Client([
    'handler' => $stack
]);

// Create a client handler with the custom client
$client = ClientHandler::createWithClient($guzzleClient);

// Create a file stream
$stream = Utils::streamFor(fopen('/path/to/large-file.mp4', 'r'));

// Upload the file
$response = $client->withMultipart([
    [
        'name' => 'file',
        'contents' => $stream,
        'filename' => 'video.mp4',
        'headers' => ['Content-Type' => 'video/mp4']
    ]
])->post('https://api.example.com/upload');

// The progress function will be called during the upload
```

## Handling File Upload Errors

File uploads can fail for various reasons. Here's how to handle common errors:

```php
try {
    $response = fetch('https://api.example.com/upload', [
        'method' => 'POST',
        'multipart' => [
            [
                'name' => 'file',
                'contents' => file_get_contents('/path/to/image.jpg'),
                'filename' => 'upload.jpg'
            ]
        ]
    ]);

    if ($response->isUnprocessableEntity()) {
        $errors = $response->json()['errors'] ?? [];
        foreach ($errors as $field => $messages) {
            echo "Error with {$field}: " . implode(', ', $messages) . "\n";
        }
    } elseif ($response->status() === 413) {
        echo "File too large. Maximum size exceeded.";
    } elseif (!$response->successful()) {
        echo "Upload failed with status: " . $response->status();
    } else {
        echo "Upload successful!";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Multipart Form Specifications

When setting up multipart form data, each part needs these elements:

- `name`: The form field name (required)
- `contents`: The content of the field (required) - can be a string, resource, or StreamInterface
- `filename`: The filename for file uploads (optional)
- `headers`: An array of headers for this part (optional)

## File Upload with Authentication

Many APIs require authentication for file uploads:

```php
$response = fetch('https://api.example.com/upload', [
    'method' => 'POST',
    'token' => 'your-api-token',  // Bearer token authentication
    'multipart' => [
        [
            'name' => 'file',
            'contents' => file_get_contents('/path/to/file.jpg'),
            'filename' => 'upload.jpg'
        ]
    ]
]);
```

Or using the ClientHandler:

```php
$response = ClientHandler::create()
    ->withToken('your-api-token')
    ->withMultipart([
        [
            'name' => 'file',
            'contents' => file_get_contents('/path/to/file.jpg'),
            'filename' => 'upload.jpg'
        ]
    ])
    ->post('https://api.example.com/upload');
```

## File Download

Although not strictly an upload, you might also need to download files:

```php
$response = fetch('https://api.example.com/files/document.pdf');

// Save the file to disk
file_put_contents('downloaded-document.pdf', $response->body());

// Or get it as a stream
$stream = $response->blob();
$fileContents = stream_get_contents($stream);
fclose($stream);
```

## Handling Large File Downloads

For large file downloads, you can use streaming:

```php
use Fetch\Http\ClientHandler;

$client = ClientHandler::create();
$response = $client
    ->withStream(true)  // Enable streaming
    ->get('https://api.example.com/large-files/video.mp4');

// Open a file to save the download
$outputFile = fopen('downloaded-video.mp4', 'w');

// Get the response body as a stream
$body = $response->getBody();

// Read the stream in chunks and write to the file
while (!$body->eof()) {
    fwrite($outputFile, $body->read(4096));  // Read 4KB at a time
}

// Close the file
fclose($outputFile);
```

## Best Practices

1. **Check File Size**: Verify file sizes before uploading to avoid timeouts or server rejections.

2. **Set Appropriate Timeouts**: Large file uploads may need longer timeouts:

   ```php
   $response = fetch('https://api.example.com/upload', [
       'method' => 'POST',
       'timeout' => 300,  // 5-minute timeout
       'multipart' => [/* ... */]
   ]);
   ```

3. **Use Streams for Large Files**: Avoid loading large files entirely into memory.

4. **Add Retry Logic**: File uploads are prone to network issues, so add retry logic:

   ```php
   $response = fetch('https://api.example.com/upload', [
       'method' => 'POST',
       'retries' => 3,
       'multipart' => [/* ... */]
   ]);
   ```

5. **Validate Before Uploading**: Check file types, sizes, and other constraints before uploading.

6. **Include Progress Tracking**: For large files, provide progress feedback to users.

7. **Log Upload Attempts**: Log uploads for troubleshooting and auditing.

## Next Steps

- Learn about [Authentication](/guide/authentication) for secured file uploads
- Explore [Error Handling](/guide/error-handling) for robust upload error management
- See [Retry Handling](/guide/retry-handling) for handling transient upload failures
