<?php

declare(strict_types=1);

namespace Fetch\Validation;

interface ValidatorInterface
{
    public function validate(mixed $data, mixed $schema): ValidationResult;
}
