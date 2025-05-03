#!/usr/bin/env bash

# Exit when undeclared variables are used
set -u

# Make pipe commands return the exit status of the last command that fails
set -o pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
cd "$PROJECT_ROOT"

# Config
DUSTER_PACKAGE="tightenco/duster"
DUSTER_PATH="vendor/bin/duster"
DIRECTORIES_TO_ANALYSE="src"
FIX_MODE=0
AUTO_INSTALL=1
LINT_ONLY=0
STRICT_MODE=0
EXTRA_DIRS=""
VERBOSE=0

show_usage() {
    echo "Usage: $0 [options]"
    echo ""
    echo "Options:"
    echo "  -d, --directories DIRS  Comma-separated directories (default: app)"
    echo "  -f, --fix               Fix mode"
    echo "  -l, --lint-only         Skip Duster"
    echo "  -n, --no-install        Don't auto-install Duster"
    echo "  -s, --strict            Exit non-zero if any issues found"
    echo "  -v, --verbose           Verbose output"
    echo "  -h, --help              Show this help"
}

while [[ $# -gt 0 ]]; do
    case $1 in
    -d | --directories)
        DIRECTORIES_TO_ANALYSE="$2"
        shift 2
        ;;
    --directories=*)
        DIRECTORIES_TO_ANALYSE="${1#*=}"
        shift
        ;;
    -f | --fix)
        FIX_MODE=1
        shift
        ;;
    -l | --lint-only)
        LINT_ONLY=1
        shift
        ;;
    -n | --no-install)
        AUTO_INSTALL=0
        shift
        ;;
    -s | --strict)
        STRICT_MODE=1
        shift
        ;;
    -v | --verbose)
        VERBOSE=1
        shift
        ;;
    -h | --help)
        show_usage
        exit 0
        ;;
    *)
        if [[ -d "$1" ]]; then
            EXTRA_DIRS="$EXTRA_DIRS $1"
        else
            echo "Unknown option or directory: $1"
            show_usage
            exit 1
        fi
        shift
        ;;
    esac
done

if [[ -n "$EXTRA_DIRS" ]]; then
    DIRECTORIES_TO_ANALYSE="$DIRECTORIES_TO_ANALYSE $EXTRA_DIRS"
fi

DIRECTORIES_TO_ANALYSE="${DIRECTORIES_TO_ANALYSE//,/ }"

is_composer_package_installed() {
    composer show "$1" >/dev/null 2>&1
}

validate_php_syntax() {
    local directory="$1"
    local status=0
    local file_count=0
    local error_count=0

    echo "Checking PHP syntax in $directory..."

    while IFS= read -r -d $'\0' file; do
        file_count=$((file_count + 1))
        if [[ $VERBOSE -eq 1 ]]; then
            echo "Checking syntax of $file"
        fi
        if ! php -l "$file" >/dev/null 2>&1; then
            error_count=$((error_count + 1))
            status=1
            php -l "$file"
        fi
    done < <(find "$directory" -type f -name "*.php" -print0)

    echo "✓ Checked $file_count PHP files in $directory with $error_count errors"
    return $status
}

EXIT_STATUS=0

if [[ $LINT_ONLY -eq 0 ]]; then
    if ! is_composer_package_installed "$DUSTER_PACKAGE"; then
        if [[ $AUTO_INSTALL -eq 1 ]]; then
            echo "Installing $DUSTER_PACKAGE..."
            composer require --dev "$DUSTER_PACKAGE"
        else
            echo "Error: $DUSTER_PACKAGE not installed."
            exit 1
        fi
    fi

    if [[ ! -f "$DUSTER_PATH" ]]; then
        echo "Error: Duster binary not found at $DUSTER_PATH"
        exit 1
    fi

    if [[ $FIX_MODE -eq 1 ]]; then
        echo "Running Duster FIX on: $DIRECTORIES_TO_ANALYSE"
        $DUSTER_PATH fix $DIRECTORIES_TO_ANALYSE || EXIT_STATUS=1
    else
        echo "Running Duster LINT on: $DIRECTORIES_TO_ANALYSE"
        $DUSTER_PATH lint $DIRECTORIES_TO_ANALYSE || EXIT_STATUS=1
    fi
fi

for dir in $DIRECTORIES_TO_ANALYSE; do
    if [[ -d "$dir" ]]; then
        if ! validate_php_syntax "$dir"; then
            EXIT_STATUS=1
        fi
    else
        echo "Warning: '$dir' not found"
    fi
done

if [[ $EXIT_STATUS -eq 0 ]]; then
    echo "✅ All linting checks passed!"
else
    echo "⚠️  Linting completed with issues."
fi

# Only block the commit if strict mode is enabled
if [[ $STRICT_MODE -eq 1 ]]; then
    exit $EXIT_STATUS
else
    exit 0
fi
