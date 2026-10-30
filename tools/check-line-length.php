<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$configFile = $root.'/pint.json';
$configContents = file_get_contents($configFile);

if ($configContents === false) {
    fwrite(STDERR, "Unable to read pint.json.\n");
    exit(1);
}

$config = json_decode(
    $configContents,
    true,
    flags: JSON_THROW_ON_ERROR,
);
$maxLength = $config['max_line_length'] ?? null;

if (! is_int($maxLength) || $maxLength < 1) {
    fwrite(
        STDERR,
        "pint.json must set max_line_length to a positive integer.\n",
    );
    exit(1);
}

$excludedDirectories = [
    '.git',
    'coverage',
    'dist',
    'node_modules',
    '.temp',
    '.phpunit.cache',
    'vendor',
];
$generatedDirectories = [
    $root.'/docs/.vitepress/.temp',
    $root.'/docs/.vitepress/cache',
];
$generatedFiles = [
    'composer.lock',
    'package-lock.json',
    '.php-cs-fixer.cache',
];
$directory = new RecursiveDirectoryIterator(
    $root,
    RecursiveDirectoryIterator::SKIP_DOTS,
);
$filter = new RecursiveCallbackFilterIterator(
    $directory,
    static function (SplFileInfo $entry) use (
        $excludedDirectories,
        $generatedDirectories,
        $generatedFiles,
    ): bool {
        if ($entry->isDir()) {
            if (in_array($entry->getPathname(), $generatedDirectories, true)) {
                return false;
            }

            if (
                $entry->getFilename() === '_'
                && basename(dirname($entry->getPathname())) === '.husky'
            ) {
                return false;
            }

            return ! in_array(
                $entry->getFilename(),
                $excludedDirectories,
                true,
            );
        }

        return ! in_array($entry->getFilename(), $generatedFiles, true);
    },
);
$files = new RecursiveIteratorIterator($filter);
$failures = [];

foreach ($files as $file) {
    $contents = file_get_contents($file->getPathname());

    if ($contents === false) {
        fwrite(STDERR, sprintf("Unable to read %s.\n", $file->getPathname()));
        exit(1);
    }

    if (str_contains($contents, "\0") || preg_match('//u', $contents) !== 1) {
        continue;
    }

    $lines = explode("\n", $contents);

    foreach ($lines as $index => $line) {
        $line = rtrim($line, "\r");
        $length = 0;
        $characters = preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY);

        if ($characters === false) {
            $length = strlen($line);
        } else {
            foreach ($characters as $character) {
                $length += $character === "\t" ? 4 - ($length % 4) : 1;
            }
        }

        if ($length > $maxLength) {
            $path = substr($file->getPathname(), strlen($root) + 1);
            $failures[] = sprintf(
                '%s:%d: %d columns',
                $path,
                $index + 1,
                $length,
            );
        }
    }
}

if ($failures !== []) {
    fwrite(
        STDERR,
        sprintf(
            "Project text files exceed the %d-column limit:\n",
            $maxLength,
        ),
    );
    fwrite(STDERR, implode("\n", $failures)."\n");
    exit(1);
}

printf("All maintained text files are at most %d columns.\n", $maxLength);
