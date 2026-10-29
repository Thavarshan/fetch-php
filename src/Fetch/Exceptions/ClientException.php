<?php

declare(strict_types=1);

namespace Fetch\Exceptions;

use Psr\Http\Client\ClientExceptionInterface as ClientContract;
use RuntimeException;

class ClientException extends RuntimeException implements ClientContract
{
    //
}
