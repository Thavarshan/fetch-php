<?php

declare(strict_types=1);

namespace Tests\Unit;

use Exception;
use Fetch\Http\Response;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Tests\Mocks\ManagesRetriesTestClass;

class ManagesRetriesTest extends TestCase
{
    /**
     * The test class instance.
     */
    private ManagesRetriesTestClass $handler;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new ManagesRetriesTestClass;
    }

    /**
     * Test setting retryable status codes.
     */
    public function test_retryable_status_codes(): void
    {
        // Get the default retryable status codes
        $defaultCodes = $this->getPropertyValue($this->handler, 'retryableStatusCodes');

        // Assert that the default includes common error codes
        $this->assertContains(429, $defaultCodes); // Too many requests
        $this->assertContains(503, $defaultCodes); // Service unavailable

        // Set custom retryable status codes
        $customCodes = [500, 502, 503];
        $this->setPropertyValue($this->handler, 'retryableStatusCodes', $customCodes);

        // Verify that the property was updated
        $this->assertSame($customCodes, $this->getPropertyValue($this->handler, 'retryableStatusCodes'));
    }

    /**
     * Test setting retryable exceptions.
     */
    public function test_retryable_exceptions(): void
    {
        // Get the default retryable exceptions
        $defaultExceptions = $this->getPropertyValue($this->handler, 'retryableExceptions');

        // Assert that ConnectException is included by default
        $this->assertContains(ConnectException::class, $defaultExceptions);

        // Set custom retryable exceptions
        $customExceptions = [ConnectException::class, RuntimeException::class];
        $this->setPropertyValue($this->handler, 'retryableExceptions', $customExceptions);

        // Verify that the property was updated
        $this->assertSame($customExceptions, $this->getPropertyValue($this->handler, 'retryableExceptions'));
    }

    /**
     * Test the exponential backoff calculation.
     */
    public function test_calculate_backoff_delay(): void
    {
        // Base delay of 100ms, attempt 0 (first attempt)
        $delay1 = $this->callMethod($this->handler, 'calculateBackoffDelay', [100, 0]);
        $this->assertGreaterThanOrEqual(100, $delay1); // Should be at least the base delay
        $this->assertLessThanOrEqual(200, $delay1); // Should not be more than 2x base delay (due to jitter)

        // Base delay of 100ms, attempt 1 (second attempt)
        $delay2 = $this->callMethod($this->handler, 'calculateBackoffDelay', [100, 1]);
        $this->assertGreaterThanOrEqual(200, $delay2); // Should be at least 2x base delay
        $this->assertLessThanOrEqual(400, $delay2); // Should not be more than 4x base delay (due to jitter)

        // Base delay of 100ms, attempt 2 (third attempt)
        $delay3 = $this->callMethod($this->handler, 'calculateBackoffDelay', [100, 2]);
        $this->assertGreaterThanOrEqual(400, $delay3); // Should be at least 4x base delay
        $this->assertLessThanOrEqual(800, $delay3); // Should not be more than 8x base delay (due to jitter)

        // Test the cap at 30 seconds (30000ms)
        $delay4 = $this->callMethod($this->handler, 'calculateBackoffDelay', [10000, 10]); // This would be over 10M ms without a cap
        $this->assertLessThanOrEqual(30000, $delay4); // Should be capped at 30000ms
    }

    /**
     * Test if errors are correctly identified as retryable based on status code.
     */
    public function test_is_retryable_error_by_status_code(): void
    {
        // Create exceptions with retryable and non-retryable status codes
        $retryableException = new RequestException(
            'Server Error',
            new Request('GET', 'https://example.com')
        );

        $nonRetryableException = new RequestException(
            'Bad Request',
            new Request('GET', 'https://example.com')
        );

        // Mock code property since it's not easy to set in RequestException
        $reflection = new ReflectionProperty($retryableException, 'code');
        $reflection->setAccessible(true);
        $reflection->setValue($retryableException, 503);

        $reflection = new ReflectionProperty($nonRetryableException, 'code');
        $reflection->setAccessible(true);
        $reflection->setValue($nonRetryableException, 400);

        $this->assertTrue($this->callMethod($this->handler, 'isRetryableError', [$retryableException]));
        $this->assertFalse($this->callMethod($this->handler, 'isRetryableError', [$nonRetryableException]));
    }

    /**
     * Test if errors are correctly identified as retryable based on exception type.
     */
    public function test_is_retryable_error_by_exception_type(): void
    {
        // Create a RequestException with ConnectException as previous
        $request = new Request('GET', 'https://example.com');

        $retryableException = new RequestException(
            'Connection Error',
            $request,
            null,
            new ConnectException('Connection Error', $request)
        );

        // Set the status code to a non-retryable one to test the exception type path
        $reflection = new ReflectionProperty($retryableException, 'code');
        $reflection->setAccessible(true);
        $reflection->setValue($retryableException, 400);

        // Create a standard RequestException which should not be retryable by default
        $nonRetryableRequestException = new RequestException(
            'Bad Request',
            $request
        );

        // Set the status code to a non-retryable one
        $reflection = new ReflectionProperty($nonRetryableRequestException, 'code');
        $reflection->setAccessible(true);
        $reflection->setValue($nonRetryableRequestException, 400);

        $this->assertTrue($this->callMethod($this->handler, 'isRetryableError', [$retryableException]));
        $this->assertFalse($this->callMethod($this->handler, 'isRetryableError', [$nonRetryableRequestException]));
    }

    /**
     * Test the retryRequest method with successful first attempt.
     */
    public function test_retry_request_success_first_attempt(): void
    {
        $expectedResponse = new Response(200);

        $request = function () use ($expectedResponse) {
            return $expectedResponse;
        };

        // Mock the logRetry method to track calls
        $mockHandler = $this->createMock(ManagesRetriesTestClass::class);
        $mockHandler->expects($this->never())
            ->method('logRetry');

        $response = $this->callMethod($this->handler, 'retryRequest', [$request]);

        $this->assertSame($expectedResponse, $response);
    }

    /**
     * Test the retryRequest method with one failure before success.
     */
    public function test_retry_request_success_after_one_failure(): void
    {
        $expectedResponse = new Response(200);
        $exceptionToThrow = new RequestException(
            'Server Error',
            new Request('GET', 'https://example.com')
        );

        // Set the status code to a retryable one
        $reflection = new ReflectionProperty($exceptionToThrow, 'code');
        $reflection->setAccessible(true);
        $reflection->setValue($exceptionToThrow, 503);

        $attempt = 0;
        $request = function () use ($expectedResponse, $exceptionToThrow, &$attempt) {
            if ($attempt++ === 0) {
                throw $exceptionToThrow;
            }

            return $expectedResponse;
        };

        // Set the maxRetries and retryDelay
        $this->setPropertyValue($this->handler, 'maxRetries', 1);
        $this->setPropertyValue($this->handler, 'retryDelay', 1);

        $response = $this->callMethod($this->handler, 'retryRequest', [$request]);

        $this->assertSame($expectedResponse, $response);
        $this->assertSame(2, $attempt); // Should have tried twice
    }

    /**
     * Test the retryRequest method with all failures.
     */
    public function test_retry_request_all_failures(): void
    {
        $exceptionToThrow = new RequestException(
            'Server Error',
            new Request('GET', 'https://example.com')
        );

        // Set the status code to a retryable one
        $reflection = new ReflectionProperty($exceptionToThrow, 'code');
        $reflection->setAccessible(true);
        $reflection->setValue($exceptionToThrow, 503);

        $attempt = 0;
        $request = function () use ($exceptionToThrow, &$attempt) {
            $attempt++;
            throw $exceptionToThrow;
        };

        // Set the maxRetries and retryDelay
        $this->setPropertyValue($this->handler, 'maxRetries', 2);
        $this->setPropertyValue($this->handler, 'retryDelay', 1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request failed after 3 attempts with status code 503');

        try {
            $this->callMethod($this->handler, 'retryRequest', [$request]);
        } catch (Exception $e) {
            $this->assertSame(3, $attempt); // Should have tried 3 times
            throw $e;
        }
    }

    /**
     * Test the retryRequest method throws immediately for non-retryable status codes.
     */
    public function test_retry_request_non_retryable_status_code(): void
    {
        $exceptionToThrow = new RequestException(
            'Bad Request',
            new Request('GET', 'https://example.com')
        );

        // Set the status code to a non-retryable one
        $reflection = new ReflectionProperty($exceptionToThrow, 'code');
        $reflection->setAccessible(true);
        $reflection->setValue($exceptionToThrow, 400);

        $attempt = 0;
        $request = function () use ($exceptionToThrow, &$attempt) {
            $attempt++;
            throw $exceptionToThrow;
        };

        // Set the maxRetries and retryDelay
        $this->setPropertyValue($this->handler, 'maxRetries', 3);
        $this->setPropertyValue($this->handler, 'retryDelay', 1);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Bad Request');

        try {
            $this->callMethod($this->handler, 'retryRequest', [$request]);
        } catch (Exception $e) {
            $this->assertSame(1, $attempt); // Should have tried only once
            throw $e;
        }
    }

    /**
     * Test the retryRequest method with RuntimeException.
     */
    public function test_retry_request_with_runtime_exception(): void
    {
        $unexpectedException = new RuntimeException('Unexpected error');

        $request = function () use ($unexpectedException) {
            throw $unexpectedException;
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unexpected error during request: Unexpected error');

        $this->callMethod($this->handler, 'retryRequest', [$request]);
    }

    /**
     * Call a protected method on an object
     */
    private function callMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Set a property value in an object
     */
    private function setPropertyValue($object, string $propertyName, $value): void
    {
        $reflection = new ReflectionClass($object);

        if ($reflection->hasProperty($propertyName)) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue($object, $value);
        } else {
            // If property doesn't exist, add it
            $object->$propertyName = $value;
        }
    }

    /**
     * Get a property value from an object
     */
    private function getPropertyValue($object, string $propertyName)
    {
        $reflection = new ReflectionClass($object);

        if ($reflection->hasProperty($propertyName)) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);

            return $property->getValue($object);
        }

        return null;
    }
}
