---
title: File Handling Examples
description: Examples of file uploads, downloads, and working with multipart requests
---

# File Handling Examples

This page provides examples of how to upload files, download files, and work with multipart form data using the Fetch HTTP package.

## Basic File Upload

Uploading a single file:

```php
use function Fetch\Http\fetch;

// Simple file upload
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
if ($response->isSuccess()) {
    $result = $response->json();
    echo "File uploaded successfully. URL: " . $result['url'];
} else {
    echo "Upload failed with status: " . $response->getStatusCode();
}
```

## Uploading Multiple Files

Uploading multiple files in a single request:

```php
use function Fetch\Http\fetch;

// Prepare the multipart data for multiple files
$multipart = [];

// Add first file
$multipart[] = [
    'name' => 'files[]',
    'contents' => file_get_contents('/path/to/image1.jpg'),
    'filename' => 'image1.jpg',
    'headers' => ['Content-Type' => 'image/jpeg']
];

// Add second file
$multipart[] = [
    'name' => 'files[]',
    'contents' => file_get_contents('/path/to/image2.jpg'),
    'filename' => 'image2.jpg',
    'headers' => ['Content-Type' => 'image/jpeg']
];

// Add third file
$multipart[] = [
    'name' => 'files[]',
    'contents' => file_get_contents('/path/to/document.pdf'),
    'filename' => 'document.pdf',
    'headers' => ['Content-Type' => 'application/pdf']
];

// Add metadata
$multipart[] = [
    'name' => 'album',
    'contents' => 'Vacation Photos'
];

// Send the request
$response = fetch('https://api.example.com/upload-multiple', [
    'method' => 'POST',
    'multipart' => $multipart
]);

// Process the response
if ($response->isSuccess()) {
    $result = $response->json();
    echo "Uploaded " . count($result['files']) . " files";

    foreach ($result['files'] as $file) {
        echo "- {$file['filename']}: {$file['url']}\n";
    }
} else {
    echo "Upload failed with status: " . $response->getStatusCode();
}
```

## Uploading From a Stream

For large files, you can upload directly from a stream:

```php
use function Fetch\Http\fetch;

// Open file as a stream
$stream = fopen('/path/to/large-file.zip', 'r');

// Upload the file
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
    ],
    'timeout' => 300  // Longer timeout for large uploads
]);

// Always close the stream
fclose($stream);

if ($response->isSuccess()) {
    $result = $response->json();
    echo "Large file uploaded successfully: " . $result['url'];
} else {
    echo "Upload failed with status: " . $response->getStatusCode();
}
```

## Upload with Progress Tracking

For large file uploads, you might want to track progress:

```php
use function Fetch\Http\fetch;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use function Fetch\Http\fetch_client;

// Create a progress callback
$onProgress = function (
    $downloadTotal,
    $downloadedBytes,
    $uploadTotal,
    $uploadedBytes
) {
    if ($uploadTotal > 0) {
        // Calculate percentage
        $percentComplete = round(($uploadedBytes / $uploadTotal) * 100, 1);
        echo "Upload progress: {$percentComplete}% complete\n";
    }
};

// Create a handler stack with the progress middleware
$stack = HandlerStack::create();
$stack->push(Middleware::tap(null, $onProgress));

// Create a custom Guzzle client
$client = new Client(['handler' => $stack]);

// Set the custom client for this request
fetch_client(['client' => $client]);

// Open file stream
$stream = fopen('/path/to/large-video.mp4', 'r');

// Upload with progress tracking
$response = fetch('https://api.example.com/upload', [
    'method' => 'POST',
    'multipart' => [
        [
            'name' => 'file',
            'contents' => $stream,
            'filename' => 'video.mp4',
            'headers' => ['Content-Type' => 'video/mp4']
        ],
        [
            'name' => 'title',
            'contents' => 'My Vacation Video'
        ]
    ],
    'timeout' => 600  // 10 minutes for large video
]);

// Close the stream
fclose($stream);

// Reset the global client
fetch_client(null, null, true);

if ($response->isSuccess()) {
    echo "Video uploaded successfully!";
} else {
    echo "Upload failed: " . $response->getStatusCode();
}
```

