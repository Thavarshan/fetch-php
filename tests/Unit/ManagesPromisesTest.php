<?php

namespace Tests\Unit;

use Exception;
use Fetch\Http\ClientHandler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use ReflectionClass;

class ManagesPromisesTest extends TestCase
{
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new class extends ClientHandler
        {
            // Expose the sendAsync method for testing
            public function exposeSendAsync(): PromiseInterface
            {
                return async(function () {
                    return 'success';
                });
            }

            // Override sendAsync for testing
            protected function sendAsync(): PromiseInterface
            {
                return async(function () {
                    return 'success';
                });
            }
        };
    }

    public function test_async_mode_setting(): void
    {
        // Default should be false
        $this->assertFalse($this->handler->isAsync());

        // Set to true
        $handler = $this->handler->async();
        $this->assertTrue($handler->isAsync());

        // Set to false
        $handler = $this->handler->async(false);
        $this->assertFalse($handler->isAsync());

        // Set with null (should default to true)
        $handler = $this->handler->async(null);
        $this->assertTrue($handler->isAsync());
    }

    public function test_wrap_async(): void
    {
        $promise = $this->handler->wrapAsync(function () {
            return 'test result';
        });

        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $result = await($promise);
        $this->assertEquals('test result', $result);
    }

    public function test_await_promise(): void
    {
        $promise = resolve('test value');
        $result = $this->handler->awaitPromise($promise);

        $this->assertEquals('test value', $result);
    }

    public function test_await_promise_with_rejection(): void
    {
        $promise = reject(new Exception('test error'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('test error');

        $this->handler->awaitPromise($promise);
    }

    public function test_await_with_timeout_success(): void
    {
        // Create a promise that resolves quickly
        $promise = async(function () {
            return 'quick result';
        });

        // Use reflection to access protected method
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('awaitWithTimeout');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, $promise, 1.0);
        $this->assertEquals('quick result', $result);
    }

    public function test_all_with_promises(): void
    {
        $promises = [
            resolve('first'),
            resolve('second'),
            resolve('third'),
        ];

        $combinedPromise = $this->handler->all($promises);
        $this->assertInstanceOf(PromiseInterface::class, $combinedPromise);

        $results = await($combinedPromise);
        $this->assertEquals(['first', 'second', 'third'], $results);
    }

    public function test_all_with_invalid_promises(): void
    {
        $promises = [
            resolve('first'),
            'not a promise',
            resolve('third'),
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item at index 1 is not a promise');

        $this->handler->all($promises);
    }

    public function test_race_with_promises(): void
    {
        // Create deferreds to control resolution order
        $deferred1 = new Deferred;
        $deferred2 = new Deferred;
        $deferred3 = new Deferred;

        $promises = [
            $deferred1->promise(),
            $deferred2->promise(),
            $deferred3->promise(),
        ];

        $racePromise = $this->handler->race($promises);
        $this->assertInstanceOf(PromiseInterface::class, $racePromise);

        $result = null;
        $racePromise->then(function ($value) use (&$result) {
            $result = $value;
        });

        // Resolve the promises in a specific order
        $deferred2->resolve('winner');
        $deferred1->resolve('too late');
        $deferred3->resolve('also too late');

        $this->assertEquals('winner', $result);
    }

    public function test_any_with_promises(): void
    {
        $promises = [
            reject(new Exception('first error')),
            resolve('success'),
            reject(new Exception('third error')),
        ];

        $anyPromise = $this->handler->any($promises);
        $this->assertInstanceOf(PromiseInterface::class, $anyPromise);

        $result = await($anyPromise);
        $this->assertEquals('success', $result);
    }

    public function test_sequence_with_callables(): void
    {
        $callables = [
            function () {
                return resolve('first');
            },
            function () {
                return resolve('second');
            },
            function () {
                return resolve('third');
            },
        ];

        $sequencePromise = $this->handler->sequence($callables);
        $this->assertInstanceOf(PromiseInterface::class, $sequencePromise);

        $results = await($sequencePromise);
        $this->assertEquals(['first', 'second', 'third'], $results);
    }

    public function test_then_method(): void
    {
        $this->handler->async();

        $promise = $this->handler->then(function ($result) {
            return $result.' with then';
        });

        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $result = await($promise);
        $this->assertEquals('success with then', $result);
    }

    public function test_catch_method(): void
    {
        $mockHandler = new class extends ClientHandler
        {
            protected function sendAsync(): PromiseInterface
            {
                return reject(new Exception('test error'));
            }
        };

        $mockHandler->async();

        $promise = $mockHandler->catch(function ($error) {
            return 'caught: '.$error->getMessage();
        });

        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $result = await($promise);
        $this->assertEquals('caught: test error', $result);
    }

    public function test_finally_method(): void
    {
        $this->handler->async();

        $finallyRun = false;

        $promise = $this->handler->finally(function () use (&$finallyRun) {
            $finallyRun = true;
        });

        $this->assertInstanceOf(PromiseInterface::class, $promise);
        await($promise);
        $this->assertTrue($finallyRun);
    }

    public function test_resolve_method(): void
    {
        $promise = $this->handler->resolve('test value');
        $this->assertInstanceOf(PromiseInterface::class, $promise);

        $result = await($promise);
        $this->assertEquals('test value', $result);
    }

    public function test_reject_method(): void
    {
        $promise = $this->handler->reject('test reason');
        $this->assertInstanceOf(PromiseInterface::class, $promise);

        try {
            await($promise);
            $this->fail('Promise should have been rejected');
        } catch (\Throwable $e) {
            $this->assertEquals('test reason', $e->getMessage());
        }
    }

    public function test_map_with_empty_items(): void
    {
        $items = [];
        $mapPromise = $this->handler->map($items, function ($item) {
            return resolve($item);
        });

        $results = await($mapPromise);
        $this->assertEquals([], $results);
    }

    public function test_map_with_items_under_concurrency_limit(): void
    {
        $items = [1, 2, 3];
        $mapPromise = $this->handler->map($items, function ($item) {
            return resolve($item * 2);
        }, 5); // Concurrency higher than item count

        $results = await($mapPromise);
        $this->assertEquals([2, 4, 6], $results);
    }

    public function test_map_throws_with_invalid_concurrency(): void
    {
        $items = [1, 2, 3];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Concurrency must be greater than 0');

        $this->handler->map($items, function ($item) {
            return resolve($item);
        }, 0); // Invalid concurrency
    }

    public function test_validate_promises(): void
    {
        $promises = [
            resolve('first'),
            resolve('second'),
        ];

        // Use reflection to access protected method
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('validatePromises');
        $method->setAccessible(true);

        // Should not throw an exception
        $method->invoke($this->handler, $promises);

        // Assert passes if no exception is thrown
        $this->assertTrue(true);
    }

    public function test_validate_promises_with_invalid_item(): void
    {
        $promises = [
            resolve('first'),
            'not a promise',
            resolve('third'),
        ];

        // Use reflection to access protected method
        $reflection = new ReflectionClass($this->handler);
        $method = $reflection->getMethod('validatePromises');
        $method->setAccessible(true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Item at index 1 is not a promise');

        $method->invoke($this->handler, $promises);
    }
}
