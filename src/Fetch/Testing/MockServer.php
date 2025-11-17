<?php

declare(strict_types=1);

namespace Fetch\Testing;

use Closure;
use Fetch\Http\Request;
use Fetch\Interfaces\Response as ResponseInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Assert as PHPUnit;

class MockServer
{
    /**
     * The singleton instance of the mock server.
     */
    protected static ?self $instance = null;

    /**
     * The registered response fakes.
     *
     * @var array<string, MockResponse|MockResponseSequence|Closure>
     */
    protected array $fakes = [];

    /**
     * The callback to use for dynamic response matching.
     */
    protected ?Closure $callback = null;

    /**
     * Whether to prevent stray requests.
     */
    protected bool $preventStrayRequests = false;

    /**
     * Allowed URL patterns for stray requests.
     *
     * @var array<string>
     */
    protected array $allowedStrayPatterns = [];

    /**
     * Recorded requests and responses.
     *
     * @var array<array{request: Request, response: ResponseInterface}>
     */
    protected array $recorded = [];

    /**
     * Whether recording is enabled.
     */
    protected bool $recording = false;

    /**
     * Get the singleton instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Set up fake responses for specific URL patterns.
     *
     * @param  array<string, MockResponse|MockResponseSequence|Closure>|Closure|null  $patterns  URL patterns and their responses
     */
    public static function fake(array|Closure|null $patterns = null): void
    {
        $instance = self::getInstance();
        $instance->reset();

        if ($patterns === null) {
            // Fake all requests with empty 200 responses
            $instance->callback = fn () => MockResponse::ok();

            return;
        }

        if ($patterns instanceof Closure) {
            // Use callback for all requests
            $instance->callback = $patterns;

            return;
        }

        // Register pattern-based fakes
        foreach ($patterns as $pattern => $response) {
            $instance->fakes[$pattern] = $response;
        }
    }

    /**
     * Prevent requests that don't match any registered fakes.
     */
    public static function preventStrayRequests(): void
    {
        self::getInstance()->preventStrayRequests = true;
    }

    /**
     * Allow stray requests to specific URL patterns.
     *
     * @param  array<string>  $patterns  URL patterns to allow
     */
    public static function allowStrayRequests(array $patterns = []): void
    {
        $instance = self::getInstance();
        $instance->preventStrayRequests = false;
        $instance->allowedStrayPatterns = $patterns;
    }

    /**
     * Start recording requests and responses.
     */
    public static function startRecording(): void
    {
        $instance = self::getInstance();
        $instance->recording = true;
        $instance->recorded = [];
    }

    /**
     * Stop recording and return the recorded requests/responses.
     *
     * @return array<array{request: Request, response: ResponseInterface}>
     */
    public static function stopRecording(): array
    {
        $instance = self::getInstance();
        $instance->recording = false;

        return $instance->recorded;
    }

    /**
     * Get all recorded requests and responses.
     *
     * @param  Closure|null  $filter  Optional filter callback
     * @return array<array{request: Request, response: ResponseInterface}>
     */
    public static function recorded(?Closure $filter = null): array
    {
        $instance = self::getInstance();
        $recorded = $instance->recorded;

        if ($filter !== null) {
            return array_filter($recorded, $filter);
        }

        return $recorded;
    }

    /**
     * Assert that a request was sent matching the given criteria.
     *
     * @param  string|Closure  $pattern  URL pattern or callback
     * @param  int|null  $times  Expected number of times (null = at least once)
     */
    public static function assertSent(string|Closure $pattern, ?int $times = null): void
    {
        $instance = self::getInstance();
        $matches = [];

        if ($pattern instanceof Closure) {
            $matches = array_filter($instance->recorded, function ($record) use ($pattern) {
                return $pattern($record['request'], $record['response']);
            });
        } else {
            $matches = array_filter($instance->recorded, function ($record) use ($pattern, $instance) {
                $url = (string) $record['request']->getUri();
                $method = $record['request']->getMethod();

                return $instance->matchesPattern("{$method} {$url}", $pattern)
                    || $instance->matchesPattern($url, $pattern);
            });
        }

        $count = count($matches);

        if ($times === null) {
            PHPUnit::assertTrue(
                $count > 0,
                'Expected request was not sent.'
            );
        } else {
            PHPUnit::assertSame(
                $times,
                $count,
                "Expected request to be sent {$times} time(s), but was sent {$count} time(s)."
            );
        }
    }

