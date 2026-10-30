<?php

declare(strict_types=1);

namespace Fetch\Validation;

/**
 * Reports that outgoing request data failed the configured validation policy.
 */
final class RequestValidationException extends ValidationException {}
