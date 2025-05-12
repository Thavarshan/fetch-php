---
title: Authentication Examples
description: Examples of different authentication methods with the Fetch HTTP package
---

# Authentication Examples

This page provides examples of different authentication methods when working with APIs using the Fetch HTTP package.

## API Key Authentication

Many APIs use API keys for authentication, typically sent as a header or query parameter:

### API Key in Header

```php
use function Fetch\Http\fetch;

// API key in a custom header
$response = fetch('https://api.example.com/data', [
    'headers' => [
        'X-API-Key' => 'your-api-key'
    ]
]);

// Using the fluent interface
$response = fetch()
    ->withHeader('X-API-Key', 'your-api-key')
    ->get('https://api.example.com/data');
```

### API Key in Query Parameter

```php
use function Fetch\Http\get;

// API key as a query parameter
$response = get('https://api.example.com/data', [
    'api_key' => 'your-api-key'
]);

// Using the fetch function
$response = fetch('https://api.example.com/data', [
    'query' => ['api_key' => 'your-api-key']
]);
```

## Bearer Token Authentication

Bearer tokens are commonly used with OAuth 2.0 and JWT:

```php
use function Fetch\Http\fetch;

// Using the token option
$response = fetch('https://api.example.com/me', [
    'token' => 'your-access-token'
]);

// Using the Authorization header directly
$response = fetch('https://api.example.com/me', [
    'headers' => [
        'Authorization' => 'Bearer your-access-token'
    ]
]);

// Using the fluent interface
$response = fetch()
    ->withToken('your-access-token')
    ->get('https://api.example.com/me');
```

## Basic Authentication

Basic authentication sends credentials in the `Authorization` header:

```php
use function Fetch\Http\fetch;

// Using the auth option
$response = fetch('https://api.example.com/protected', [
    'auth' => ['username', 'password']
]);

// Using the fluent interface
$response = fetch()
    ->withAuth('username', 'password')
    ->get('https://api.example.com/protected');

// Manually setting the Authorization header with Base64 encoding
$credentials = base64_encode('username:password');
$response = fetch('https://api.example.com/protected', [
    'headers' => [
        'Authorization' => "Basic {$credentials}"
    ]
]);
```

## Digest Authentication

Some APIs use digest authentication, which requires a more complex challenge-response flow:

```php
use function Fetch\Http\fetch;
use GuzzleHttp\Client;

// The easiest way is to use Guzzle's built-in support
$client = new Client();
fetch_client(['client' => $client]);

$response = fetch('https://api.example.com/protected', [
    'auth' => ['username', 'password', 'digest']
]);
```

## OAuth 1.0a Authentication

OAuth 1.0a requires signing requests with a complex algorithm:

```php
use function Fetch\Http\fetch;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;

// Create a handler stack
$stack = HandlerStack::create();

// Add the OAuth 1.0 middleware
$middleware = new Oauth1([
    'consumer_key'    => 'your-consumer-key',
    'consumer_secret' => 'your-consumer-secret',
    'token'           => 'your-token',
    'token_secret'    => 'your-token-secret'
]);
$stack->push($middleware);

// Create a client with the handler
$client = new Client(['handler' => $stack]);
fetch_client(['client' => $client]);

// Make a request - OAuth 1.0 signature is added automatically
$response = fetch('https://api.example.com/resources');

// Reset when done
fetch_client(reset: true);
```

## OAuth 2.0 Client Credentials Flow

For server-to-server API authentication:

```php
use function Fetch\Http\fetch;
use function Fetch\Http\post;

function getAccessToken(string $clientId, string $clientSecret, string $tokenUrl): string
{
    // Request an access token
    $response = post($tokenUrl, [
        'grant_type' => 'client_credentials',
        'client_id' => $clientId,
        'client_secret' => $clientSecret
    ], [
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json'
        ]
    ]);

    if (!$response->successful()) {
        throw new \RuntimeException(
            "Failed to get access token: " . $response->status() . " " . $response->body()
        );
    }

    $tokenData = $response->json();

    if (!isset($tokenData['access_token'])) {
        throw new \RuntimeException("No access token in response");
    }

    return $tokenData['access_token'];
}

// Get an access token
$token = getAccessToken(
    'your-client-id',
    'your-client-secret',
    'https://auth.example.com/oauth/token'
);

// Use the token for API requests
$response = fetch('https://api.example.com/data', [
    'token' => $token
]);

// Process the response
if ($response->successful()) {
    $data = $response->json();
    // Process data...
}
```

