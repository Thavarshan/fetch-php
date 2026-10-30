<?php

declare(strict_types=1);

namespace Fetch\Validation;

use RuntimeException;

/**
 * Reports that an optional validation integration is not installed.
 */
final class ValidationDependencyException extends RuntimeException {}
