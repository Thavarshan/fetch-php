<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Interfaces\StreamedResponse as StreamedResponseContract;
use GuzzleHttp\ClientInterface;

final class StreamingRequestExecutor
{
    public function __construct(private readonly ClientInterface $client) {}

    /** @param array<string, mixed> $options */
    public function execute(
        string $method,
        string $uri,
        array $options,
    ): StreamedResponseContract {
        $options['stream'] = true;

        return StreamedResponse::createFromBase(
            $this->client->request($method, $uri, $options),
        );
    }
}
