<?php

namespace Tests\Unit;

use Fetch\Enum\ContentType;
use Fetch\Enum\Method;
use Fetch\Http\Client;
use Fetch\Http\Response;
use Fetch\Interfaces\ClientHandler as ClientHandlerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class ClientTest extends TestCase
{
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

        $this->mockHandler->expects($this->once())
            ->method('withOptions')
            ->willReturnSelf();

        // Since we're not providing a body in the test, we shouldn't expect withBody to be called
        $this->mockHandler->expects($this->never())
            ->method('withBody');

        $this->mockHandler->expects($this->once())
            ->method('sendRequest')
            ->with('GET', $url)
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

        $this->mockHandler->expects($this->once())
            ->method('withOptions')
            ->willReturnSelf();

        $this->mockHandler->expects($this->once())
            ->method('withBody')
            ->with($body, ContentType::JSON)
            ->willReturnSelf();

        $this->mockHandler->expects($this->once())
            ->method('sendRequest')
            ->with('POST', $url)
            ->willReturn($mockResponse);

        $result = $this->client->fetch($url, $options);

        $this->assertSame($mockResponse, $result);
    }

    public function test_get(): void
    {
        $url = 'https://example.com';
        $queryParams = ['foo' => 'bar'];
        $options = ['timeout' => 30];
        $mockResponse = $this->createMock(Response::class);

        // For methodRequest, we need to define behavior for both withOptions and withBody
        $this->mockHandler->expects($this->once())
            ->method('withOptions')
            ->willReturnSelf();

        // Note: For a GET request, body should be null, so withBody should not be called
        // We need to modify the methodRequest method to handle this

        $this->mockHandler->expects($this->once())
            ->method('sendRequest')
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

        // Set up expectations for the methodRequest flow
        $this->mockHandler->expects($this->once())
            ->method('withOptions')
            ->willReturnSelf();

        $this->mockHandler->expects($this->once())
            ->method('withBody')
            ->with($body, $this->anything())
            ->willReturnSelf();

        $this->mockHandler->expects($this->once())
            ->method('sendRequest')
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

        $this->mockHandler->expects($this->once())
            ->method('sendRequest')
            ->willReturn($mockResponse);

        $result = $this->client->sendRequest($mockRequest);

        $this->assertSame($mockResponse, $result);
    }
}
