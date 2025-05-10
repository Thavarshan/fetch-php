<?php

declare(strict_types=1);

namespace Fetch\Traits;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

trait ResponseImmutabilityTrait
{
    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     */
    public function withStatus($code, $reasonPhrase = ''): static
    {
        return $this->toStatic(parent::withStatus($code, $reasonPhrase));
    }

    /**
     * Return an instance with the specified header appended with the given value.
     */
    public function withAddedHeader($name, $value): static
    {
        return $this->toStatic(parent::withAddedHeader($name, $value));
    }

    /**
     * Return an instance without the specified header.
     */
    public function withoutHeader($name): static
    {
        return $this->toStatic(parent::withoutHeader($name));
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     */
    public function withHeader($name, $value): static
    {
        return $this->toStatic(parent::withHeader($name, $value));
    }

    /**
     * Return an instance with the specified protocol version.
     */
    public function withProtocolVersion($version): static
    {
        return $this->toStatic(parent::withProtocolVersion($version));
    }

    /**
     * Return an instance with the specified body.
     */
    public function withBody(StreamInterface $body): static
    {
        $new = parent::withBody($body);
        $response = $this->toStatic($new);

        // Update the buffered body contents
        if (property_exists($this, 'bodyContents')) {
            $response->bodyContents = (string) $body;
        }

        return $response;
    }

    /**
     * Convert a parent method result to the current class type.
     */
    protected function toStatic(ResponseInterface $new): static
    {
        return new static(
            $new->getStatusCode(),
            $new->getHeaders(),
            (string) $new->getBody(),
            $new->getProtocolVersion(),
            $new->getReasonPhrase()
        );
    }
}
