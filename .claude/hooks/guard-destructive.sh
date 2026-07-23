#!/bin/sh
# PreToolUse(Bash) guard for Fetch PHP.
#
# Blocks the destructive Git operations forbidden by AGENTS.md and a few
# catastrophic `rm` targets. This is defense-in-depth; the declarative
# permission rules in .claude/settings.json are the primary, fully-portable
# guard. Pure POSIX sh + grep — no jq/php/node dependency.
#
# Contract (see https://code.claude.com/docs/en/hooks): read the tool call as
# JSON on stdin; emit a PreToolUse permissionDecision of "deny" to block, or
# exit 0 with no output to let normal permission handling proceed.
#
# Platform note: requires a POSIX shell. On Windows, Claude Code runs hooks via
# the bundled Git Bash, so this works there too.

INPUT=$(cat)

deny() {
  # $1 = human-readable reason
  printf '{"hookSpecificOutput":{"hookEventName":"PreToolUse","permissionDecision":"deny","permissionDecisionReason":"%s"}}\n' "$1"
  exit 0
}

# Isolate the command string; fall back to the whole payload if extraction fails.
CMD=$(printf '%s' "$INPUT" | grep -oE '"command"[[:space:]]*:[[:space:]]*"([^"\\]|\\.)*"' | head -n1)
[ -z "$CMD" ] && CMD="$INPUT"

match() { printf '%s' "$CMD" | grep -Eiq "$1"; }

if match 'git[[:space:]]+reset[[:space:]]+--hard'; then
  deny "git reset --hard is forbidden by AGENTS.md. Run it manually if you truly intend to discard changes."
fi

if match 'git[[:space:]]+clean[[:space:]]+-[a-z]*(fd|df)' || match 'git[[:space:]]+clean[[:space:]]+.*--force'; then
  deny "git clean -fd is forbidden by AGENTS.md; it deletes untracked work. Run it manually if intended."
fi

if match 'git[[:space:]]+push[[:space:]]+.*(--force-with-lease|--force|(^| )-f( |$))'; then
  deny "Force-pushing is forbidden by AGENTS.md. Tag/immutability recovery (fetch-php-release) must be run manually by a maintainer."
fi

# Catastrophic recursive deletes of high-level roots only (repo-local rm -rf is allowed).
# The trailing class also accepts a JSON closing quote (") since the command is
# usually embedded in a JSON payload, plus space, glob star, or end of string.
if match 'rm[[:space:]]+-[a-z]*r[a-z]*f|rm[[:space:]]+-[a-z]*f[a-z]*r'; then
  if match 'rm[[:space:]]+-[a-z]+[[:space:]]+"?(/|~|\$HOME)([[:space:]"]|/?\*|$)'; then
    deny "Refusing recursive delete of a top-level path. Scope rm -rf to a specific repo subdirectory."
  fi
fi

exit 0
