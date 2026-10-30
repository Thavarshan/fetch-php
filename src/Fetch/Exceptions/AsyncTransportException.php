<?php

declare(strict_types=1);

namespace Fetch\Exceptions;

use RuntimeException;

/**
 * Reports that the configured Guzzle client cannot complete async I/O.
 */
final class AsyncTransportException extends RuntimeException {}
