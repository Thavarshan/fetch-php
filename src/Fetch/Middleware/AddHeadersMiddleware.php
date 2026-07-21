<?php

declare(strict_types=1);

namespace Fetch\Middleware;

use Fetch\Interfaces\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;

/**
 * Adds (or overrides) a fixed set of headers on every outgoing request.
 *
 * Useful for cross-cutting concerns such as API versioning, tenant isolation,
 * or a static correlation prefix:
 *
 * ```php
 * $client->addMiddleware(new AddHeadersMiddleware([
 *     'X-Api-Version' => '2.1',
 *     'X-Tenant-ID'   => $tenantId,
 * ]));
 * ```
 */
final class AddHeadersMiddleware implements Middleware
{
    /**
     * @param  array<string, string|string[]>  $headers  Header name => value(s).
     * @param  bool  $overwrite  Replace existing headers (true) or only add when absent (false).
     */
    public function __construct(
        private readonly array $headers,
        private readonly bool $overwrite = true,
    ) {}

    public function handle(RequestInterface $request, callable $next): ResponseInterface|PromiseInterface
    {
        foreach ($this->headers as $name => $value) {
            if (! $this->overwrite && $request->hasHeader($name)) {
                continue;
            }

            $request = $request->withHeader($name, $value);
        }

        return $next($request);
    }
}
