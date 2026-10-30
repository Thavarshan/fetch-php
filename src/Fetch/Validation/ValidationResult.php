<?php

declare(strict_types=1);

namespace Fetch\Validation;

/**
 * Immutable collection of validation errors returned by an adapter.
 *
 * An empty error list represents a valid payload.
 *
 * @psalm-immutable
 */
final readonly class ValidationResult
{
    /**
     * Creates a validation result from its validity flag and errors.
     *
     * @param  array<ValidationError>  $errors
     */
    public function __construct(private array $errors = []) {}

    /**
     * Create a successful result with no validation errors.
     */
    public static function valid(): self
    {
        return new self;
    }

    /**
     * Report whether this result contains no errors.
     */
    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /**
     * Return the errors collected by the validator.
     *
     * @return array<ValidationError>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Return the number of validation errors.
     */
    public function count(): int
    {
        return count($this->errors);
    }
}
