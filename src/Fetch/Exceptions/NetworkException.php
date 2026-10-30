<?php

declare(strict_types=1);

namespace Fetch\Exceptions;

use Psr\Http\Client\NetworkExceptionInterface as NetworkContract;
use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * Reports a transport failure and retains the request that could not complete.
 */
class NetworkException extends ClientException implements NetworkContract
{
    /**
     * The request.
     */
    private RequestInterface $request;

    /**
     * Constructor.
     *
     * @param  string  $message  The error message
     * @param  RequestInterface  $request  The request
     * @param  Throwable|null  $previous  The previous exception
     */
    public function __construct(
        string $message,
        RequestInterface $request,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
        $this->request = $request;
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
}
