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

# Check if we're in the right directory (has artisan file)
if [ ! -f "artisan" ]; then
    echo "Error: Could not find artisan file. Make sure you're running this from the project root or bin directory."
    exit 1
fi

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

# Check if .env exists and create it from .env.example if it doesn't
if [ ! -f ".env" ]; then
    echo "Creating .env file from .env.example..."
    cp .env.example .env
fi

# Run composer install if vendor directory is missing
if [ ! -d "vendor" ]; then
    echo "Vendor directory missing. Running composer install..."
    composer install
fi

# Clear cache before running tests
echo "Clearing application cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Build test command
TEST_CMD="php artisan test"

if [ -n "$FILTER" ]; then
    TEST_CMD="$TEST_CMD --filter=$FILTER"
fi

if [ -n "$SPECIFIC_TEST" ]; then
    TEST_CMD="$TEST_CMD $SPECIFIC_TEST"
fi

if [ "$PARALLEL" -eq 1 ]; then
    TEST_CMD="$TEST_CMD --parallel"
fi

# Run the tests
echo "Running tests with command: $TEST_CMD"
if [ "$COVERAGE" -eq 1 ]; then
    # Check if Xdebug is installed
    if php -m | grep -q xdebug; then
        echo "Generating test coverage report..."
        XDEBUG_MODE=coverage $TEST_CMD --coverage
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
