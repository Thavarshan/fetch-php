<?php

declare(strict_types=1);

namespace Fetch\Exceptions;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class RequestException extends ClientException implements RequestExceptionInterface
{
    /**
     * The request.
     */
    private RequestInterface $request;

    /**
     * The response.
     */
    private ?ResponseInterface $response;

    /**
     * Constructor.
     *
     * @param  string  $message  The error message
     * @param  RequestInterface  $request  The request
     * @param  ResponseInterface|null  $response  The response if available
     * @param  Throwable|null  $previous  The previous exception
     */
    public function __construct(
        string $message,
        RequestInterface $request,
        ?ResponseInterface $response = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Get the request.
     *
     * @return RequestInterface The request
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Get the response if available.
     *
     * @return ResponseInterface|null The response or null
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
