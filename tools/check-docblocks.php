<?php

declare(strict_types=1);

/**
 * Return the ID of a token, or null for a punctuation token.
 *
 * @param  array{0: int, 1: string, 2: int}|string  $token  PHP token
 */
function tokenId(array|string $token): ?int
{
    return is_array($token) ? $token[0] : null;
}

/**
 * Return the source text for a token.
 *
 * @param  array{0: int, 1: string, 2: int}|string  $token  PHP token
 */
function tokenText(array|string $token): string
{
    return is_array($token) ? $token[1] : $token;
}

/**
 * Skip a PHP attribute group while walking backwards through tokens.
 *
 * @param  list<array{0: int, 1: string, 2: int}|string>  $tokens  PHP tokens
 * @param  int  $index  Closing bracket index
 * @return int Index before the attribute group, or -1 if it is malformed
 */
function skipAttributeGroup(array $tokens, int $index): int
{
    $depth = 0;

    for ($cursor = $index; $cursor >= 0; $cursor--) {
        $token = $tokens[$cursor];
        $text = tokenText($token);

        if ($text === ']') {
            $depth++;
        } elseif ($text === '[') {
            $depth--;
        } elseif (tokenId($token) === T_ATTRIBUTE) {
            if ($depth === 1) {
                return $cursor - 1;
            }

            return -1;
        }
    }

    return -1;
}

/**
 * Find the docblock immediately preceding a declaration.
 *
 * @param  list<array{0: int, 1: string, 2: int}|string>  $tokens  PHP tokens
 * @param  int  $index  Declaration token index
 */
function findDeclarationDocblock(array $tokens, int $index): ?string
{
    $modifiers = [
        T_ABSTRACT,
        T_FINAL,
        T_PRIVATE,
        T_PROTECTED,
        T_PUBLIC,
        T_READONLY,
        T_STATIC,
    ];

    for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
        $token = $tokens[$cursor];
        $id = tokenId($token);
        $text = tokenText($token);

        if ($id === T_WHITESPACE) {
            continue;
        }

        if ($id === T_DOC_COMMENT) {
            return $text;
        }

        if ($id === T_COMMENT) {
            return null;
        }

        if ($id !== null && in_array($id, $modifiers, true)) {
            continue;
        }

        if ($id === T_ATTRIBUTE && $text === '#[') {
            continue;
        }

        if ($text === ']') {
            $cursor = skipAttributeGroup($tokens, $cursor);

            if ($cursor < 0) {
                return null;
            }

            continue;
        }

        if ($text === '&') {
            continue;
        }

        return null;
    }

    return null;
}

/**
 * Check whether a docblock is multiline and contains descriptive prose.
 *
 * @param  string|null  $docblock  Candidate PHPDoc comment
 * @return string|null Failure reason, or null when valid
 */
function docblockFailure(?string $docblock): ?string
{
    if ($docblock === null) {
        return 'missing a PHPDoc block';
    }

    if (count(preg_split('/\R/', $docblock) ?: []) < 3) {
        return 'requires a multiline PHPDoc block';
    }

    $hasDescription = false;

    foreach (preg_split('/\R/', $docblock) ?: [] as $line) {
        $line = trim($line);
        $line = preg_replace('/^\/\*\*?\s?/', '', $line) ?? $line;
        $line = preg_replace('/\*\/\s*$/', '', $line) ?? $line;
        $line = preg_replace('/^\*\s?/', '', $line) ?? $line;
        $line = trim($line);

        if ($line !== '' && ! str_starts_with($line, '@')) {
            $hasDescription = true;

            break;
        }
    }

    if (! $hasDescription) {
        return 'requires a non-empty description';
    }

    return null;
}

/**
 * Detect a second property in the same declaration statement.
 *
 * @param  list<array{0: int, 1: string, 2: int}|string>  $tokens  PHP tokens
 * @param  int  $index  First property variable index
 */
function hasMultipleProperties(array $tokens, int $index): bool
{
    $depth = 0;

    for (
        $cursor = $index + 1, $count = count($tokens);
        $cursor < $count;
        $cursor++
    ) {
        $text = tokenText($tokens[$cursor]);

        if (in_array($text, ['(', '[', '{'], true)) {
            $depth++;

            continue;
        }

        if (in_array($text, [')', ']', '}'], true)) {
            $depth--;

            continue;
        }

        if ($depth === 0 && $text === ';') {
            return false;
        }

        if ($depth === 0 && $text === ',') {
            return true;
        }
    }

    return false;
}

$sourceDirectory = dirname(__DIR__).'/src/Fetch';

if (! is_dir($sourceDirectory)) {
    fwrite(STDERR, "Unable to locate src/Fetch.\n");
    exit(1);
}

