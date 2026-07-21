<?php

declare(strict_types=1);

namespace Fetch\Middleware;

use Fetch\Interfaces\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use React\Promise\PromiseInterface;

/**
 * Logs the request/response lifecycle to a PSR-3 logger.
 *
 * A correlation ID is generated per request, attached as a header, and included
 * in both log entries so a request and its response can be tied together. Works
 * transparently in both synchronous and asynchronous modes.
 */
final class LoggingMiddleware implements Middleware
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $level = LogLevel::INFO,
        private readonly string $correlationHeader = 'X-Correlation-ID',
    ) {}

    public function handle(RequestInterface $request, callable $next): ResponseInterface|PromiseInterface
    {
        $correlationId = $this->generateCorrelationId();
        $request = $request->withHeader($this->correlationHeader, $correlationId);

        $start = microtime(true);

        $this->logger->log($this->level, 'HTTP request started', [
            'correlation_id' => $correlationId,
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
        ]);

        $result = $next($request);

        if ($result instanceof PromiseInterface) {
            return $result->then(function (ResponseInterface $response) use ($correlationId, $start): ResponseInterface {
                $this->logResponse($response, $correlationId, $start);

                return $response;
            });
        }

        $this->logResponse($result, $correlationId, $start);

        return $result;
    }

    private function logResponse(ResponseInterface $response, string $correlationId, float $start): void
    {
        $this->logger->log($this->level, 'HTTP request completed', [
            'correlation_id' => $correlationId,
            'status_code' => $response->getStatusCode(),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);
    }

    private function generateCorrelationId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
