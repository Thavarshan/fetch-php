<?php

declare(strict_types=1);

namespace Fetch\Validation;

/** @psalm-immutable */
final readonly class ValidationResult
{
    /**
     * @param  array<ValidationError>  $errors
     */
    public function __construct(private array $errors = []) {}

    public static function valid(): self
    {
        return new self;
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /** @return array<ValidationError> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function count(): int
    {
        return count($this->errors);
    }
}
