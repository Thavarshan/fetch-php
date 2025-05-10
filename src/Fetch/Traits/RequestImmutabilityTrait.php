<?php

declare(strict_types=1);

namespace Fetch\Traits;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

trait RequestImmutabilityTrait
{
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
     * Return an instance with the specified URI.
     */
    public function withUri(UriInterface $uri, $preserveHost = false): static
    {
        return $this->toStatic(parent::withUri($uri, $preserveHost));
    }

    /**
     * Return an instance with the provided HTTP method.
     */
    public function withMethod($method): static
    {
        return $this->toStatic(parent::withMethod($method));
    }

    /**
     * Return an instance with the specified request target.
     */
    public function withRequestTarget($requestTarget): static
    {
        return $this->toStatic(parent::withRequestTarget($requestTarget));
    }

    /**
     * Convert a parent method result to the current class type.
     */
    protected function toStatic(RequestInterface $new): static
    {
        return new static(
            $new->getMethod(),
            $new->getUri(),
            $new->getHeaders(),
            $new->getBody()
        );
    }
}