## File Download

Downloading a file to disk:

```php
use function Fetch\Http\fetch;

// Download a file
$response = fetch('https://example.com/files/document.pdf');

// Check if the download was successful
if ($response->isSuccess()) {
    // Save to disk
    file_put_contents('downloaded-document.pdf', $response->getBody()->getContents());

    echo "File downloaded successfully";
} else {
    echo "Download failed with status: " . $response->getStatusCode();
}
```

## Streaming Large File Downloads

For large file downloads, you can stream the response to disk:

```php
use function Fetch\Http\fetch;

// Enable streaming for the request
$response = fetch('https://example.com/files/large-video.mp4', [
    'stream' => true
]);

if ($response->isSuccess()) {
    // Open file for writing
    $outFile = fopen('downloaded-video.mp4', 'w');

    // Get the response body as a stream
    $body = $response->getBody();

    // Read the stream in chunks and write to the file
    while (!$body->eof()) {
        fwrite($outFile, $body->read(4096));  // Read 4KB at a time
    }

    // Close the file
    fclose($outFile);

    echo "Large file downloaded successfully";
} else {
    echo "Download failed with status: " . $response->getStatusCode();
}

// Reset the global client
fetch_client(null, null, true);
```

## Download with Progress Tracking

Similar to uploads, you can track download progress:

```php
use function Fetch\Http\fetch;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use function Fetch\Http\fetch_client;

// Create a progress callback
$onProgress = function (
    $downloadTotal,
    $downloadedBytes,
    $uploadTotal,
    $uploadedBytes
) {
    if ($downloadTotal > 0) {
        // Calculate percentage
        $percentComplete = round(($downloadedBytes / $downloadTotal) * 100, 1);
        echo "Download progress: {$percentComplete}% complete\n";
    }
};

// Create a handler stack with the progress middleware
$stack = HandlerStack::create();
$stack->push(Middleware::tap(null, $onProgress));

// Create a custom Guzzle client
$client = new Client(['handler' => $stack]);

// Set the custom client for this request
fetch_client(['client' => $client]);

// Download with progress tracking
$response = fetch('https://example.com/files/large-file.zip', [
    'stream' => true
]);

if ($response->isSuccess()) {
    // Open file for writing
    $outFile = fopen('downloaded-file.zip', 'w');

    // Get the response body as a stream
    $body = $response->getBody();

    // Read the stream in chunks and write to the file
    while (!$body->eof()) {
        fwrite($outFile, $body->read(4096));  // Read 4KB at a time
    }

    // Close the file
    fclose($outFile);

    echo "File downloaded successfully with progress tracking";
} else {
    echo "Download failed with status: " . $response->getStatusCode();
}

// Reset the global client
fetch_client(null, null, true);
```

## Resumable Downloads

Implementing a resumable download:

```php
use function Fetch\Http\fetch;

function downloadFileWithResume(string $url, string $localPath) {
    $fileSize = 0;
    $resume = false;

    // Check if the file already exists and has content
    if (file_exists($localPath) && ($fileSize = filesize($localPath)) > 0) {
        $resume = true;
        echo "Existing file found ({$fileSize} bytes). Attempting to resume...\n";
    }

    // Set up headers for resumable download
    $headers = [];
    if ($resume) {
        $headers['Range'] = "bytes={$fileSize}-";
        echo "Requesting range starting at byte {$fileSize}\n";
    }

    // Make the request
    $response = fetch($url, [
        'headers' => $headers,
        'stream' => true
    ]);

    // Check response status
    if ($resume && $response->getStatusCode() === 206) {
        // Partial content as expected for resume
        echo "Server supports resume. Continuing download...\n";
        $mode = 'a'; // Append mode
    } elseif ($response->isSuccess()) {
        // Full content - start from beginning
        echo "Starting new download...\n";
        $mode = 'w'; // Write mode
    } else {
        echo "Download failed with status: " . $response->getStatusCode() . "\n";
        return false;
    }

    // Open file for writing
    $file = fopen($localPath, $mode);

    // Get the response body as a stream
    $body = $response->getBody();

    // Get content length if available
    $contentLength = $response->getHeaderLine('Content-Length');
    $totalSize = $fileSize + (int)$contentLength;

    // Read the stream in chunks and write to the file
    $downloadedBytes = $fileSize;
    while (!$body->eof()) {
        $chunk = $body->read(4096);
        fwrite($file, $chunk);

        $downloadedBytes += strlen($chunk);

        if ($totalSize > 0) {
            $percent = round(($downloadedBytes / $totalSize) * 100, 1);
            echo "Progress: {$percent}% ({$downloadedBytes}/{$totalSize} bytes)\r";
        }
    }

    // Close the file
    fclose($file);

    echo "\nDownload complete!\n";
    return true;
}

// Usage
$url = 'https://example.com/files/large-dataset.zip';
$localPath = 'large-dataset.zip';
downloadFileWithResume($url, $localPath);
```

