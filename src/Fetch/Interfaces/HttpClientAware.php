<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use GuzzleHttp\ClientInterface;

/**
 * Exposes the Guzzle client used by an HTTP request handler.
 */
interface HttpClientAware
{
    /**
     * Return the Guzzle client currently used for transport.
     */
    public function getHttpClient(): ClientInterface;

    /**
     * Replace the Guzzle client used for subsequent requests.
     */
    public function setHttpClient(ClientInterface $client): self;
}
