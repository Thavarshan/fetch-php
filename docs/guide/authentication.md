---
title: Authentication
description: Learn how to authenticate HTTP requests with the Fetch HTTP package
---

# Authentication

This guide explains the various authentication methods available in the Fetch HTTP package and how to use them.

## Bearer Token Authentication

Bearer token authentication is a common method used for API authentication, particularly with OAuth 2.0 and JWT tokens.

### Using Helper Functions

```php
// Using the fetch() function
$response = fetch('https://api.example.com/users', [
    'token' => 'your-oauth-token'
]);

// Using the token option with any HTTP method helper
$response = get('https://api.example.com/users', null, [
    'token' => 'your-oauth-token'
]);
```

### Using the ClientHandler

```php
use Fetch\Http\ClientHandler;

$response = ClientHandler::create()
    ->withToken('your-oauth-token')
    ->get('https://api.example.com/users');
```

## Basic Authentication

Basic authentication sends credentials encoded in the Authorization header.

### Using Helper Functions

```php
// Using the fetch() function
$response = fetch('https://api.example.com/users', [
    'auth' => ['username', 'password']
]);

// Using the auth option with any HTTP method helper
$response = get('https://api.example.com/protected', null, [
    'auth' => ['username', 'password']
]);
```

### Using the ClientHandler

```php
use Fetch\Http\ClientHandler;

$response = ClientHandler::create()
    ->withAuth('username', 'password')
    ->get('https://api.example.com/protected');
```

## API Key Authentication

Many APIs use API keys for authentication, which can be sent in different ways.

### As a Query Parameter

```php
// Using the fetch() function
$response = fetch('https://api.example.com/data', [
    'query' => ['api_key' => 'your-api-key']
]);

// Using get() with query params
$response = get('https://api.example.com/data', [
    'api_key' => 'your-api-key'
]);

// Using the ClientHandler
$response = ClientHandler::create()
    ->withQueryParameter('api_key', 'your-api-key')
    ->get('https://api.example.com/data');
```

### As a Header

```php
// Using the fetch() function
$response = fetch('https://api.example.com/data', [
    'headers' => ['X-API-Key' => 'your-api-key']
]);

// Using get() with options
$response = get('https://api.example.com/data', null, [
    'headers' => ['X-API-Key' => 'your-api-key']
]);

// Using the ClientHandler
$response = ClientHandler::create()
    ->withHeader('X-API-Key', 'your-api-key')
    ->get('https://api.example.com/data');
```

## Custom Authentication Schemes

For APIs using custom authentication schemes, you can set headers directly.

```php
// Example for a custom scheme
$response = fetch('https://api.example.com/data', [
    'headers' => ['Authorization' => 'CustomScheme your-credential']
]);

// Using the ClientHandler
$response = ClientHandler::create()
    ->withHeader('Authorization', 'CustomScheme your-credential')
    ->get('https://api.example.com/data');
```

## OAuth 2.0 Authorization Flow

For more complex OAuth 2.0 flows, you'll need to implement the authorization code flow first, then use the resulting access token:

```php
// Step 1: Redirect user to authorization URL (not part of the HTTP client)
// ...

// Step 2: Exchange authorization code for an access token
$tokenResponse = post('https://oauth.example.com/token', [
    'grant_type' => 'authorization_code',
    'code' => $authorizationCode,
    'client_id' => 'your-client-id',
    'client_secret' => 'your-client-secret',
    'redirect_uri' => 'your-redirect-uri'
]);

// Step 3: Extract and store the access token
$accessToken = $tokenResponse->json()['access_token'];

// Step 4: Use the access token for API requests
$apiResponse = get('https://api.example.com/resource', null, [
    'token' => $accessToken
]);
```

## Global Authentication Configuration

Configure authentication globally to apply it to all requests:

```php
// Configure client with authentication
fetch_client([
    'base_uri' => 'https://api.example.com',
    'headers' => [
        'Authorization' => 'Bearer your-oauth-token' // For Bearer token auth
    ]
    // OR for basic auth
    // 'auth' => ['username', 'password']
]);

// Now all requests will include the authentication
$users = get('/users')->json();
$user = get("/users/{$id}")->json();
$newUser = post('/users', ['name' => 'John Doe'])->json();
```

## Authentication for Specific Clients

Create dedicated clients for different authentication scenarios:

```php
// Client for public endpoints
$publicClient = ClientHandler::createWithBaseUri('https://api.example.com');

// Client for authenticated endpoints
$authClient = ClientHandler::createWithBaseUri('https://api.example.com')
    ->withToken('your-oauth-token');

// Use the appropriate client for each request
$publicData = $publicClient->get('/public-data')->json();
$privateData = $authClient->get('/private-data')->json();
```

## Token Refresh

For APIs that use short-lived access tokens, you might need to implement token refreshing:

```php
function getAuthenticatedClient() {
    static $token = null;
    static $expiresAt = 0;

    // Check if token is expired
    if ($token === null || time() > $expiresAt) {
        // Refresh the token
        $response = post('https://oauth.example.com/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => 'your-refresh-token',
            'client_id' => 'your-client-id',
            'client_secret' => 'your-client-secret'
        ]);

        $tokenData = $response->json();
        $token = $tokenData['access_token'];
        $expiresAt = time() + ($tokenData['expires_in'] - 60); // Buffer of 60 seconds
    }

    // Return a client with the current token
    return ClientHandler::createWithBaseUri('https://api.example.com')
        ->withToken($token);
}

// Use the authenticated client
$client = getAuthenticatedClient();
$response = $client->get('/protected-resource');
```