## OAuth 2.0 Authorization Code Flow

For web apps that need user authentication:

```php
use function Fetch\Http\fetch;
use function Fetch\Http\post;

class OAuth2Client
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $tokenUrl;
    private string $authorizationUrl;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $redirectUri,
        string $tokenUrl,
        string $authorizationUrl
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->tokenUrl = $tokenUrl;
        $this->authorizationUrl = $authorizationUrl;
    }

    public function getAuthorizationUrl(array $scopes = [], string $state = null): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes)
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return $this->authorizationUrl . '?' . http_build_query($params);
    }

    public function getAccessToken(string $code): array
    {
        $response = post($this->tokenUrl, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri
        ], [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json'
            ]
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "Failed to get access token: " . $response->status() . " " . $response->body()
            );
        }

        return $response->json();
    }

    public function refreshToken(string $refreshToken): array
    {
        $response = post($this->tokenUrl, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ], [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json'
            ]
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "Failed to refresh token: " . $response->status() . " " . $response->body()
            );
        }

        return $response->json();
    }

    public function request(string $url, string $accessToken, string $method = 'GET', array $options = []): array
    {
        // Set the Authorization header
        $options['headers'] = $options['headers'] ?? [];
        $options['headers']['Authorization'] = "Bearer {$accessToken}";
        $options['method'] = $method;

        $response = fetch($url, $options);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "API request failed: " . $response->status() . " " . $response->body()
            );
        }

        return $response->json();
    }
}

// Usage in a controller
function oauthCallback()
{
    // Create an OAuth2 client
    $oauth = new OAuth2Client(
        'your-client-id',
        'your-client-secret',
        'https://your-app.com/oauth/callback',
        'https://auth.example.com/oauth/token',
        'https://auth.example.com/oauth/authorize'
    );

    // Check for authorization code in the request
    $code = $_GET['code'] ?? null;
    $state = $_GET['state'] ?? null;

    // Verify state parameter (CSRF protection)
    if ($state !== $_SESSION['oauth_state']) {
        die('Invalid state parameter');
    }

    try {
        // Exchange the code for tokens
        $tokens = $oauth->getAccessToken($code);

        // Store tokens securely
        $_SESSION['access_token'] = $tokens['access_token'];
        $_SESSION['refresh_token'] = $tokens['refresh_token'] ?? null;
        $_SESSION['token_expires_at'] = time() + ($tokens['expires_in'] ?? 3600);

        // Fetch user profile
        $user = $oauth->request(
            'https://api.example.com/me',
            $tokens['access_token']
        );

        // Store user data
        $_SESSION['user'] = $user;

        // Redirect to dashboard
        header('Location: /dashboard');
        exit;
    } catch (\Exception $e) {
        die('Authentication error: ' . $e->getMessage());
    }
}

// Starting the OAuth flow
function startOAuth()
{
    $oauth = new OAuth2Client(
        'your-client-id',
        'your-client-secret',
        'https://your-app.com/oauth/callback',
        'https://auth.example.com/oauth/token',
        'https://auth.example.com/oauth/authorize'
    );

    // Generate a random state parameter for CSRF protection
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;

    // Redirect user to authorization URL
    $authUrl = $oauth->getAuthorizationUrl(
        ['profile', 'email', 'read', 'write'],
        $state
    );

    header('Location: ' . $authUrl);
    exit;
}
```

## Token Refresh Logic

Handling token expiration and refresh:

