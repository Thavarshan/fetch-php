#!/usr/bin/env bash

# Make script exit when a command fails
set -e

# Make script exit when an undeclared variable is used
set -u

# Make pipe commands return the exit status of the last command that fails or all commands if successful
set -o pipefail

# Get the directory where the script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Change to project root directory
cd "$PROJECT_ROOT"

# Variables for customizing test behavior
COVERAGE=0
PARALLEL=0
FILTER=""
SPECIFIC_TEST=""

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
    --coverage)
        COVERAGE=1
        shift
        ;;
    --parallel)
        PARALLEL=1
        shift
        ;;
    --filter=*)
        FILTER="${1#*=}"
        shift
        ;;
    --test=*)
        SPECIFIC_TEST="${1#*=}"
        shift
        ;;
    --help)
        echo "Usage: $0 [options]"
        echo "Options:"
        echo "  --coverage      Generate code coverage report"
        echo "  --parallel      Run tests in parallel"
        echo "  --filter=NAME   Only run tests matching the filter"
        echo "  --test=PATH     Run a specific test file or directory"
        echo "  --help          Display this help message"
        exit 0
        ;;
    *)
        echo "Unknown option: $1"
        echo "Use --help for usage information."
        exit 1
        ;;
    esac
done

# Check PHP version
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "Using PHP version: $PHP_VERSION"

# Run composer install if vendor directory is missing
if [ ! -d "vendor" ]; then
    echo "Vendor directory missing. Running composer install..."
    composer install
fi

# Build test command
TEST_CMD="vendor/bin/phpunit"

if [ -n "$FILTER" ]; then
    TEST_CMD="$TEST_CMD --filter=$FILTER"
fi

if [ -n "$SPECIFIC_TEST" ]; then
    TEST_CMD="$TEST_CMD $SPECIFIC_TEST"
fi

if [ "$PARALLEL" -eq 1 ]; then
    TEST_CMD="vendor/bin/paratest"
    if [ -n "$FILTER" ]; then
        echo "Warning: --filter is not supported with parallel testing. Ignoring filter."
    fi
fi

# Run the tests
if [ "$COVERAGE" -eq 1 ]; then
    # Check if Xdebug is installed
    if php -m | grep -q xdebug; then
        echo "Generating test coverage report..."
        XDEBUG_MODE=coverage $TEST_CMD --coverage-text --coverage-html=coverage
    else
        echo "Warning: Xdebug is not installed. Cannot generate coverage report."
        $TEST_CMD
    fi
else
    $TEST_CMD
fi

# Check exit code
TEST_EXIT_CODE=$?

# Show summary message
if [ $TEST_EXIT_CODE -eq 0 ]; then
    echo "✅ Tests completed successfully!"
else
    echo "❌ Tests failed with exit code: $TEST_EXIT_CODE"
fi

exit $TEST_EXIT_CODE