$directory = new RecursiveDirectoryIterator(
    $sourceDirectory,
    RecursiveDirectoryIterator::SKIP_DOTS,
);
$files = new RecursiveIteratorIterator($directory);
$failures = [];
$checkedFunctions = 0;
$checkedProperties = 0;

foreach ($files as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $contents = file_get_contents($file->getPathname());

    if ($contents === false) {
        fwrite(STDERR, sprintf("Unable to read %s.\n", $file->getPathname()));
        exit(1);
    }

    try {
        $tokens = token_get_all($contents, TOKEN_PARSE);
    } catch (ParseError $exception) {
        fwrite(
            STDERR,
            sprintf(
                "Unable to parse %s: %s\n",
                $file->getPathname(),
                $exception->getMessage(),
            ),
        );
        exit(1);
    }

    foreach ($tokens as $index => $token) {
        if (tokenId($token) !== T_FUNCTION) {
            continue;
        }

        $nameIndex = $index + 1;

        while (
            isset($tokens[$nameIndex]) &&
            tokenId($tokens[$nameIndex]) === T_WHITESPACE
        ) {
            $nameIndex++;
        }

        if (
            isset($tokens[$nameIndex]) &&
            tokenText($tokens[$nameIndex]) === '&'
        ) {
            $nameIndex++;
        }

        while (
            isset($tokens[$nameIndex]) &&
            tokenId($tokens[$nameIndex]) === T_WHITESPACE
        ) {
            $nameIndex++;
        }

        if (
            ! isset($tokens[$nameIndex]) ||
            tokenId($tokens[$nameIndex]) !== T_STRING
        ) {
            continue;
        }

        $checkedFunctions++;
        $failure = docblockFailure(
            findDeclarationDocblock($tokens, $index),
        );

        if ($failure !== null) {
            $line = is_array($token) ? $token[2] : 0;
            $failures[] = sprintf(
                '%s:%d: %s %s.',
                substr($file->getPathname(), strlen(dirname(__DIR__)) + 1),
                $line,
                $failure,
                tokenText($tokens[$nameIndex]),
            );
        }
    }

    foreach ($tokens as $index => $token) {
        if (tokenId($token) !== T_VARIABLE) {
            continue;
        }

        $modifierIndex = null;
        $isPromoted = false;

        for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
            $previous = $tokens[$cursor];
            $id = tokenId($previous);
            $text = tokenText($previous);

            if (
                $id !== null &&
                in_array($id, [T_PRIVATE, T_PROTECTED, T_PUBLIC, T_VAR], true)
            ) {
                $modifierIndex = $cursor;

                for ($scope = $cursor - 1; $scope >= 0; $scope--) {
                    $scopeToken = $tokens[$scope];
                    $scopeId = tokenId($scopeToken);
                    $scopeText = tokenText($scopeToken);

                    if ($scopeId === T_FUNCTION) {
                        $isPromoted = true;

                        break;
                    }

                    if (
                        $scopeText === '{' ||
                        $scopeText === '}' ||
                        $scopeText === ';'
                    ) {
                        break;
                    }
                }

                break;
            }

            if (
                in_array($text, [',', ';', '{', '}', '=', '(', '=>'], true) ||
                $id === T_FUNCTION
            ) {
                break;
            }
        }

        if ($modifierIndex === null) {
            continue;
        }

        $checkedProperties++;
        $docblock = $isPromoted
            ? null
            : findDeclarationDocblock($tokens, $modifierIndex);

        if ($isPromoted) {
            for ($cursor = $modifierIndex - 1; $cursor >= 0; $cursor--) {
                if (tokenId($tokens[$cursor]) === T_FUNCTION) {
                    $docblock = findDeclarationDocblock($tokens, $cursor);

                    break;
                }
            }
        }

        $failure = docblockFailure($docblock);

        if (! $isPromoted && hasMultipleProperties($tokens, $index)) {
            $failure = 'must be declared separately for its own docblock';
        }

        if ($failure !== null) {
            $line = is_array($token) ? $token[2] : 0;
            $failures[] = sprintf(
                '%s:%d: %s property %s.',
                substr($file->getPathname(), strlen(dirname(__DIR__)) + 1),
                $line,
                $failure,
                tokenText($token),
            );
        }
    }
}

if ($failures !== []) {
    fwrite(
        STDERR,
        "PHP declarations require multiline docblocks with descriptions:\n".
            implode("\n", $failures)."\n",
    );
    exit(1);
}

printf(
    'All %d functions/methods and %d properties in src/Fetch have '.
        "multiline docblocks with descriptions.\n",
    $checkedFunctions,
    $checkedProperties,
);