```php
use function Fetch\Http\fetch;
use function Fetch\Http\post;

class TokenManager
{
    private string $clientId;
    private string $clientSecret;
    private string $tokenUrl;
    private ?string $accessToken = null;
    private ?string $refreshToken = null;
    private ?int $expiresAt = null;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $tokenUrl
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tokenUrl = $tokenUrl;
    }

    public function setTokens(string $accessToken, ?string $refreshToken = null, ?int $expiresIn = null)
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;

        if ($expiresIn !== null) {
            $this->expiresAt = time() + $expiresIn - 60; // 60-second buffer
        } else {
            $this->expiresAt = null;
        }
    }

    public function getAccessToken(): string
    {
        if ($this->isTokenExpired() && $this->refreshToken) {
            $this->refreshAccessToken();
        }

        if (!$this->accessToken) {
            throw new \RuntimeException("No access token available");
        }

        return $this->accessToken;
    }

    private function isTokenExpired(): bool
    {
        if (!$this->expiresAt) {
            return false; // No expiration time, assume still valid
        }

        return time() >= $this->expiresAt;
    }

    private function refreshAccessToken()
    {
        if (!$this->refreshToken) {
            throw new \RuntimeException("No refresh token available");
        }

        try {
            $response = post($this->tokenUrl, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ], [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json'
                ]
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException(
                    "Failed to refresh token: " . $response->status() . " " . $response->body()
                );
            }

            $tokenData = $response->json();

            $this->accessToken = $tokenData['access_token'];
            $this->refreshToken = $tokenData['refresh_token'] ?? $this->refreshToken;

            if (isset($tokenData['expires_in'])) {
                $this->expiresAt = time() + $tokenData['expires_in'] - 60;
            }
        } catch (\Exception $e) {
            // If refresh fails, invalidate tokens to force re-authentication
            $this->accessToken = null;
            $this->refreshToken = null;
            $this->expiresAt = null;

            throw new \RuntimeException("Token refresh failed: " . $e->getMessage(), 0, $e);
        }
    }
}

// Usage with a protected API client
class ApiClient
{
    private string $baseUrl;
    private TokenManager $tokenManager;

    public function __construct(string $baseUrl, TokenManager $tokenManager)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->tokenManager = $tokenManager;
    }

    public function get(string $endpoint, array $query = null)
    {
        return $this->request('GET', $endpoint, $query);
    }

    public function post(string $endpoint, array $data)
    {
        return $this->request('POST', $endpoint, $data);
    }

    private function request(string $method, string $endpoint, ?array $data = null)
    {
        try {
            // Get a valid access token
            $accessToken = $this->tokenManager->getAccessToken();

            $options = [
                'method' => $method,
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Accept' => 'application/json'
                ]
            ];

            if ($data !== null) {
                if ($method === 'GET') {
                    $options['query'] = $data;
                } else {
                    $options['json'] = $data;
                }
            }

            $response = fetch($this->baseUrl . '/' . ltrim($endpoint, '/'), $options);

            if ($response->status() === 401 && $response->body() === 'Token has expired') {
                // Force token refresh and retry
                $this->tokenManager->setTokens(null);
                return $this->request($method, $endpoint, $data);
            }

            if (!$response->successful()) {
                throw new \RuntimeException(
                    "API request failed: " . $response->status() . " " . $response->body()
                );
            }

            return $response->json();
        } catch (\Exception $e) {
            // Handle exceptions
            throw new \RuntimeException("API request error: " . $e->getMessage(), 0, $e);
        }
    }
}

// Example usage
$tokenManager = new TokenManager(
    'your-client-id',
    'your-client-secret',
    'https://auth.example.com/oauth/token'
);

// Initial token setup (from a previous auth flow)
$tokenManager->setTokens(
    'initial-access-token',
    'refresh-token',
    3600 // Expires in 1 hour
);

$api = new ApiClient('https://api.example.com', $tokenManager);

try {
    // This will automatically refresh the token if needed
    $user = $api->get('me');
    echo "User profile: " . $user['name'];

    $posts = $api->get('posts', ['limit' => 10]);
    echo "Found " . count($posts) . " posts";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## JWT Authentication

Using JSON Web Tokens for authentication:

```php
use function Fetch\Http\fetch;

// Function to create a JWT
function createJwt(array $payload, string $secret): string
{
    // Create header
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];

    // Encode Header
    $header = base64UrlEncode(json_encode($header));

    // Encode Payload
    $payload = base64UrlEncode(json_encode($payload));

    // Create Signature
    $signature = hash_hmac('sha256', "$header.$payload", $secret, true);
    $signature = base64UrlEncode($signature);

    // Create JWT
    return "$header.$payload.$signature";
}

// Base64Url encode helper function
function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Create JWT payload
$payload = [
    'sub' => '123456',
    'name' => 'John Doe',
    'iat' => time(),
    'exp' => time() + 3600 // Expires in 1 hour
];

// Generate JWT
$jwt = createJwt($payload, 'your-jwt-secret');

// Use JWT for API requests
$response = fetch('https://api.example.com/protected', [
    'headers' => [
        'Authorization' => "Bearer {$jwt}"
    ]
]);

if ($response->successful()) {
    $data = $response->json();
    echo "Successfully authenticated with JWT";
} else {
    echo "JWT authentication failed: " . $response->status();
}
```

## API Key Rotation

Handling API key rotation for reliability:

```php
use function Fetch\Http\fetch;

