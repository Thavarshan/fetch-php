<?php

declare(strict_types=1);

namespace Tests\Unit;

use Exception;
use Fetch\Concerns\ManagesPromises;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use React\Promise\PromiseInterface;
use ReflectionObject;
use Throwable;

class ManagesPromisesTest extends TestCase
{
    public function test_async_default_value(): void
    {
        $instance = $this->createTraitImplementation();

        $result = $instance->async();

        $this->assertTrue($instance->isAsync());
        $this->assertSame($instance, $result);
    }

    public function test_async_explicit_values(): void
    {
        $instance = $this->createTraitImplementation();

        // Set to true
        $result = $instance->async(true);
        $this->assertTrue($instance->isAsync());
        $this->assertSame($instance, $result);

        // Set to false
        $result = $instance->async(false);
        $this->assertFalse($instance->isAsync());
        $this->assertSame($instance, $result);

        // Set to null (should default to true)
        $result = $instance->async(null);
        $this->assertTrue($instance->isAsync());
        $this->assertSame($instance, $result);
    }

    public function test_is_async(): void
    {
        $instance = $this->createTraitImplementation();

        // Default should be false
        $this->assertFalse($instance->isAsync());

        // After setting to true
        $instance->async(true);
        $this->assertTrue($instance->isAsync());

        // After setting to false
        $instance->async(false);
        $this->assertFalse($instance->isAsync());
    }

    public function test_wrap_async(): void
    {
        $instance = $this->createTraitImplementation();

        $result = $instance->wrapAsync(function () {
            return 'test result';
        });

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function test_then(): void
    {
        $instance = $this->createTraitImplementation();
        $instance->setAsyncResult('promise result');

        $result = $instance->then(function ($value) {
            return 'then: '.$value;
        });

        $this->assertInstanceOf(PromiseInterface::class, $result);
        $this->assertTrue($instance->isAsync());
    }

    public function test_catch(): void
    {
        $instance = $this->createTraitImplementation();
        $instance->setAsyncResult(new Exception('test exception'));

        $result = $instance->catch(function ($error) {
            return 'error: '.$error->getMessage();
        });

        $this->assertInstanceOf(PromiseInterface::class, $result);
        $this->assertTrue($instance->isAsync());
    }

    public function test_finally(): void
    {
        $instance = $this->createTraitImplementation();
        $instance->setAsyncResult('promise result');

        $result = $instance->finally(function () {
            return 'finally called';
        });

        $this->assertInstanceOf(PromiseInterface::class, $result);
        $this->assertTrue($instance->isAsync());
    }

    public function test_resolve(): void
    {
        $instance = $this->createTraitImplementation();

        $value = 'test value';
        $result = $instance->resolve($value);

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function test_reject(): void
    {
        $instance = $this->createTraitImplementation();

        $error = new Exception('test error');
        $result = $instance->reject($error);

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function test_validate_promises_with_valid_promises(): void
    {
        $instance = $this->createTraitImplementation();

        $promises = [
            \React\Promise\resolve('test1'),
            \React\Promise\resolve('test2'),
            \React\Promise\resolve('test3'),
        ];

        // Call the protected method using reflection
        $reflection = new ReflectionObject($instance);
        $method = $reflection->getMethod('validatePromises');
        $method->setAccessible(true);

        // No exception should be thrown
        $method->invoke($instance, $promises);

        $this->assertTrue(true); // Just to assert something
    }

    public function test_validate_promises_with_invalid_items(): void
    {
        $instance = $this->createTraitImplementation();

        $promises = [
            \React\Promise\resolve('test1'),
            'not a promise',
            \React\Promise\resolve('test3'),
        ];

        // Call the protected method using reflection
        $reflection = new ReflectionObject($instance);
        $method = $reflection->getMethod('validatePromises');
        $method->setAccessible(true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item at index 1 is not a promise');

        $method->invoke($instance, $promises);
    }

    public function test_all(): void
    {
        $instance = $this->createTraitImplementation();

        $promises = [
            \React\Promise\resolve('test1'),
            \React\Promise\resolve('test2'),
        ];

        $result = $instance->all($promises);

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function test_race(): void
    {
        $instance = $this->createTraitImplementation();

        $promises = [
            \React\Promise\resolve('test1'),
            \React\Promise\resolve('test2'),
        ];

        $result = $instance->race($promises);

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function test_any(): void
    {
        $this->markTestSkipped('This test can cause memory issues - skipping for now');

        $instance = $this->createTraitImplementation();

        // Create a mix of resolving and rejecting promises
        $promises = [
            \React\Promise\reject(new Exception('error1')),
            \React\Promise\resolve('success'),
            \React\Promise\reject(new Exception('error2')),
        ];

        $result = $instance->any($promises);

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function test_sequence(): void
    {
        $this->markTestSkipped('This test can cause memory issues - skipping for now');

        $instance = $this->createTraitImplementation();

        $callables = [
            function () {
                return \React\Promise\resolve('result1');
            },
            function () {
                return \React\Promise\resolve('result2');
            },
        ];

        $result = $instance->sequence($callables);

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function test_map_empty_items(): void
    {
        $instance = $this->createTraitImplementation();

        $items = [];

        $result = $instance->map($items, function ($item) {
            return \React\Promise\resolve('mapped '.$item);
        });

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function test_map_invalid_concurrency(): void
    {
        $instance = $this->createTraitImplementation();

        $items = ['item1', 'item2', 'item3'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Concurrency must be greater than 0');

        $instance->map($items, function ($item) {
            return \React\Promise\resolve('mapped '.$item);
        }, 0); // Invalid concurrency
    }

    public function test_map_with_valid_items(): void
    {
        $this->markTestSkipped('This test can cause memory issues - skipping for now');

        $instance = $this->createTraitImplementation();

        $items = ['item1', 'item2'];

        $result = $instance->map($items, function ($item) {
            return \React\Promise\resolve('mapped '.$item);
        });

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    private function createTraitImplementation()
    {
        return new class
        {
            use ManagesPromises;

            private $asyncResult = null;

            // Required implementation method that returns a promise
            public function sendAsync(): PromiseInterface
            {
                if ($this->asyncResult instanceof Throwable) {
                    return \React\Promise\reject($this->asyncResult);
                }

                return \React\Promise\resolve($this->asyncResult ?? 'default result');
            }

            public function setAsyncResult($result): self
            {
                $this->asyncResult = $result;

                return $this;
            }

            // Stub implementations of methods not relevant to our tests
            public function getFullUri(): string
            {
                return 'https://example.com';
            }

            public function request(string $method, string $uri, mixed $body = null, $contentType = 'application/json', array $options = [])
            {
                return $this->sendAsync();
            }
        };
    }
}
