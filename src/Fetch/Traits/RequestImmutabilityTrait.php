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
        $new = parent::withAddedHeader($name, $value);

        return $this->toStatic($new);
    }

    /**
     * Return an instance without the specified header.
     */
    public function withoutHeader($name): static
    {
        $new = parent::withoutHeader($name);

        return $this->toStatic($new);
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     */
    public function withHeader($name, $value): static
    {
        $new = parent::withHeader($name, $value);

        return $this->toStatic($new);
    }

    /**
     * Return an instance with the specified protocol version.
     */
    public function withProtocolVersion($version): static
    {
        $new = parent::withProtocolVersion($version);

        return $this->toStatic($new);
    }

    /**
     * Return an instance with the specified URI.
     */
    public function withUri(UriInterface $uri, $preserveHost = false): static
    {
        $new = parent::withUri($uri, $preserveHost);

        return $this->toStatic($new);
    }

    /**
     * Return an instance with the provided HTTP method.
     */
    public function withMethod($method): static
    {
        $new = parent::withMethod($method);

        return $this->toStatic($new);
    }

    /**
     * Convert a parent method result to the current class type.
     * This preserves all properties including custom request target.
     */
    protected function toStatic(RequestInterface $new): static
    {
        // Get the custom request target if this class has it set
        $requestTarget = null;
        if (property_exists($this, 'customRequestTarget') && $this->customRequestTarget !== null) {
            $requestTarget = $this->customRequestTarget;
        }

        // If the new instance has a different request target than what's derived from its URI,
        // it means it has a custom request target set
        $defaultTarget = '/'.ltrim($new->getUri()->getPath(), '/');
        $query = $new->getUri()->getQuery();
        if ($query !== '') {
            $defaultTarget .= '?'.$query;
        }

        if ($new->getRequestTarget() !== $defaultTarget) {
            $requestTarget = $new->getRequestTarget();
        }

        // Create new instance with all properties
        $instance = new static(
            $new->getMethod(),
            $new->getUri(),
            $new->getHeaders(),
            $new->getBody(),
            $new->getProtocolVersion(),
            $requestTarget
        );

        return $instance;
    }
}
