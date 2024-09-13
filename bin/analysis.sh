#!/usr/bin/env bash

# Exit on error
set -e

# Function to check if a composer package is installed
is_composer_package_installed() {
    composer show "$1" >/dev/null 2>&1
}

# Constants
PHPSTAN_PACKAGE="phpstan/phpstan"
PHPSTAN_PATH="vendor/bin/phpstan"
DIRECTORIES_TO_ANALYSE="src"

# Check if phpstan is installed
if ! is_composer_package_installed $PHPSTAN_PACKAGE; then
    echo "Installing $PHPSTAN_PACKAGE..."
    composer require --dev $PHPSTAN_PACKAGE
fi

# Check if Xdebug is enabled
XDEBUG_FLAG=""
if php -m | grep -q 'xdebug'; then
    XDEBUG_FLAG="--xdebug"
    echo "Xdebug is enabled. Including --xdebug flag."
fi

# Run the phpstan analysis
echo "Running PHPStan analysis on $DIRECTORIES_TO_ANALYSE..."
php -d memory_limit=-1 $PHPSTAN_PATH analyse $DIRECTORIES_TO_ANALYSE -c ./phpstan.neon $XDEBUG_FLAG
