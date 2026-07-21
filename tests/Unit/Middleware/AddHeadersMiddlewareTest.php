<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use Fetch\Middleware\AddHeadersMiddleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class AddHeadersMiddlewareTest extends TestCase
{
    public function test_adds_headers(): void
    {
        $middleware = new AddHeadersMiddleware(['X-Api-Version' => '2.1', 'X-Tenant' => 'acme']);
        $request = new Request('GET', 'https://example.com');

        $middleware->handle($request, function (RequestInterface $req) {
            $this->assertSame('2.1', $req->getHeaderLine('X-Api-Version'));
            $this->assertSame('acme', $req->getHeaderLine('X-Tenant'));

            return new Response(200);
        });
    }

    public function test_overwrites_existing_headers_by_default(): void
    {
        $middleware = new AddHeadersMiddleware(['X-Api-Version' => 'new']);
        $request = (new Request('GET', 'https://example.com'))->withHeader('X-Api-Version', 'old');

        $middleware->handle($request, function (RequestInterface $req) {
            $this->assertSame('new', $req->getHeaderLine('X-Api-Version'));

            return new Response(200);
        });
    }

    public function test_preserves_existing_headers_when_overwrite_disabled(): void
    {
        $middleware = new AddHeadersMiddleware(['X-Api-Version' => 'new'], overwrite: false);
        $request = (new Request('GET', 'https://example.com'))->withHeader('X-Api-Version', 'old');

        $middleware->handle($request, function (RequestInterface $req) {
            $this->assertSame('old', $req->getHeaderLine('X-Api-Version'));

            return new Response(200);
        });
    }
}
