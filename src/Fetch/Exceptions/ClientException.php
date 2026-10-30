<?php

declare(strict_types=1);

namespace Fetch\Exceptions;

use Psr\Http\Client\ClientExceptionInterface as ClientContract;
use RuntimeException;

/**
 * Represents a FetchPHP client failure covered by the PSR-18 contract.
 */
class ClientException extends RuntimeException implements ClientContract
{
    //
}
