<?php

declare(strict_types=1);

namespace Fetch\Testing;

use Fetch\Http\Request;
use Fetch\Interfaces\Response as ResponseInterface;
use InvalidArgumentException;

class Recorder
{
    /**
     * The singleton instance of the recorder.
     */
    protected static ?self $instance = null;

    /**
     * Whether recording is currently active.
     */
    protected bool $isRecording = false;

    /**
     * Recorded requests and responses.
     *
     * @var array<array{request: Request, response: ResponseInterface, timestamp: float}>
     */
    protected array $recordings = [];

    /**
     * Get the singleton instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Start recording requests and responses.
     */
    public static function start(): void
    {
        $instance = self::getInstance();
        $instance->isRecording = true;
        $instance->recordings = [];

        // Also enable recording in MockServer if it's being used
        MockServer::startRecording();
    }

    /**
     * Stop recording and return the recordings.
     *
     * @return array<array{request: Request, response: ResponseInterface, timestamp: float}>
     */
    public static function stop(): array
    {
        $instance = self::getInstance();
        $instance->isRecording = false;

        return $instance->recordings;
    }

    /**
     * Record a request and response.
     */
    public static function record(Request $request, ResponseInterface $response): void
    {
        $instance = self::getInstance();

        if (! $instance->isRecording) {
            return;
        }

        $instance->recordings[] = [
            'request' => $request,
            'response' => $response,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Check if recording is active.
     */
    public static function isRecording(): bool
    {
        return self::getInstance()->isRecording;
    }

    /**
     * Get all recordings.
     *
     * @return array<array{request: Request, response: ResponseInterface, timestamp: float}>
     */
    public static function getRecordings(): array
    {
        return self::getInstance()->recordings;
    }

    /**
     * Clear all recordings.
     */
    public static function clear(): void
    {
        self::getInstance()->recordings = [];
    }

    /**
     * Replay recordings by setting up mock responses.
     *
     * @param  array<array{request: Request, response: ResponseInterface, timestamp?: float}>  $recordings  Recordings to replay
     */
    public static function replay(array $recordings): void
    {
        $fakes = [];

        foreach ($recordings as $recording) {
            $request = $recording['request'];
            $response = $recording['response'];

            $url = (string) $request->getUri();
            $method = $request->getMethod();
            $pattern = "{$method} {$url}";

            // Convert the recorded response to a MockResponse
            $mockResponse = MockResponse::create(
                $response->status(),
                $response->body(),
                $response->getHeaders()
            );

            // If the pattern already exists, convert to sequence
            if (isset($fakes[$pattern])) {
                if (! $fakes[$pattern] instanceof MockResponseSequence) {
                    $fakes[$pattern] = MockResponse::sequence([$fakes[$pattern]]);
                }
                $fakes[$pattern]->pushResponse($mockResponse);
            } else {
                $fakes[$pattern] = $mockResponse;
            }
        }

        MockServer::fake($fakes);
    }

    /**
     * Export recordings to JSON.
     */
    public static function exportToJson(): string
    {
        $instance = self::getInstance();
        $exportData = [];

        foreach ($instance->recordings as $recording) {
            $request = $recording['request'];
            $response = $recording['response'];

            $exportData[] = [
                'request' => [
                    'method' => $request->getMethod(),
                    'url' => (string) $request->getUri(),
                    'headers' => $request->getHeaders(),
                    'body' => (string) $request->getBody(),
                ],
                'response' => [
                    'status' => $response->status(),
                    'headers' => $response->getHeaders(),
                    'body' => $response->body(),
                ],
                'timestamp' => $recording['timestamp'] ?? null,
            ];
        }

        return json_encode($exportData, JSON_PRETTY_PRINT);
    }

    /**
     * Import recordings from JSON and replay them.
     */
    public static function importFromJson(string $json): void
    {
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON format for recordings.');
        }

        $fakes = [];

        foreach ($data as $recording) {
            $method = $recording['request']['method'] ?? 'GET';
            $url = $recording['request']['url'] ?? '';
            $pattern = "{$method} {$url}";

            $mockResponse = MockResponse::create(
                $recording['response']['status'] ?? 200,
                $recording['response']['body'] ?? '',
                $recording['response']['headers'] ?? []
            );

            // If the pattern already exists, convert to sequence
            if (isset($fakes[$pattern])) {
                if (! $fakes[$pattern] instanceof MockResponseSequence) {
                    $fakes[$pattern] = MockResponse::sequence([$fakes[$pattern]]);
                }
                $fakes[$pattern]->pushResponse($mockResponse);
            } else {
                $fakes[$pattern] = $mockResponse;
            }
        }

        MockServer::fake($fakes);
    }

    /**
     * Reset the recorder instance.
     */
    public static function reset(): void
    {
        $instance = self::getInstance();
        $instance->isRecording = false;
        $instance->recordings = [];
    }

    /**
     * Reset the singleton instance completely.
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}
