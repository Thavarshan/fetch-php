<?php

declare(strict_types=1);

namespace Tests\Integration;

use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;
use Fetch\Testing\Recorder;
use PHPUnit\Framework\TestCase;

use function fetch;
use function get;
use function post;

class MockingIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        MockServer::resetInstance();
        Recorder::resetInstance();
    }

    protected function tearDown(): void
    {
        MockServer::resetInstance();
        Recorder::resetInstance();
        parent::tearDown();
    }

    public function test_mocks_simple_get_request(): void
    {
        MockServer::fake([
            'https://api.example.com/users' => MockResponse::json(['users' => ['John', 'Jane']]),
        ]);

        $response = get('https://api.example.com/users');

        $this->assertSame(200, $response->status());
        $this->assertSame(['users' => ['John', 'Jane']], $response->json());
    }

    public function test_mocks_post_request(): void
    {
        MockServer::fake([
            'POST https://api.example.com/users' => MockResponse::created(['id' => 123]),
        ]);

        $response = post('https://api.example.com/users', ['name' => 'John Doe']);

        $this->assertSame(201, $response->status());
        $this->assertSame(['id' => 123], $response->json());
    }

    public function test_mocks_with_wildcard_pattern(): void
    {
        MockServer::fake([
            'https://api.example.com/users/*' => MockResponse::json(['user' => 'found']),
        ]);

        $response1 = get('https://api.example.com/users/123');
        $response2 = get('https://api.example.com/users/456');

        $this->assertSame(['user' => 'found'], $response1->json());
        $this->assertSame(['user' => 'found'], $response2->json());
    }

    public function test_mocks_with_sequence(): void
    {
        MockServer::fake([
            'https://api.example.com/flaky' => MockResponse::sequence()
                ->pushStatus(500)
                ->pushStatus(500)
                ->pushStatus(200),
        ]);

        $response1 = get('https://api.example.com/flaky');
        $response2 = get('https://api.example.com/flaky');
        $response3 = get('https://api.example.com/flaky');

        $this->assertSame(500, $response1->status());
        $this->assertSame(500, $response2->status());
        $this->assertSame(200, $response3->status());
    }

    public function test_mocks_with_callback(): void
    {
        MockServer::fake(function ($request) {
            if ($request->hasHeader('Authorization')) {
                return MockResponse::json(['authenticated' => true]);
            }

            return MockResponse::unauthorized();
        });

        $authenticatedResponse = fetch('https://api.example.com/protected', [
            'headers' => ['Authorization' => 'Bearer token'],
        ]);

        $unauthenticatedResponse = get('https://api.example.com/protected');

        $this->assertSame(200, $authenticatedResponse->status());
        $this->assertSame(['authenticated' => true], $authenticatedResponse->json());
        $this->assertSame(401, $unauthenticatedResponse->status());
    }

    public function test_asserts_requests_sent(): void
    {
        MockServer::fake([
            '*' => MockResponse::ok(),
        ]);

        post('https://api.example.com/users', ['name' => 'John']);
        get('https://api.example.com/users');

        MockServer::assertSent('POST https://api.example.com/users');
        MockServer::assertSent('GET https://api.example.com/users');
        MockServer::assertSentCount(2);
    }

    public function test_asserts_with_callback(): void
    {
        MockServer::fake([
            '*' => MockResponse::ok(),
        ]);

        post('https://api.example.com/users', ['name' => 'John Doe']);

        MockServer::assertSent(function ($request) {
            $body = (string) $request->getBody();

            return str_contains($body, 'John Doe');
        });
    }

    public function test_asserts_not_sent(): void
    {
        MockServer::fake([
            '*' => MockResponse::ok(),
        ]);

        get('https://api.example.com/users');

        MockServer::assertNotSent('POST https://api.example.com/users');
        MockServer::assertNotSent('https://api.example.com/posts');
    }

    public function test_prevents_stray_requests(): void
    {
        MockServer::fake([
            'https://api.example.com/*' => MockResponse::ok(),
        ]);
        MockServer::preventStrayRequests();

        // This should work
        $response = get('https://api.example.com/users');
        $this->assertSame(200, $response->status());

        // This should throw
        $this->expectException(\InvalidArgumentException::class);
        get('https://other-api.example.com/data');
    }

    public function test_records_and_replays_requests(): void
    {
        // First, make some "real" requests with mocked responses
        MockServer::fake([
            'https://api.example.com/users' => MockResponse::json(['users' => ['John']]),
            'POST https://api.example.com/users' => MockResponse::created(['id' => 1]),
        ]);

        Recorder::start();

        get('https://api.example.com/users');
        post('https://api.example.com/users', ['name' => 'Jane']);

        $recordings = Recorder::stop();

        // Verify we recorded 2 requests
        $this->assertCount(2, $recordings);

        // Reset the mock server
        MockServer::resetInstance();

        // Replay the recordings
        Recorder::replay($recordings);

        // Now make the same requests again
        $response1 = get('https://api.example.com/users');
        $response2 = post('https://api.example.com/users', ['name' => 'Jane']);

        $this->assertSame(['users' => ['John']], $response1->json());
        $this->assertSame(201, $response2->status());
    }

    public function test_exports_and_imports_recordings(): void
    {
        MockServer::fake([
            'https://api.example.com/users' => MockResponse::json(['users' => []]),
        ]);

        Recorder::start();
        get('https://api.example.com/users');
        Recorder::stop();

        // Export to JSON
        $json = Recorder::exportToJson();

        // Reset everything
        MockServer::resetInstance();
        Recorder::resetInstance();

        // Import from JSON
        Recorder::importFromJson($json);

        // Verify it works
        $response = get('https://api.example.com/users');
        $this->assertSame(['users' => []], $response->json());
    }

    public function test_fake_with_delay(): void
    {
        MockServer::fake([
            'https://api.example.com/slow' => MockResponse::ok('Done')->delay(50),
        ]);

        $start = microtime(true);
        $response = get('https://api.example.com/slow');
        $duration = (microtime(true) - $start) * 1000;

        $this->assertGreaterThanOrEqual(50, $duration);
        $this->assertSame('Done', $response->body());
    }

    public function test_fake_with_exception(): void
    {
        MockServer::fake([
            'https://api.example.com/error' => MockResponse::ok()->throw(new \RuntimeException('Network error')),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Network error');

        get('https://api.example.com/error');
    }

    public function test_multiple_patterns_with_priority(): void
    {
        MockServer::fake([
            'https://api.example.com/users/123' => MockResponse::json(['id' => 123, 'specific' => true]),
            'https://api.example.com/users/*' => MockResponse::json(['id' => 999, 'wildcard' => true]),
        ]);

        // Exact match should take priority
        $response1 = get('https://api.example.com/users/123');
        $this->assertTrue($response1->json()['specific']);

        // Wildcard should match others
        $response2 = get('https://api.example.com/users/456');
        $this->assertTrue($response2->json()['wildcard']);
    }

    public function test_fetch_with_various_options(): void
    {
        MockServer::fake([
            '*' => MockResponse::ok('Success'),
        ]);

        $response = fetch('https://api.example.com/users', [
            'method' => 'POST',
            'headers' => [
                'Authorization' => 'Bearer token',
                'Accept' => 'application/json',
            ],
            'json' => ['name' => 'John'],
            'timeout' => 30,
        ]);

        $this->assertSame(200, $response->status());

        MockServer::assertSent(function ($request) {
            return $request->hasHeader('Authorization')
                && $request->hasHeader('Accept')
                && $request->getMethod() === 'POST';
        });
    }

    public function test_recorded_requests_include_all_details(): void
    {
        MockServer::fake([
            '*' => MockResponse::json(['result' => 'ok']),
        ]);

        post('https://api.example.com/users', ['name' => 'John'], [
            'headers' => ['X-Custom' => 'value'],
        ]);

        $recorded = MockServer::recorded();

        $this->assertCount(1, $recorded);
        $this->assertSame('POST', $recorded[0]['request']->getMethod());
        $this->assertSame('https://api.example.com/users', (string) $recorded[0]['request']->getUri());
        $this->assertSame(['result' => 'ok'], $recorded[0]['response']->json());
    }
}
