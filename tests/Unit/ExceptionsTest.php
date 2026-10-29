<?php

declare(strict_types=1);

namespace Tests\Unit;

use Fetch\Exceptions\ClientException;
use Fetch\Exceptions\HttpException;
use Fetch\Exceptions\NetworkException;
use Fetch\Exceptions\RequestException;
use Fetch\Http\Request;
use Fetch\Http\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use RuntimeException;

class ExceptionsTest extends TestCase
{
    public function test_client_exception_implements_psr_client_contract(): void
    {
        $exception = new ClientException('Client failure');

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertInstanceOf(ClientExceptionInterface::class, $exception);
        $this->assertSame('Client failure', $exception->getMessage());
    }

    public function test_request_exception_preserves_context_and_cause(): void
    {
        $request = new Request('GET', 'https://example.test');
        $response = new Response(400);
        $cause = new RuntimeException('cause');
        $exception = new RequestException(
            'Request failed',
            $request,
            $response,
            $cause,
        );

        $this->assertInstanceOf(RequestExceptionInterface::class, $exception);
        $this->assertSame($request, $exception->getRequest());
        $this->assertSame($response, $exception->getResponse());
        $this->assertSame($cause, $exception->getPrevious());
    }

    public function test_network_exception_keeps_request_and_cause(): void
    {
        $request = new Request('GET', 'https://example.test');
        $cause = new RuntimeException('connection failed');
        $exception = new NetworkException('Network failed', $request, $cause);

        $this->assertInstanceOf(NetworkExceptionInterface::class, $exception);
        $this->assertSame($request, $exception->getRequest());
        $this->assertSame($cause, $exception->getPrevious());
    }

    public function test_http_exception_exposes_set_response(): void
    {
        $response = new Response(500);
        $exception = (new HttpException('HTTP failure'))->setResponse(
            $response,
        );

        $this->assertSame($response, $exception->getResponse());
        $this->assertSame('HTTP failure', $exception->getMessage());
    }
}
