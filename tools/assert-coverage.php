<?php

declare(strict_types=1);

$reportPath = $argv[1] ?? 'coverage/index.xml';
$minimum = isset($argv[2]) ? (float) $argv[2] : 80.0;

if (! is_file($reportPath)) {
    fwrite(STDERR, "Coverage report not found: {$reportPath}\n");
    exit(1);
}

$document = new DOMDocument;
if (! $document->load($reportPath)) {
    fwrite(STDERR, "Unable to read coverage report: {$reportPath}\n");
    exit(1);
}

$xpath = new DOMXPath($document);
$xpath->registerNamespace('coverage', 'https://schema.phpunit.de/coverage/1.0');
$percent = $xpath->evaluate(
    'string(/coverage:phpunit/coverage:project/'
        .'coverage:directory[@name="/"]/coverage:totals/'
        .'coverage:lines/@percent)',
);

if (! is_numeric($percent)) {
    fwrite(STDERR, "Coverage report has no project line coverage total.\n");
    exit(1);
}

$coverage = (float) $percent;
printf("Line coverage: %.2f%% (minimum %.2f%%)\n", $coverage, $minimum);

exit($coverage >= $minimum ? 0 : 1);
