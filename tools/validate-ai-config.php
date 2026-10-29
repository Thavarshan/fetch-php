<?php

declare(strict_types=1);

/**
 * Validates the Fetch PHP cross-agent AI configuration.
 *
 * Pure PHP, no Composer dependencies, no network. Run locally or in CI:
 *
 *     composer ai:validate      # or: php tools/validate-ai-config.php
 *
 * Exit code 0 = all checks pass (warnings allowed), 1 = one or more errors.
 */
$root = dirname(__DIR__);

$errors = [];
$warnings = [];
$checks = 0;

function rel(string $root, string $path): string
{
    return ltrim(str_replace($root, '', $path), '/\\');
}

/**
 * Minimal front-matter reader: returns [frontmatterAssoc, body] or [null,
 *     body].
 * Supports `key: value` and simple `key:` + `- item` YAML lists. No YAML dep.
 *
 * @return array{0: array<string, mixed>|null, 1: string}
 */
function read_front_matter(string $contents): array
{
    if (! preg_match('/^---\R(.*?)\R---\R?(.*)$/s', $contents, $m)) {
        return [null, $contents];
    }

    $data = [];
    $currentListKey = null;
    $currentTextKey = null;
    $lines = preg_split('/\R/', $m[1]) ?: [];

    foreach ($lines as $index => $line) {
        if (trim($line) === '') {
            continue;
        }

        if (
            preg_match('/^\s*-\s+(.*)$/', $line, $lm) &&
            $currentListKey !== null
        ) {
            $data[$currentListKey][] = trim($lm[1], " \"'");

            continue;
        }

        if (
            $currentTextKey !== null &&
            preg_match('/^\s+\S/', $line) &&
            ! preg_match('/^\s*-\s+/', $line)
        ) {
            $data[$currentTextKey] .= ' '.trim($line);

            continue;
        }

        if (preg_match('/^([A-Za-z0-9_-]+):\s*(.*)$/', $line, $km)) {
            $key = $km[1];
            $value = trim($km[2]);
            if ($value === '') {
                $currentListKey = null;
                $currentTextKey = null;

                for ($next = $index + 1; $next < count($lines); $next++) {
                    if (trim($lines[$next]) === '') {
                        continue;
                    }

                    if (preg_match('/^\s*-\s+/', $lines[$next])) {
                        $currentListKey = $key;
                        $data[$key] = [];
                    } elseif (preg_match('/^\s+\S/', $lines[$next])) {
                        $currentTextKey = $key;
                        $data[$key] = '';
                    } else {
                        $data[$key] = '';
                    }

                    break;
                }

                if (! array_key_exists($key, $data)) {
                    $data[$key] = '';
                }
            } else {
                $currentListKey = null;
                $currentTextKey = null;
                $data[$key] = trim($value, " \"'");
            }
        }
    }

    return [$data, $m[2]];
}

// ---------------------------------------------------------------------------
// 1. Required files exist
// ---------------------------------------------------------------------------
$required = [
    'AGENTS.md',
    'CLAUDE.md',
    'CODE_MAP.md',
    'README.md',
    '.claude/settings.json',
    '.claude/hooks/guard',
    'docs/ai/README.md',
    'docs/ai/SOURCES.md',
    'tools/validate-ai-config.php',
];
foreach ($required as $file) {
    $checks++;
    if (! is_file("$root/$file")) {
        $errors[] = "Missing required file: $file";
    }
}

// ---------------------------------------------------------------------------
// 2. CLAUDE.md imports the canonical AGENTS.md
// ---------------------------------------------------------------------------
$checks++;
$claude = @file_get_contents("$root/CLAUDE.md") ?: '';
if (! preg_match('/(^|\s)@(\.\/)?AGENTS\.md(\s|$)/m', $claude)) {
    $errors[] =
        'CLAUDE.md must import the canonical instructions with `@AGENTS.md`.';
}

$agents = @file_get_contents("$root/AGENTS.md") ?: '';

