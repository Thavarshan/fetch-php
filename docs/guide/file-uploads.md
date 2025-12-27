---
title: File Uploads
description: Learn how to upload files and work with multipart form data
---

# File Uploads

This guide explains how to upload files and work with multipart form data using the Fetch HTTP package.

## Basic File Upload

To upload a file, you need to use multipart form data. The simplest way is with the helper functions:

```php
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
} catch (\Fetch\Exceptions\NetworkException $e) {
    echo "Network error: " . $e->getMessage();
} catch (\Fetch\Exceptions\RequestException $e) {
    echo "Request error: " . $e->getMessage();
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

## Using Enums for Content Types

You can use the `ContentType` enum for specifying content types in your file uploads:

```php
use Fetch\Enum\ContentType;
use Fetch\Http\ClientHandler;

$response = ClientHandler::create()
    ->withMultipart([
        [
            'name' => 'file',
            'contents' => file_get_contents('/path/to/document.pdf'),
            'filename' => 'document.pdf',
            'headers' => ['Content-Type' => ContentType::PDF->value]
        ],
        [
            'name' => 'image',
            'contents' => file_get_contents('/path/to/image.jpg'),
            'filename' => 'image.jpg',
            'headers' => ['Content-Type' => ContentType::JPEG->value]
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

// Or get it as a binary string
$binaryData = $response->arrayBuffer();
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

## Asynchronous File Uploads

For large files, you might want to use asynchronous uploads to prevent blocking:

```php
use function Matrix\Support\async;
use function Matrix\Support\await;

$result = await(async(function() {
    // Open file as a stream
    $stream = fopen('/path/to/large-file.zip', 'r');

    // Upload the file
    $response = await(async(function() use ($stream) {
        return fetch('https://api.example.com/upload', [
            'method' => 'POST',
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => $stream,
                    'filename' => 'large-file.zip'
                ]
            ],
            'timeout' => 300  // 5-minute timeout for large uploads
        ]);
    }));

    // Close the stream
    fclose($stream);

    return $response->json();
}));

echo "Upload complete with ID: " . $result['id'];
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
   $response = fetch_client()
       ->retry(3, 1000)  // 3 retries with 1 second initial delay
       ->retryStatusCodes([408, 429, 500, 502, 503, 504])
       ->withMultipart([/* ... */])
       ->post('https://api.example.com/upload');
   ```

5. **Validate Before Uploading**: Check file types, sizes, and other constraints before uploading.

6. **Include Progress Tracking**: For large files, provide progress feedback to users.

7. **Log Upload Attempts**: Log uploads for troubleshooting and auditing:

   ```php
   use Monolog\Logger;
   use Monolog\Handler\StreamHandler;

   // Create a logger
   $logger = new Logger('uploads');
   $logger->pushHandler(new StreamHandler('logs/uploads.log', Logger::INFO));

   // Set the logger on the client
   $client = fetch_client();
   $client->setLogger($logger);

   // Make the upload request
   $response = $client->withMultipart([/* ... */])
       ->post('https://api.example.com/upload');
   ```

## Next Steps

- Learn about [Authentication](/guide/authentication) for secured file uploads
- Explore [Error Handling](/guide/error-handling) for robust upload error management
- See [Retry Handling](/guide/retry-handling) for handling transient upload failures
- Check out [Asynchronous Requests](/guide/async-requests) for non-blocking uploads
