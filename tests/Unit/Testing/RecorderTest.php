<?php

declare(strict_types=1);

namespace Tests\Unit\Testing;

use Fetch\Http\Request;
use Fetch\Http\Response;
use Fetch\Testing\MockServer;
use Fetch\Testing\Recorder;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;

class RecorderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Recorder::resetInstance();
        MockServer::resetInstance();
    }

    protected function tearDown(): void
    {
        Recorder::resetInstance();
        MockServer::resetInstance();
        parent::tearDown();
    }

    public function test_starts_recording(): void
    {
        $this->assertFalse(Recorder::isRecording());

        Recorder::start();

        $this->assertTrue(Recorder::isRecording());
    }

    public function test_stops_recording(): void
    {
        Recorder::start();
        $this->assertTrue(Recorder::isRecording());

        Recorder::stop();

        $this->assertFalse(Recorder::isRecording());
    }

    public function test_records_request_and_response(): void
    {
        Recorder::start();

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://example.com'));
        $response = Response::createFromBase(new GuzzleResponse(200, [], 'Test'));

        Recorder::record($request, $response);

        $recordings = Recorder::getRecordings();

        $this->assertCount(1, $recordings);
        $this->assertArrayHasKey('request', $recordings[0]);
        $this->assertArrayHasKey('response', $recordings[0]);
        $this->assertArrayHasKey('timestamp', $recordings[0]);
        $this->assertSame('GET', $recordings[0]['request']->getMethod());
        $this->assertSame('Test', $recordings[0]['response']->body());
    }

    public function test_does_not_record_when_not_active(): void
    {
        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://example.com'));
        $response = Response::createFromBase(new GuzzleResponse(200, [], 'Test'));

        Recorder::record($request, $response);

        $recordings = Recorder::getRecordings();

        $this->assertCount(0, $recordings);
    }

    public function test_clears_recordings_on_start(): void
    {
        Recorder::start();

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://example.com'));
        $response = Response::createFromBase(new GuzzleResponse(200, [], 'Test'));
        Recorder::record($request, $response);

        $this->assertCount(1, Recorder::getRecordings());

        Recorder::start(); // Start again should clear

        $this->assertCount(0, Recorder::getRecordings());
    }

    public function test_stop_returns_recordings(): void
    {
        Recorder::start();

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://example.com'));
        $response = Response::createFromBase(new GuzzleResponse(200, [], 'Test'));
        Recorder::record($request, $response);

        $recordings = Recorder::stop();

        $this->assertCount(1, $recordings);
        $this->assertSame('GET', $recordings[0]['request']->getMethod());
    }

    public function test_clears_recordings(): void
    {
        Recorder::start();

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://example.com'));
        $response = Response::createFromBase(new GuzzleResponse(200, [], 'Test'));
        Recorder::record($request, $response);

        $this->assertCount(1, Recorder::getRecordings());

        Recorder::clear();

        $this->assertCount(0, Recorder::getRecordings());
    }

    public function test_replays_recordings(): void
    {
        $recordings = [
            [
                'request' => Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users')),
                'response' => Response::createFromBase(new GuzzleResponse(200, [], json_encode(['users' => []]))),
            ],
            [
                'request' => Request::createFromBase(new GuzzleRequest('POST', 'https://api.example.com/users')),
                'response' => Response::createFromBase(new GuzzleResponse(201, [], json_encode(['id' => 1]))),
            ],
        ];

        Recorder::replay($recordings);

        // Verify that MockServer has been set up with these recordings
        $getRequest = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users'));
        $getResponse = MockServer::getInstance()->handleRequest($getRequest);

        $this->assertNotNull($getResponse);
        $this->assertSame(['users' => []], $getResponse->json());

        $postRequest = Request::createFromBase(new GuzzleRequest('POST', 'https://api.example.com/users'));
        $postResponse = MockServer::getInstance()->handleRequest($postRequest);

        $this->assertNotNull($postResponse);
        $this->assertSame(['id' => 1], $postResponse->json());
    }

    public function test_replays_multiple_requests_to_same_endpoint(): void
    {
        $recordings = [
            [
                'request' => Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users')),
                'response' => Response::createFromBase(new GuzzleResponse(200, [], 'First')),
            ],
            [
                'request' => Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users')),
                'response' => Response::createFromBase(new GuzzleResponse(200, [], 'Second')),
            ],
        ];

        Recorder::replay($recordings);

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users'));

        $firstResponse = MockServer::getInstance()->handleRequest($request);
        $this->assertSame('First', $firstResponse->body());

        $secondResponse = MockServer::getInstance()->handleRequest($request);
        $this->assertSame('Second', $secondResponse->body());
    }

    public function test_exports_to_json(): void
    {
        Recorder::start();

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users', ['Authorization' => 'Bearer token']));
        $response = Response::createFromBase(new GuzzleResponse(200, ['Content-Type' => 'application/json'], json_encode(['users' => []])));

        Recorder::record($request, $response);

        $json = Recorder::exportToJson();

        $this->assertJson($json);

        $data = json_decode($json, true);
        $this->assertCount(1, $data);
        $this->assertSame('GET', $data[0]['request']['method']);
        $this->assertSame('https://api.example.com/users', $data[0]['request']['url']);
        $this->assertArrayHasKey('Authorization', $data[0]['request']['headers']);
        $this->assertSame(200, $data[0]['response']['status']);
    }

    public function test_imports_from_json(): void
    {
        $json = json_encode([
            [
                'request' => [
                    'method' => 'GET',
                    'url' => 'https://api.example.com/users',
                    'headers' => ['Authorization' => ['Bearer token']],
                    'body' => '',
                ],
                'response' => [
                    'status' => 200,
                    'headers' => ['Content-Type' => ['application/json']],
                    'body' => json_encode(['users' => []]),
                ],
                'timestamp' => microtime(true),
            ],
        ]);

        Recorder::importFromJson($json);

        // Verify that MockServer has been set up
        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://api.example.com/users'));
        $response = MockServer::getInstance()->handleRequest($request);

        $this->assertNotNull($response);
        $this->assertSame(200, $response->status());
        $this->assertSame(['users' => []], $response->json());
    }

    public function test_imports_invalid_json_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON format');

        Recorder::importFromJson('invalid json');
    }

    public function test_resets_recorder(): void
    {
        Recorder::start();

        $request = Request::createFromBase(new GuzzleRequest('GET', 'https://example.com'));
        $response = Response::createFromBase(new GuzzleResponse(200, [], 'Test'));
        Recorder::record($request, $response);

        $this->assertTrue(Recorder::isRecording());
        $this->assertCount(1, Recorder::getRecordings());

        Recorder::reset();

        $this->assertFalse(Recorder::isRecording());
        $this->assertCount(0, Recorder::getRecordings());
    }

    public function test_start_enables_mock_server_recording(): void
    {
        Recorder::start();

        // This should enable recording in MockServer as well
        // We can't directly test this without integration, but we can verify it doesn't error
        $this->assertTrue(Recorder::isRecording());
    }
}