// ---------------------------------------------------------------------------
// 3. Skills: SKILL.md present, valid + unique names, name matches directory,
//    and every skill is referenced in AGENTS.md (Claude <-> Codex sync).
// ---------------------------------------------------------------------------
$skillsDir = "$root/.claude/skills";
$skillNames = [];
$skillBodies = [];
if (is_dir($skillsDir)) {
    foreach (glob("$skillsDir/*", GLOB_ONLYDIR) as $dir) {
        $dirName = basename($dir);
        $skillFile = "$dir/SKILL.md";
        $checks++;
        if (! is_file($skillFile)) {
            $errors[] = "Skill '$dirName' is missing SKILL.md";

            continue;
        }
        [$fm, $body] = read_front_matter(
            (string) file_get_contents($skillFile),
        );
        if ($fm === null) {
            $errors[] = "Skill '$dirName' SKILL.md has no YAML front matter";

            continue;
        }
        $name = $fm['name'] ?? null;
        if (! is_string($name) || $name === '') {
            $errors[] = "Skill '$dirName' is missing a 'name' field";
            $name = $dirName;
        }
        if (empty($fm['description']) || ! is_string($fm['description'])) {
            $errors[] = "Skill '$dirName' is missing a 'description' field";
        } elseif (strlen($fm['description']) > 1024) {
            $errors[] = "Skill '$dirName' description exceeds 1024 characters";
        }
        if (
            is_string($name) &&
            ! preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $name)
        ) {
            $errors[] = sprintf(
                "Skill '%s' name '%s' must be lowercase alphanumeric "
                    . 'with single hyphens',
                $dirName,
                $name,
            );
        }
        if (is_string($name) && strlen($name) > 64) {
            $errors[] = "Skill '$dirName' name exceeds 64 characters";
        }
        if ($name !== $dirName) {
            $errors[] = sprintf(
                "Skill '%s' name '%s' must match its directory name",
                $dirName,
                $name,
            );
        }
        if (isset($skillNames[$name])) {
            $errors[] = "Duplicate skill name '$name'";
        }
        $skillNames[$name] = true;
        $bodyHash = md5(preg_replace('/\s+/', ' ', trim($body)) ?? '');
        if (isset($skillBodies[$bodyHash])) {
            $warnings[] = sprintf(
                "Skill '%s' body is identical to '%s' (possible drift/copy)",
                $dirName,
                $skillBodies[$bodyHash],
            );
        }
        $skillBodies[$bodyHash] = $dirName;

        // Sync check: referenced in AGENTS.md so Codex/AGENTS.md-aware tools
        // find it.
        $checks++;
        if (strpos($agents, (string) $dirName) === false) {
            $errors[] = sprintf(
                "Skill '%s' is not referenced in AGENTS.md "
                    . '(Claude/Codex skill discovery would drift)',
                $dirName,
            );
        }
    }
}
if ($skillNames === []) {
    $warnings[] = 'No skills found under .claude/skills/';
}

// Reverse sync: skill-shaped names mentioned in AGENTS.md must exist on disk.
if (preg_match_all('/`(fetch-php-[a-z0-9-]+)`/', $agents, $mm)) {
    foreach (array_unique($mm[1]) as $mentioned) {
        $checks++;
        if (! isset($skillNames[$mentioned])) {
            $errors[] = sprintf(
                "AGENTS.md references skill '%s' without a matching "
                    . '.claude/skills/%s/SKILL.md file',
                $mentioned,
                $mentioned,
            );
        }
    }
}

// ---------------------------------------------------------------------------
// 4. Subagents: name + description present and unique.
// ---------------------------------------------------------------------------
$agentNames = [];
foreach (glob("$root/.claude/agents/*.md") ?: [] as $file) {
    $checks++;
    [$fm] = read_front_matter((string) file_get_contents($file));
    $base = rel($root, $file);
    if ($fm === null) {
        $errors[] = "Subagent $base has no YAML front matter";

        continue;
    }
    if (empty($fm['name']) || ! is_string($fm['name'])) {
        $errors[] = "Subagent $base is missing a 'name' field";
    } else {
        if (isset($agentNames[$fm['name']])) {
            $errors[] = "Duplicate subagent name '{$fm['name']}'";
        }
        $agentNames[$fm['name']] = true;
    }
    if (empty($fm['description']) || ! is_string($fm['description'])) {
        $errors[] = "Subagent $base is missing a 'description' field";
    }
}

