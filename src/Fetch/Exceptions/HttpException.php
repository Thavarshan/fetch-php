<?php

namespace Fetch\Exceptions;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class HttpException extends RuntimeException
{
    /**
     * The HTTP response that caused the exception.
     *
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Set the HTTP response that caused the exception.
     */
    public function setResponse(ResponseInterface $response): self
    {
        $this->response = $response;

        return $this;
    }
}
