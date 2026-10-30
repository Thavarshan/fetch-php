<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use Fetch\Middleware\LoggingMiddleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Checks correlation IDs and request/response logging behavior.
 */
class LoggingMiddlewareTest extends TestCase
{
    /**
     * Creates a logger that records entries for middleware assertions.
     */
    private function collectingLogger(array &$logs): AbstractLogger
    {
        return new
        /**
         * Collects structured log entries for middleware assertions.
         */
        class($logs) extends AbstractLogger {
            /**
             * Keeps the shared log buffer supplied by the test helper.
             */
            public function __construct(private array &$logs) {}

            /**
             * Records a normalized log entry for later test assertions.
             */
            public function log($level, $message, array $context = []): void
            {
                $this->logs[] = [
                    'level' => $level,
                    'message' => (string) $message,
                    'context' => $context,
                ];
            }
        };
    }

    public function test_logs_request_and_response_with_correlation_id(): void
    {
        $logs = [];
        $middleware = new LoggingMiddleware($this->collectingLogger($logs));
        $request = new Request('GET', 'https://example.com/api');

        $middleware->handle(
            $request,
            fn (RequestInterface $req) => new Response(200),
        );

        $this->assertCount(2, $logs);
        $this->assertSame('HTTP request started', $logs[0]['message']);
        $this->assertSame('HTTP request completed', $logs[1]['message']);
        $this->assertSame(200, $logs[1]['context']['status_code']);

        // Same correlation id links both entries.
        $this->assertNotEmpty($logs[0]['context']['correlation_id']);
        $this->assertSame(
            $logs[0]['context']['correlation_id'],
            $logs[1]['context']['correlation_id'],
        );
    }

    public function test_attaches_correlation_header_to_request(): void
    {
        $logs = [];
        $middleware = new LoggingMiddleware($this->collectingLogger($logs));
        $request = new Request('GET', 'https://example.com');

        $middleware->handle($request, function (RequestInterface $req) {
            $this->assertNotEmpty($req->getHeaderLine('X-Correlation-ID'));

            return new Response(200);
        });
    }

    public function test_respects_custom_level(): void
    {
        $logs = [];
        $middleware = new LoggingMiddleware(
            $this->collectingLogger($logs),
            LogLevel::DEBUG,
        );

        $middleware->handle(
            new Request('GET', 'https://example.com'),
            fn () => new Response(200),
        );

        $this->assertSame(LogLevel::DEBUG, $logs[0]['level']);
    }
}
