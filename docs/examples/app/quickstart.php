<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use Fetch\Exceptions\ClientException;

try {
    $response = fetch('https://jsonplaceholder.typicode.com/users/1');

    if ($response->successful()) {
        $user = $response->json();
        echo $user['name'].PHP_EOL;
    } else {
        echo 'HTTP '.$response->status().PHP_EOL;
    }
} catch (ClientException $exception) {
    echo 'The request could not be completed.'.PHP_EOL;
}