class ApiKeyManager
{
    private array $apiKeys;
    private int $currentKeyIndex = 0;
    private array $failedKeys = [];

    public function __construct(array $apiKeys)
    {
        if (empty($apiKeys)) {
            throw new \InvalidArgumentException("At least one API key must be provided");
        }

        $this->apiKeys = $apiKeys;
    }

    public function getCurrentKey(): string
    {
        return $this->apiKeys[$this->currentKeyIndex];
    }

    public function markCurrentKeyAsFailed(): bool
    {
        $failedKey = $this->getCurrentKey();
        $this->failedKeys[$failedKey] = time();

        // Try to rotate to the next valid key
        return $this->rotateToNextValidKey();
    }

    public function rotateToNextValidKey(): bool
    {
        $initialIndex = $this->currentKeyIndex;
        $keysChecked = 0;

        do {
            // Move to next key (with wraparound)
            $this->currentKeyIndex = ($this->currentKeyIndex + 1) % count($this->apiKeys);
            $keysChecked++;

            // Check if we've tried all keys
            if ($keysChecked >= count($this->apiKeys)) {
                // Reset to initial key
                $this->currentKeyIndex = $initialIndex;
                return false;
            }

            // Check if current key is valid
            $currentKey = $this->getCurrentKey();

            // If key was marked as failed more than 5 minutes ago, try it again
            if (isset($this->failedKeys[$currentKey]) &&
                time() - $this->failedKeys[$currentKey] > 300) {
                unset($this->failedKeys[$currentKey]);
            }
        } while (isset($this->failedKeys[$this->getCurrentKey()]));

        return true;
    }
}

class ApiClient
{
    private string $baseUrl;
    private ApiKeyManager $keyManager;

    public function __construct(string $baseUrl, ApiKeyManager $keyManager)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->keyManager = $keyManager;
    }

    public function request(string $endpoint, string $method = 'GET', array $data = null)
    {
        $maxRetries = 3;
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                $apiKey = $this->keyManager->getCurrentKey();

                $options = [
                    'method' => $method,
                    'headers' => [
                        'X-API-Key' => $apiKey,
                        'Accept' => 'application/json'
                    ]
                ];

                if ($data !== null) {
                    if ($method === 'GET') {
                        $options['query'] = $data;
                    } else {
                        $options['json'] = $data;
                    }
                }

                $response = fetch($this->baseUrl . '/' . ltrim($endpoint, '/'), $options);

                // Handle API key errors
                if ($response->status() === 401 || $response->status() === 403) {
                    // This key might be invalid or rate limited
                    $hasValidKey = $this->keyManager->markCurrentKeyAsFailed();

                    if (!$hasValidKey) {
                        throw new \RuntimeException("All API keys are invalid or rate limited");
                    }

                    $attempts++;
                    continue;
                }

                // Handle other errors
                if (!$response->successful()) {
                    throw new \RuntimeException(
                        "API request failed: " . $response->status() . " " . $response->body()
                    );
                }

                return $response->json();
            } catch (\Exception $e) {
                $attempts++;

                if ($attempts >= $maxRetries) {
                    throw $e;
                }
            }
        }
    }
}

// Usage
$keyManager = new ApiKeyManager([
    'primary-api-key-123',
    'backup-api-key-456',
    'emergency-api-key-789'
]);

$api = new ApiClient('https://api.example.com', $keyManager);

