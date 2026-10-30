<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$markdownFiles = [$root.'/README.md'];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $root.'/docs',
        FilesystemIterator::SKIP_DOTS,
    ),
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'md') {
        $markdownFiles[] = $file->getPathname();
    }
}

/**
 * Returns the process exit code and combined output from the command.
 *
 * @return array{int, string}
 */
$run = static function (array $command) use ($root): array {
    $process = proc_open(
        $command,
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $root,
    );

    if (! is_resource($process)) {
        throw new RuntimeException(
            'Could not start the PHP documentation example check.',
        );
    }

    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $output];
};

$phpBlocks = 0;
foreach ($markdownFiles as $file) {
    $markdown = file_get_contents($file);

    if ($markdown === false) {
        throw new RuntimeException(sprintf('Could not read %s.', $file));
    }

    preg_match_all('/```php(?:[^\r\n]*)\R(.*?)\R```/s', $markdown, $matches);

    foreach ($matches[1] as $index => $code) {
        $phpBlocks++;
        $source = str_starts_with(ltrim($code), '<?php')
            ? $code
            : "<?php\n".$code;
        $temporaryFile = tempnam(sys_get_temp_dir(), 'fetch-doc-example-');

        if ($temporaryFile === false) {
            throw new RuntimeException(
                'Could not create a temporary PHP example file.',
            );
        }

        try {
            file_put_contents($temporaryFile, $source);
            [$exitCode, $output] = $run([PHP_BINARY, '-l', $temporaryFile]);

            if ($exitCode !== 0) {
                throw new RuntimeException(
                    sprintf(
                        "Invalid PHP example in %s (block %d):\n%s",
                        $file,
                        $index + 1,
                        $output,
                    ),
                );
            }
        } finally {
            unlink($temporaryFile);
        }
    }
}

$exampleFiles = glob($root.'/docs/examples/snippets/*.php') ?: [];

if ($exampleFiles === []) {
    throw new RuntimeException(
        'No runnable documentation examples were found.',
    );
}

foreach ($exampleFiles as $file) {
    [$lintExitCode, $lintOutput] = $run([PHP_BINARY, '-l', $file]);

    if ($lintExitCode !== 0) {
        throw new RuntimeException(
            sprintf("Invalid PHP example file %s:\n%s", $file, $lintOutput),
        );
    }

    [$runExitCode, $runOutput] = $run([PHP_BINARY, $file]);

    if ($runExitCode !== 0) {
        throw new RuntimeException(
            sprintf("PHP example failed: %s\n%s", $file, $runOutput),
        );
    }
}

$application = $root.'/docs/examples/app/quickstart.php';
$sandbox = sys_get_temp_dir().'/fetch-doc-app-'.bin2hex(random_bytes(6));
mkdir($sandbox.'/vendor', 0777, true);

try {
    $bootstrap = "<?php\nrequire "
        .var_export($root.'/vendor/autoload.php', true).";\n"
        ."Fetch\\Testing\\MockServer::fake([\n"
        ."'GET https://jsonplaceholder.typicode.com/users/1' => "
        ."Fetch\\Testing\\MockResponse::json(['name' => 'Ada Lovelace'])"
        ."]);\nFetch\\Testing\\MockServer::preventStrayRequests();\n";
    file_put_contents($sandbox.'/vendor/autoload.php', $bootstrap);
    copy($application, $sandbox.'/example.php');
    [$exitCode, $output] = $run([PHP_BINARY, $sandbox.'/example.php']);

    if ($exitCode !== 0 || trim($output) !== 'Ada Lovelace') {
        throw new RuntimeException(
            "The application quickstart failed offline:\n".$output,
        );
    }
} finally {
    unlink($sandbox.'/example.php');
    unlink($sandbox.'/vendor/autoload.php');
    rmdir($sandbox.'/vendor');
    rmdir($sandbox);
}

printf(
    'Checked syntax for %d Markdown PHP block(s) and executed '
        ."%d offline PHP example(s).\n",
    $phpBlocks,
    count($exampleFiles) + 1,
);
