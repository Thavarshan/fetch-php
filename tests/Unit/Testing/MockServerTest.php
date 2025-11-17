<?php

declare(strict_types=1);

namespace Tests\Unit\Testing;

use Fetch\Http\Request;
use Fetch\Testing\MockResponse;
use Fetch\Testing\MockServer;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MockServerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        MockServer::resetInstance();
    }

    protected function tearDown(): void
    {
        MockServer::resetInstance();
        parent::tearDown();
    }

    public function test_fakes_all_requests_with_empty_200(): void
    {
        MockServer::fake();

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://example.com'));
        $response = MockServer::getInstance()->handleRequest($request);

        $this->assertNotNull($response);
        $this->assertSame(200, $response->status());
    }

    public function test_fakes_specific_url_pattern(): void
    {
        MockServer::fake([
            'https://api.example.com/users' => MockResponse::json(['users' => []]),
        ]);

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users'));
        $response = MockServer::getInstance()->handleRequest($request);

        $this->assertNotNull($response);
        $this->assertSame(['users' => []], $response->json());
    }

    public function test_fakes_with_method_and_url(): void
    {
        MockServer::fake([
            'GET https://api.example.com/users' => MockResponse::json(['users' => []]),
            'POST https://api.example.com/users' => MockResponse::created(),
        ]);

        $getRequest = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users'));
        $getResponse = MockServer::getInstance()->handleRequest($getRequest);

        $postRequest = Request::createFromBase(new GuzzleRequest('POST', 'https://api.example.com/users'));
        $postResponse = MockServer::getInstance()->handleRequest($postRequest);

        $this->assertSame(200, $getResponse->status());
        $this->assertSame(201, $postResponse->status());
    }

    public function test_fakes_with_wildcard_pattern(): void
    {
        MockServer::fake([
            'https://api.example.com/users/*' => MockResponse::json(['id' => 1]),
        ]);

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users/123'));
        $response = MockServer::getInstance()->handleRequest($request);

        $this->assertNotNull($response);
        $this->assertSame(['id' => 1], $response->json());
    }

    public function test_fakes_with_callback(): void
    {
        MockServer::fake(function (Request $request) {
            if (str_contains((string) $request->getUri(), 'users')) {
                return MockResponse::json(['type' => 'users']);
            }

            return MockResponse::json(['type' => 'other']);
        });

        $usersRequest = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users'));
        $usersResponse = MockServer::getInstance()->handleRequest($usersRequest);

        $otherRequest = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/posts'));
        $otherResponse = MockServer::getInstance()->handleRequest($otherRequest);

        $this->assertSame('users', $usersResponse->json()['type']);
        $this->assertSame('other', $otherResponse->json()['type']);
    }

    public function test_fakes_with_sequence(): void
    {
        $sequence = MockResponse::sequence()
            ->push(500, 'Error')
            ->push(200, 'Success');

        MockServer::fake([
            'https://api.example.com/flaky' => $sequence,
        ]);

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/flaky'));

        $firstResponse = MockServer::getInstance()->handleRequest($request);
        $this->assertSame(500, $firstResponse->status());
        $this->assertSame('Error', $firstResponse->body());

        $secondResponse = MockServer::getInstance()->handleRequest($request);
        $this->assertSame(200, $secondResponse->status());
        $this->assertSame('Success', $secondResponse->body());
    }

    public function test_prevents_stray_requests(): void
    {
        MockServer::fake([
            'https://allowed.com/*' => MockResponse::ok(),
        ]);
        MockServer::preventStrayRequests();

        $allowedRequest = Request::createFromBase(new GuzzleRequest('GET', 'https://allowed.com/path'));
        $allowedResponse = MockServer::getInstance()->handleRequest($allowedRequest);
        $this->assertNotNull($allowedResponse);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No fake response registered');

        $strayRequest = Request::createFromBase(new GuzzleRequest('GET', 'https://other.com/path'));
        MockServer::getInstance()->handleRequest($strayRequest);
    }

    public function test_allows_stray_requests_with_patterns(): void
    {
        MockServer::fake([
            'https://api.example.com/*' => MockResponse::ok(),
        ]);
        MockServer::allowStrayRequests(['https://localhost/*']);

        $apiRequest = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users'));
        $apiResponse = MockServer::getInstance()->handleRequest($apiRequest);
        $this->assertNotNull($apiResponse);

        $localhostRequest = Request::createFromBase(new GuzzleRequest('GET', 'https://localhost/test'));
        $localhostResponse = MockServer::getInstance()->handleRequest($localhostRequest);
        $this->assertNull($localhostResponse); // Allowed to go through
    }

    public function test_records_requests(): void
    {
        MockServer::fake([
            'https://api.example.com/*' => MockResponse::ok('Success'),
        ]);

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users'));
        MockServer::getInstance()->handleRequest($request);

        $recorded = MockServer::recorded();

        $this->assertCount(1, $recorded);
        $this->assertArrayHasKey('request', $recorded[0]);
        $this->assertArrayHasKey('response', $recorded[0]);
        $this->assertSame('GET', $recorded[0]['request']->getMethod());
        $this->assertSame('Success', $recorded[0]['response']->body());
    }

    public function test_records_with_filter(): void
    {
        MockServer::fake([
            '*' => MockResponse::ok(),
        ]);

        $getRequest = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users'));
        $postRequest = Request::createFromBase(new GuzzleRequest('POST', 'https://api.example.com/users'));

        MockServer::getInstance()->handleRequest($getRequest);
        MockServer::getInstance()->handleRequest($postRequest);

        $recorded = MockServer::recorded(fn ($record) => $record['request']->getMethod() === 'POST');

        $this->assertCount(1, $recorded);
    }

    public function test_asserts_sent(): void
    {
        MockServer::fake([
            '*' => MockResponse::ok(),
        ]);

        $request = Request::createFromBase(new GuzzleRequest('POST', 'https://api.example.com/users'));
        MockServer::getInstance()->handleRequest($request);

        MockServer::assertSent('POST https://api.example.com/users');
        MockServer::assertSent('https://api.example.com/users');
        MockServer::assertSent('*');
    }

    public function test_asserts_sent_with_callback(): void
    {
        MockServer::fake([
            '*' => MockResponse::ok(),
        ]);

        $psrRequest = new GuzzleRequest('POST', 'https://api.example.com/users', ['Authorization' => 'Bearer token']);
        $request = Request::createFromBase($psrRequest);
        MockServer::getInstance()->handleRequest($request);

        MockServer::assertSent(function (Request $request) {
            return $request->hasHeader('Authorization');
        });
    }

    public function test_asserts_sent_with_times(): void
    {
        MockServer::fake([
            '*' => MockResponse::ok(),
        ]);

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users'));
        MockServer::getInstance()->handleRequest($request);
        MockServer::getInstance()->handleRequest($request);

        MockServer::assertSent('https://api.example.com/users', 2);
    }

    public function test_asserts_not_sent(): void
    {
        MockServer::fake([
            '*' => MockResponse::ok(),
        ]);

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users'));
        MockServer::getInstance()->handleRequest($request);

        MockServer::assertNotSent('https://api.example.com/posts');
    }

    public function test_asserts_sent_count(): void
    {
        MockServer::fake([
            '*' => MockResponse::ok(),
        ]);

        $request1 = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users'));
        $request2 = Request::createFromBase(new GuzzleRequest('POST', 'https://api.example.com/posts'));

        MockServer::getInstance()->handleRequest($request1);
        MockServer::getInstance()->handleRequest($request2);

        MockServer::assertSentCount(2);
    }

    public function test_asserts_nothing_sent(): void
    {
        MockServer::fake();

        MockServer::assertNothingSent();
    }

    public function test_resets_state(): void
    {
        MockServer::fake([
            'https://api.example.com/*' => MockResponse::ok(),
        ]);

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users'));
        MockServer::getInstance()->handleRequest($request);

        $this->assertCount(1, MockServer::recorded());

        MockServer::getInstance()->reset();

        $this->assertCount(0, MockServer::recorded());
    }

    public function test_callback_returning_array_converts_to_json(): void
    {
        MockServer::fake(fn () => ['data' => 'value']);

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://example.com'));
        $response = MockServer::getInstance()->handleRequest($request);

        $this->assertSame(['data' => 'value'], $response->json());
    }

    public function test_wildcard_matches_any_path(): void
    {
        MockServer::fake([
            'https://api.example.com/*' => MockResponse::ok('Matched'),
        ]);

        $request1 = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users'));
        $request2 = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/posts/123'));
        $request3 = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/deeply/nested/path'));

        $this->assertSame('Matched', MockServer::getInstance()->handleRequest($request1)->body());
        $this->assertSame('Matched', MockServer::getInstance()->handleRequest($request2)->body());
        $this->assertSame('Matched', MockServer::getInstance()->handleRequest($request3)->body());
    }
}
