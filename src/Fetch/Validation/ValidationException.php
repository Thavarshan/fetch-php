<?php

declare(strict_types=1);

namespace Fetch\Validation;

use RuntimeException;

class ValidationException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ValidationResult $result,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function result(): ValidationResult
    {
        return $this->result;
    }
}