## Asynchronous Authentication

For scenarios where you need to perform authentication in an asynchronous context:

```php
use function Matrix\Support\async;
use function Matrix\Support\await;

await(async(function() {
    // First, get an auth token
    $tokenResponse = await(async(fn() =>
        post('https://oauth.example.com/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'your-client-id',
            'client_secret' => 'your-client-secret'
        ])
    ));

    $token = $tokenResponse->json()['access_token'];

    // Then use the token for subsequent requests
    $apiResponse = await(async(fn() =>
        fetch('https://api.example.com/protected', [
            'token' => $token
        ])
    ));

    return $apiResponse->json();
}));
```

## Testing with Authentication

For testing, you can use mock responses:

```php
use Fetch\Http\ClientHandler;

// Create a mock response
$mockResponse = ClientHandler::createJsonResponse(
    ['username' => 'testuser', 'role' => 'admin'],
    200
);

// Test code that uses authentication
function testAuthenticatedRequest() {
    global $mockResponse;

    // In your test framework, you would mock the actual HTTP client
    // and return the mock response

    // Then in your application code:
    $response = get('https://api.example.com/me', null, [
        'token' => 'test-token'
    ]);

    // Assert against the response
    assert($response->successful());
    assert($response->json()['username'] === 'testuser');
}
```

## Working with Authentication Response Status

Fetch PHP provides convenient methods to check authentication-related status codes:

```php
$response = get('https://api.example.com/protected');

// Check specific authentication-related status codes
if ($response->isUnauthorized()) {
    echo "Authentication required (401)";
} elseif ($response->isForbidden()) {
    echo "Permission denied (403)";
} elseif ($response->isStatus(429)) {
    echo "Rate limited (429)";
}

// Using Status enums
use Fetch\Enum\Status;

if ($response->statusEnum() === Status::UNAUTHORIZED) {
    echo "Authentication required (401)";
} elseif ($response->statusEnum() === Status::FORBIDDEN) {
    echo "Permission denied (403)";
}
```

## Common Authentication Errors

### Unauthorized (401)

This typically means your credentials are missing or invalid:

```php
$response = get('https://api.example.com/protected');

if ($response->isUnauthorized()) {
    echo "Authentication required. Please provide valid credentials.";
    // Redirect to login or prompt for credentials
}
```

### Forbidden (403)

This means you're authenticated, but don't have permission for the resource:

```php
$response = get('https://api.example.com/admin-only', null, [
    'token' => 'user-token'  // Token for a non-admin user
]);

if ($response->isForbidden()) {
    echo "You don't have permission to access this resource.";
    // Show appropriate message or redirect
}
```

## Error Handling for Authentication

Use try/catch to handle authentication errors:

```php
try {
    $response = fetch('https://api.example.com/protected', [
        'token' => 'possibly-expired-token'
    ]);

    if ($response->isUnauthorized()) {
        // Handle invalid or expired token
        throw new \Exception("Authentication failed: Token is invalid or expired");
    }

    $data = $response->json();
} catch (\Fetch\Exceptions\NetworkException $e) {
    echo "Network error during authentication: " . $e->getMessage();
} catch (\Fetch\Exceptions\RequestException $e) {
    echo "Request error during authentication: " . $e->getMessage();
} catch (\Exception $e) {
    echo "Authentication error: " . $e->getMessage();
}
```

## Security Best Practices

1. **Never Hard-Code Credentials**: Use environment variables or a secure configuration system

   ```php
   // Bad: Hard-coding credentials
   $client->withToken('my-secret-token');

   // Good: Using environment variables
   $client->withToken($_ENV['API_TOKEN']);
   ```

2. **Use HTTPS**: Always use HTTPS for authenticated requests

   ```php
   // Ensure HTTPS is used
   if (!str_starts_with($apiUrl, 'https://')) {
       throw new \Exception('Authentication requires HTTPS');
   }
   ```

3. **Handle Tokens Securely**: Store tokens securely and never expose them to clients

4. **Use Short-Lived Tokens**: Prefer short-lived access tokens with refresh capability

5. **Implement Rate Limiting**: To protect against brute force attacks

6. **Add Retry Logic for Authentication Failures**: Some authentication failures are transient

   ```php
   $client = fetch_client()
       ->retry(3, 1000)  // 3 retries with 1s initial delay
       ->retryStatusCodes([401, 429])  // Retry on 401 (Unauthorized) and 429 (Too Many Requests)
       ->withToken($token)
       ->get('https://api.example.com/protected');
   ```

7. **Log Authentication Failures**: But be careful not to log sensitive information

   ```php
   use Monolog\Logger;
   use Monolog\Handler\StreamHandler;

   $logger = new Logger('auth');
   $logger->pushHandler(new StreamHandler('logs/auth.log', Logger::WARNING));

   $client = fetch_client();
   $client->setLogger($logger);

   $response = $client
       ->withToken($token)
       ->get('https://api.example.com/protected');

   if ($response->isUnauthorized()) {
       // Log will include request details but credentials will be redacted
       // thanks to the sanitization in the ClientHandler
   }
   ```

## Next Steps

- Learn about [Error Handling](/guide/error-handling) for authentication errors
- Explore [Retry Handling](/guide/retry-handling) for handling token expiration
- See [Asynchronous Requests](/guide/async-requests) for asynchronous authentication flows
- Check out [Logging](/guide/logging) for logging authenticated requests securely
