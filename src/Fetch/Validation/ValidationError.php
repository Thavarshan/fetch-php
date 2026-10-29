<?php

declare(strict_types=1);

namespace Fetch\Validation;

/** @psalm-immutable */
final readonly class ValidationError
{
    /**
     * @param  array<int|string>  $path
     * @param  array<string, mixed>  $constraint
     */
    public function __construct(
        public array $path,
        public string $message,
        public array $constraint = [],
    ) {}

    public function pathString(): string
    {
        if ($this->path === []) {
            return '/';
        }

        return '/'.implode('/', array_map(
            static fn (int|string $segment): string => str_replace(['~', '/'], ['~0', '~1'], (string) $segment),
            $this->path
        ));
    }
}