try {
    $data = $api->request('data');
    echo "Successfully fetched data with API key";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Multi-tenant API Client

An API client for applications that need to support multiple users or organizations:

```php
use function Fetch\Http\fetch;
use function Matrix\async;
use function Matrix\await;

class MultiTenantApiClient
{
    private string $baseUrl;
    private array $tenantTokens = [];

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function setTenantToken(string $tenantId, string $token): void
    {
        $this->tenantTokens[$tenantId] = $token;
    }

    public function getTenantToken(string $tenantId): ?string
    {
        return $this->tenantTokens[$tenantId] ?? null;
    }

    public function request(string $tenantId, string $endpoint, string $method = 'GET', array $data = null)
    {
        $token = $this->getTenantToken($tenantId);

        if (!$token) {
            throw new \RuntimeException("No token available for tenant: {$tenantId}");
        }

        $options = [
            'method' => $method,
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
                'X-Tenant-ID' => $tenantId
            ]
        ];

        if ($data !== null) {
            if ($method === 'GET') {
                $options['query'] = $data;
            } else {
                $options['json'] = $data;
            }
        }

        $response = fetch($this->baseUrl . '/' . ltrim($endpoint, '/'), $options);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "API request failed for tenant {$tenantId}: " .
                $response->status() . " " . $response->body()
            );
        }

        return $response->json();
    }

    public async function requestAsync(string $tenantId, string $endpoint, string $method = 'GET', array $data = null)
    {
        return async(function() use ($tenantId, $endpoint, $method, $data) {
            return $this->request($tenantId, $endpoint, $method, $data);
        });
    }

    public async function requestForAllTenants(string $endpoint, string $method = 'GET', array $data = null)
    {
        $promises = [];

        foreach (array_keys($this->tenantTokens) as $tenantId) {
            $promises[$tenantId] = $this->requestAsync($tenantId, $endpoint, $method, $data);
        }

        return await(all($promises));
    }
}

// Usage
$multiTenantApi = new MultiTenantApiClient('https://api.example.com');

// Set up tokens for different tenants
$multiTenantApi->setTenantToken('tenant1', 'token-for-tenant1');
$multiTenantApi->setTenantToken('tenant2', 'token-for-tenant2');
$multiTenantApi->setTenantToken('tenant3', 'token-for-tenant3');

// Make a request for a specific tenant
try {
    $user = $multiTenantApi->request('tenant1', 'users/me');
    echo "Tenant1 user: " . $user['name'];
} catch (\Exception $e) {
    echo "Tenant1 error: " . $e->getMessage();
}

// Make requests for all tenants in parallel
await(async(function() use ($multiTenantApi) {
    try {
        $allTenantsData = await($multiTenantApi->requestForAllTenants('stats/summary'));

        foreach ($allTenantsData as $tenantId => $stats) {
            echo "{$tenantId} stats: " . json_encode($stats) . "\n";
        }
    } catch (\Exception $e) {
        echo "Error fetching data for all tenants: " . $e->getMessage();
    }
}));
```

## Handling Authentication Challenges

Dealing with complex authentication flows:

```php
use function Fetch\Http\fetch;

function fetchWithAuthChallenge(string $url, array $options = [])
{
    // First request
    $response = fetch($url, $options);

    // Check if we got an auth challenge
    if ($response->status() === 401) {
        $challengeHeader = $response->header('WWW-Authenticate');

        if ($challengeHeader && strpos($challengeHeader, 'Digest') === 0) {
            // Parse the digest challenge
            preg_match_all('/(\w+)=(?:"([^"]+)"|([^,]+))/', $challengeHeader, $matches, PREG_SET_ORDER);

            $challenge = [];
            foreach ($matches as $match) {
                $challenge[$match[1]] = $match[2] ?: $match[3];
            }

            if (isset($challenge['nonce'], $challenge['realm'])) {
                // Compute the digest response
                $username = 'your-username';
                $password = 'your-password';
                $method = $options['method'] ?? 'GET';

                $ha1 = md5("{$username}:{$challenge['realm']}:{$password}");
                $ha2 = md5("{$method}:{$url}");
                $nc = '00000001';
                $cnonce = md5(uniqid());

                $response = md5("{$ha1}:{$challenge['nonce']}:{$nc}:{$cnonce}:{$challenge['qop']}:{$ha2}");

                // Build the Authorization header
                $authHeader = 'Digest ' .
                    "username=\"{$username}\", " .
                    "realm=\"{$challenge['realm']}\", " .
                    "nonce=\"{$challenge['nonce']}\", " .
                    "uri=\"{$url}\", " .
                    "cnonce=\"{$cnonce}\", " .
                    "nc={$nc}, " .
                    "qop={$challenge['qop']}, " .
                    "response=\"{$response}\"";

                // Set the Authorization header for the second request
                $options['headers'] = $options['headers'] ?? [];
                $options['headers']['Authorization'] = $authHeader;

                // Make the second request with the auth response
                return fetch($url, $options);
            }
        }
    }

    // Either no challenge or we couldn't handle it
    return $response;
}

// Usage
$response = fetchWithAuthChallenge('https://api.example.com/protected');

if ($response->successful()) {
    $data = $response->json();
    echo "Successfully authenticated";
} else {
    echo "Authentication failed: " . $response->status();
}
```

## Next Steps

- Check out [API Integration Examples](/examples/api-integration) for more API integration patterns
- Explore [Error Handling](/examples/error-handling) for handling authentication errors
- See [Async Patterns](/examples/async-patterns) for asynchronous authentication
