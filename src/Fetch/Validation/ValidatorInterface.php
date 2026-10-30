<?php

declare(strict_types=1);

namespace Fetch\Validation;

/**
 * Contract for an adapter that validates data against a schema.
 */
interface ValidatorInterface
{
    /**
     * Validate one value and return all errors the adapter can collect.
     *
     * @throws ValidationConfigurationException When the schema is invalid
     */
    public function validate(mixed $data, mixed $schema): ValidationResult;
}
