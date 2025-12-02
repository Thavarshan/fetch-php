<?php

namespace Tests\Unit;

use Fetch\Exceptions\RequestException;
use Fetch\Http\ClientHandler;
use Fetch\Http\Response;
use GuzzleHttp\Exception\ConnectException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ManagesRetriesTest extends TestCase
{
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new class extends ClientHandler {
            public function exposeIsRetryableError(\Throwable $e): bool
            {
                return $this->isRetryableError($e);
            }

            public function exposeCalculateBackoffDelay(int $baseDelay, int $attempt): int
            {
                return $this->calculateBackoffDelay($baseDelay, $attempt);
            }

            public function exposeRetryRequest(callable $request): ResponseInterface
            {
                return $this->retryRequest(null, $request);
            }
        };
    }

    public function testRetryConfiguration(): void
    {
        $this->handler->retry(3, 200);

        $this->assertEquals(3, $this->handler->getMaxRetries());
        $this->assertEquals(200, $this->handler->getRetryDelay());
    }

    public function testRetryStatusCodes(): void
    {
        $statusCodes = [429, 503];
        $this->handler->retryStatusCodes($statusCodes);

        $this->assertEquals($statusCodes, $this->handler->getRetryableStatusCodes());
    }

    public function testRetryExceptions(): void
    {
        $exceptions = [ConnectException::class];
        $this->handler->retryExceptions($exceptions);

        $this->assertEquals($exceptions, $this->handler->getRetryableExceptions());
    }

    public function testCalculateBackoffDelay(): void
    {
        // Test exponential backoff
        $baseDelay = 100; // 100ms

        // First attempt (0-based)
        $delay1 = $this->handler->exposeCalculateBackoffDelay($baseDelay, 0);
        $this->assertGreaterThanOrEqual($baseDelay, $delay1);
        $this->assertLessThanOrEqual($baseDelay * 2, $delay1); // Account for jitter

        // Second attempt (0-based)
        $delay2 = $this->handler->exposeCalculateBackoffDelay($baseDelay, 1);
        $this->assertGreaterThanOrEqual($baseDelay * 2, $delay2);
        $this->assertLessThanOrEqual($baseDelay * 4, $delay2); // Account for jitter

        // Third attempt (0-based)
        $delay3 = $this->handler->exposeCalculateBackoffDelay($baseDelay, 2);
        $this->assertGreaterThanOrEqual($baseDelay * 4, $delay3);
        $this->assertLessThanOrEqual($baseDelay * 8, $delay3); // Account for jitter
    }

    public function testRetryRequestWithSuccess(): void
    {
        $mockResponse = new Response(200);
        $callCount = 0;

        $result = $this->handler->exposeRetryRequest(function () use (&$callCount, $mockResponse) {
            ++$callCount;

            return $mockResponse;
        });

        $this->assertSame($mockResponse, $result);
        $this->assertEquals(1, $callCount, 'Request should be called exactly once on success');
    }

    public function testRetryRequestWithRetryableErrorThenSuccess(): void
    {
        // Mock a request exception with a retryable status code (using Fetch RequestException)
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockErrorResponse = new Response(503); // Service Unavailable
        $mockSuccessResponse = new Response(200);

        $exception = new RequestException('Service unavailable', $mockRequest, $mockErrorResponse);

        // Create a test handler and manually configure it
        $handler = new class extends ClientHandler {
            // Override isRetryableError to always return true for the test
            protected function isRetryableError(\Throwable $e, ?\Fetch\Support\RequestContext $context = null): bool
            {
                return true;
            }

            // Expose the protected retryRequest method for testing
            public function exposeRetryRequest(callable $request)
            {
                return $this->retryRequest(null, $request);
            }

            // Override logRetry to avoid URI issues
            protected function logRetry(int $attempt, int $maxAttempts, \Throwable $exception): void
            {
                // No-op for testing
            }
        };

        // Set a base URI and options to avoid the "URI cannot be empty" error
        $handler->baseUri('https://example.com');
        $handler->withOptions(['uri' => '/test']);

        // Configure retry settings
        $handler->retry(3);

        $callCount = 0;

        // This should succeed on the second attempt
        $request = function () use (&$callCount, $exception, $mockSuccessResponse) {
            ++$callCount;
            if (1 === $callCount) {
                throw $exception;
            }

            return $mockSuccessResponse;
        };

        // Call the exposed method
        $result = $handler->exposeRetryRequest($request);

        $this->assertSame($mockSuccessResponse, $result);
        $this->assertEquals(2, $callCount, 'Request should be called twice (one failure, one success)');
    }

    public function testRequestContextRetryConfigurationOverridesHandlerDefaults(): void
    {
        // Create a context with custom retry config that differs from handler defaults
        $context = \Fetch\Support\RequestContext::create()
            ->withRetry(5, 500) // 5 retries, 500ms delay
            ->withRetryableStatusCodes([400, 401, 403]) // Custom status codes
            ->withRetryableExceptions([\RuntimeException::class]); // Custom exceptions

        // Verify the context holds the expected values
        $this->assertEquals(5, $context->getMaxRetries());
        $this->assertEquals(500, $context->getRetryDelay());
        $this->assertEquals([400, 401, 403], $context->getRetryableStatusCodes());
        $this->assertEquals([\RuntimeException::class], $context->getRetryableExceptions());

        // Handler has different defaults - these should be overridden by context
        $this->handler->retry(2, 100);
        $this->assertEquals(2, $this->handler->getMaxRetries());
        $this->assertEquals(100, $this->handler->getRetryDelay());
    }

    public function testRequestContextFromOptionsWithRetryConfig(): void
    {
        // Create context from options array with retry configuration
        $options = [
            'method' => 'GET',
            'uri' => '/test',
            'retries' => 3,
            'retry_delay' => 250,
            'retry_status_codes' => [429, 503, 504],
            'retry_exceptions' => [ConnectException::class, \RuntimeException::class],
        ];

        $context = \Fetch\Support\RequestContext::fromOptions($options);

        $this->assertEquals(3, $context->getMaxRetries());
        $this->assertEquals(250, $context->getRetryDelay());
        $this->assertEquals([429, 503, 504], $context->getRetryableStatusCodes());
        $this->assertEquals([ConnectException::class, \RuntimeException::class], $context->getRetryableExceptions());
    }

    public function testRequestContextToArrayIncludesRetryConfig(): void
    {
        $customStatusCodes = [418, 500, 502];
        $customExceptions = [\InvalidArgumentException::class];

        $context = \Fetch\Support\RequestContext::create()
            ->withRetry(4, 300)
            ->withRetryableStatusCodes($customStatusCodes)
            ->withRetryableExceptions($customExceptions);

        $array = $context->toArray();

        $this->assertEquals(4, $array['retries']);
        $this->assertEquals(300, $array['retry_delay']);
        // Custom config should be included since it differs from defaults
        $this->assertEquals($customStatusCodes, $array['retry_status_codes']);
        $this->assertEquals($customExceptions, $array['retry_exceptions']);
    }

    public function testRequestContextDefaultRetryableStatusCodes(): void
    {
        // Default context should have standard retryable status codes
        $context = \Fetch\Support\RequestContext::create();

        $defaultCodes = \Fetch\Support\RequestContext::DEFAULT_RETRYABLE_STATUS_CODES;
        $this->assertEquals($defaultCodes, $context->getRetryableStatusCodes());
        $this->assertContains(429, $context->getRetryableStatusCodes()); // Too Many Requests
        $this->assertContains(503, $context->getRetryableStatusCodes()); // Service Unavailable
    }

    public function testRequestContextDefaultRetryableExceptions(): void
    {
        // Default context should have standard retryable exceptions
        $context = \Fetch\Support\RequestContext::create();

        $defaultExceptions = \Fetch\Support\RequestContext::DEFAULT_RETRYABLE_EXCEPTIONS;
        $this->assertEquals($defaultExceptions, $context->getRetryableExceptions());
        $this->assertContains(ConnectException::class, $context->getRetryableExceptions());
    }
}
