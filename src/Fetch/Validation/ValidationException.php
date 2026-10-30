<?php

declare(strict_types=1);

namespace Fetch\Validation;

use RuntimeException;

/**
 * Base exception carrying structured validation failures.
 */
class ValidationException extends RuntimeException
{
    /**
     * Attach a result and optional lower-level validation cause.
     */
    public function __construct(
        string $message,
        private readonly ValidationResult $result,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Return the validation errors that caused this exception.
     */
    public function result(): ValidationResult
    {
        return $this->result;
    }
}