// ---------------------------------------------------------------------------
// 5. Rules: front matter (when present) must expose `paths` as a list.
// ---------------------------------------------------------------------------
foreach (glob("$root/.claude/rules/*.md") ?: [] as $file) {
    $checks++;
    [$fm] = read_front_matter((string) file_get_contents($file));
    $base = rel($root, $file);
    if (
        $fm !== null &&
        array_key_exists('paths', $fm) &&
        ! is_array($fm['paths'])
    ) {
        $errors[] = "Rule $base has a 'paths' field that is not a YAML list";
    }
}

// ---------------------------------------------------------------------------
// 6. JSON config files parse.
// ---------------------------------------------------------------------------
foreach (
    [
        '.claude/settings.json',
        '.claude/settings.local.json',
        'composer.json',
        'package.json',
        'context7.json',
    ] as $json
) {
    $path = "$root/$json";
    if (! is_file($path)) {
        continue;
    }
    $checks++;
    json_decode((string) file_get_contents($path));
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = "$json is not valid JSON: ".json_last_error_msg();
    }
}

// ---------------------------------------------------------------------------
// 7. No personal absolute paths or secret-looking material in the AI surface.
// ---------------------------------------------------------------------------
$surface = array_merge(
    ['AGENTS.md', 'CLAUDE.md', '.claude/settings.json'],
    array_map(
        fn ($f) => rel($root, $f),
        glob("$root/.claude/skills/*/SKILL.md") ?: [],
    ),
    array_map(
        fn ($f) => rel($root, $f),
        glob("$root/.claude/rules/*.md") ?: [],
    ),
    array_map(
        fn ($f) => rel($root, $f),
        glob("$root/.claude/agents/*.md") ?: [],
    ),
);
$pathPatterns = [
    '#/home/[a-z0-9._-]+/#i',
    '#/Users/[a-z0-9._-]+/#i',
    '#[A-Z]:\\\\Users\\\\#i',
    '#/root/#',
];
$secretPatterns = [
    '/AKIA[0-9A-Z]{16}/',
    '/ghp_[A-Za-z0-9]{36}/',
    '/xox[baprs]-[A-Za-z0-9-]{10,}/',
    '/-----BEGIN [A-Z ]*PRIVATE KEY-----/',
    '/(api[_-]?token|secret|password)\s*[=:]\s*["\']?[A-Za-z0-9]{16,}/i',
];
foreach ($surface as $file) {
    $path = "$root/$file";
    if (! is_file($path)) {
        continue;
    }
    $checks++;
    $contents = (string) file_get_contents($path);
    foreach ($pathPatterns as $p) {
        if (preg_match($p, $contents)) {
            $errors[] = "$file contains a personal absolute path (matched $p)";
        }
    }
    foreach ($secretPatterns as $p) {
        if (preg_match($p, $contents)) {
            $errors[] = "$file contains a possible secret (matched $p)";
        }
    }
}

// ---------------------------------------------------------------------------
// 8. External sources are documented.
// ---------------------------------------------------------------------------
$checks++;
$sources = @file_get_contents("$root/docs/ai/SOURCES.md") ?: '';
if (strlen(trim($sources)) < 200) {
    $warnings[] =
        'docs/ai/SOURCES.md looks empty — external sources should be '
            . 'attributed there.';
}

// ---------------------------------------------------------------------------
// Report
// ---------------------------------------------------------------------------
echo "Fetch PHP AI-config validation\n";
echo str_repeat('-', 34)."\n";
echo "Checks run: $checks\n";
foreach ($warnings as $w) {
    echo "  [warn]  $w\n";
}
foreach ($errors as $e) {
    echo "  [FAIL]  $e\n";
}

if ($errors === []) {
    echo count($warnings) > 0
        ? "\nOK with ".count($warnings)." warning(s).\n"
        : "\nAll checks passed.\n";
    exit(0);
}

echo "\n".
    count($errors).
    ' error(s), '.
    count($warnings).
    " warning(s).\n";
exit(1);
