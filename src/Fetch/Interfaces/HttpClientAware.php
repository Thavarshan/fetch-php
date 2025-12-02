<?php

declare(strict_types=1);

namespace Fetch\Interfaces;

use GuzzleHttp\ClientInterface;

interface HttpClientAware
{
    public function getHttpClient(): ClientInterface;

    public function setHttpClient(ClientInterface $client): self;
}
