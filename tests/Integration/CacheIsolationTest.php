<?php

declare(strict_types=1);

namespace Tests\Integration;

use Fetch\Cache\MemoryCache;
use Fetch\Http\ClientHandler;
use Fetch\Testing\MockServer;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

final class CacheIsolationTest extends TestCase
{
    protected function tearDown(): void
    {
        MockServer::resetInstance();
        parent::tearDown();
    }

    public function test_cache_uses_middleware_headers(): void
    {
        $handler = $this->handler();
        $language = 'en';
        $handler->addMiddleware(static function (
            RequestInterface $request,
            callable $next,
        ) use (&$language) {
            return $next($request->withHeader('Accept-Language', $language));
        });

        self::assertSame('first', $handler->get('/profile')->text());
        $language = 'fr';
        self::assertSame('second', $handler->get('/profile')->text());
        $language = 'en';
        self::assertSame('first', $handler->get('/profile')->text());
    }

    public function test_cache_isolates_credentials_with_custom_keys(): void
    {
        $contexts = [
            [
                ['headers' => ['Authorization' => 'Bearer first']],
                ['headers' => ['Authorization' => 'Bearer second']],
            ],
            [
                ['auth' => ['first', 'fixture']],
                ['auth' => ['second', 'fixture']],
            ],
            [
                ['cookies' => CookieJar::fromArray(
                    ['session' => 'first'],
                    'example.test',
                )],
                ['cookies' => CookieJar::fromArray(
                    ['session' => 'second'],
                    'example.test',
                )],
            ],
        ];

        foreach ($contexts as [$first, $second]) {
            foreach ([[], ['cache' => ['key' => 'profile']]] as $options) {
                $handler = $this->handler();
                $a = array_replace($options, $first);
                $b = array_replace($options, $second);

                self::assertSame('first', $handler->sendRequest(
                    'GET',
                    '/profile',
                    $a,
                )->text());
                self::assertSame('second', $handler->sendRequest(
                    'GET',
                    '/profile',
                    $b,
                )->text());
                self::assertSame('first', $handler->sendRequest(
                    'GET',
                    '/profile',
                    $a,
                )->text());
            }
        }
    }

    private function handler(): ClientHandler
    {
        MockServer::resetInstance();
        $responses = [
            new Response(200, ['Cache-Control' => 'max-age=60'], 'first'),
            new Response(200, ['Cache-Control' => 'max-age=60'], 'second'),
        ];
        $client = new Client([
            'handler' => HandlerStack::create(new MockHandler($responses)),
        ]);

        return ClientHandler::createWithClient($client)
            ->baseUri('https://example.test')
            ->withCache(new MemoryCache);
    }
}
