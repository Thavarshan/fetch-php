<?php

declare(strict_types=1);

namespace Fetch\Validation;

use InvalidArgumentException;

/**
 * Reports malformed or unsupported validation configuration.
 */
final class ValidationConfigurationException extends InvalidArgumentException {}
