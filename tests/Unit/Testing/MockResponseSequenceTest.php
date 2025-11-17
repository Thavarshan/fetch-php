<?php

declare(strict_types=1);

namespace Tests\Unit\Testing;

use Fetch\Testing\MockResponse;
use Fetch\Testing\MockResponseSequence;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

class MockResponseSequenceTest extends TestCase
{
    public function test_creates_empty_sequence(): void
    {
        $sequence = new MockResponseSequence;

        $this->assertTrue($sequence->isEmpty());
        $this->assertSame(0, $sequence->count());
    }

    public function test_creates_sequence_with_initial_responses(): void
    {
        $responses = [
            MockResponse::ok(),
            MockResponse::created(),
        ];

        $sequence = new MockResponseSequence($responses);

        $this->assertFalse($sequence->isEmpty());
        $this->assertSame(2, $sequence->count());
    }

    public function test_pushes_response(): void
    {
        $sequence = new MockResponseSequence;
        $sequence->push(200, 'First');
        $sequence->push(201, 'Second');

        $this->assertSame(2, $sequence->count());
    }

    public function test_pushes_json_response(): void
    {
        $sequence = new MockResponseSequence;
        $sequence->pushJson(['data' => 'value'], 200);

        $response = $sequence->next();

        $this->assertSame(200, $response->getStatus());
        $this->assertSame(json_encode(['data' => 'value']), $response->getBody());
    }

    public function test_pushes_status_response(): void
    {
        $sequence = new MockResponseSequence;
        $sequence->pushStatus(404);

        $response = $sequence->next();

        $this->assertSame(404, $response->getStatus());
        $this->assertSame('', $response->getBody());
    }

    public function test_pushes_response_instance(): void
    {
        $mockResponse = MockResponse::ok('Test');
        $sequence = new MockResponseSequence;
        $sequence->pushResponse($mockResponse);

        $response = $sequence->next();

        $this->assertSame($mockResponse, $response);
    }

    public function test_gets_next_response_in_sequence(): void
    {
        $sequence = new MockResponseSequence;
        $sequence->push(200, 'First');
        $sequence->push(201, 'Second');
        $sequence->push(202, 'Third');

        $first = $sequence->next();
        $this->assertSame(200, $first->getStatus());
        $this->assertSame('First', $first->getBody());

        $second = $sequence->next();
        $this->assertSame(201, $second->getStatus());
        $this->assertSame('Second', $second->getBody());

        $third = $sequence->next();
        $this->assertSame(202, $third->getStatus());
        $this->assertSame('Third', $third->getBody());
    }

    public function test_throws_when_sequence_exhausted(): void
    {
        $sequence = new MockResponseSequence;
        $sequence->push(200, 'Only one');

        $sequence->next(); // Get the first and only response

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('No more responses in the sequence.');

        $sequence->next();
    }

    public function test_uses_default_response_when_empty(): void
    {
        $defaultResponse = MockResponse::ok('Default');
        $sequence = new MockResponseSequence;
        $sequence->push(200, 'First');
        $sequence->whenEmpty($defaultResponse);

        $sequence->next(); // Get the first response

        // Now sequence is exhausted, should return default
        $response = $sequence->next();

        $this->assertSame($defaultResponse, $response);
    }

    public function test_loops_sequence(): void
    {
        $sequence = new MockResponseSequence;
        $sequence->push(200, 'First');
        $sequence->push(201, 'Second');
        $sequence->loop();

        // Get first round
        $first = $sequence->next();
        $second = $sequence->next();

        // Should loop back to the beginning
        $firstAgain = $sequence->next();
        $secondAgain = $sequence->next();

        $this->assertSame(200, $first->getStatus());
        $this->assertSame(201, $second->getStatus());
        $this->assertSame(200, $firstAgain->getStatus());
        $this->assertSame(201, $secondAgain->getStatus());
    }

    public function test_checks_has_more(): void
    {
        $sequence = new MockResponseSequence;
        $sequence->push(200, 'First');

        $this->assertTrue($sequence->hasMore());

        $sequence->next();

        $this->assertFalse($sequence->hasMore());
    }

    public function test_has_more_with_default_response(): void
    {
        $sequence = new MockResponseSequence;
        $sequence->push(200, 'First');
        $sequence->whenEmpty(MockResponse::ok());

        $sequence->next(); // Exhaust the sequence

        $this->assertTrue($sequence->hasMore()); // Still has more because of default
    }

    public function test_has_more_with_loop(): void
    {
        $sequence = new MockResponseSequence;
        $sequence->push(200, 'First');
        $sequence->loop();

        $sequence->next(); // Would normally exhaust

        $this->assertTrue($sequence->hasMore()); // Still has more because of loop
    }

    public function test_resets_sequence(): void
    {
        $sequence = new MockResponseSequence;
        $sequence->push(200, 'First');
        $sequence->push(201, 'Second');

        $sequence->next(); // Move to index 1
        $this->assertSame(1, $sequence->getCurrentIndex());

        $sequence->reset();
        $this->assertSame(0, $sequence->getCurrentIndex());

        $response = $sequence->next();
        $this->assertSame(200, $response->getStatus());
    }

    public function test_gets_current_index(): void
    {
        $sequence = new MockResponseSequence;
        $sequence->push(200, 'First');
        $sequence->push(201, 'Second');

        $this->assertSame(0, $sequence->getCurrentIndex());

        $sequence->next();
        $this->assertSame(1, $sequence->getCurrentIndex());

        $sequence->next();
        $this->assertSame(2, $sequence->getCurrentIndex());
    }

    public function test_fluent_interface(): void
    {
        $sequence = (new MockResponseSequence)
            ->push(200, 'First')
            ->pushJson(['data' => 'value'])
            ->pushStatus(404)
            ->whenEmpty(MockResponse::ok())
            ->loop()
            ->reset();

        $this->assertInstanceOf(MockResponseSequence::class, $sequence);
        $this->assertSame(3, $sequence->count());
    }
}