    /**
     * Assert that a request was not sent matching the given criteria.
     *
     * @param  string|Closure  $pattern  URL pattern or callback
     */
    public static function assertNotSent(string|Closure $pattern): void
    {
        $instance = self::getInstance();
        $matches = [];

        if ($pattern instanceof Closure) {
            $matches = array_filter($instance->recorded, function ($record) use ($pattern) {
                return $pattern($record['request'], $record['response']);
            });
        } else {
            $matches = array_filter($instance->recorded, function ($record) use ($pattern, $instance) {
                $url = (string) $record['request']->getUri();
                $method = $record['request']->getMethod();

                return $instance->matchesPattern("{$method} {$url}", $pattern)
                    || $instance->matchesPattern($url, $pattern);
            });
        }

        PHPUnit::assertCount(
            0,
            $matches,
            'Unexpected request was sent.'
        );
    }

    /**
     * Assert that exactly N requests were sent.
     */
    public static function assertSentCount(int $count): void
    {
        $instance = self::getInstance();

        PHPUnit::assertCount(
            $count,
            $instance->recorded,
            "Expected {$count} request(s) to be sent, but ".count($instance->recorded).' were sent.'
        );
    }

    /**
     * Assert that no requests were sent.
     */
    public static function assertNothingSent(): void
    {
        self::assertSentCount(0);
    }

    /**
     * Reset the singleton instance completely.
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Handle a request and return the mocked response.
     *
     * @throws InvalidArgumentException
     */
    public function handleRequest(Request $request): ?ResponseInterface
    {
        $url = (string) $request->getUri();
        $method = $request->getMethod();

        // Try to find a matching fake
        $response = $this->findMatchingResponse($request, $url, $method);

        // If no match found
        if ($response === null) {
            if ($this->preventStrayRequests && ! $this->isAllowedStrayRequest($url)) {
                throw new InvalidArgumentException(
                    "No fake response registered for [{$method} {$url}] and stray requests are prevented."
                );
            }

            return null; // Let the real request go through
        }

        // Execute the response
        $executedResponse = $response instanceof MockResponse
            ? $response->execute()
            : $response;

        // Record the request/response if recording is enabled
        if ($this->recording) {
            $this->recorded[] = [
                'request' => $request,
                'response' => $executedResponse,
            ];
        }

        return $executedResponse;
    }

    /**
     * Reset the mock server state.
     */
    public function reset(): void
    {
        $this->fakes = [];
        $this->callback = null;
        $this->preventStrayRequests = false;
        $this->allowedStrayPatterns = [];
        $this->recorded = [];
        $this->recording = true; // Auto-enable recording when faking
    }

    /**
     * Find a matching response for the given request.
     */
    protected function findMatchingResponse(Request $request, string $url, string $method): MockResponse|ResponseInterface|null
    {
        // Try callback first
        if ($this->callback !== null) {
            $response = ($this->callback)($request);

            return $this->normalizeResponse($response);
        }

        // Try exact pattern matches with method
        $fullPattern = "{$method} {$url}";
        if (isset($this->fakes[$fullPattern])) {
            return $this->getResponseFromFake($this->fakes[$fullPattern]);
        }

        // Try URL-only patterns
        if (isset($this->fakes[$url])) {
            return $this->getResponseFromFake($this->fakes[$url]);
        }

        // Try wildcard pattern matches with method
        foreach ($this->fakes as $pattern => $fake) {
            if ($this->matchesPattern("{$method} {$url}", $pattern)) {
                return $this->getResponseFromFake($fake);
            }
        }

        // Try URL-only wildcard patterns
        foreach ($this->fakes as $pattern => $fake) {
            if ($this->matchesPattern($url, $pattern)) {
                return $this->getResponseFromFake($fake);
            }
        }

        return null;
    }

    /**
     * Get the response from a fake (handles sequences and closures).
     */
    protected function getResponseFromFake(mixed $fake): MockResponse|ResponseInterface|null
    {
        if ($fake instanceof MockResponseSequence) {
            return $fake->next();
        }

        if ($fake instanceof Closure) {
            $response = $fake();

            return $this->normalizeResponse($response);
        }

        return $fake;
    }

    /**
     * Normalize a response (convert arrays to JSON responses).
     */
    protected function normalizeResponse(mixed $response): MockResponse|ResponseInterface|null
    {
        if (is_array($response)) {
            return MockResponse::json($response);
        }

        return $response;
    }

    /**
     * Check if a URL matches a pattern (supports wildcards).
     */
    protected function matchesPattern(string $url, string $pattern): bool
    {
        // Convert wildcard pattern to regex
        $regex = preg_quote($pattern, '/');
        $regex = str_replace('\*', '.*', $regex);
        $regex = '/^'.$regex.'$/';

        return (bool) preg_match($regex, $url);
    }

    /**
     * Check if a URL is allowed as a stray request.
     */
    protected function isAllowedStrayRequest(string $url): bool
    {
        if (empty($this->allowedStrayPatterns)) {
            return false;
        }

        foreach ($this->allowedStrayPatterns as $pattern) {
            if ($this->matchesPattern($url, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
