<?php

declare(strict_types=1);

require dirname(__DIR__, 3).'/vendor/autoload.php';

use Matrix\Exceptions\CancellationException;

use function Matrix\Support\async;

$started = false;
$promise = async(static function () use (&$started): void {
    $started = true;
});

try {
    $promise->cancel();
} catch (CancellationException) {
    // Matrix rejects a cancelled promise with this exception.
}

if ($started) {
    throw new RuntimeException('The queued operation should not have started.');
}
