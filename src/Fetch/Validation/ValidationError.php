<?php

declare(strict_types=1);

namespace Fetch\Validation;

/**
 * Immutable validation issue with a structured path and constraint metadata.
 *
 * Path segments are retained as strings or integers and can be rendered as a
 * JSON Pointer for display or diagnostics.
 *
 * @psalm-immutable
 */
final readonly class ValidationError
{
    /**
     * Creates a validation error with its message and data path.
     *
     * @param  array<int|string>  $path
     * @param  array<string, mixed>  $constraint
     */
    public function __construct(
        public array $path,
        public string $message,
        public array $constraint = [],
    ) {}

    /**
     * Render the path as an escaped JSON Pointer, using `/` for the root.
     */
    public function pathString(): string
    {
        if ($this->path === []) {
            return '/';
        }

        return '/'.
            implode(
                '/',
                array_map(
                    static fn (int|string $segment): string => str_replace(
                        ['~', '/'],
                        ['~0', '~1'],
                        (string) $segment,
                    ),
                    $this->path,
                ),
            );
    }
}
