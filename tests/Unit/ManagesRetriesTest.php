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
        $this->handler = new class extends ClientHandler
        {
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

    public function test_retry_configuration(): void
    {
        $this->handler->retry(3, 200);

        $this->assertEquals(3, $this->handler->getMaxRetries());
        $this->assertEquals(200, $this->handler->getRetryDelay());
    }

    public function test_retry_status_codes(): void
    {
        $statusCodes = [429, 503];
        $this->handler->retryStatusCodes($statusCodes);

        $this->assertEquals($statusCodes, $this->handler->getRetryableStatusCodes());
    }

    public function test_retry_exceptions(): void
    {
        $exceptions = [ConnectException::class];
        $this->handler->retryExceptions($exceptions);

        $this->assertEquals($exceptions, $this->handler->getRetryableExceptions());
    }

    public function test_calculate_backoff_delay(): void
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

    public function test_retry_request_with_success(): void
    {
        $mockResponse = new Response(200);
        $callCount = 0;

        $result = $this->handler->exposeRetryRequest(function () use (&$callCount, $mockResponse) {
            $callCount++;

            return $mockResponse;
        });

        $this->assertSame($mockResponse, $result);
        $this->assertEquals(1, $callCount, 'Request should be called exactly once on success');
    }

    public function test_retry_request_with_retryable_error_then_success(): void
    {
        // Mock a request exception with a retryable status code (using Fetch RequestException)
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockErrorResponse = new Response(503); // Service Unavailable
        $mockSuccessResponse = new Response(200);

        $exception = new RequestException('Service unavailable', $mockRequest, $mockErrorResponse);

        // Create a test handler and manually configure it
        $handler = new class extends ClientHandler
        {
            // Override isRetryableError to always return true for the test
            protected function isRetryableError(\Throwable $e): bool
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
            $callCount++;
            if ($callCount === 1) {
                throw $exception;
            }

            return $mockSuccessResponse;
        };

        // Call the exposed method
        $result = $handler->exposeRetryRequest($request);

        $this->assertSame($mockSuccessResponse, $result);
        $this->assertEquals(2, $callCount, 'Request should be called twice (one failure, one success)');
    }
}