## Working with Image Files

Uploading and downloading images:

```php
use function Fetch\Http\fetch;

// Upload an image with resizing instructions
$response = fetch('https://api.example.com/images', [
    'method' => 'POST',
    'multipart' => [
        [
            'name' => 'image',
            'contents' => file_get_contents('/path/to/photo.jpg'),
            'filename' => 'photo.jpg',
            'headers' => ['Content-Type' => 'image/jpeg']
        ],
        [
            'name' => 'width',
            'contents' => '800'
        ],
        [
            'name' => 'height',
            'contents' => '600'
        ],
        [
            'name' => 'crop',
            'contents' => 'true'
        ]
    ]
]);

if ($response->isSuccess()) {
    $result = $response->json();
    echo "Image uploaded and processed. URLs:\n";
    echo "- Original: " . $result['original_url'] . "\n";
    echo "- Resized: " . $result['resized_url'] . "\n";

    // Download the processed image
    $processedImage = fetch($result['resized_url']);

    if ($processedImage->isSuccess()) {
        // Save to disk
        file_put_contents('processed-image.jpg', $processedImage->getBody()->getContents());
        echo "Processed image downloaded successfully";
    }
} else {
    echo "Image upload failed: " . $response->getStatusCode();
}
```

## File Upload with Base64 Encoding

Uploading a file with Base64 encoding:

```php
use function Fetch\Http\fetch;

// Read the file and encode it as base64
$filePath = '/path/to/document.pdf';
$fileData = file_get_contents($filePath);
$base64Data = base64_encode($fileData);

// Send the base64-encoded file
$response = fetch('https://api.example.com/documents', [
    'method' => 'POST',
    'json' => [
        'name' => 'Important Document',
        'file_data' => $base64Data,
        'file_type' => 'application/pdf'
    ]
]);

if ($response->isSuccess()) {
    $result = $response->json();
    echo "Document uploaded successfully. ID: " . $result['document_id'];
} else {
    echo "Upload failed: " . $response->getStatusCode();
}
```

## Handling File Upload Validation Errors

Processing validation errors for file uploads:

```php
use function Fetch\Http\fetch;
use Fetch\Enum\Status;

// Upload a file that might have validation issues
$response = fetch('https://api.example.com/upload', [
    'method' => 'POST',
    'multipart' => [
        [
            'name' => 'file',
            'contents' => file_get_contents('/path/to/large-file.zip'),
            'filename' => 'large-file.zip',
            'headers' => ['Content-Type' => 'application/zip']
        ]
    ]
]);

if ($response->isSuccess()) {
    $result = $response->json();
    echo "File uploaded successfully: " . $result['url'];
} elseif ($response->getStatus() === Status::UNPROCESSABLE_ENTITY) {
    // Handle validation errors (422)
    $errors = $response->json()['errors'] ?? [];

    echo "Validation errors:\n";
    foreach ($errors as $field => $messages) {
        echo "- {$field}: " . implode(', ', $messages) . "\n";
    }

    // Check for specific file errors
    if (isset($errors['file'])) {
        $fileErrors = $errors['file'];

        if (in_array('The file is too large', $fileErrors)) {
            echo "Try compressing the file or splitting it into smaller parts.";
        } elseif (in_array('Invalid file type', $fileErrors)) {
            echo "Only the following file types are allowed: " .
                 $response->json()['allowed_types'] ?? 'unknown';
        }
    }
} elseif ($response->getStatusCode() === 413) {
    // Request Entity Too Large
    echo "The file is too large for the server to process.";
} else {
    echo "Upload failed with status: " . $response->getStatusCode();
}
```

