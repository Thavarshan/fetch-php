<?php

declare(strict_types=1);

namespace Tests\Unit\Testing;

use Fetch\Enum\Status;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockResponseSequence;
use PHPUnit\Framework\TestCase;

class MockResponseTest extends TestCase
{
    public function test_creates_basic_mock_response(): void
    {
        $response = MockResponse::create(200, 'Hello World', ['X-Custom' => 'value']);

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('Hello World', $response->getBody());
        $this->assertSame(['X-Custom' => 'value'], $response->getHeaders());
    }

    public function test_creates_json_response(): void
    {
        $data = ['name' => 'John', 'age' => 30];
        $response = MockResponse::json($data, 201);

        $this->assertSame(201, $response->getStatus());
        $this->assertSame(json_encode($data), $response->getBody());
        $this->assertArrayHasKey('Content-Type', $response->getHeaders());
        $this->assertSame('application/json', $response->getHeaders()['Content-Type']);
    }

    public function test_sets_delay(): void
    {
        $response = MockResponse::create()->delay(100);

        $this->assertSame(100, $response->getDelay());
    }

    public function test_sets_throwable(): void
    {
        $exception = new \RuntimeException('Test error');
        $response = MockResponse::create()->throw($exception);

        $this->assertSame($exception, $response->getThrowable());
    }

    public function test_executes_response_with_delay(): void
    {
        $response = MockResponse::create(200, 'Test')->delay(10);

        $start = microtime(true);
        $executed = $response->execute();
        $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds

        // Allow 2ms tolerance for timer precision issues on Windows
        $this->assertGreaterThanOrEqual(8, $duration);
        $this->assertSame(200, $executed->status());
        $this->assertSame('Test', $executed->body());
    }

    public function test_executes_response_throws_exception(): void
    {
        $exception = new \RuntimeException('Test error');
        $response = MockResponse::create()->throw($exception);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test error');

        $response->execute();
    }

    public function test_creates_ok_response(): void
    {
        $response = MockResponse::ok('Success');

        $this->assertSame(Status::OK->value, $response->getStatus());
        $this->assertSame('Success', $response->getBody());
    }

    public function test_creates_created_response(): void
    {
        $response = MockResponse::created('Resource created');

        $this->assertSame(Status::CREATED->value, $response->getStatus());
        $this->assertSame('Resource created', $response->getBody());
    }

    public function test_creates_no_content_response(): void
    {
        $response = MockResponse::noContent();

        $this->assertSame(Status::NO_CONTENT->value, $response->getStatus());
        $this->assertSame('', $response->getBody());
    }

    public function test_creates_bad_request_response(): void
    {
        $response = MockResponse::badRequest('Invalid input');

        $this->assertSame(Status::BAD_REQUEST->value, $response->getStatus());
        $this->assertSame('Invalid input', $response->getBody());
    }

    public function test_creates_unauthorized_response(): void
    {
        $response = MockResponse::unauthorized();

        $this->assertSame(Status::UNAUTHORIZED->value, $response->getStatus());
    }

    public function test_creates_forbidden_response(): void
    {
        $response = MockResponse::forbidden();

        $this->assertSame(Status::FORBIDDEN->value, $response->getStatus());
    }

    public function test_creates_not_found_response(): void
    {
        $response = MockResponse::notFound();

        $this->assertSame(Status::NOT_FOUND->value, $response->getStatus());
    }

    public function test_creates_unprocessable_entity_response(): void
    {
        $response = MockResponse::unprocessableEntity(['errors' => ['field' => 'required']]);

        $this->assertSame(Status::UNPROCESSABLE_ENTITY->value, $response->getStatus());
    }

    public function test_creates_server_error_response(): void
    {
        $response = MockResponse::serverError();

        $this->assertSame(Status::INTERNAL_SERVER_ERROR->value, $response->getStatus());
    }

    public function test_creates_service_unavailable_response(): void
    {
        $response = MockResponse::serviceUnavailable();

        $this->assertSame(Status::SERVICE_UNAVAILABLE->value, $response->getStatus());
    }

    public function test_creates_sequence(): void
    {
        $sequence = MockResponse::sequence([
            MockResponse::ok(),
            MockResponse::created(),
        ]);

        $this->assertInstanceOf(MockResponseSequence::class, $sequence);
    }

    public function test_executes_response_converts_array_to_json(): void
    {
        $response = MockResponse::create(200, ['key' => 'value']);

        $executed = $response->execute();

        $this->assertSame(json_encode(['key' => 'value']), $executed->body());
    }

    public function test_executes_response_keeps_string_body(): void
    {
        $response = MockResponse::create(200, 'Plain text');

        $executed = $response->execute();

        $this->assertSame('Plain text', $executed->body());
    }
}
