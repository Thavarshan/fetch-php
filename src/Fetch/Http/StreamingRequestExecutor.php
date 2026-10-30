<?php

declare(strict_types=1);

namespace Fetch\Http;

use Fetch\Interfaces\StreamedResponse as StreamedResponseContract;
use GuzzleHttp\ClientInterface;

/**
 * Sends requests through Guzzle with response streaming enabled.
 */
final class StreamingRequestExecutor
{
    /**
     * Bind the executor to the Guzzle client used for streaming requests.
     */
    public function __construct(private readonly ClientInterface $client) {}

    /**
     * Request an unbuffered response and wrap it as a streamed FetchPHP value.
     *
     * @param  array<string, mixed>  $options
     */
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
