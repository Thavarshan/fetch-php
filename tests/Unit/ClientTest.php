<?php

namespace Tests\Unit;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Exceptions\NetworkException;
use Fetch\Http\Client;
use Fetch\Http\Request;
use Fetch\Http\Response;
use Fetch\Interfaces\ClientHandler as ClientHandlerInterface;
use GuzzleHttp\Exception\ConnectException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ClientTest extends TestCase
{
    public function test_client_forwards_loggers_to_custom_handlers(): void
    {
        $handler = $this->createMock(ClientHandlerInterface::class);
        $received = [];
        $handler
            ->expects($this->exactly(2))
            ->method('setLogger')
            ->willReturnCallback(function (LoggerInterface $logger) use (
                &$received,
                $handler,
            ): ClientHandlerInterface {
                $received[] = $logger;

                return $handler;
            });
        $initial = new NullLogger;
        $replacement = new NullLogger;

        $client = new Client($handler, logger: $initial);
        $client->setLogger($replacement);

        self::assertSame([$initial, $replacement], $received);
    }

    private $mockHandler;

    private $client;

    protected function setUp(): void
    {
        $this->mockHandler = $this->createMock(ClientHandlerInterface::class);
        $this->client = new Client($this->mockHandler);
    }

    public function test_fetch_with_no_url_returns_handler(): void
    {
        $this->mockHandler->expects($this->never())->method('sendRequest');

        $result = $this->client->fetch();

        $this->assertSame($this->mockHandler, $result);
    }

    public function test_fetch_with_url(): void
    {
        $url = 'https://example.com';
        $options = ['timeout' => 30];
        $mockResponse = $this->createMock(Response::class);

        $this->mockHandler
            ->expects($this->once())
            ->method('request')
            ->with(
                Method::GET,
                $url,
                null,
                ContentType::JSON,
                $this->callback(
                    static fn (array $requestOptions): bool => $requestOptions[
                        'timeout'
                    ] === 30,
                ),
            )
            ->willReturn($mockResponse);

        $result = $this->client->fetch($url, $options);

        $this->assertSame($mockResponse, $result);
    }

    public function test_fetch_with_body(): void
    {
        $url = 'https://example.com';
        $body = ['name' => 'John'];
        $options = [
            'method' => 'POST',
            'json' => $body,
        ];
        $mockResponse = $this->createMock(Response::class);

        $this->mockHandler
            ->expects($this->once())
            ->method('request')
            ->with(
                Method::POST,
                $url,
                null,
                ContentType::JSON,
                $this->callback(
                    static fn (array $requestOptions): bool => $requestOptions[
                        'json'
                    ] === $body,
                ),
            )
            ->willReturn($mockResponse);

        $result = $this->client->fetch($url, $options);

        $this->assertSame($mockResponse, $result);
    }

    public function test_fetch_uses_normalized_form_options(): void
    {
        $body = ['name' => 'Ada Lovelace'];
        $mockResponse = $this->createMock(Response::class);

        $this->mockHandler
            ->expects($this->once())
            ->method('request')
            ->with(
                Method::POST,
                'https://example.com/users',
                null,
                ContentType::JSON,
                $this->callback(
                    static function (array $requestOptions) use (
                        $body,
                    ): bool {
                        return $requestOptions['form_params'] === $body &&
                            ! isset($requestOptions['body']);
                    },
                ),
            )
            ->willReturn($mockResponse);

        self::assertSame(
            $mockResponse,
            $this->client->fetch('https://example.com/users', [
                'method' => 'POST',
                'form_params' => $body,
                'body' => 'ignored',
            ]),
        );
    }

    public function test_fetch_respects_case_insensitive_content_type(): void
    {
        $body = ['name' => 'Ada Lovelace'];
        $mockResponse = $this->createMock(Response::class);

        $this->mockHandler
            ->expects($this->once())
            ->method('request')
            ->with(
                Method::POST,
                'https://example.com/users',
                null,
                ContentType::JSON,
                $this->callback(
                    static function (array $requestOptions): bool {
                        return $requestOptions['body'] ===
                                '{"name":"Ada Lovelace"}' &&
                            $requestOptions['headers']['content-type'] ===
                                'text/plain';
                    },
                ),
            )
            ->willReturn($mockResponse);

        self::assertSame(
            $mockResponse,
            $this->client->fetch('https://example.com/users', [
                'method' => 'POST',
                'body' => $body,
                'headers' => ['content-type' => 'text/plain'],
            ]),
        );
    }

    public function test_get(): void
    {
        $url = 'https://example.com';
        $queryParams = ['foo' => 'bar'];
        $options = ['timeout' => 30];
        $mockResponse = $this->createMock(Response::class);

        $this->mockHandler
            ->expects($this->once())
            ->method('request')
            ->with(
                Method::GET,
                $url,
                null,
                ContentType::JSON,
                $this->callback(
                    static fn (array $requestOptions): bool => $requestOptions[
                        'query'
                    ] === $queryParams,
                ),
            )
            ->willReturn($mockResponse);

        $result = $this->client->get($url, $queryParams, $options);

        $this->assertSame($mockResponse, $result);
    }

    public function test_post(): void
    {
        $url = 'https://example.com';
        $body = ['name' => 'John'];
        $contentType = ContentType::JSON;
        $options = ['timeout' => 30];
        $mockResponse = $this->createMock(Response::class);

        $this->mockHandler
            ->expects($this->once())
            ->method('request')
            ->with(
                Method::POST,
                $url,
                $body,
                ContentType::JSON,
                $this->callback(
                    static fn (array $requestOptions): bool => $requestOptions[
                        'timeout'
                    ] === 30 && ! isset($requestOptions['json']),
                ),
            )
            ->willReturn($mockResponse);

        $result = $this->client->post($url, $body, $contentType, $options);

        $this->assertSame($mockResponse, $result);
    }

    public function test_send_request_with_psr7_request(): void
    {
        // Create proper mocks for PSR interfaces
        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('__toString')->willReturn('https://example.com');

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('__toString')->willReturn('');

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getMethod')->willReturn('GET');
        $mockRequest->method('getUri')->willReturn($mockUri);
        $mockRequest->method('getHeaders')->willReturn([]);
        $mockRequest->method('getBody')->willReturn($mockStream);

        $mockResponse = $this->createMock(Response::class);

        $this->mockHandler
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $result = $this->client->sendRequest($mockRequest);

        $this->assertSame($mockResponse, $result);
    }

    public function test_fetch_wraps_connection_failure_as_network_error(): void
    {
        $request = new Request('GET', 'https://example.com');
        $this->mockHandler
            ->expects($this->once())
            ->method('request')
            ->with(
                Method::GET,
                'https://example.com',
                null,
                ContentType::JSON,
                $this->anything(),
            )
            ->willThrowException(
                new ConnectException('Connection refused', $request),
            );

        try {
            $this->client->fetch('https://example.com');
            $this->fail('Expected a network exception');
        } catch (NetworkException $exception) {
            $this->assertSame(
                $request->getUri(),
                $exception->getRequest()->getUri(),
            );
            $this->assertInstanceOf(
                ConnectException::class,
                $exception->getPrevious(),
            );
        }
    }
}
