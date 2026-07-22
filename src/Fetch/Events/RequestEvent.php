<?php

declare(strict_types=1);

namespace Fetch\Events;

use Psr\Http\Message\RequestInterface;

/**
 * Dispatched just before a request is sent (after middleware, before the
 * built-in mock/cache/transport layers).
 */
final class RequestEvent extends FetchEvent
{
    public const NAME = 'request.sending';

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        RequestInterface $request,
        string $correlationId,
        float $timestamp,
        array $context = [],
        protected readonly array $options = [],
    ) {
        parent::__construct($request, $correlationId, $timestamp, $context);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