## Async File Operations

Uploading and downloading files asynchronously:

```php
use function Fetch\Http\fetch_client;

// Function to upload a file asynchronously
function uploadFileAsync($filePath, $description) {
    $client = fetch_client()->async();

    return $client
        ->withMultipart([
            [
                'name' => 'file',
                'contents' => file_get_contents($filePath),
                'filename' => basename($filePath),
                'headers' => ['Content-Type' => mime_content_type($filePath)]
            ],
            [
                'name' => 'description',
                'contents' => $description
            ]
        ])
        ->post('https://api.example.com/upload');
}

// Function to download a file asynchronously
function downloadFileAsync($url, $localPath) {
    return fetch_client()
        ->async()
        ->get($url)
        ->then(function ($response) use ($localPath) {
            if ($response->isSuccess()) {
                file_put_contents($localPath, $response->getBody()->getContents());
                return [
                    'success' => true,
                    'path' => $localPath,
                    'size' => filesize($localPath)
                ];
            }

            return [
                'success' => false,
                'error' => "Download failed with status: " . $response->getStatusCode()
            ];
        });
}

// Upload multiple files in parallel
$files = [
    '/path/to/file1.jpg' => 'First image',
    '/path/to/file2.pdf' => 'Important document',
    '/path/to/file3.zip' => 'Archive of resources'
];

$uploadPromises = [];
$client = fetch_client();

foreach ($files as $path => $description) {
    $uploadPromises[basename($path)] = uploadFileAsync($path, $description);
}

// Wait for all uploads to complete
$client->all($uploadPromises)
    ->then(function ($results) {
        echo "Upload results:\n";
        $downloadPromises = [];

        foreach ($results as $filename => $response) {
            if ($response->isSuccess()) {
                $data = $response->json();
                echo "- {$filename}: Success, URL: {$data['url']}\n";

                // Now download each file that was uploaded
                $downloadPromises[$filename] = downloadFileAsync(
                    $data['url'],
                    "downloads/{$filename}"
                );
            } else {
                echo "- {$filename}: Failed with status {$response->getStatusCode()}\n";
            }
        }

        // If we started any downloads, wait for them to complete
        if (!empty($downloadPromises)) {
            return fetch_client()->all($downloadPromises);
        }
    })
    ->then(function ($downloadResults) {
        if ($downloadResults) {
            echo "Download results:\n";
            foreach ($downloadResults as $filename => $result) {
                if ($result['success']) {
                    echo "- {$filename}: Downloaded to {$result['path']} ({$result['size']} bytes)\n";
                } else {
                    echo "- {$filename}: {$result['error']}\n";
                }
            }
        }
    });
```

## Creating a File Upload API Client

A complete file API client example:

```php
use function Fetch\Http\fetch;

class FileApiClient
{
    private string $baseUrl;
    private ?string $apiKey;

    public function __construct(string $baseUrl, string $apiKey = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
    }

    public function uploadFile(string $filePath, array $metadata = [])
    {
        // Check if the file exists
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        // Build the multipart request
        $multipart = [];

        // Add the file
        $multipart[] = [
            'name' => 'file',
            'contents' => file_get_contents($filePath),
            'filename' => basename($filePath),
            'headers' => ['Content-Type' => mime_content_type($filePath)]
        ];

        // Add metadata fields
        foreach ($metadata as $key => $value) {
            $multipart[] = [
                'name' => $key,
                'contents' => (string)$value
            ];
        }

        // Set up options
        $options = [
            'method' => 'POST',
            'multipart' => $multipart
        ];

        // Add authentication if API key is provided
        if ($this->apiKey) {
            $options['headers'] = [
                'Authorization' => "Bearer {$this->apiKey}"
            ];
        }

        // Make the request
        $response = fetch("{$this->baseUrl}/files", $options);

        // Handle errors
        if (!$response->isSuccess()) {
            $this->handleErrorResponse($response);
        }

        return $response->json();
    }

    public function listFiles(int $page = 1, int $perPage = 20, array $filters = [])
    {
        $query = array_merge(['page' => $page, 'per_page' => $perPage], $filters);

        $options = ['query' => $query];

        // Add authentication if API key is provided
        if ($this->apiKey) {
            $options['headers'] = [
                'Authorization' => "Bearer {$this->apiKey}"
            ];
        }

        $response = fetch("{$this->baseUrl}/files", $options);

        // Handle errors
        if (!$response->isSuccess()) {
            $this->handleErrorResponse($response);
        }

        return $response->json();
    }

    public function getFile(string $fileId)
    {
        $options = [];

        // Add authentication if API key is provided
        if ($this->apiKey) {
            $options['headers'] = [
                'Authorization' => "Bearer {$this->apiKey}"
            ];
        }

        $response = fetch("{$this->baseUrl}/files/{$fileId}", $options);

        // Handle errors
        if (!$response->isSuccess()) {
            $this->handleErrorResponse($response);
        }

        return $response->json();
    }

    public function downloadFile(string $fileId, string $destinationPath)
    {
        $options = [
            'stream' => true
        ];

        // Add authentication if API key is provided
        if ($this->apiKey) {
            $options['headers'] = [
                'Authorization' => "Bearer {$this->apiKey}"
            ];
        }

        $response = fetch("{$this->baseUrl}/files/{$fileId}/download", $options);

        // Handle errors
        if (!$response->isSuccess()) {
            $this->handleErrorResponse($response);
        }

        // Open destination file
        $file = fopen($destinationPath, 'w');

        // Get the response body as a stream
        $body = $response->getBody();

        // Write the stream to file
        while (!$body->eof()) {
            fwrite($file, $body->read(4096));
        }

        // Close the file
        fclose($file);

        return [
            'path' => $destinationPath,
            'size' => filesize($destinationPath)
        ];
    }

    public function deleteFile(string $fileId)
    {
        $options = [
            'method' => 'DELETE'
        ];

        // Add authentication if API key is provided
        if ($this->apiKey) {
            $options['headers'] = [
                'Authorization' => "Bearer {$this->apiKey}"
            ];
        }

        $response = fetch("{$this->baseUrl}/files/{$fileId}", $options);

        // Handle errors
        if (!$response->isSuccess()) {
            $this->handleErrorResponse($response);
        }

        return $response->json();
    }

    private function handleErrorResponse($response)
    {
        $status = $response->getStatusCode();

        // Try to get error details from response
        try {
            $error = $response->json();
            $message = $error['message'] ?? "API error: {$status}";
        } catch (\Exception $e) {
            $message = "API error: {$status}";
        }

        switch ($status) {
            case 401:
                throw new \RuntimeException("Authentication required: {$message}");
            case 403:
                throw new \RuntimeException("Permission denied: {$message}");
            case 404:
                throw new \RuntimeException("File not found: {$message}");
            case 413:
                throw new \RuntimeException("File too large: {$message}");
            case 422:
                throw new \RuntimeException("Validation error: {$message}");
            default:
                throw new \RuntimeException($message);
        }
    }
}

// Usage
$client = new FileApiClient('https://files.example.com/api', 'your-api-key');

try {
    // Upload a file with metadata
    $result = $client->uploadFile('/path/to/document.pdf', [
        'title' => 'Important Document',
        'category' => 'contracts',
        'tags' => 'legal,signed,2023'
    ]);

    echo "File uploaded with ID: {$result['id']}\n";

    // List files in a category
    $files = $client->listFiles(1, 10, ['category' => 'contracts']);

    echo "Found " . count($files['data']) . " files\n";

    // Download the first file
    if (!empty($files['data'])) {
        $fileId = $files['data'][0]['id'];
        $downloadResult = $client->downloadFile($fileId, "downloads/{$fileId}.pdf");

        echo "Downloaded file to {$downloadResult['path']} ({$downloadResult['size']} bytes)\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Next Steps

- Check out [API Integration Examples](/examples/api-integration) for more API integration patterns
- Explore [Async Patterns](/examples/async-patterns) for advanced asynchronous techniques
- See [Error Handling](/examples/error-handling) for handling upload and download errors
